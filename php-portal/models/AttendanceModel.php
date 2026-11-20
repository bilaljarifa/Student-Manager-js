<?php

class AttendanceModel
{
    public const STATUSES = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent'];

    public function __construct(private PDO $db)
    {
    }

    public function getClasses(): array
    {
        return $this->db->query(
            'SELECT c.id, c.name, c.room, t.name AS term_name, t.starts_on, t.ends_on
             FROM classes c LEFT JOIN students s ON s.class_id = c.id
             JOIN terms t ON t.id = c.term_id WHERE ' . classScope('c.id') . '
             GROUP BY c.id, c.name, c.room, t.name, t.starts_on, t.ends_on
             ORDER BY COUNT(s.id) DESC, c.name, c.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoster(int $classId, string $date, bool $lock = false): array
    {
        $sql = 'SELECT s.id, s.name, s.last_name, s.email_academic, a.status
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = :recorded_class
                    AND a.attendance_date = :attendance_date
                WHERE a.student_id IS NOT NULL OR EXISTS (
                    SELECT 1 FROM enrollments r WHERE r.student_id = s.id AND r.class_id = :enrolled_class
                    AND r.starts_on <= :enrolled_date AND (r.ends_on IS NULL OR r.ends_on > :end_date)
                )
                ORDER BY s.last_name, s.name, s.id';
        $stmt = $this->db->prepare($sql . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute(['recorded_class' => $classId, 'attendance_date' => $date, 'enrolled_class' => $classId,
            'enrolled_date' => $date, 'end_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function rosterVersion(array $roster): string
    {
        $statuses = array_column($roster, 'status', 'id');
        ksort($statuses);
        return hash('sha256', json_encode($statuses, JSON_THROW_ON_ERROR));
    }

    public function save(int $classId, string $date, string $version, array $statuses): bool
    {
        $this->db->beginTransaction();
        try {
            $class = $this->db->prepare('SELECT id FROM classes WHERE id = ? AND ' . classScope('classes.id') . ' FOR UPDATE');
            $class->execute([$classId]);
            if (!$class->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            $roster = $this->getRoster($classId, $date, true);
            if (!hash_equals(self::rosterVersion($roster), $version)) {
                $this->db->rollBack();
                return false;
            }

            $existing = array_column($roster, 'status', 'id');
            if (!$existing || array_diff_key($existing, $statuses) || array_diff_key($statuses, $existing)
                || array_filter($statuses, static fn ($status) => !is_string($status) || !isset(self::STATUSES[$status]))) {
                throw new InvalidArgumentException('Mark every student in this class.');
            }

            $save = $this->db->prepare(
                'INSERT INTO attendance (class_id, attendance_date, student_id, status)
                 VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?'
            );
            foreach ($statuses as $studentId => $status) {
                if ($status !== $existing[$studentId]) {
                    $save->execute([$classId, $date, $studentId, $status, $status]);
                    auditEvent($this->db, $existing[$studentId] === null ? 'recorded' : 'corrected', 'attendance', $studentId, $classId,
                        ['date' => $date, 'before' => $existing[$studentId], 'after' => $status, 'student_id' => $studentId]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function getForStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.attendance_date, a.status, c.name AS class_name
             FROM attendance a JOIN classes c ON c.id = a.class_id
             WHERE a.student_id = ?
             ORDER BY a.attendance_date DESC, c.name, c.id'
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

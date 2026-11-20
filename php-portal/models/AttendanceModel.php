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
            'SELECT c.id, c.name, c.room
             FROM classes c LEFT JOIN students s ON s.class_id = c.id
             GROUP BY c.id, c.name, c.room
             ORDER BY COUNT(s.id) DESC, c.name, c.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoster(int $classId, string $date, bool $lock = false): array
    {
        $sql = 'SELECT s.id, s.name, s.last_name, s.email_academic, a.status
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = :recorded_class
                    AND a.attendance_date = :attendance_date
                WHERE s.class_id = :enrolled_class OR a.student_id IS NOT NULL
                ORDER BY s.last_name, s.name, s.id';
        $stmt = $this->db->prepare($sql . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute(['recorded_class' => $classId, 'attendance_date' => $date, 'enrolled_class' => $classId]);
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
            $class = $this->db->prepare('SELECT id FROM classes WHERE id = ? FOR UPDATE');
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

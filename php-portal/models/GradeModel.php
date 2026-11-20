<?php

class GradeModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getByStudent(int $studentId, ?int $classId = null): PDOStatement
    {
        $sql = 'SELECT g.*, c.name AS class_name, t.name AS term_name FROM grades g
            LEFT JOIN classes c ON c.id = g.class_id LEFT JOIN terms t ON t.id = c.term_id WHERE g.student_id = ?';
        $params = [$studentId];
        if ($classId !== null) {
            $sql .= ' AND g.class_id = ?';
            $params[] = $classId;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY g.created_at DESC, g.id DESC');
        $stmt->execute($params);
        return $stmt;
    }

    public function save(int $classId, int $studentId, ?int $gradeId, ?int $subjectId, ?float $grade, bool $remove = false): void
    {
        portalTransaction($this->db, function () use ($classId, $studentId, $gradeId, $subjectId, $grade, $remove) {
            requireClass($this->db, $classId);
            if (!portalRow($this->db, 'SELECT id FROM enrollments WHERE class_id = ? AND student_id = ? LIMIT 1', [$classId, $studentId])) {
                throw new DomainException('The student is not enrolled in the selected class.');
            }
            $old = $gradeId ? portalRow($this->db, 'SELECT subject, grade FROM grades WHERE id = ? AND class_id = ? AND student_id = ? FOR UPDATE', [$gradeId, $classId, $studentId]) : null;
            if ($gradeId && !$old) {
                throw new DomainException('That grade could not be found in this class.');
            }
            $details = ['student_id' => $studentId];
            if ($old) {
                $details['before'] = $old['subject'] . ' · ' . $old['grade'];
            }
            if ($remove) {
                $this->db->prepare('DELETE FROM grades WHERE id = ?')->execute([$gradeId]);
                $action = 'removed';
            } else {
                $subject = portalRow($this->db, 'SELECT s.name FROM subjects s JOIN class_subjects cs ON s.id = cs.subject_id WHERE s.id = ? AND cs.class_id = ?', [$subjectId, $classId]);
                if (!$subject || $grade === null || abs($grade) > 999.99) {
                    throw new DomainException('Choose a subject and enter a valid numeric grade.');
                }
                if ($old) {
                    $this->db->prepare('UPDATE grades SET subject = ?, subject_id = ?, grade = ? WHERE id = ?')
                        ->execute([$subject['name'], $subjectId, $grade, $gradeId]);
                } else {
                    $this->db->prepare('INSERT INTO grades (class_id, student_id, subject, subject_id, grade) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$classId, $studentId, $subject['name'], $subjectId, $grade]);
                    $gradeId = (int) $this->db->lastInsertId();
                }
                $details['after'] = $subject['name'] . ' · ' . number_format($grade, 2, '.', '');
                $action = $old ? 'corrected' : 'recorded';
            }
            auditEvent($this->db, $action, 'grade', $gradeId, $classId, $details);
        });
    }

    public function getAverage(int $studentId): array
    {
        return portalRow($this->db, 'SELECT AVG(grade) AS average_grade FROM grades WHERE student_id = ?', [$studentId]);
    }
}

<?php

class GradebookModel
{
    public const KINDS = ['assignment' => 'Assignment', 'quiz' => 'Quiz', 'exam' => 'Exam', 'project' => 'Project'];
    public const MARKS = ['ungraded' => 'Ungraded', 'graded' => 'Graded', 'missing' => 'Missing', 'excused' => 'Excused'];
    public const PAGE_SIZE = 60;

    public function __construct(private PDO $db)
    {
    }

    public function book(int $classId): array
    {
        return portalRow($this->db, 'SELECT * FROM gradebooks WHERE class_id = ?', [$classId])
            ?: ['state' => 'draft', 'revision' => 0, 'review_note' => null];
    }

    public function assessments(int $classId): array
    {
        return portalRows($this->db, 'SELECT a.*, s.name AS subject FROM gradebook_assessments a
            JOIN subjects s ON s.id = a.subject_id WHERE a.class_id = ? ORDER BY s.name, a.due_on, a.id', [$classId]);
    }

    public function markSheet(array $assessment): array
    {
        return portalRows($this->db, 'SELECT s.id, s.name, s.last_name, m.status, m.points
            FROM students s LEFT JOIN gradebook_marks m ON m.student_id = s.id AND m.assessment_id = ?
            WHERE EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = s.id AND e.class_id = ?
                AND e.starts_on <= ? AND (e.ends_on IS NULL OR e.ends_on > ?))
            ORDER BY s.last_name, s.name, s.id', [$assessment['id'], $assessment['class_id'], $assessment['due_on'], $assessment['due_on']]);
    }

    public function results(int $classId): array
    {
        return portalRows($this->db, 'SELECT r.*, s.name AS first_name, s.last_name FROM gradebook_results r
            JOIN students s ON r.student_id = s.id WHERE r.class_id = ? ORDER BY s.last_name, s.name, r.subject_name', [$classId]);
    }

    public function publishedForUser(int $userId): array
    {
        return portalRows($this->db, "SELECT r.*, c.name AS class_name, t.name AS term_name, y.name AS year_name, b.published_at
            FROM gradebook_results r JOIN gradebooks b ON b.class_id = r.class_id AND b.state = 'published'
            JOIN students s ON s.id = r.student_id JOIN classes c ON c.id = r.class_id
            JOIN terms t ON t.id = c.term_id JOIN academic_years y ON y.id = t.year_id
            WHERE s.user_id = ? ORDER BY t.starts_on DESC, c.name, c.id, r.subject_name", [$userId]);
    }

    public function change(int $classId, int $revision, string $action, array $input): void
    {
        portalTransaction($this->db, function () use ($classId, $revision, $action, $input) {
            $class = requireClass($this->db, $classId);
            $this->db->prepare('INSERT IGNORE INTO gradebooks (class_id) VALUES (?)')->execute([$classId]);
            $book = $this->book($classId);
            if ((int) $book['revision'] !== $revision) {
                throw new DomainException('This gradebook changed while you were working. Review the latest entries and try again.');
            }
            if ($action === 'publish' || $action === 'return') {
                $this->review($classId, $book, $action, $input['review_note'] ?? '');
            } else {
                if ($book['state'] !== 'draft') {
                    throw new DomainException('Only a draft gradebook can be edited.');
                }
                match ($action) {
                    'save_assessment' => $this->saveAssessment($class, $input),
                    'delete_assessment' => $this->deleteAssessment($classId, (int) $input['assessment_id']),
                    'save_marks' => $this->saveMarks($classId, $input),
                    'submit' => $this->submit($classId),
                    default => throw new DomainException('Choose a valid gradebook action.'),
                };
            }
            $this->db->prepare('UPDATE gradebooks SET revision = revision + 1 WHERE class_id = ?')->execute([$classId]);
        });
    }

    private function findAssessment(int $classId, int $assessmentId): array
    {
        $assessment = portalRow($this->db, 'SELECT * FROM gradebook_assessments WHERE id = ? AND class_id = ?', [$assessmentId, $classId]);
        if (!$assessment) {
            throw new DomainException('That assessment does not belong to the selected class.');
        }
        return $assessment;
    }

    private static function decimal($value, int $digits): ?float
    {
        return is_string($value) && preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,2})?$/D', $value) ? (float) $value : null;
    }

    private function saveAssessment(array $class, array $input): void
    {
        $id = (int) $input['assessment_id'];
        $classId = (int) $class['id'];
        $subjectId = (int) $input['subject_id'];
        $title = $input['title'];
        $kind = $input['kind'];
        $date = $input['due_on'];
        $max = self::decimal($input['max_points'], 6);
        $weight = self::decimal($input['weight'], 3);
        $term = portalRow($this->db, 'SELECT starts_on, ends_on FROM terms WHERE id = ?', [$class['term_id']]);
        if (!$title || mb_strlen($title) > 120 || !isset(self::KINDS[$kind])) {
            throw new DomainException('Enter an assessment name and choose a valid type.');
        }
        if (!$term || !portalDate($date) || $date < $term['starts_on'] || $date > $term['ends_on']) {
            throw new DomainException('Choose a due date within the class term.');
        }
        if ($max === null || $max <= 0 || $weight === null || $weight <= 0 || $weight > 100) {
            throw new DomainException('Enter positive maximum points and a weight up to 100%, using at most two decimal places.');
        }
        if (!portalRow($this->db, 'SELECT subject_id FROM class_subjects WHERE class_id = ? AND subject_id = ?', [$classId, $subjectId])) {
            throw new DomainException('Choose a subject assigned to this class.');
        }

        $values = [$subjectId, $title, $kind, $date, $max, $weight];
        if ($id) {
            $previous = $this->findAssessment($classId, $id);
            $mark = portalRow($this->db, 'SELECT MAX(points) AS highest, COUNT(*) AS total FROM gradebook_marks WHERE assessment_id = ?', [$id]);
            if ($mark['total'] && (int) $previous['subject_id'] !== $subjectId) {
                throw new DomainException('Clear the assessment marks before changing its subject.');
            }
            if ($mark['highest'] !== null && (float) $mark['highest'] > $max) {
                throw new DomainException('The new maximum is below a score already recorded.');
            }
            $this->db->prepare('UPDATE gradebook_assessments SET subject_id = ?, title = ?, kind = ?, due_on = ?, max_points = ?, weight = ? WHERE id = ?')
                ->execute([...$values, $id]);
        } else {
            $this->db->prepare('INSERT INTO gradebook_assessments (subject_id, title, kind, due_on, max_points, weight, class_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$values, $classId]);
            $id = (int) $this->db->lastInsertId();
        }
        auditEvent($this->db, isset($previous) ? 'updated' : 'created', 'assessment', $id, $classId, ['title' => $title, 'weight' => $weight]);
    }

    private function deleteAssessment(int $classId, int $id): void
    {
        $assessment = $this->findAssessment($classId, $id);
        $this->db->prepare('DELETE FROM gradebook_assessments WHERE id = ?')->execute([$id]);
        auditEvent($this->db, 'removed', 'assessment', $id, $classId, ['title' => $assessment['title']]);
    }

    private function saveMarks(int $classId, array $input): void
    {
        $assessment = $this->findAssessment($classId, (int) $input['assessment_id']);
        $pageIndex = $input['mark_page'] - 1;
        $roster = array_slice($this->markSheet($assessment), $pageIndex * self::PAGE_SIZE, self::PAGE_SIZE);
        $statuses = $input['statuses'];
        $points = $input['points'];
        $ids = array_flip(array_column($roster, 'id'));
        if (!$roster || array_diff_key($ids, $statuses) || array_diff_key($statuses, $ids) || array_diff_key($points, $ids)) {
            throw new DomainException('The assessment roster changed. Reload the mark sheet and try again.');
        }

        $save = $this->db->prepare('INSERT INTO gradebook_marks (assessment_id, student_id, status, points) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = ?, points = ?');
        $remove = $this->db->prepare('DELETE FROM gradebook_marks WHERE assessment_id = ? AND student_id = ?');
        foreach ($roster as $student) {
            $id = (int) $student['id'];
            $status = $statuses[$id];
            $entered = $points[$id] ?? '';
            if (!is_string($status) || !isset(self::MARKS[$status]) || !is_string($entered)) {
                throw new DomainException('Choose a valid status for each student.');
            }
            $entered = trim($entered);
            if ($status === 'ungraded' && $entered !== '') {
                $status = 'graded';
            }
            $score = $status === 'graded' ? self::decimal($entered, 6) : null;
            $valid = $status === 'graded' ? $score !== null && $score <= (float) $assessment['max_points'] : $entered === '';
            if (!$valid) {
                throw new DomainException('Check the score for ' . trim($student['name'] . ' ' . $student['last_name'])
                    . '. Enter a score between zero and the maximum for Graded. Leave the score blank for Missing or Excused.');
            }
            $before = $student['status'] ?? 'ungraded';
            if ($before === $status && ($score === null || $score === (float) $student['points'])) {
                continue;
            }
            if ($status === 'ungraded') {
                $remove->execute([$assessment['id'], $id]);
            } else {
                $save->execute([$assessment['id'], $id, $status, $score, $status, $score]);
            }
            auditEvent($this->db, $before === 'ungraded' ? 'recorded' : 'corrected', 'assessment mark', (int) $assessment['id'], $classId,
                ['student_id' => $id, 'before' => $before . ($student['points'] === null ? '' : ' ' . $student['points']),
                    'after' => $status . ($score === null ? '' : ' ' . $score)]);
        }
    }

    private function submit(int $classId): void
    {
        $subjects = classSubjects($this->db, $classId);
        $weights = [];
        foreach ($this->assessments($classId) as $assessment) {
            $id = $assessment['subject_id'];
            $weights[$id] = ($weights[$id] ?? 0) + (int) round((float) $assessment['weight'] * 100);
        }
        if (!$subjects) {
            throw new DomainException('An administrator needs to assign subjects to this class.');
        }
        foreach ($subjects as $subject) {
            if (($weights[$subject['id']] ?? 0) !== 10000) {
                throw new DomainException('The assessment weights for ' . $subject['name'] . ' must add up to 100%.');
            }
        }

        $marks = portalRows($this->db, 'SELECT a.*, sb.name AS subject, s.id AS student_id, s.name, s.last_name, m.status, m.points
            FROM gradebook_assessments a JOIN subjects sb ON sb.id = a.subject_id
            JOIN students s ON EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = s.id AND e.class_id = a.class_id
                AND e.starts_on <= a.due_on AND (e.ends_on IS NULL OR e.ends_on > a.due_on))
            LEFT JOIN gradebook_marks m ON m.assessment_id = a.id AND m.student_id = s.id
            WHERE a.class_id = ? ORDER BY a.due_on, a.id, s.last_name, s.name, s.id', [$classId]);
        if (!$marks) {
            throw new DomainException('There are no students enrolled on the assessment dates.');
        }
        $groups = [];
        foreach ($marks as $mark) {
            if ($mark['status'] === null) {
                throw new DomainException('Record a mark for ' . trim($mark['name'] . ' ' . $mark['last_name']) . ' in ' . $mark['title'] . '.');
            }
            $groups[$mark['student_id'] . ':' . $mark['subject_id']][] = $mark;
        }
        $this->db->prepare('DELETE FROM gradebook_results WHERE class_id = ?')->execute([$classId]);
        $save = $this->db->prepare('INSERT INTO gradebook_results (class_id, student_id, subject_id, subject_name, percentage, graded_count, missing_count, excused_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($groups as $group) {
            $first = $group[0];
            $result = self::summarizeMarks($group);
            $save->execute([$classId, $first['student_id'], $first['subject_id'], $first['subject'], ...array_values($result)]);
        }
        $this->db->prepare("UPDATE gradebooks SET state = 'in_review', review_note = NULL, submitted_at = CURRENT_TIMESTAMP WHERE class_id = ?")->execute([$classId]);
        auditEvent($this->db, 'submitted', 'gradebook', $classId, $classId, ['subject_results' => count($groups)]);
    }

    public static function summarizeMarks(array $marks): array
    {
        $counts = ['graded_count' => 0, 'missing_count' => 0, 'excused_count' => 0];
        $includedWeight = 0;
        $earned = 0.0;
        foreach ($marks as $mark) {
            $counts[$mark['status'] . '_count']++;
            if ($mark['status'] !== 'excused') {
                $weight = (int) round((float) $mark['weight'] * 100);
                $includedWeight += $weight;
                if ($mark['status'] === 'graded') {
                    $earned += ((float) $mark['points'] / (float) $mark['max_points']) * $weight;
                }
            }
        }
        return ['percentage' => $includedWeight ? round(100 * $earned / $includedWeight, 2) : null] + $counts;
    }

    private function review(int $classId, array $book, string $action, string $note): void
    {
        if (!isAdmin()) {
            throw new DomainException('Only an administrator can publish or return a gradebook.');
        }
        if ($book['state'] !== 'in_review') {
            throw new DomainException('Only a gradebook awaiting review can be published or returned.');
        }
        if ($action === 'publish') {
            $this->db->prepare("UPDATE gradebooks SET state = 'published', published_at = CURRENT_TIMESTAMP WHERE class_id = ?")->execute([$classId]);
            auditEvent($this->db, 'published', 'gradebook', $classId, $classId);
        } else {
            if ($note === '' || mb_strlen($note) > 500) {
                throw new DomainException('Enter a review note of up to 500 characters before returning the gradebook.');
            }
            $this->db->prepare('DELETE FROM gradebook_results WHERE class_id = ?')->execute([$classId]);
            $this->db->prepare("UPDATE gradebooks SET state = 'draft', review_note = ? WHERE class_id = ?")->execute([$note, $classId]);
            auditEvent($this->db, 'returned', 'gradebook', $classId, $classId, ['note' => $note]);
        }
    }
}

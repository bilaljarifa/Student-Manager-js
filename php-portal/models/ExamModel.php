<?php

class ExamModel
{
    private $conn;
    private $table = "exams";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all exams with class name
    public function getAll()
    {
        $sql = "SELECT e.*, c.name AS class_name
                FROM exams e
                LEFT JOIN classes c ON e.class_id = c.id
                ORDER BY e.exam_date DESC, e.exam_time DESC, e.id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function getUpcoming(string $now)
    {
        $stmt = $this->conn->prepare(
            "SELECT e.*, c.name AS class_name
             FROM exams e
             LEFT JOIN classes c ON e.class_id = c.id
             WHERE TIMESTAMP(e.exam_date, e.exam_time) >= :now
             ORDER BY e.exam_date, e.exam_time, e.id
             LIMIT 5"
        );
        $stmt->execute(['now' => $now]);
        return $stmt;
    }

    // Create exam
    public function create($class_id, $subject, $exam_date, $exam_time)
    {
        $sql = "INSERT INTO exams (class_id, subject, exam_date, exam_time)
                VALUES (:class_id, :subject, :exam_date, :exam_time)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":class_id", $class_id);
        $stmt->bindParam(":subject", $subject);
        $stmt->bindParam(":exam_date", $exam_date);
        $stmt->bindParam(":exam_time", $exam_time);

        return $stmt->execute();
    }

    // Get exams by class_id
public function getByClass($class_id)
{
    $sql = "SELECT *, TIMESTAMP(exam_date, exam_time) >= :now AS is_upcoming FROM exams
            WHERE class_id = :class_id
            ORDER BY exam_date, exam_time, id";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['class_id' => $class_id, 'now' => date('Y-m-d H:i:s')]);

    return $stmt;
}
// Delete exam
public function delete($id)
{
    $sql = "DELETE FROM exams WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(":id", $id);
    return $stmt->execute();
}

}

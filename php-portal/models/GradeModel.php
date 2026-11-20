<?php

class GradeModel
{
    private $conn;
    private $table = "grades";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Add grade
    public function create($student_id, $subject, $grade)
    {
        $sql = "INSERT INTO " . $this->table . "
                (student_id, subject, grade)
                VALUES (:student_id, :subject, :grade)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(":student_id", $student_id);
        $stmt->bindParam(":subject", $subject);
        $stmt->bindParam(":grade", $grade);

        return $stmt->execute();
    }

    // Get grades by student
    public function getByStudent($student_id)
    {
        $sql = "SELECT * FROM " . $this->table . "
                WHERE student_id = :student_id
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();

        return $stmt;
    }

    // Delete grade
    public function delete($id, $studentId): bool
    {
        $sql = "DELETE FROM " . $this->table . " WHERE id = :id AND student_id = :student_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id, 'student_id' => $studentId]);
        return $stmt->rowCount() === 1;
    }

    // Calculate average
    public function getAverage($student_id)
    {
        $sql = "SELECT AVG(grade) as average_grade
                FROM " . $this->table . "
                WHERE student_id = :student_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

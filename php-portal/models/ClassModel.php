<?php

class ClassModel
{
    private $conn;
    private $table = "classes";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all classes
    public function getAll()
    {
        $sql = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    // Create class
    public function create($name, $room)
    {
        $sql = "INSERT INTO " . $this->table . " (name, room)
                VALUES (:name, :room)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":room", $room);
        return $stmt->execute();
    }
    public function exists(int $id): bool
    {
        $stmt = $this->conn->prepare('SELECT 1 FROM classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function delete($id): bool
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM classes WHERE id = :id
             AND NOT EXISTS (SELECT 1 FROM students WHERE students.class_id = classes.id)
             AND NOT EXISTS (SELECT 1 FROM exams WHERE exams.class_id = classes.id)
             AND NOT EXISTS (SELECT 1 FROM courses WHERE courses.class_id = classes.id)'
        );
        try {
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() === 1;
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1451) {
                return false;
            }
            throw $exception;
        }
    }
}

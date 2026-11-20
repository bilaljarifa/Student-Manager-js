<?php

class CourseModel
{
    private $conn;
    private $table = "courses";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Add new course
    public function create($class_id, $title, $file_path)
    {
        $sql = "INSERT INTO " . $this->table . "
                (class_id, title, file_path)
                VALUES (:class_id, :title, :file_path)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(":class_id", $class_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":file_path", $file_path);

        return $stmt->execute();
    }

    // Get courses by class
    public function getByClass($class_id)
    {
        $sql = "SELECT * FROM " . $this->table . "
                WHERE class_id = :class_id
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":class_id", $class_id);
        $stmt->execute();

        return $stmt;
    }

    public function getAll(?string $scope = null)
    {
        return $this->conn->query(
            'SELECT courses.*, classes.name AS class_name
             FROM courses LEFT JOIN classes ON courses.class_id = classes.id
             ' . ($scope ? "WHERE $scope" : '') . '
             ORDER BY courses.created_at DESC, courses.id DESC'
        );
    }

    // Delete course
    public function delete($id)
    {
        $sql = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }
}

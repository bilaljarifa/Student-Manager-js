<?php

class Student
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll(string $search = '', ?int $classId = null)
    {
        $sql = 'SELECT s.*, c.name AS class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id';
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = "CONCAT_WS(' ', s.name, s.last_name, s.email_academic) LIKE :search ESCAPE '!'";
            $params['search'] = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        }
        if ($classId !== null) {
            $conditions[] = 's.class_id = :class_id';
            $params['class_id'] = $classId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->conn->prepare($sql . ' ORDER BY s.created_at DESC, s.id DESC');
        $stmt->execute($params);
        return $stmt;
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $values)
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO students (name, last_name, email_academic, age, class_id)
             VALUES (:name, :last_name, :email_academic, :age, :class_id)'
        );
        return $stmt->execute($values);
    }

    public function update($id, array $values)
    {
        $stmt = $this->conn->prepare(
            'UPDATE students SET name = :name, last_name = :last_name,
                 email_academic = :email_academic, age = :age, class_id = :class_id
             WHERE id = :id'
        );
        return $stmt->execute($values + ['id' => $id]);
    }

    public function delete($id): bool
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM students WHERE id = :id
             AND NOT EXISTS (SELECT 1 FROM grades WHERE grades.student_id = students.id)'
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

    public function getStudentWithClass($userId)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, c.name AS class_name, c.room
             FROM students s LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

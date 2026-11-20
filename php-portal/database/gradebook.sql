CREATE TABLE IF NOT EXISTS gradebooks (
    class_id INT PRIMARY KEY,
    state ENUM('draft', 'in_review', 'published') NOT NULL DEFAULT 'draft',
    revision INT UNSIGNED NOT NULL DEFAULT 0,
    review_note VARCHAR(500) DEFAULT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    published_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gradebook_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    kind ENUM('assignment', 'quiz', 'exam', 'project') NOT NULL,
    due_on DATE NOT NULL,
    max_points DECIMAL(8,2) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    INDEX assessments_class_date (class_id, due_on, id),
    FOREIGN KEY (class_id) REFERENCES gradebooks(class_id),
    FOREIGN KEY (class_id, subject_id) REFERENCES class_subjects(class_id, subject_id),
    CHECK (max_points > 0),
    CHECK (weight > 0 AND weight <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gradebook_marks (
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('graded', 'missing', 'excused') NOT NULL,
    points DECIMAL(8,2) DEFAULT NULL,
    PRIMARY KEY (assessment_id, student_id),
    FOREIGN KEY (assessment_id) REFERENCES gradebook_assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    CHECK ((status = 'graded' AND points IS NOT NULL AND points >= 0) OR (status <> 'graded' AND points IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gradebook_results (
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) DEFAULT NULL,
    graded_count INT UNSIGNED NOT NULL,
    missing_count INT UNSIGNED NOT NULL,
    excused_count INT UNSIGNED NOT NULL,
    PRIMARY KEY (class_id, student_id, subject_id),
    INDEX gradebook_student_reports (student_id, class_id),
    FOREIGN KEY (class_id) REFERENCES gradebooks(class_id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

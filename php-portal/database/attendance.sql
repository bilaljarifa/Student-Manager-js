CREATE TABLE IF NOT EXISTS attendance (
    class_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'late', 'absent') NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id, attendance_date, student_id),
    INDEX attendance_student_date (student_id, attendance_date),
    CONSTRAINT attendance_class_fk FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT attendance_student_fk FOREIGN KEY (student_id) REFERENCES students (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

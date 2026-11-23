<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';

$db = (new Database())->connect();
if (!$db->query("SELECT GET_LOCK('school_portal_schema', 10)")->fetchColumn()) {
    fwrite(STDERR, "Another setup process is running.\n");
    exit(1);
}
try {
    foreach (['attendance.sql', 'professional.sql', 'gradebook.sql'] as $file) {
        $sql = file_get_contents(dirname(__DIR__) . '/database/' . $file);
        foreach (preg_split('/;\s*(?:\r?\n|$)/', trim($sql), -1, PREG_SPLIT_NO_EMPTY) as $statement) {
            $db->exec($statement);
        }
    }
    $db->exec('CREATE TABLE IF NOT EXISTS portal_migrations (version VARCHAR(80) PRIMARY KEY) ENGINE=InnoDB');
    $columns = [
        'users' => ['active' => 'TINYINT NOT NULL DEFAULT 1', 'must_change' => 'TINYINT NOT NULL DEFAULT 0', 'auth_version' => 'INT NOT NULL DEFAULT 1'],
        'classes' => ['term_id' => 'INT NULL'],
        'exams' => ['subject_id' => 'INT NULL'],
        'grades' => ['class_id' => 'INT NULL', 'subject_id' => 'INT NULL'],
    ];
    $columnExists = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    foreach ($columns as $table => $definitions) {
        foreach ($definitions as $name => $type) {
            $columnExists->execute([$table, $name]);
            if (!$columnExists->fetchColumn()) {
                $db->exec("ALTER TABLE $table ADD COLUMN $name $type");
            }
        }
    }
    if (!$db->query("SELECT 1 FROM portal_migrations WHERE version = 'professional-v1'")->fetchColumn()) {
        $db->beginTransaction();
        $db->exec("INSERT INTO academic_years (name, starts_on, ends_on) VALUES ('Imported school records', '1000-01-01', '9999-12-31')");
        $year = (int) $db->lastInsertId();
        $db->prepare("INSERT INTO terms (year_id, name, starts_on, ends_on) VALUES (?, 'Imported term', '1000-01-01', '9999-12-31')")->execute([$year]);
        $db->prepare('UPDATE classes SET term_id = ? WHERE term_id IS NULL')->execute([$db->lastInsertId()]);
        $db->exec("INSERT INTO enrollments (student_id, class_id, starts_on)
                   SELECT s.id, c.id, '1000-01-01' FROM students s JOIN classes c ON c.id = s.class_id");
        $db->exec('UPDATE grades g JOIN students s ON s.id = g.student_id JOIN classes c ON c.id = s.class_id SET g.class_id = c.id WHERE g.class_id IS NULL');
        $db->exec("INSERT IGNORE INTO subjects (name)
                   SELECT subject FROM grades WHERE TRIM(subject) <> '' UNION SELECT subject FROM exams WHERE TRIM(subject) <> ''");
        $db->exec('UPDATE grades g JOIN subjects sb ON sb.name = g.subject SET g.subject_id = sb.id');
        $db->exec('UPDATE exams e JOIN subjects sb ON sb.name = e.subject SET e.subject_id = sb.id');
        $teachers = $db->query("SELECT id FROM users WHERE role = 'teacher' AND active = 1")->fetchAll(PDO::FETCH_COLUMN);
        if (count($teachers) === 1) {
            $db->prepare('INSERT IGNORE INTO teacher_classes (teacher_id, class_id) SELECT ?, id FROM classes')->execute([$teachers[0]]);
        }
        $update = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        foreach ($db->query('SELECT id, password FROM users')->fetchAll(PDO::FETCH_ASSOC) as $user) {
            if (!password_get_info($user['password'])['algo']) {
                $update->execute([password_hash($user['password'], PASSWORD_DEFAULT), $user['id']]);
            }
        }
        $save = $db->prepare('INSERT INTO course_files (course_id, file_name, contents) VALUES (?, ?, ?)');
        foreach ($db->query('SELECT id, file_path FROM courses')->fetchAll(PDO::FETCH_ASSOC) as $course) {
            $filename = basename(str_replace('\\', '/', $course['file_path']));
            $file = dirname(__DIR__) . '/uploads/' . $filename;
            if ($filename && is_file($file) && (new finfo(FILEINFO_MIME_TYPE))->file($file) === 'application/pdf') {
                $save->execute([$course['id'], $filename, file_get_contents($file)]);
            }
        }
        $db->exec("INSERT INTO portal_migrations (version) VALUES ('professional-v1')");
        $db->commit();
    }
    if (!$db->query("SELECT 1 FROM portal_migrations WHERE version = 'class-subjects-v1'")->fetchColumn()) {
        $db->beginTransaction();
        $db->exec('INSERT IGNORE INTO class_subjects (class_id, subject_id)
            SELECT r.class_id, r.subject_id FROM (
                SELECT class_id, subject_id FROM grades UNION SELECT class_id, subject_id FROM exams
                UNION SELECT class_id, subject_id FROM lessons
            ) r JOIN classes c ON c.id = r.class_id JOIN subjects s ON s.id = r.subject_id');
        $db->exec("INSERT INTO portal_migrations (version) VALUES ('class-subjects-v1')");
        $db->commit();
    }
    if (($argv[1] ?? '') !== '--schema-only'
        && !$db->query("SELECT 1 FROM users WHERE role = 'admin' AND active = 1 LIMIT 1")->fetchColumn()) {
        $email = $argv[1] ?? 'admin@schoolportal.test';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            throw new RuntimeException('Enter a valid email as the first setup argument.');
        }
        $temporaryPassword = bin2hex(random_bytes(12));
        $storage = dirname(__DIR__) . '/storage';
        if (!is_dir($storage) && !mkdir($storage, 0700, true) && !is_dir($storage)) {
            throw new RuntimeException('Could not create the School Portal storage folder.');
        }
        $path = dirname(__DIR__) . '/storage/first-admin.txt';
        $file = fopen($path, 'xb');
        if (!$file) {
            throw new RuntimeException('Cannot create the first admin credentials file.');
        }
        try {
            $db->beginTransaction();
            $db->prepare("INSERT INTO users (email, password, role, must_change) VALUES (?, ?, 'admin', 1)")
                ->execute([$email, password_hash($temporaryPassword, PASSWORD_DEFAULT)]);
            fwrite($file, "Email: $email\nTemporary password: $temporaryPassword\nSign in and choose a new password.\n");
            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            fclose($file);
            unlink($path);
            throw $exception;
        }
        fclose($file);
        @chmod($path, 0600);
        echo "First administrator created. Credentials saved in storage/first-admin.txt.\n";
    }
    echo "School Portal database is up to date.\n";
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/portal.php';
require_once __DIR__ . '/../models/GradebookModel.php';

function demoInsert(PDO $db, string $table, array $record): int
{
    $columns = implode(', ', array_keys($record));
    $parameters = implode(', ', array_fill(0, count($record), '?'));
    $db->prepare("INSERT INTO $table ($columns) VALUES ($parameters)")->execute(array_values($record));
    return (int) $db->lastInsertId();
}

function demoProcess(array $arguments): void
{
    $command = array_merge([PHP_BINARY], $arguments);
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__), null, ['bypass_shell' => true]);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('A required PHP process could not finish.');
    }
}

function seedSchoolDemo(PDO $db): void
{
    $today = new DateTimeImmutable('today', new DateTimeZone('Africa/Casablanca'));
    $date = fn (string $offset) => $today->modify($offset)->format('Y-m-d');
    $starts = $date('-135 days');
    $previousEnd = $date('-41 days');
    $activeStart = $date('-40 days');
    $activeEnd = $date('+150 days');
    $year = demoInsert($db, 'academic_years', [
        'name' => substr($starts, 0, 4) . '–' . substr($activeEnd, 0, 4) . ' Demo Year',
        'starts_on' => $starts, 'ends_on' => $activeEnd,
    ]);
    $pastTerm = demoInsert($db, 'terms', ['year_id' => $year, 'name' => 'Term 1', 'starts_on' => $starts, 'ends_on' => $previousEnd]);
    $currentTerm = demoInsert($db, 'terms', ['year_id' => $year, 'name' => 'Term 2', 'starts_on' => $activeStart, 'ends_on' => $activeEnd]);

    $accounts = [];
    foreach (['admin', 'teacher', 'student'] as $role) {
        $accounts[$role] = demoInsert($db, 'users', [
            'email' => $role . '@demo.schoolportal.test', 'role' => $role,
            'password' => password_hash('SchoolPortalDemo!', PASSWORD_DEFAULT),
        ]);
    }

    $subjects = [];
    foreach (['Mathematics', 'Physics'] as $name) {
        $subjects[$name] = demoInsert($db, 'subjects', ['name' => $name]);
    }

    $classSpecs = [
        'published' => ['name' => 'Grade 9A', 'room' => 'Room 204', 'term_id' => $pastTerm],
        'in_review' => ['name' => 'Grade 9A', 'room' => 'Room 204', 'term_id' => $currentTerm],
        'draft' => ['name' => 'Grade 9B', 'room' => 'Room 207', 'term_id' => $currentTerm],
    ];
    $classes = [];
    foreach ($classSpecs as $state => $spec) {
        $id = demoInsert($db, 'classes', $spec);
        $classes[$state] = $id;
        demoInsert($db, 'teacher_classes', ['class_id' => $id, 'teacher_id' => $accounts['teacher']]);
        foreach ($subjects as $subject) {
            demoInsert($db, 'class_subjects', ['class_id' => $id, 'subject_id' => $subject]);
        }
        demoInsert($db, 'gradebooks', [
            'class_id' => $id, 'state' => $state, 'revision' => 1,
            'submitted_at' => $state === 'draft' ? null : $date($state === 'published' ? '-45 days' : '-2 days') . ' 15:30:00',
            'published_at' => $state === 'published' ? $date('-41 days') . ' 09:15:00' : null,
        ]);
    }

    $names = [
        ['Lina', 'Amani'], ['Nour', 'Berrada'], ['Yassine', 'Fahmi'], ['Salma', 'Idrissi'],
        ['Adam', 'El Amrani'], ['Amal', 'Haddad'], ['Samir', 'Naciri'], ['Yasmine', 'Safi'],
    ];
    $rosters = ['published' => [], 'in_review' => [], 'draft' => []];
    foreach ($names as $index => [$first, $last]) {
        $group = $index < 4 ? 'in_review' : 'draft';
        $id = demoInsert($db, 'students', [
            'name' => $first, 'last_name' => $last, 'email_academic' => strtolower($first) . '@students.demo.schoolportal.test',
            'age' => 14 + ($index % 2), 'class_id' => $classes[$group], 'user_id' => $index === 0 ? $accounts['student'] : null,
        ]);
        $rosters[$group][] = $id;
        demoInsert($db, 'enrollments', ['student_id' => $id, 'class_id' => $classes[$group], 'starts_on' => $activeStart]);
        if ($group === 'in_review') {
            $rosters['published'][] = $id;
            demoInsert($db, 'enrollments', [
                'student_id' => $id, 'class_id' => $classes['published'], 'starts_on' => $starts, 'ends_on' => $activeStart,
            ]);
        }
    }

    $assessmentSpecs = [
        'Mathematics' => [['Linear equations', 'assignment', 20, 35], ['Algebra exam', 'exam', 50, 65]],
        'Physics' => [['Motion lab', 'project', 30, 40], ['Forces quiz', 'quiz', 20, 60]],
    ];
    foreach ($classes as $state => $classId) {
        $marked = [];
        foreach ($assessmentSpecs as $subjectName => $assessments) {
            foreach ($assessments as $order => [$title, $kind, $max, $weight]) {
                $assessmentId = demoInsert($db, 'gradebook_assessments', [
                    'class_id' => $classId, 'subject_id' => $subjects[$subjectName], 'title' => $title, 'kind' => $kind,
                    'due_on' => $date($state === 'published' ? (-85 + $order * 23) . ' days' : (-23 + $order * 17) . ' days'),
                    'max_points' => $max, 'weight' => $weight,
                ]);
                foreach ($rosters[$state] as $index => $studentId) {
                    if ($state === 'draft' && $order === 1 && $index === 3) {
                        continue;
                    }
                    $status = match (true) {
                        $index === 2 && $subjectName === 'Mathematics' && $order === 0 => 'missing',
                        $index === 3 && $subjectName === 'Physics' && $order === 1 => 'excused',
                        default => 'graded',
                    };
                    $ratio = [0.93, 0.85, 0.76, 0.90][$index] - ($order * 0.04) - ($subjectName === 'Physics' ? 0.025 : 0);
                    $points = $status === 'graded' ? round($max * $ratio, 2) : null;
                    demoInsert($db, 'gradebook_marks', [
                        'assessment_id' => $assessmentId, 'student_id' => $studentId, 'status' => $status, 'points' => $points,
                    ]);
                    $marked[$studentId][$subjectName][] = ['status' => $status, 'points' => $points, 'weight' => $weight, 'max_points' => $max];
                }
            }
        }
        if ($state !== 'draft') {
            foreach ($marked as $studentId => $subjectResults) {
                foreach ($subjectResults as $subjectName => $marks) {
                    demoInsert($db, 'gradebook_results', [
                        'class_id' => $classId, 'student_id' => $studentId, 'subject_id' => $subjects[$subjectName], 'subject_name' => $subjectName,
                    ] + GradebookModel::summarizeMarks($marks));
                }
            }
            demoInsert($db, 'portal_audit', [
                'actor_id' => $accounts['teacher'], 'action' => 'submitted', 'entity' => 'gradebook', 'class_id' => $classId,
                'entity_id' => $classId, 'details' => '{"source":"fictional demo"}',
                'recorded_at' => $date($state === 'published' ? '-45 days' : '-2 days') . ' 15:30:00',
            ]);
            if ($state === 'published') {
                demoInsert($db, 'portal_audit', [
                    'actor_id' => $accounts['admin'], 'action' => 'published', 'entity' => 'gradebook', 'class_id' => $classId,
                    'entity_id' => $classId, 'details' => '{"source":"fictional demo"}', 'recorded_at' => $previousEnd . ' 09:15:00',
                ]);
            }
        }
    }

    foreach (['in_review', 'draft'] as $state) {
        $classId = $classes[$state];
        foreach ([[1, 'Mathematics', '09:00:00', '10:00:00'], [2, 'Physics', '10:15:00', '11:15:00'],
            [3, 'Mathematics', '09:00:00', '10:00:00'], [5, 'Physics', '11:30:00', '12:30:00']] as [$day, $subject, $from, $until]) {
            demoInsert($db, 'lessons', [
                'class_id' => $classId, 'subject_id' => $subjects[$subject], 'weekday' => $day, 'starts_at' => $from,
                'ends_at' => $until, 'room' => $classSpecs[$state]['room'],
            ]);
        }
        demoInsert($db, 'announcements', [
            'class_id' => $classId, 'author_id' => $accounts['teacher'], 'title' => 'Bring graph paper on Wednesday',
            'message' => 'We will plot data from the motion lab. Bring your notebook and a ruler.',
            'posted_at' => $date('-3 days') . ' 10:00:00',
        ]);
        demoInsert($db, 'exams', [
            'class_id' => $classId, 'subject_id' => $subjects['Mathematics'], 'subject' => 'Mathematics',
            'exam_date' => $date('+7 days'), 'exam_time' => '09:00:00',
        ]);
        foreach (range(1, 12) as $offset) {
            $day = $today->modify('-' . $offset . ' days');
            if ((int) $day->format('N') > 5) {
                continue;
            }
            foreach ($rosters[$state] as $index => $studentId) {
                $pattern = ($offset + $index * 3) % 11;
                demoInsert($db, 'attendance', [
                    'class_id' => $classId, 'student_id' => $studentId, 'attendance_date' => $day->format('Y-m-d'),
                    'status' => $pattern === 0 ? 'absent' : ($pattern <= 2 ? 'late' : 'present'),
                ]);
            }
        }
    }
}

function seedDemoMaterials(PDO $db): void
{
    $classes = portalRows($db, "SELECT c.id, c.name FROM classes c
        JOIN terms t ON t.id = c.term_id JOIN academic_years y ON y.id = t.year_id
        JOIN teacher_classes tc ON tc.class_id = c.id JOIN users u ON u.id = tc.teacher_id
        WHERE t.name = 'Term 2' AND y.name LIKE '% Demo Year'
          AND u.email = 'teacher@demo.schoolportal.test' AND c.name IN ('Grade 9A', 'Grade 9B')");
    $fixtures = [
        ['title' => 'Linear equations practice', 'file' => 'linear-equations-practice.pdf', 'classes' => ['Grade 9A', 'Grade 9B']],
        ['title' => 'Motion lab worksheet', 'file' => 'motion-lab-worksheet.pdf', 'classes' => ['Grade 9A']],
    ];
    foreach ($fixtures as $fixture) {
        $source = dirname(__DIR__) . '/database/demo-materials/' . $fixture['file'];
        $pdf = is_readable($source) ? file_get_contents($source) : false;
        if ($pdf === false || !str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('The demo worksheet is missing or invalid: ' . $fixture['file']);
        }
        foreach ($classes as $class) {
            if (!in_array($class['name'], $fixture['classes'], true)) {
                continue;
            }
            $courseId = demoInsert($db, 'courses', ['class_id' => $class['id'], 'title' => $fixture['title'], 'file_path' => 'private']);
            demoInsert($db, 'course_files', ['course_id' => $courseId, 'file_name' => $fixture['file'], 'contents' => $pdf]);
        }
    }
}

$mode = $argv[1] ?? 'seed';
$database = $argv[2] ?? 'school_portal_demo';
try {
    if (!in_array($mode, ['seed', 'serve'], true) || !preg_match('/^[a-z][a-z0-9_]{0,54}_demo$/D', $database)) {
        throw new RuntimeException('Use php scripts/demo.php [seed|serve] [database_name_ending_in_demo].');
    }
    $server = (new Database())->connect('information_schema');
    $server->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4");
    $db = (new Database())->connect($database);
    $key = 'school_portal_demo:' . substr(hash('sha256', $database), 0, 32);
    $lock = portalRow($db, 'SELECT GET_LOCK(?, 10) AS acquired', [$key]);
    if (!(int) $lock['acquired']) {
        throw new RuntimeException('Another demo setup is already running.');
    }
    try {
        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if ($tables && !in_array('portal_demo_seed', $tables, true)) {
            throw new RuntimeException('This database already contains data from another setup. Choose a new demo database name.');
        }
        $db->exec('CREATE TABLE IF NOT EXISTS portal_demo_seed (id TINYINT PRIMARY KEY, seeded_at TIMESTAMP NULL) ENGINE=InnoDB');
        putenv('DB_NAME=' . $database);
        if (!$db->query('SELECT seeded_at FROM portal_demo_seed WHERE id = 1')->fetchColumn()) {
            $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
            foreach (preg_split('/;\s*(?:\r?\n|$)/', trim($schema), -1, PREG_SPLIT_NO_EMPTY) as $statement) {
                $db->exec($statement);
            }
            demoProcess(['scripts/setup.php', '--schema-only']);
            portalTransaction($db, function () use ($db) {
                seedSchoolDemo($db);
                $db->exec('INSERT INTO portal_demo_seed (id, seeded_at) VALUES (1, CURRENT_TIMESTAMP)');
            });
            echo "Fictional demo data created in $database.\n";
        } else {
            echo "Demo data is already ready in $database.\n";
        }
        if (!$db->query('SELECT seeded_at FROM portal_demo_seed WHERE id = 2')->fetchColumn()) {
            portalTransaction($db, function () use ($db) {
                seedDemoMaterials($db);
                $db->exec('INSERT INTO portal_demo_seed (id, seeded_at) VALUES (2, CURRENT_TIMESTAMP)');
            });
            echo "Added the demo course worksheets to $database.\n";
        }
    } finally {
        portalRow($db, 'SELECT RELEASE_LOCK(?)', [$key]);
    }
    if ($mode === 'serve') {
        $runtime = sys_get_temp_dir() . '/school-portal-demo-' . substr(hash('sha256', __DIR__ . $database), 0, 12);
        if (!is_dir($runtime) && !mkdir($runtime, 0700, true) && !is_dir($runtime)) {
            throw new RuntimeException('Could not prepare the demo runtime folder.');
        }
        echo "Open http://127.0.0.1:8001/ to try the demo.\n";
        demoProcess(['-d', 'session.save_path=' . $runtime, '-d', 'session.name=SCHOOLPORTALDEMO',
            '-d', 'upload_tmp_dir=' . $runtime, '-d', 'date.timezone=Africa/Casablanca',
            '-S', '127.0.0.1:8001', 'router.php']);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/Database.php';

function mysqlProgram(string $program): string
{
    $windows = PHP_OS_FAMILY === 'Windows';
    $file = $program . ($windows ? '.exe' : '');
    $bin = getenv('MYSQL_BIN');
    if (!$bin && $windows) {
        $bin = getenv('ProgramFiles') . '/MySQL/MySQL Server 8.0/bin';
    }
    return $bin && is_file($bin . '/' . $file) ? $bin . '/' . $file : $file;
}

function runMysql(string $program, array $arguments, $input, $output): void
{
    $settings = Database::settings();
    $environment = getenv();
    $environment['MYSQL_PWD'] = $settings['password'];
    $command = array_merge(
        [mysqlProgram($program), '--host=' . $settings['host'], '--port=' . $settings['port'], '--user=' . $settings['user']],
        $arguments
    );
    $process = proc_open($command, [$input, $output, ['pipe', 'w']], $pipes, null, $environment, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start ' . $program . '. Set MYSQL_BIN to the MySQL bin directory.');
    }
    $failure = trim(stream_get_contents($pipes[2]));
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException($program . ' failed: ' . $failure);
    }
}

$directory = dirname(__DIR__) . '/storage/backups';
$action = $argv[1] ?? 'create';
try {
    if ($action === 'create') {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the backup folder.');
        }
        $path = $directory . '/school-portal-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sql';
        $file = fopen($path, 'xb');
        if (!$file) {
            throw new RuntimeException('Could not open a new backup file.');
        }
        try {
            runMysql('mysqldump', ['--single-transaction', '--quick', '--no-tablespaces', '--hex-blob', '--set-gtid-purged=OFF',
                '--skip-lock-tables', '--default-character-set=utf8mb4', Database::settings()['database']], STDIN, $file);
        } catch (Throwable $exception) {
            fclose($file);
            unlink($path);
            throw $exception;
        }
        fclose($file);
        @chmod($path, 0600);
        echo $path, PHP_EOL;
    } elseif ($action === 'verify') {
        $name = $argv[2] ?? '';
        if (!preg_match('/^school-portal-\d{8}-\d{6}-[a-f0-9]{6}\.sql$/D', $name)) {
            throw new RuntimeException('Supply the filename of a School Portal backup.');
        }
        $path = $directory . '/' . $name;
        if (!is_file($path)) {
            throw new RuntimeException('Backup was not found.');
        }
        $db = (new Database())->connect();
        $test = 'portal_restore_' . bin2hex(random_bytes(8));
        $db->exec('CREATE DATABASE ' . $test . ' CHARACTER SET utf8mb4');
        try {
            $input = fopen($path, 'rb');
            try {
                runMysql('mysql', ['--default-character-set=utf8mb4', '--database=' . $test], $input, STDOUT);
            } finally {
                fclose($input);
            }
            $restored = (new Database())->connect($test);
            $tables = $restored->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            foreach (['classes', 'students', 'users', 'grades', 'attendance'] as $required) {
                if (!in_array($required, $tables, true)) {
                    throw new RuntimeException('Missing restored table ' . $required);
                }
            }
            foreach ($tables as $table) {
                if (!preg_match('/^[a-z_]+$/D', $table)) {
                    throw new RuntimeException('Unexpected table name in backup.');
                }
                $restored->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
            }
            echo 'Restored and read ', count($tables), ' tables in an isolated database.', PHP_EOL;
            $restored = null;
        } finally {
            $db->exec('DROP DATABASE ' . $test);
        }
    } else {
        throw new RuntimeException('Use create or verify BACKUP_FILENAME.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

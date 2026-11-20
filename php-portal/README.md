# School Portal

The PHP portal provides teacher and student dashboards, student and class management, exams, grades, PDF course materials, and daily attendance. It uses PHP 8.2 with PDO MySQL, Fileinfo, and Mbstring, and MySQL 8.

## Set up the database

For a new installation, create a MySQL database and import `database/schema.sql`, then `database/attendance.sql`. For an existing Student Management System database, apply only `database/attendance.sql`. Both files can be opened and run in MySQL Workbench.

For local testing, add a user record with a unique email and a password you choose for this local instance. The portal uses the legacy `users.password` format for these local accounts.

```sql
INSERT INTO users (email, password, role)
VALUES ('your-teacher@example.test', 'replace-with-your-local-password', 'teacher');
```

A student account uses role `student` and a student record whose `user_id` points to that account. Use local test accounts when running the current sign-in implementation.

## Start the portal

Open PowerShell in the `php-portal` folder. Set the MySQL connection environment variables to match your local configuration.

```powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_NAME = 'student_management'
$env:DB_USER = 'your-mysql-user'
$env:DB_PASSWORD = 'your-mysql-password'
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000/`. Keep `uploads/` writable by PHP to allow PDF uploads.

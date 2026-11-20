# School Portal

School Portal is a PHP and MySQL app for managing academic terms, accounts, classes, attendance, grades, exams, course materials, timetables, announcements, and reports.

## Local setup

Use PHP 8.2 or later with PDO MySQL, Fileinfo, and Mbstring, and MySQL 8. Create a MySQL database and import [database/schema.sql](database/schema.sql) for a new installation. Existing installations already have these tables.

Set the connection with environment variables or an untracked local settings file. The environment takes precedence.

| Variable | Local default |
| --- | --- |
| DB_HOST | 127.0.0.1 |
| DB_PORT | 3306 |
| DB_NAME | student_management |
| DB_USER | root |
| DB_PASSWORD | empty |

An optional config/local.php file can return an associative array with host, port, database, user, and password keys. Keep this file local to the installation.

Apply the database migrations and create the first administrator. Use an email address you control when setting up a new installation.

~~~powershell
php scripts/setup.php admin@example.test
php -S 127.0.0.1:8000 router.php
~~~

Open http://127.0.0.1:8000/. The first administrator's temporary credentials are saved in storage/first-admin.txt. Temporary passwords and existing passwords that fall short of the new policy must be changed at sign-in. The setup process preserves the existing accounts and imports eligible course PDFs into private database storage.

The PHP development server requires router.php to block access to folders that contain private files. With Apache, enable mod_rewrite and AllowOverride for the project so the included .htaccess rules are enforced.

## First school setup

1. In **Administration**, add an academic year, term, and subjects.
2. In **Classes**, create a class for a term. Assign its subjects in **Administration** under **Years, terms and subjects**.
3. In **Administration**, create teacher and student accounts and assign the teacher to the class.
4. In **Students**, add the student, select the class and effective enrollment date, and choose the linked student login.
5. Share each temporary password with its assigned user. Teachers can then take attendance, record grades, and post class updates. Students can view their own records and report cards.

## Gradebook and published report cards

Use **Gradebook** to add assessments for the class's assigned subjects. Enter the maximum points and each assessment's weight. The weights must add up to 100% for each subject before submission.

- **Graded** uses points divided by the maximum.
- **Missing** counts as zero.
- **Excused** removes the assessment's weight from that student's calculation.
- **Ungraded** requires a mark or status before submission.

Mark sheets use the class roster on each assessment's due date. Teachers submit the completed gradebook for review. An administrator can return it with a note or publish the reviewed results. Students see only their own published results and can print them from **Published grades**. Quick grades remain available separately.

## Backups and restore checks

The backup commands use MySQL's native client tools. Set MYSQL_BIN to their directory if PHP cannot find them. Run these from the project folder.

~~~powershell
php scripts/backup.php create
php scripts/backup.php verify school-portal-YYYYMMDD-HHMMSS-xxxxxx.sql
~~~

Backups are saved in the local storage/backups folder and include the database and private course PDFs. Supply the filename printed by the first command to verify it. The verification command restores to a temporary MySQL database, reads the restored tables, and removes that temporary database afterward. The database account running verification needs permission to create and drop the temporary database.

For recovery, import a verified backup into a separate MySQL database, check its contents, and point DB_NAME at the recovered database after validation.

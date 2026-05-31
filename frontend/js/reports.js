// reports.js

export class ReportGenerator {
    constructor(students, teachers, courses, exams, attendance, grades) {
        this.students = students;
        this.teachers = teachers;
        this.courses = courses;
        this.exams = exams;
        this.attendance = attendance;
    }

    // ── STUDENT REPORTS ──────────────────────

    getTotalStudents() {
        return this.students.length;
    }

    getStudentsByClass() {
        const classMap = {};
        this.students.forEach(s => {
            classMap[s.studentClass] = (classMap[s.studentClass] || 0) + 1;
        });
        return classMap;
    }

    getPaymentStats() {
        const paid = this.students.filter(s => s.paymentStatus === "Paid").length;
        const pending = this.students.filter(s => s.paymentStatus === "Pending").length;
        return { paid, pending, total: this.students.length };
    }

    // ── TEACHER REPORTS ──────────────────────

    getTotalTeachers() {
        return this.teachers.length;
    }

    getTeacherByDepartment() {
        const deptMap = {};
        this.teachers.forEach(t => {
            const dept = t.department || "Unassigned";
            deptMap[dept] = (deptMap[dept] || 0) + 1;
        });
        return deptMap;
    }

    getCoursesPerTeacher(teacherId) {
        return this.courses.filter(c => c.teacher === teacherId).length;
    }

    // ── COURSE REPORTS ──────────────────────

    getTotalCourses() {
        return this.courses.length;
    }

    getStudentsPerCourse() {
        const courseMap = {};
        this.courses.forEach(c => {
            courseMap[c.code] = c.getStudentCount();
        });
        return courseMap;
    }

    // ── ATTENDANCE REPORTS ──────────────────────

    getAttendancePercentageByStudent(studentId) {
        const studentAttendance = this.attendance.filter(a => a.studentId === studentId);
        if (studentAttendance.length === 0) return 0;
        const present = studentAttendance.filter(a => a.isPresent()).length;
        return Math.round((present / studentAttendance.length) * 100);
    }

    getOverallAttendancePercentage() {
        if (this.attendance.length === 0) return 0;
        const present = this.attendance.filter(a => a.isPresent()).length;
        return Math.round((present / this.attendance.length) * 100);
    }

    // ── EXAM REPORTS ──────────────────────

    getTotalExams() {
        return this.exams.length;
    }

    getExamsByStatus() {
        const upcoming = this.exams.filter(e => new Date(e.date) > new Date()).length;
        const completed = this.exams.filter(e => new Date(e.date) <= new Date()).length;
        return { upcoming, completed };
    }

    // ── SUMMARY DASHBOARD ──────────────────────

    generateDashboardSummary() {
        const payment = this.getPaymentStats();
        const examStatus = this.getExamsByStatus();

        return {
            students: this.getTotalStudents(),
            teachers: this.getTotalTeachers(),
            courses: this.getTotalCourses(),
            exams: this.getTotalExams(),
            paymentStats: payment,
            examStats: examStatus,
            overallAttendance: this.getOverallAttendancePercentage(),
            studentsByClass: this.getStudentsByClass(),
            teachersByDept: this.getTeacherByDepartment(),
            studentsPerCourse: this.getStudentsPerCourse()
        };
    }
}
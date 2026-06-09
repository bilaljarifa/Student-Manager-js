// ═══════════════════════════════════════════════════════════════════
// DASHBOARD STATS — Calculate KPI metrics
// ═══════════════════════════════════════════════════════════════════

import { getAverage } from "./grades.js";

export function updateDashboardStats(students, courses, attendance) {
    // ── TOTAL STUDENTS ──
    const totalStudents = students.length;
    const activeStudents = totalStudents; // All are active

    // ── TOTAL COURSES ──
    const totalCourses = courses.length;
    const activeCourses = totalCourses; // All are active

    // ── AVERAGE GPA ──
    let totalGPA = 0;
    let studentsWithGrades = 0;

    students.forEach(student => {
        if (student.grades && student.grades.length > 0) {
            const scores = student.grades.map(g =>
                typeof g === "object" ? g.score : g
            );
            const avg = getAverage(scores);

            // Convert average (0-100) to GPA (0-4.0)
            const gpa = (avg / 100) * 4.0;
            totalGPA += gpa;
            studentsWithGrades++;
        }
    });

    const avgGPA = studentsWithGrades > 0
        ? (totalGPA / studentsWithGrades).toFixed(2)
        : 0;

    // ── AVERAGE ATTENDANCE ──
    let totalAttendance = 0;
    let studentsWithAttendance = 0;

    students.forEach(student => {
        const studentAttendance = attendance.filter(a => a.studentId === student.id);
        if (studentAttendance.length > 0) {
            const presentCount = studentAttendance.filter(a =>
                a.status === "Present"
            ).length;
            const percentage = Math.round((presentCount / studentAttendance.length) * 100);
            totalAttendance += percentage;
            studentsWithAttendance++;
        }
    });

    const avgAttendance = studentsWithAttendance > 0
        ? Math.round(totalAttendance / studentsWithAttendance)
        : 0;

    // ── UPDATE DOM ──
    const elements = {
        totalStudentsVal: document.querySelector("#totalStudentsVal"),
        activeStudents: document.querySelector("#activeStudents"),
        totalCoursesVal: document.querySelector("#totalCoursesVal"),
        activeCourses: document.querySelector("#activeCourses"),
        avgGPAVal: document.querySelector("#avgGPAVal"),
        avgAttendanceVal: document.querySelector("#avgAttendanceVal"),
    };

    if (elements.totalStudentsVal) elements.totalStudentsVal.textContent = totalStudents;
    if (elements.activeStudents) elements.activeStudents.textContent = `${activeStudents} active`;
    if (elements.totalCoursesVal) elements.totalCoursesVal.textContent = totalCourses;
    if (elements.activeCourses) elements.activeCourses.textContent = `${activeCourses} active`;
    if (elements.avgGPAVal) elements.avgGPAVal.textContent = avgGPA;
    if (elements.avgAttendanceVal) elements.avgAttendanceVal.textContent = `${avgAttendance}%`;

    console.log("✅ Dashboard stats updated:", {
        totalStudents,
        totalCourses,
        avgGPA,
        avgAttendance
    });
}
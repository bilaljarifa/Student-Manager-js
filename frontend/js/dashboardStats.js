// dashboardStats.js
import { getAverage } from "./grades.js";

export function updateDashboardStats(students) {
    // passing students → average >= 60
    const passingStudents = students.filter((student) => {
        if (student.grades.length === 0) return false;

        // extract just the scores from grade objects
        const scores = student.grades.map(g => g.score);  // ← ADD THIS
        const avg = getAverage(scores);  // ← pass scores, not objects
        return avg >= 60;
    }).length;

    // failing students → average < 60
    const failingStudents = students.filter((student) => {
        if (student.grades.length === 0) return false;

        // extract just the scores
        const scores = student.grades.map(g => g.score);  // ← ADD THIS
        const avg = getAverage(scores);  // ← pass scores
        return avg < 60;
    }).length;

    // unique classes
    const uniqueClasses = [...new Set(students.map((s) => s.studentClass))].length;

    // update HTML
    document.querySelector("#passingCount").textContent = passingStudents;
    document.querySelector("#failingCount").textContent = failingStudents;
    document.querySelector("#classCount").textContent = uniqueClasses;
}
// ── IMPORTS ────────────────────────────────────────────
import { getAverage } from "./grades.js";

// ── GRADES PAGE FUNCTIONS ──────────────────────────────

// FUNCTION 1: renderGradesPage
// Fill the student dropdown with all student names
export function renderGradesPage(students, filterStudentSelector) {
    filterStudentSelector.innerHTML = '<option value="">All Students</option>';

    students.forEach((student) => {
        const option = document.createElement("option");
        option.value = student.id;
        option.textContent = student.name;
        filterStudentSelector.appendChild(option);
    });
}

// ──────────────────────────────────────────────────────

// FUNCTION 2: updateGradesTable (IMPROVED)
// Display grades in a professional table
export function updateGradesTable(students, filterStudentSelector, gradesTableBodySelector) {
    gradesTableBodySelector.innerHTML = "";

    const selectedStudentId = filterStudentSelector.value;
    let filteredStudents = students;

    if (selectedStudentId) {
        filteredStudents = students.filter(s => s.id === parseInt(selectedStudentId));
    }

    filteredStudents.forEach((student) => {
        student.grades.forEach((grade, index) => {
            const row = document.createElement("tr");
            row.style.borderBottom = "1px solid var(--border)";
            row.style.transition = "var(--transition)";

            // Hover effect
            row.addEventListener("mouseenter", () => {
                row.style.backgroundColor = "var(--border-light)";
            });
            row.addEventListener("mouseleave", () => {
                row.style.backgroundColor = "transparent";
            });

            // Status: PASS (>=60) or FAIL (<60)
            const isPass = grade.score >= 60;
            const statusColor = isPass ? "#10B981" : "#EF4444";
            const statusText = isPass ? "✅ PASS" : "❌ FAIL";

            row.innerHTML = `
                <td style="padding: 14px 16px; color: var(--text-primary);">${student.name}</td>
                <td style="padding: 14px 16px; color: var(--text-primary);">${grade.subject}</td>
                <td style="padding: 14px 16px; text-align: center; font-weight: 600; color: var(--primary); font-size: 1.1rem;">${grade.score}</td>
                <td style="padding: 14px 16px; text-align: center;">
                    <span style="color: ${statusColor}; font-weight: 600; font-size: 0.9rem;">${statusText}</span>
                </td>
                <td style="padding: 14px 16px; text-align: center;">
                    <button class="updateGradeBtn" data-student-id="${student.id}" data-subject="${grade.subject}" style="padding: 6px 12px; background: rgba(37, 99, 235, 0.08); color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: var(--transition); margin-right: 6px;">✏️ Update</button>
                    <button class="deleteGradeBtn" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.08); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: var(--transition);">🗑️ Delete</button>
                </td>
            `;
            gradesTableBodySelector.appendChild(row);
        });
    });

    // Show message if no grades
    if (gradesTableBodySelector.children.length === 0) {
        const emptyRow = document.createElement("tr");
        emptyRow.innerHTML = `
            <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-secondary);">
                📝 No grades added yet
            </td>
        `;
        gradesTableBodySelector.appendChild(emptyRow);
    }
}

// ──────────────────────────────────────────────────────

// FUNCTION 3: getStudentGradeStats (FOR ANALYTICS ONLY)
// Calculate average, highest, and lowest grades
// This is NOW used in Analytics, not in Grades page
export function getStudentGradeStats(students, selectedStudentId) {
    let allGrades = [];

    if (selectedStudentId) {
        const student = students.find(s => s.id === parseInt(selectedStudentId));
        if (student) {
            allGrades = student.grades.map(g => g.score);
        }
    } else {
        students.forEach(s => {
            s.grades.forEach(g => {
                allGrades.push(g.score);
            });
        });
    }

    if (allGrades.length === 0) {
        return { average: 0, highest: 0, lowest: 0, total: 0 };
    }

    return {
        average: getAverage(allGrades),
        highest: Math.max(...allGrades),
        lowest: Math.min(...allGrades),
        total: allGrades.length
    };
}
// ──────────────────────────────────────────────────────

// FUNCTION 4: updateGradesStats (NEW)
// PURPOSE: Update the stats display on the Grades page
// INPUT: students array, selected student selector, and 3 DOM elements
export function updateGradesStats(students, filterStudentSelector, avgGradeEl, highestGradeEl, lowestGradeEl) {
    const selectedStudentId = filterStudentSelector?.value;
    const stats = getStudentGradeStats(students, selectedStudentId);

    // Update DOM elements with the stats
    if (avgGradeEl) avgGradeEl.textContent = stats.average;
    if (highestGradeEl) highestGradeEl.textContent = stats.highest;
    if (lowestGradeEl) lowestGradeEl.textContent = stats.lowest;
}
// ── IMPORTS ────────────────────────────────────────────
import { getAverage } from "./grades.js";

// ── GRADES PAGE FUNCTIONS ──────────────────────────────

// FUNCTION 1: renderGradesPage
// Fill the student dropdown with all student names
export function renderGradesPage(students, filterStudentSelector) {
    if (!filterStudentSelector) {
        console.error("❌ ERROR: #studentFilterGrades element not found in HTML!");
        return;
    }

    filterStudentSelector.innerHTML = '<option value="">All Students</option>';

    if (!students || students.length === 0) {
        console.warn("⚠️ WARNING: No students found! Add a student first.");
        return;
    }

    students.forEach((student) => {
        const option = document.createElement("option");
        option.value = student.id;
        option.textContent = student.name;
        filterStudentSelector.appendChild(option);
    });

    console.log(`✅ SUCCESS: ${students.length} students loaded into dropdown`);
}

// ──────────────────────────────────────────────────────

// FUNCTION 2: updateGradesTable (WITH EDIT BUTTON)
// Display grades in a professional table
export function updateGradesTable(students, classFilterSelector, studentFilterSelector, gradesTableBodySelector) {
    gradesTableBodySelector.innerHTML = "";

    const selectedClass = classFilterSelector?.value || "";
    const selectedStudentId = studentFilterSelector?.value || "";

    let filteredStudents = students;

    // Filter by class
    if (selectedClass) {
        filteredStudents = filteredStudents.filter(s => s.studentClass === selectedClass);
    }

    // Filter by student
    if (selectedStudentId) {
        filteredStudents = filteredStudents.filter(s => s.id === parseInt(selectedStudentId));
    }

    filteredStudents.forEach((student) => {
        student.grades.forEach((grade, gradeIndex) => {
            const row = document.createElement("tr");
            row.style.borderBottom = "1px solid var(--border)";
            row.style.transition = "var(--transition)";
            row.dataset.studentId = student.id;
            row.dataset.gradeIndex = gradeIndex;

            // Hover effect
            row.addEventListener("mouseenter", () => {
                row.style.backgroundColor = "var(--background)";
            });
            row.addEventListener("mouseleave", () => {
                row.style.backgroundColor = "transparent";
            });

            row.innerHTML = `
                <td style="padding: 14px 16px; color: var(--text);">${student.name}</td>
                <td style="padding: 14px 16px;">
                    <span class="type-badge">${student.studentClass}</span>
                </td>
                <td style="padding: 14px 16px; color: var(--text);">${grade.subject}</td>
                <td style="padding: 14px 16px; text-align: center; font-weight: 600; color: var(--primary); font-size: 1.1rem;">${grade.score}</td>
               <td style="padding: 14px 16px; text-align: center;">
        <button class="editGradeBtn" data-student-id="${student.id}" data-grade-index="${gradeIndex}" data-student-name="${student.name}" data-subject="${grade.subject}" data-score="${grade.score}" style="padding: 6px 12px; background: rgba(37, 99, 235, 0.08); color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: var(--transition);">✏️ Edit</button>
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
            <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">
                📝 No grades found
            </td>
        `;
        gradesTableBodySelector.appendChild(emptyRow);
    }
}

// ──────────────────────────────────────────────────────

// FUNCTION 3: getStudentGradeStats
// Calculate average, highest, and lowest grades
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

// FUNCTION 4: updateGradesStats
// Update the stats display on the Grades page
export function updateGradesStats(students, filterStudentSelector, avgGradeEl, highestGradeEl, lowestGradeEl) {
    const selectedStudentId = filterStudentSelector?.value;
    const stats = getStudentGradeStats(students, selectedStudentId);

    // Update DOM elements with the stats
    if (avgGradeEl) avgGradeEl.textContent = stats.average;
    if (highestGradeEl) highestGradeEl.textContent = stats.highest;
    if (lowestGradeEl) lowestGradeEl.textContent = stats.lowest;
}
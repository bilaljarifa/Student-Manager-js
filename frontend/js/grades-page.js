// ── IMPORTS ────────────────────────────────────────────
import { getAverage } from "./grades.js"; // import the calculation function

// ── GRADES PAGE FUNCTIONS ──────────────────────────────

// FUNCTION 1: renderGradesPage
// PURPOSE: Fill the student dropdown with all student names
// CALLED: When page loads, or when a student is added/deleted
export function renderGradesPage(students, filterStudentSelector) {
    // clear the dropdown and add default option
    filterStudentSelector.innerHTML = '<option value="">All Students</option>';

    // loop through every student in the array
    students.forEach((student) => {
        // create a new <option> element
        const option = document.createElement("option");

        // set the value to the student's ID (so we can identify them later)
        option.value = student.id;

        // set the text that user sees as the student's name
        option.textContent = student.name;

        // add this option to the dropdown
        filterStudentSelector.appendChild(option);
    });
}

// ──────────────────────────────────────────────────────

// FUNCTION 2: updateGradesTable
// PURPOSE: Display grades in a table based on selected student
// CALLED: When page loads, or when user changes the student dropdown
export function updateGradesTable(students, filterStudentSelector, gradesTableBodySelector) {
    // clear the table (remove all old rows)
    gradesTableBodySelector.innerHTML = "";

    // get the ID of the student selected in the dropdown
    const selectedStudentId = filterStudentSelector.value;

    // start by assuming we want to show all students
    let filteredStudents = students;

    // if user selected a specific student (not "All Students")
    if (selectedStudentId) {
        // filter the array to get only that ONE student
        filteredStudents = students.filter(s => s.id === parseInt(selectedStudentId));
    }
    // if selectedStudentId is empty, filteredStudents stays as all students

    // loop through each student we're displaying
    filteredStudents.forEach((student) => {

        // loop through each grade that student has
        student.grades.forEach((grade, index) => {

            // create a new table row for this grade
            const row = document.createElement("tr");

            // grade is now an object: { subject: "Math", score: 85 }
            // check if the score passes (>=60) or fails (<60)
            const status = grade.score >= 60 ? "Pass" : "Fail";

            // fill the row with data
            row.innerHTML = `
                <td>${student.name}</td>
                <td>${grade.subject}</td>
                <td>${grade.score}</td>
                <td><span class="status ${status.toLowerCase()}">${status}</span></td>
                <td>
                  <button class="deleteGradeBtn">Delete</button>
                </td>
            `;

            // add the row to the table
            gradesTableBodySelector.appendChild(row);
        });
    });
}

// ──────────────────────────────────────────────────────

// FUNCTION 3: updateGradesStats
// PURPOSE: Calculate and display average, highest, and lowest grades
// CALLED: When page loads, or when user changes the student dropdown
export function updateGradesStats(
    students,
    filterStudentSelector,
    avgGradeSelector,
    highestGradeSelector,
    lowestGradeSelector
) {
    // get the ID of the selected student
    const selectedStudentId = filterStudentSelector.value;

    // empty array to collect grade scores
    let allGrades = [];

    // if a specific student is selected
    if (selectedStudentId) {
        // find that ONE student in the array
        const student = students.find(s => s.id === parseInt(selectedStudentId));

        // extract just the SCORES from their grades
        // grade is { subject: "Math", score: 85 } → we want [85, 90, 78...]
        allGrades = student.grades.map(g => g.score);
    } else {
        // if "All Students" is selected, collect scores from EVERYONE
        students.forEach(s => {
            // loop through each student
            s.grades.forEach(g => {
                // add each grade's score to the array
                allGrades.push(g.score);
            });
        });
    }

    // if there are no grades at all, show 0 for everything
    if (allGrades.length === 0) {
        avgGradeSelector.textContent = "0";
        highestGradeSelector.textContent = "0";
        lowestGradeSelector.textContent = "0";
        return; // stop here, no need to calculate
    }

    // calculate average using the getAverage function from grades.js
    avgGradeSelector.textContent = getAverage(allGrades);

    // Math.max(...array) finds the highest number
    // ... spreads the array into individual numbers: max(85, 90, 78) = 90
    highestGradeSelector.textContent = Math.max(...allGrades);

    // Math.min(...array) finds the lowest number
    lowestGradeSelector.textContent = Math.min(...allGrades);
}
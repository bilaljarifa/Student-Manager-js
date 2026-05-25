// ── IMPORTS ────────────────────────────────────────────
// each import brings in a function/class from another file
import { Student } from "./student.js";        // the Student blueprint
import { validation } from "./validation.js";  // form validation
import { renderStudents } from "./ui.js";       // draws the table
import { saveStudents, loadStudents } from "./storage.js"; // localStorage
import { updateCounter } from "./counter.js";  // updates dashboard numbers
import { updateChart } from "./chart.js";       // updates pie chart
import { showToast } from "./toast.js";         // shows notifications
import { getAverage, getStatus } from "./grades.js"; // grade calculations
import { renderGradesPage, updateGradesTable, updateGradesStats } from "./grades-page.js";// ── AUTH CHECK ─────────────────────────────────────────
// this runs immediately when the page loads
// if no "isLoggedIn" in localStorage → kick user to login page
if (!localStorage.getItem("isLoggedIn")) {
  window.location.href = "login.html";
}

// ── SELECTORS ──────────────────────────────────────────
// grabbing all HTML elements we need to interact with
const searchInput = document.querySelector("#search");
const nameInput = document.querySelector("#name");
const emailInput = document.querySelector("#email");
const classInput = document.querySelector("#studentClass");
const paymentInput = document.querySelector("#paymentStatus");
const addBtn = document.querySelector("#addBtn");
const studentsContainer = document.querySelector("#students"); // the <tbody>
const sortBtn = document.querySelector("#sortBtn");
const darkBtn = document.querySelector("#darkModeBtn");
const clearAllBtn = document.querySelector("#clearAllBtn");
const exportBtn = document.querySelector("#exportBtn");
const classFilter = document.querySelector("#classFilter");
const sortPayment = document.querySelector("#sortPayment");
const prevBtn = document.querySelector("#prevBtn");
const nextBtn = document.querySelector("#nextBtn");
const pageInfo = document.querySelector("#pageInfo");
const logoutBtn = document.querySelector("#logoutBtn");
const filterStudent = document.querySelector("#filterStudent");
const gradesTableBody = document.querySelector("#gradesTableBody");
const avgGrade = document.querySelector("#avgGrade");
const highestGrade = document.querySelector("#highestGrade");
const lowestGrade = document.querySelector("#lowestGrade");

// ── STATE ──────────────────────────────────────────────
// these variables hold the live data of your app
let editingStudentId = null;       // null = adding mode, has id = editing mode
const students = loadStudents();   // load saved students from localStorage
let currentPage = 1;               // which page we're on
const studentsPerPage = 10;        // how many students per page

// ── INITIAL RENDER ─────────────────────────────────────
// runs once when page loads to show existing data
renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
updateCounter(students);
updateChart(students);
updateClassFilter();
updatePagination();
renderGradesPage(students, filterStudent);
updateGradesTable(students, filterStudent, gradesTableBody);
updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);

// ── FUNCTIONS ──────────────────────────────────────────

// called when user clicks Delete on a student row
function onDelete(studentId) {
  const confirmed = window.confirm("Are you sure you want to delete?");
  if (!confirmed) return; // stop if user cancels

  // find the row element by its data-id attribute and add fadeOut animation
  const cardElement = document.querySelector(`[data-id="${studentId}"]`);
  cardElement.classList.add("fadeOut");

  // wait for animation to finish (400ms) then actually delete
  setTimeout(() => {
    // filter out the deleted student — returns everyone except this one
    const updatedStudents = students.filter((student) => {
      return student.id !== studentId;
    });

    // update the students array in place (can't reassign because it's const)
    students.length = 0;
    students.push(...updatedStudents);

    saveStudents(students);   // save to localStorage
    renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
    updateCounter(students);
    updateChart(students);
    updateClassFilter();
    updatePagination();
    showToast("Student deleted!", "error");
  }, 400);
}

// called when user clicks Edit on a student row
function onEdit(student) {
  // fill the form inputs with the student's current data
  nameInput.value = student.name;
  emailInput.value = student.email;
  classInput.value = student.studentClass;
  paymentInput.value = student.paymentStatus;

  // store the id so addBtn knows we're editing, not adding
  editingStudentId = student.id;
  addBtn.textContent = "Update Student"; // change button text
  showToast("Editing student...", "info");
}

// called when user clicks "Clear All" button
function clearAll() {
  const confirmed = window.confirm("Are you sure you want to delete all?");
  if (!confirmed) return;

  students.length = 0; // empty the array
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  showToast("All students deleted!", "error");
}

// builds the class dropdown from unique class names in students array
function updateClassFilter() {
  // Set removes duplicates, spread converts it back to array
  const uniqueClasses = [...new Set(students.map((student) => {
    return student.studentClass;
  }))];

  // reset dropdown to just the default option
  classFilter.innerHTML = '<option value="">All Classes</option>';

  // add one option per unique class
  uniqueClasses.forEach((className) => {
    const option = document.createElement("option");
    option.value = className;
    option.textContent = className;
    classFilter.appendChild(option);
  });
}

// returns only students that match search input and class filter
function getFilteredStudents() {
  const searchValue = searchInput.value.toLowerCase();
  const classValue = classFilter.value;

  return students.filter((student) => {
    // check if search value matches any field
    const matchesSearch =
      student.name.toLowerCase().includes(searchValue) ||
      student.email.toLowerCase().includes(searchValue) ||
      student.studentClass.toLowerCase().includes(searchValue) ||
      student.paymentStatus.toLowerCase().includes(searchValue);

    // check if class filter matches (empty = show all)
    const matchesClass =
      classValue === "" || student.studentClass === classValue;

    // student must match BOTH conditions
    return matchesSearch && matchesClass;
  });
}

// exports all students as a downloadable CSV file
function exportCSV() {
  const header = "Name,Email,Class,Payment";

  // convert each student object to a CSV row string
  const rows = students.map((student) => {
    return `${student.name},${student.email},${student.studentClass},${student.paymentStatus}`;
  });

  // join header + rows with newlines
  const csv = [header, ...rows].join("\n");

  // create a file in memory and trigger a download
  const blob = new Blob([csv], { type: "text/csv" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "students.csv";
  a.click();
  URL.revokeObjectURL(url); // clean up memory
  showToast("Students exported!", "info");
}

// returns only the students for the current page
function getPageStudents() {
  const start = (currentPage - 1) * studentsPerPage; // e.g page 2 = start at 10
  const end = start + studentsPerPage;               // end at 20
  return students.slice(start, end);                 // return items 10-20
}

// updates the pagination UI
function updatePagination() {
  const totalPages = Math.ceil(students.length / studentsPerPage);
  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
  prevBtn.disabled = currentPage === 1;            // disable prev on first page
  nextBtn.disabled = currentPage === totalPages;   // disable next on last page
}

// called when user clicks "Add Grade" button on a student row
function onAddGrade(studentId, grade) {
  // find the student by id
  const student = students.find((s) => s.id === studentId);
  student.grades.push(grade); // add new grade to their grades array
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
}

// called when user clicks a student row (not a button)
function onRowClick(student) {
  const average = getAverage(student.grades);
  const status = getStatus(average);

  // fill modal with student data
  document.querySelector("#modalName").textContent = student.name;
  document.querySelector("#modalEmail").textContent = student.email;
  document.querySelector("#modalClass").textContent = student.studentClass;
  document.querySelector("#modalPayment").textContent = student.paymentStatus;
  document.querySelector("#modalAverage").textContent = `Average: ${average}`;
  document.querySelector("#modalStatus").textContent = `Status: ${status}`;
  document.querySelector("#modalGrades").textContent = `Grades: ${student.grades.join(", ")}`;

  // show the modal by adding the active class
  document.querySelector("#modalOverlay").classList.add("active");
}

// ── EVENT LISTENERS ────────────────────────────────────

// logout — clear session and redirect to login
logoutBtn.addEventListener("click", () => {
  localStorage.removeItem("isLoggedIn");
  localStorage.removeItem("username");
  window.location.href = "login.html";
  showToast("Logged out!", "info");
});

// dark mode — toggle dark class on body
darkBtn.addEventListener("click", () => {
  document.body.classList.toggle("dark");
});

// close modal when X is clicked
document.querySelector("#closeModal").addEventListener("click", () => {
  document.querySelector("#modalOverlay").classList.remove("active");
});

// ── SIDEBAR NAVIGATION ─────────────────────────────────
// clicking a nav item shows its section and hides all others
document.querySelectorAll(".nav-item").forEach((item) => {
  item.addEventListener("click", (e) => {
    e.preventDefault(); // stop <a> tag from navigating to a URL

    // remove active from all nav items and sections
    document.querySelectorAll(".nav-item").forEach((nav) => nav.classList.remove("active"));
    document.querySelectorAll(".section").forEach((section) => section.classList.remove("active"));

    // activate clicked item and its matching section
    item.classList.add("active");
    const section = item.dataset.section; // reads data-section="dashboard"
    document.querySelector(`#section-${section}`).classList.add("active");
  });
});

// sort students alphabetically by name
sortBtn.addEventListener("click", () => {
  students.sort((a, b) => {
    // sort returns -1, 0, or 1 to tell JS which item goes first
    if (a.name < b.name) return -1; // a comes before b
    if (a.name > b.name) return 1;  // b comes before a
    return 0;                        // same position
  });
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  showToast("Sorted by name!", "info");
});

// sort by payment status (Paid first or Pending first)
sortPayment.addEventListener("change", () => {
  const sortValue = sortPayment.value;
  students.sort((a, b) => {
    if (sortValue === "Paid") {
      if (a.paymentStatus < b.paymentStatus) return -1;
      if (a.paymentStatus > b.paymentStatus) return 1;
    }
    if (sortValue === "Pending") {
      if (a.paymentStatus > b.paymentStatus) return -1;
      if (a.paymentStatus < b.paymentStatus) return 1;
    }
    return 0;
  });
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
  showToast("Sorted by payment!", "info");
});

// pagination — go to previous page
prevBtn.addEventListener("click", () => {
  currentPage--;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
});

// pagination — go to next page
nextBtn.addEventListener("click", () => {
  currentPage++;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
});

// search — filter students as user types
searchInput.addEventListener("input", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit, onAddGrade, onRowClick);
});

// class filter — filter students by class
classFilter.addEventListener("change", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit, onAddGrade, onRowClick);
});
// ── GRADES PAGE ────────────────────────────────
filterStudent.addEventListener("change", () => {
  updateGradesTable(students, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
});
// add or update a student
addBtn.addEventListener("click", () => {
  // validate first — if fails, stop here
  if (!validation(nameInput, emailInput, classInput, students)) return;

  if (editingStudentId === null) {
    // ADDING — create a new Student object and push to array
    const student = new Student(
      Date.now(),        // unique id using current timestamp
      nameInput.value,
      emailInput.value,
      classInput.value,
      paymentInput.value
    );
    students.push(student);
    showToast("Student added successfully!");
  } else {
    // EDITING — find the student and update their data
    const studentToEdit = students.find((student) => {
      return student.id === editingStudentId;
    });
    studentToEdit.name = nameInput.value;
    studentToEdit.email = emailInput.value;
    studentToEdit.studentClass = classInput.value;
    studentToEdit.paymentStatus = paymentInput.value;
    editingStudentId = null;           // reset back to adding mode
    addBtn.textContent = "Add Student"; // reset button text
    showToast("Student updated!", "info");
  }

  saveStudents(students);
  currentPage = Math.ceil(students.length / studentsPerPage); // go to last page
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();

  // clear the form inputs
  nameInput.value = "";
  emailInput.value = "";
  classInput.value = "";
  paymentInput.value = "Paid";
});

clearAllBtn.addEventListener("click", clearAll);
exportBtn.addEventListener("click", exportCSV);
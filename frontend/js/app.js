// ── IMPORTS ────────────────────────────────────────────
// each import brings in a function/class from another file

import { isAdmin, isTeacher, filterStudentsByRole, canDeleteStudent, canEditStudent, canAddGrades } from "./role-management.js";
import { Student } from "./student.js";
import { validation } from "./validation.js";
import { renderStudents } from "./ui.js";
import { saveStudents, loadStudents } from "./storage.js";
import { updateCounter } from "./counter.js";
import { updateChart } from "./chart.js";
import { showToast } from "./toast.js";
import { getAverage, getStatus } from "./grades.js";
import { renderGradesPage, updateGradesTable, updateGradesStats } from "./grades-page.js";
import { displayWelcome } from "./welcome.js";
import { updateDashboardStats } from "./dashboardStats.js";
import { loadTeachers } from "./storage.js";
import { Teacher } from "./teacher.js";        // Teacher blueprint
import { renderTeachers } from "./teacher-ui.js"; // render teachers table
import { saveTeachers } from "./storage.js"; // ← ADD THIS
// ── AUTH CHECK ─────────────────────────────────────────
// this runs immediately when the page loads
// if no "isLoggedIn" in localStorage → kick user to login page
if (!localStorage.getItem("isLoggedIn")) {
  window.location.href = "login.html";
}
// At the start
// ── SELECTORS ──────────────────────────────────────────
const searchInput = document.querySelector("#search");
const nameInput = document.querySelector("#name");
const emailInput = document.querySelector("#email");
const classInput = document.querySelector("#studentClass");
const paymentInput = document.querySelector("#paymentStatus");
const addBtn = document.querySelector("#addBtn");
const studentsContainer = document.querySelector("#students");
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
const gradeSubject = document.querySelector("#gradeSubject");
const gradeScore = document.querySelector("#gradeScore");
const addGradeBtn = document.querySelector("#addGradeBtn");
const highestGrade = document.querySelector("#highestGrade");
const lowestGrade = document.querySelector("#lowestGrade");
const teacherName = document.querySelector("#teacherName");
const teacherUsername = document.querySelector("#teacherUsername");
const teacherPassword = document.querySelector("#teacherPassword");
const addTeacherBtn = document.querySelector("#addTeacherBtn");
const teachersContainer = document.querySelector("#teachers");
const teacherClass = document.querySelector("#teacherClass");
const teacherRoom = document.querySelector("#teacherRoom");
const teacherSchedule = document.querySelector("#teacherSchedule");
// ── STATE ──────────────────────────────────────────────
let editingStudentId = null;
let editingTeacherId = null;        // ← NEW: track which teacher we're editing
const students = loadStudents();
const teachers = loadTeachers();    // ← NEW: load teachers from localStorage
let currentPage = 1;
const studentsPerPage = 10;


// ── INITIAL RENDER ─────────────────────────────────────
const roleFilteredStudents = filterStudentsByRole(students);
renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher); // ← NEW
renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
updateDashboardStats(students);
updateCounter(students);
updateChart(students);
updateClassFilter();
updatePagination();
renderGradesPage(students, filterStudent);
updateGradesTable(students, filterStudent, gradesTableBody);
updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
updateTeacherClassFilter();

// ── FUNCTIONS ──────────────────────────────────────────
displayWelcome();
// ── TEACHERS FUNCTIONS ──────────────────────────
function onEditTeacher(teacher) {
  // CHECK IF ADMIN
  if (!isAdmin()) {
    showToast("Only admins can edit teachers!", "error");
    return;
  }

  teacherName.value = teacher.name;
  teacherUsername.value = teacher.username;
  teacherPassword.value = teacher.password;
  teacherClass.value = teacher.teacherClass;
  teacherRoom.value = teacher.room;
  teacherSchedule.value = teacher.schedule;

  editingTeacherId = teacher.id;
  addTeacherBtn.textContent = "Update Teacher";
  showToast("Editing teacher...", "info");
}
async function onDeleteTeacher(teacherId) {
  // CHECK IF ADMIN
  if (!isAdmin()) {
    showToast("Only admins can delete teachers!", "error");
    return;
  }

  const confirmed = await showConfirm("Delete this teacher?");
  if (!confirmed) return;

  const updatedTeachers = teachers.filter(t => t.id !== teacherId);
  teachers.length = 0;
  teachers.push(...updatedTeachers);
  saveTeachers(teachers);
  renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher);
  showToast("Teacher deleted!", "error");
}
async function onDelete(studentId) {
  if (!canDeleteStudent()) {
    showToast("Teachers cannot delete students!", "error");
    return;
  }

  const confirmed = await showConfirm("Delete this student?");
  if (!confirmed) return;

  const cardElement = document.querySelector(`[data-id="${studentId}"]`);
  cardElement.classList.add("fadeOut");

  setTimeout(() => {
    const updatedStudents = students.filter((student) => {
      return student.id !== studentId;
    });

    students.length = 0;
    students.push(...updatedStudents);

    saveStudents(students);
    renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
    updateCounter(students);
    updateChart(students);
    updateClassFilter();
    updatePagination();
    updateDashboardStats(students);
    showToast("Student deleted!", "error");
  }, 400);
}

function onEdit(student) {
  if (!canEditStudent()) {
    showToast("Teachers cannot edit students!", "error");
    return;
  }
  nameInput.value = student.name;
  emailInput.value = student.email;
  classInput.value = student.studentClass;
  paymentInput.value = student.paymentStatus;

  editingStudentId = student.id;
  addBtn.textContent = "Update Student";
  showToast("Editing student...", "info");
}

async  function clearAll() {
  const confirmed = await showConfirm("Are you sure you want to delete all?");
  if (!confirmed) return;

  students.length = 0;
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  updateDashboardStats(students);
  showToast("All students deleted!", "error");
}

function updateClassFilter() {
  const uniqueClasses = [...new Set(students.map((student) => {
    return student.studentClass;
  }))];

  classFilter.innerHTML = '<option value="">All Classes</option>';

  uniqueClasses.forEach((className) => {
    const option = document.createElement("option");
    option.value = className;
    option.textContent = className;
    classFilter.appendChild(option);
  });
}
function updateTeacherClassFilter() {
  const uniqueClasses = [...new Set(students.map((student) => {
    return student.studentClass;
  }))];

  teacherClass.innerHTML = '<option value="">Select Class</option>';

  uniqueClasses.forEach((className) => {
    const option = document.createElement("option");
    option.value = className;
    option.textContent = className;
    teacherClass.appendChild(option);
  });
}

function getFilteredStudents() {
  const searchValue = searchInput.value.toLowerCase();
  const classValue = classFilter.value;

  return students.filter((student) => {
    const matchesSearch =
      student.name.toLowerCase().includes(searchValue) ||
      student.email.toLowerCase().includes(searchValue) ||
      student.studentClass.toLowerCase().includes(searchValue) ||
      student.paymentStatus.toLowerCase().includes(searchValue);

    const matchesClass =
      classValue === "" || student.studentClass === classValue;

    return matchesSearch && matchesClass;
  });
}

function exportCSV() {
  const header = "Name,Email,Class,Payment";

  const rows = students.map((student) => {
    return `${student.name},${student.email},${student.studentClass},${student.paymentStatus}`;
  });

  const csv = [header, ...rows].join("\n");

  const blob = new Blob([csv], { type: "text/csv" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "students.csv";
  a.click();
  URL.revokeObjectURL(url);
  showToast("Students exported!", "info");
}

function getPageStudents() {
  const start = (currentPage - 1) * studentsPerPage;
  const end = start + studentsPerPage;
  return students.slice(start, end);
}

function updatePagination() {
  const totalPages = Math.ceil(students.length / studentsPerPage);
  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
}

function onAddGrade(studentId, grade) {
  const student = students.find((s) => s.id === studentId);
  student.grades.push({ subject: "General", score: grade }); // ← make it an object
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateDashboardStats(students);  // ← ADD THIS
  updateGradesTable(students, filterStudent, gradesTableBody);  // ← ADD THIS
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);  // ← ADD THIS
}

// ── CUSTOM CONFIRMATION MODAL ──────────────────────────────
function showConfirm(title, message) {
  return new Promise((resolve) => {
    const overlay = document.querySelector("#confirmOverlay");
    const titleEl = document.querySelector("#confirmTitle");
    const messageEl = document.querySelector("#confirmMessage");
    const okBtn = document.querySelector("#confirmOkBtn");
    const cancelBtn = document.querySelector("#confirmCancelBtn");

    titleEl.textContent = title;
    messageEl.textContent = message;

    overlay.classList.add("active");

    const handleOk = () => {
      overlay.classList.remove("active");
      okBtn.removeEventListener("click", handleOk);
      cancelBtn.removeEventListener("click", handleCancel);
      resolve(true);
    };

    const handleCancel = () => {
      overlay.classList.remove("active");
      okBtn.removeEventListener("click", handleOk);
      cancelBtn.removeEventListener("click", handleCancel);
      resolve(false);
    };

    okBtn.addEventListener("click", handleOk);
    cancelBtn.addEventListener("click", handleCancel);
  });
}
function onRowClick(student) {
  // Extract just the scores from grade objects
  const scores = student.grades.map(g => typeof g === 'object' ? g.score : g);
  const average = getAverage(scores);
  const status = getStatus(average);

  // Format grades for display
  const gradesList = student.grades.map(g => {
    if (typeof g === 'object') {
      return `${g.subject}: ${g.score}`;
    }
    return g;
  }).join(", ");

  document.querySelector("#modalName").textContent = student.name;
  document.querySelector("#modalEmail").textContent = student.email;
  document.querySelector("#modalClass").textContent = student.studentClass;
  document.querySelector("#modalPayment").textContent = student.paymentStatus;
  document.querySelector("#modalAverage").textContent = `Average: ${average}`;
  document.querySelector("#modalStatus").textContent = `Status: ${status}`;
  document.querySelector("#modalGrades").textContent = `Grades: ${gradesList}`;

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
document.querySelectorAll(".nav-item").forEach((item) => {
  item.addEventListener("click", (e) => {
    e.preventDefault();

    document.querySelectorAll(".nav-item").forEach((nav) => nav.classList.remove("active"));
    document.querySelectorAll(".section").forEach((section) => section.classList.remove("active"));

    item.classList.add("active");
    const section = item.dataset.section;
    document.querySelector(`#section-${section}`).classList.add("active");
  });
});

// sort students alphabetically by name
sortBtn.addEventListener("click", () => {
  students.sort((a, b) => {
    if (a.name < b.name) return -1;
    if (a.name > b.name) return 1;
    return 0;
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
// ── DELETE GRADE ────────────────────────────────
document.addEventListener("click",async  (e) => {
  if (e.target.classList.contains("deleteGradeBtn")) {
    const confirmed = await showConfirm("Delete this grade?");
    if (!confirmed) return;

    const row = e.target.closest("tr");
    const studentName = row.cells[0].textContent;
    const gradeScore = row.cells[2].textContent;

    const student = students.find(s => s.name === studentName);
    const gradeIndex = student.grades.findIndex(g => g.score == gradeScore);
    student.grades.splice(gradeIndex, 1);
    saveStudents(students);

    updateGradesTable(students, filterStudent, gradesTableBody);
    updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
    updateDashboardStats(students);

    showToast("Grade deleted!", "error");
  }
});

// ── ADD TEACHER (ADMIN ONLY) ────────────────────────────
addTeacherBtn.addEventListener("click", () => {
  // CHECK IF ADMIN
  if (!isAdmin()) {
    showToast("Only admins can add teachers!", "error");
    return;
  }

  const name = teacherName.value.trim();
  const username = teacherUsername.value.trim();
  const password = teacherPassword.value.trim();
  const classAssigned = teacherClass.value.trim();
  const room = teacherRoom.value.trim();
  const schedule = teacherSchedule.value.trim();

  // Validate
  if (!name || !username || !password) {
    showToast("Please fill all required fields!", "error");
    return;
  }

  if (editingTeacherId === null) {
    // ADDING NEW TEACHER
    const teacher = new Teacher(
      Date.now(),
      name,
      username,
      password,
      classAssigned,
      room,
      schedule
    );
    teachers.push(teacher);
    showToast("Teacher added successfully!");
  } else {
    // UPDATING TEACHER
    const teacherToEdit = teachers.find(t => t.id === editingTeacherId);
    teacherToEdit.name = name;
    teacherToEdit.username = username;
    teacherToEdit.password = password;
    teacherToEdit.teacherClass = classAssigned;
    teacherToEdit.room = room;
    teacherToEdit.schedule = schedule;
    editingTeacherId = null;
    addTeacherBtn.textContent = "Add Teacher";
    showToast("Teacher updated!", "info");
  }

  saveTeachers(teachers);
  renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher);

  // Clear form
  teacherName.value = "";
  teacherUsername.value = "";
  teacherPassword.value = "";
  teacherClass.value = "";
  teacherRoom.value = "";
  teacherSchedule.value = "";
});
// ── UPDATE WHEN DROPDOWN CHANGES ────────────────────────────────
filterStudent.addEventListener("change", () => {
  updateGradesTable(students);
  updateGradesStats(students);
});
// ──  update grades  ────────────────────────────────────

document.addEventListener("click", (e) => {
  if (e.target.classList.contains("updateGradeBtn")) {
    const newScore = prompt("Enter new score (0-100):");
    if (newScore === null || isNaN(newScore) || newScore < 0 || newScore > 100) return;

    // Convert to number
    const studentId = parseInt(e.target.dataset.studentId);
    const subject = e.target.dataset.subject;

    // Debug: check if student exists
    console.log("Looking for student ID:", studentId);
    console.log("All student IDs:", students.map(s => s.id));

    const student = students.find(s => s.id === studentId);

    if (!student) {
      showToast("Student not found!", "error");
      return;
    }

    const gradeIndex = student.grades.findIndex(g => g.subject === subject);

    if (gradeIndex === -1) {
      showToast("Grade not found!", "error");
      return;
    }

    student.grades[gradeIndex].score = parseInt(newScore);

    saveStudents(students);
    updateGradesTable(students, filterStudent, gradesTableBody);
    updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
    showToast("Grade updated!", "info");
  }
});
// ── ADD GRADE ────────────────────────────────────
addGradeBtn.addEventListener("click", () => {
  const selectedStudentId = filterStudent.value;
  const subject = gradeSubject.value.trim();
  const score = Number(gradeScore.value);

  if (!selectedStudentId) {
    showToast("Please select a student!", "error");
    return;
  }

  // Define student FIRST
  const student = students.find(s => s.id === parseInt(selectedStudentId));

  // THEN check permission
  if (!canAddGrades(student)) {
    showToast("You cannot add grades to this student!", "error");
    return;
  }

  if (!subject) {
    showToast("Please enter a subject name!", "error");
    return;
  }

  if (isNaN(score) || score < 0 || score > 100) {
    showToast("Score must be between 0-100!", "error");
    return;
  }

  student.grades.push({ subject, score });

  saveStudents(students);

  updateGradesTable(students, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
  updateDashboardStats(students);

  gradeSubject.value = "";
  gradeScore.value = "";

  showToast("Grade added successfully!", "success");
});

// ── GRADES PAGE ────────────────────────────────
filterStudent.addEventListener("change", () => {
  updateGradesTable(students, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
});

// add or update a student
addBtn.addEventListener("click", () => {
  if (!validation(nameInput, emailInput, classInput, students)) return;

  if (editingStudentId === null) {
    const student = new Student(
      Date.now(),
      nameInput.value,
      emailInput.value,
      classInput.value,
      paymentInput.value
    );
    students.push(student);
    showToast("Student added successfully!");
  } else {
    const studentToEdit = students.find((student) => {
      return student.id === editingStudentId;
    });
    studentToEdit.name = nameInput.value;
    studentToEdit.email = emailInput.value;
    studentToEdit.studentClass = classInput.value;
    studentToEdit.paymentStatus = paymentInput.value;
    editingStudentId = null;
    addBtn.textContent = "Add Student";
    showToast("Student updated!", "info");
  }

  saveStudents(students);
  currentPage = Math.ceil(students.length / studentsPerPage);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  updateDashboardStats(students);
  nameInput.value = "";
  emailInput.value = "";
  classInput.value = "";
  paymentInput.value = "Paid";
});

clearAllBtn.addEventListener("click", clearAll);
exportBtn.addEventListener("click", exportCSV);
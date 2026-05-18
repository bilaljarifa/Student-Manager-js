import { Student } from "./student.js";
import { validation } from "./validation.js";
import { renderStudents } from "./ui.js";
import { saveStudents, loadStudents } from "./storage.js";
import { updateCounter } from "./counter.js";
import { updateChart } from "./chart.js";
import { showToast } from "./toast.js";

// ── Selectors ──────────────────────────────────────────
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

// ── State ──────────────────────────────────────────────
let editingStudentId = null;
const students = loadStudents();
let currentPage = 1;
const studentsPerPage = 10;

// ── Initial Render ─────────────────────────────────────
renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
updateCounter(students);
updateChart(students);
updateClassFilter();
updatePagination();

// ── Functions ──────────────────────────────────────────
function onDelete(studentId) {
  const confirmed = window.confirm("Are you sure you want to delete?");
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
    renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
    updateCounter(students);
    updateChart(students);
    updateClassFilter();
    updatePagination();
    showToast("Student deleted!", "error"); // ✅
  }, 400);
}

function onEdit(student) {
  nameInput.value = student.name;
  emailInput.value = student.email;
  classInput.value = student.studentClass;
  paymentInput.value = student.paymentStatus;
  editingStudentId = student.id;
  addBtn.textContent = "Update Student";
  showToast("Editing student...", "info"); // ✅
}

function clearAll() {
  const confirmed = window.confirm("Are you sure you want to delete all?");
  if (!confirmed) return;

  students.length = 0;
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  showToast("All students deleted!", "error"); // ✅
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
  showToast("Students exported!", "info"); // ✅
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

// ── Event Listeners ────────────────────────────────────
darkBtn.addEventListener("click", () => {
  document.body.classList.toggle("dark");
});

sortBtn.addEventListener("click", () => {
  students.sort((a, b) => {
    if (a.name < b.name) return -1;
    if (a.name > b.name) return 1;
    return 0;
  });
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  showToast("Sorted by name!", "info"); // ✅
});

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

  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updatePagination();
  showToast("Sorted by payment!", "info"); // ✅
});

prevBtn.addEventListener("click", () => {
  currentPage--;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updatePagination();
});

nextBtn.addEventListener("click", () => {
  currentPage++;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updatePagination();
});

searchInput.addEventListener("input", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit);
});

classFilter.addEventListener("change", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit);
});

addBtn.addEventListener("click", () => {
  if (!validation(nameInput.value, emailInput.value, classInput.value)) return;

  if (editingStudentId === null) {
    const student = new Student(
      Date.now(),
      nameInput.value,
      emailInput.value,
      classInput.value,
      paymentInput.value
    );
    students.push(student);
    showToast("Student added successfully!"); // ✅
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
    showToast("Student updated!", "info"); // ✅
  }

  saveStudents(students);
  currentPage = Math.ceil(students.length / studentsPerPage);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  nameInput.value = "";
  emailInput.value = "";
  classInput.value = "";
  paymentInput.value = "Paid";
});

clearAllBtn.addEventListener("click", clearAll);
exportBtn.addEventListener("click", exportCSV);
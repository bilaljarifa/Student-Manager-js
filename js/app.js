import { Student } from "./student.js";
import { validation } from "./validation.js";
import { renderStudents } from "./ui.js";
import { saveStudents, loadStudents } from "./storage.js";
import { updateCounter } from "./counter.js";

const searchInput = document.querySelector("#search");
const nameInput = document.querySelector("#name");
const emailInput = document.querySelector("#email");
const classInput = document.querySelector("#studentClass");
const paymentInput = document.querySelector("#paymentStatus");
const addBtn = document.querySelector("#addBtn");
const studentsContainer = document.querySelector("#students");
const sortBtn = document.querySelector("#sortBtn");
const darkBtn = document.querySelector("#darkModeBtn");



let editingStudentId = null;
const students = loadStudents();
renderStudents(studentsContainer, students, onDelete, onEdit);
updateCounter(students);
function onDelete(studentId) {
  const confirmed = window.confirm("are y sure y want to delet");
  if (!confirmed) {
    return;
  }
  const updatedStudents = students.filter((student) => {
    return student.id !== studentId;
  });
  students.length = 0;
  students.push(...updatedStudents);
  saveStudents(students);
  renderStudents(studentsContainer, students, onDelete, onEdit);
  updateCounter(students);

}


function onEdit(student) {
  nameInput.value = student.name;
  emailInput.value = student.email;
  classInput.value = student.studentClass;
  paymentInput.value = student.paymentStatus;
  editingStudentId = student.id;
  addBtn.textContent = "Update Student";
}


darkBtn.addEventListener(("click"), () => {
  document.body.classList.toggle("dark");
})
sortBtn.addEventListener("click", () => {
  students.sort((a, b) => {
    if (a.name < b.name) return -1;
    if (a.name > b.name) return 1;
    return 0;
  });

  saveStudents(students);  // ← missing this!
  renderStudents(studentsContainer, students, onDelete, onEdit);
});

searchInput.addEventListener("input", () => {

  const searchValue = searchInput.value.toLowerCase();

  const filteredStudents = students.filter((student) => {
    return student.name.toLowerCase().includes(searchValue) ||
      student.email.toLowerCase().includes(searchValue) ||
      student.studentClass.toLowerCase().includes(searchValue) ||
      student.paymentStatus.toLowerCase().includes(searchValue)
  });

  renderStudents(studentsContainer, filteredStudents, onDelete, onEdit);
});


addBtn.addEventListener("click", () => {
  if (!validation(nameInput.value, emailInput.value, classInput.value)) {
    return;
  }

  if (editingStudentId === null) {
    const student = new Student(
      Date.now(),
      nameInput.value,
      emailInput.value,
      classInput.value,
      paymentInput.value
    );
    students.push(student);
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
  }

  saveStudents(students);
  renderStudents(studentsContainer, students, onDelete, onEdit);
  updateCounter(students);
  nameInput.value = "";
  emailInput.value = "";
  classInput.value = "";
  paymentInput.value = "Paid";
});
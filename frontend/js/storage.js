import { Teacher } from "./teacher.js";

// Default teachers
const defaultTeachers = [
  new Teacher(
    1,
    "Mr. Ahmed",
    "ahmed@school.com",
    "teacher1",
    "teacher123",
    "Mathematics",
    "Permanent",
    5
  ),
  new Teacher(
    2,
    "Mrs. Fatima",
    "fatima@school.com",
    "teacher2",
    "teacher123",
    "Science",
    "Contract",
    3
  ),
];
// SAVE STUDENTS
export function saveStudents(students) {
  localStorage.setItem("students", JSON.stringify(students));
}

// LOAD STUDENTS
export function loadStudents() {
  return JSON.parse(localStorage.getItem("students")) || [];
}

// SAVE TEACHERS
export function saveTeachers(teachers) {
  localStorage.setItem("teachers", JSON.stringify(teachers));
}

// LOAD TEACHERS
export function loadTeachers() {
  const stored = localStorage.getItem("teachers");

  if (!stored) {
    // First time: create defaults
    saveTeachers(defaultTeachers);
    return defaultTeachers;
  }

  return JSON.parse(stored);
}
// ───────────────────────────────────────
// SAVE EXAMS
// ───────────────────────────────────────
export function saveExams(exams) {
  localStorage.setItem("exams", JSON.stringify(exams));
}

// ───────────────────────────────────────
// LOAD EXAMS
// ───────────────────────────────────────
export function loadExams() {
  const stored = localStorage.getItem("exams");

  if (!stored) {
    // First time: no exams yet
    return [];
  }

  return JSON.parse(stored);
}
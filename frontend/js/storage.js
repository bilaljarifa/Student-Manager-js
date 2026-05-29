import { Teacher } from "./teacher.js";

// Default teachers
const defaultTeachers = [
  new Teacher(1, "Mr. Ahmed", "teacher1", "teacher123"),
  new Teacher(2, "Mrs. Fatima", "teacher2", "teacher123"),
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
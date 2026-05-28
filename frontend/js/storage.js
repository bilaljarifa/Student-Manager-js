import { Teacher } from "./teacher.js";

const defaultTeachers = [
  new Teacher(1, "Mr. Ahmed", "teacher1", "teacher123"),
];

export function saveStudents(students) {
  localStorage.setItem("students", JSON.stringify(students));
}

export function loadStudents() {
  return JSON.parse(localStorage.getItem("students")) || [];
}
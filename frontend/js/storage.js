import { Teacher } from "./teacher.js";
import { Class } from "./class.js";
import { Course } from "./course.js";
import { Attendance } from "./attendance.js";  // ← ADD THIS

// ═══════════════════════════════════════════════════════════════════
// DEFAULT DATA — Initial data when app first loads
// ═══════════════════════════════════════════════════════════════════

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
    5,
    [1, 2]
  ),
  new Teacher(
    2,
    "Mrs. Fatima",
    "fatima@school.com",
    "teacher2",
    "teacher123",
    "Science",
    "Contract",
    3,
    [3]
  ),
];

// Default classes
const defaultClasses = [
  new Class(1, "Class 7", "Science", 1),
  new Class(2, "Class 4", "Mathematics", 1),
  new Class(3, "Class 5", "English", 2),
];

// ═══════════════════════════════════════════════════════════════════
// STUDENTS — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveStudents(students) {
  localStorage.setItem("students", JSON.stringify(students));
}

export function loadStudents() {
  return JSON.parse(localStorage.getItem("students")) || [];
}

// ═══════════════════════════════════════════════════════════════════
// TEACHERS — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveTeachers(teachers) {
  localStorage.setItem("teachers", JSON.stringify(teachers));
}

export function loadTeachers() {
  const stored = localStorage.getItem("teachers");

  if (!stored) {
    saveTeachers(defaultTeachers);
    return defaultTeachers;
  }

  return JSON.parse(stored);
}

// ═══════════════════════════════════════════════════════════════════
// CLASSES — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveClasses(classes) {
  localStorage.setItem("classes", JSON.stringify(classes));
}

export function loadClasses() {
  const stored = localStorage.getItem("classes");

  if (!stored) {
    saveClasses(defaultClasses);
    return defaultClasses;
  }

  const parsed = JSON.parse(stored);
  return parsed.map(cls => {
    const classInstance = new Class(cls.id, cls.name, cls.section, cls.teacher, cls.students);
    classInstance.createdAt = cls.createdAt || new Date().toLocaleDateString();
    return classInstance;
  });
}

// ═══════════════════════════════════════════════════════════════════
// EXAMS — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveExams(exams) {
  localStorage.setItem("exams", JSON.stringify(exams));
}

export function loadExams() {
  const stored = localStorage.getItem("exams");
  if (!stored) return [];
  return JSON.parse(stored);
}

// ═══════════════════════════════════════════════════════════════════
// ATTENDANCE — Save and Load (FIXED!)
// ═══════════════════════════════════════════════════════════════════

export function saveAttendance(attendance) {
  localStorage.setItem("attendance", JSON.stringify(attendance));
}

export function loadAttendance() {
  try {
    const stored = localStorage.getItem("attendance");

    if (!stored) {
      console.log("✅ No attendance data yet");
      return [];
    }

    const data = JSON.parse(stored);
    console.log("📊 Raw attendance data:", data);

    // Reconstruct as Attendance objects with methods
    const reconstructed = data.map(record => {
      const att = new Attendance(
        record.id,
        record.studentId,
        record.classId || 1,
        record.date,
        record.status,
        record.subject || "General"
      );
      return att;
    });

    console.log("✅ Reconstructed attendance:", reconstructed);
    return reconstructed;
  } catch (error) {
    console.error("❌ Error loading attendance:", error);
    return [];
  }
}

// ═══════════════════════════════════════════════════════════════════
// COURSES — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveCourses(courses) {
  localStorage.setItem("courses", JSON.stringify(courses));
}

export function loadCourses() {
  const stored = localStorage.getItem("courses");
  if (!stored) return [];

  const parsed = JSON.parse(stored);
  return parsed.map(c => {
    const course = new Course(
      c.id,
      c.name,
      c.code,
      c.description,
      c.teacher,
      c.credits,
      c.semester,
      c.classId
    );
    course.students = c.students || [];
    return course;
  });
}

// ═══════════════════════════════════════════════════════════════════
// GRADES — Save and Load
// ═══════════════════════════════════════════════════════════════════

export function saveGrades(grades) {
  localStorage.setItem("grades", JSON.stringify(grades));
}

export function loadGrades() {
  return JSON.parse(localStorage.getItem("grades")) || [];
}
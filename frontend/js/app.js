// ═══════════════════════════════════════════════════════════════════
// IMPORTS — bring in functions/classes from other files
// ═══════════════════════════════════════════════════════════════════
import { renderEnrolledStudents } from "./enrollment-ui.js";
import { isAdmin, filterStudentsByRole, canDeleteStudent, canEditStudent, canAddGrades } from "./role-management.js";
import { Student } from "./student.js";
import { validation } from "./validation.js";
import { renderStudents } from "./ui.js";
import { saveStudents, loadStudents, saveTeachers, loadTeachers, saveExams, loadExams, saveAttendance, loadAttendance, saveCourses, loadCourses } from "./storage.js";
import { updateCounter } from "./counter.js";
import { updateChart } from "./chart.js";
import { showToast } from "./toast.js";
import { getAverage, getStatus } from "./grades.js";
import { renderGradesPage, updateGradesTable, updateGradesStats } from "./grades-page.js";
import { displayWelcome } from "./welcome.js";
import { updateDashboardStats } from "./dashboardStats.js";
import { Teacher } from "./teacher.js";
import { renderTeachers } from "./teacher-ui.js";
import { Exam } from "./exam.js";
import { renderExams } from "./exam-ui.js";
import { Attendance } from "./attendance.js";
import { renderAttendance, getAttendancePercentage } from "./attendance-ui.js";
import { Course } from "./course.js";
import { renderCourses, getCoursesByTeacher, getCoursesByStudent } from "./course-ui.js";
import { ReportGenerator } from "./reports.js";
import { renderReportsSummary } from "./reports-ui.js";
import { getGradeDistribution, getStudentPerformanceStats } from "./analytics.js";

// ═══════════════════════════════════════════════════════════════════
// AUTH CHECK — redirect to login if not logged in
// ═══════════════════════════════════════════════════════════════════
if (!localStorage.getItem("isLoggedIn")) {
  window.location.href = "login.html";
}

// ═══════════════════════════════════════════════════════════════════
// DOM SELECTORS — grab all elements from the HTML
// ═══════════════════════════════════════════════════════════════════

// ── Students Form ──
const nameInput = document.querySelector("#name");
const emailInput = document.querySelector("#email");
const classInput = document.querySelector("#studentClass");
const paymentInput = document.querySelector("#paymentStatus");
const addBtn = document.querySelector("#addBtn");
const studentsContainer = document.querySelector("#students");

// ── Students Toolbar ──
const searchInput = document.querySelector("#search");
const sortBtn = document.querySelector("#sortBtn");
const sortPayment = document.querySelector("#sortPayment");
const classFilter = document.querySelector("#classFilter");
const exportBtn = document.querySelector("#exportBtn");

// ── Pagination ──
const prevBtn = document.querySelector("#prevBtn");
const nextBtn = document.querySelector("#nextBtn");
const pageInfo = document.querySelector("#pageInfo");

// ── Sidebar / Global ──
const darkBtn = document.querySelector("#darkModeBtn");
const logoutBtn = document.querySelector("#logoutBtn");

// ── Grades Page ──
const classFilterGrades = document.querySelector("#classFilterGrades");
const filterStudent = document.querySelector("#studentFilterGrades");
const gradesTableBody = document.querySelector("#gradesTableBody");
const avgGrade = document.querySelector("#avgGrade");
const highestGrade = document.querySelector("#highestGrade");
const lowestGrade = document.querySelector("#lowestGrade");
const gradeSubject = document.querySelector("#gradeSubject");
const gradeScore = document.querySelector("#gradeScore");
const addGradeBtn = document.querySelector("#addGradeBtn");

// ── Teachers Form ──
const teacherName = document.querySelector("#teacherName");
const teacherEmail = document.querySelector("#teacherEmail");
const teacherUsername = document.querySelector("#teacherUsername");
const teacherPassword = document.querySelector("#teacherPassword");
const teacherDepartment = document.querySelector("#teacherDepartment");
const teacherContractStatus = document.querySelector("#teacherContractStatus");
const teacherCourseWorkload = document.querySelector("#teacherCourseWorkload");
const addTeacherBtn = document.querySelector("#addTeacherBtn");
const teachersContainer = document.querySelector("#teachers");

// ── Exams Page ──
const examName = document.querySelector("#examName");
const examSubject = document.querySelector("#examSubject");
const examDate = document.querySelector("#examDate");
const examTotalMarks = document.querySelector("#examTotalMarks");
const addExamBtn = document.querySelector("#addExamBtn");
const examsContainer = document.querySelector("#exams");

// ── Attendance Page ──
const attendanceStudent = document.querySelector("#attendanceStudent");
const attendanceDate = document.querySelector("#attendanceDate");
const attendanceSubject = document.querySelector("#attendanceSubject");
const attendanceStatus = document.querySelector("#attendanceStatus");
const addAttendanceBtn = document.querySelector("#addAttendanceBtn");
const attendanceContainer = document.querySelector("#attendance");
const overallAttendance = document.querySelector("#overallAttendance");

// ── Courses Page ──
const courseName = document.querySelector("#courseName");
const courseCode = document.querySelector("#courseCode");
const courseDescription = document.querySelector("#courseDescription");
const courseCredits = document.querySelector("#courseCredits");
const courseSemester = document.querySelector("#courseSemester");
const courseTeacher = document.querySelector("#courseTeacher");
const addCourseBtn = document.querySelector("#addCourseBtn");
const coursesContainer = document.querySelector("#courses");

// ── Reports Page ──
const reportsContainer = document.querySelector("#reportsContainer");
const exportReportBtn = document.querySelector("#exportReportBtn");

// ── Analytics Page ──
const analyticsContainer = document.querySelector("#analytics");

// ── Enrollment Modal ──
const enrollmentModalContainer = document.querySelector("#enrollmentModalContainer");

// ═══════════════════════════════════════════════════════════════════
// STATE — data loaded from localStorage + page state
// ═══════════════════════════════════════════════════════════════════
const students = loadStudents();
const teachers = loadTeachers();
const exams = loadExams();
const attendance = loadAttendance();
const courses = loadCourses();

let currentPage = 1;
const studentsPerPage = 10;
let editingStudentId = null;
let editingTeacherId = null;
let editingExamId = null;
let editingAttendanceId = null;
let editingCourseId = null;

const reportGenerator = new ReportGenerator(students, teachers, courses, exams, attendance);

// ═══════════════════════════════════════════════════════════════════
// INITIAL RENDER — run everything once when page loads
// ═══════════════════════════════════════════════════════════════════
displayWelcome();
renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher);
renderGradesPage(students, filterStudent);
renderExams(examsContainer, exams, onDeleteExam, onEditExam);
renderCourses(coursesContainer, courses, teachers, onDeleteCourse, onEditCourse);

updateDashboardStats(students);
updateCounter(students);
updateChart(students);
updateClassFilter();
updateClassFilterGrades();
updatePagination();
updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
updateAttendanceStudentDropdown();
updateCourseTeacherDropdown();

generateAndRenderReport();
updateAnalytics(students);  // ← runs AFTER report is ready

// ═══════════════════════════════════════════════════════════════════
// STUDENTS — CRUD functions
// ═══════════════════════════════════════════════════════════════════

// ── Edit student — opens the edit modal (NOT the add form) ──
function onEdit(student) {
  if (!canEditStudent()) {
    showToast("Teachers cannot edit students!", "error");
    return;
  }

  document.querySelector("#editName").value = student.name;
  document.querySelector("#editEmail").value = student.email;
  document.querySelector("#editClass").value = student.studentClass;
  document.querySelector("#editPayment").value = student.paymentStatus;

  document.querySelector("#editModalOverlay").classList.add("active");

  document.querySelector("#editSaveBtn").onclick = () => {
    student.name = document.querySelector("#editName").value.trim();
    student.email = document.querySelector("#editEmail").value.trim();
    student.studentClass = document.querySelector("#editClass").value.trim();
    student.paymentStatus = document.querySelector("#editPayment").value;

    saveStudents(students);
    renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
    updateCounter(students);
    updateChart(students);
    updateClassFilter();
    updateDashboardStats(students);

    document.querySelector("#editModalOverlay").classList.remove("active");
    showToast("Student updated!", "info");
  };

  document.querySelector("#editCancelBtn").onclick = () => {
    document.querySelector("#editModalOverlay").classList.remove("active");
  };
}

// ── Delete student — confirms then removes ──
async function onDelete(studentId) {
  if (!canDeleteStudent()) {
    showToast("Teachers cannot delete students!", "error");
    return;
  }

  const confirmed = await showConfirm("Delete Student", "Are you sure you want to delete this student?");
  if (!confirmed) return;

  const cardElement = document.querySelector(`[data-id="${studentId}"]`);
  if (cardElement) cardElement.classList.add("fadeOut");

  setTimeout(() => {
    const updatedStudents = students.filter(s => s.id !== studentId);
    students.length = 0;
    students.push(...updatedStudents);

    saveStudents(students);
    renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
    updateCounter(students);
    updateChart(students);
    updateClassFilter();
    updatePagination();
    updateDashboardStats(students);
    renderGradesPage(students, filterStudent);
    updateClassFilterGrades();
    showToast("Student deleted!", "error");
  }, 400);
}

// ── Row click — opens student detail modal ──
function onRowClick(student) {
  const scores = student.grades.map(g => typeof g === "object" ? g.score : g);
  const average = getAverage(scores);
  const status = getStatus(average);

  const gradesList = student.grades.map(g =>
    typeof g === "object" ? `${g.subject}: ${g.score}` : g
  ).join(", ");

  document.querySelector("#modalName").textContent = student.name;
  document.querySelector("#modalEmail").textContent = student.email;
  document.querySelector("#modalClass").textContent = student.studentClass;
  document.querySelector("#modalPayment").textContent = student.paymentStatus;
  document.querySelector("#modalAverage").textContent = `Average: ${average}`;
  document.querySelector("#modalStatus").textContent = `Status: ${status}`;
  document.querySelector("#modalGrades").textContent = `Grades: ${gradesList}`;

  document.querySelector("#modalOverlay").classList.add("active");
}

// ── Add grade from student row ──
function onAddGrade(studentId, grade) {
  const student = students.find(s => s.id === studentId);
  student.grades.push({ subject: "General", score: grade });

  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateDashboardStats(students);
  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateAnalytics(students);
}

// ── Clear all students ──
async function clearAll() {
  const confirmed = await showConfirm("Delete All", "Are you sure you want to delete all students?");
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

// ── Export students to CSV ──
function exportCSV() {
  const header = "Name,Email,Class,Payment";
  const rows = students.map(s => `${s.name},${s.email},${s.studentClass},${s.paymentStatus}`);
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

// ═══════════════════════════════════════════════════════════════════
// STUDENTS — Helper functions (filter, pagination, class filter)
// ═══════════════════════════════════════════════════════════════════

// Update class filter dropdown with unique classes (for Grades page)
function updateClassFilterGrades() {
  const uniqueClasses = [...new Set(students.map(s => s.studentClass))];
  classFilterGrades.innerHTML = '<option value="">All Classes</option>';
  uniqueClasses.forEach(className => {
    const option = document.createElement("option");
    option.value = className;
    option.textContent = className;
    classFilterGrades.appendChild(option);
  });
}

// Update student dropdown when class changes
function updateStudentFilterByClass() {
  const selectedClass = classFilterGrades.value;
  const filteredStudents = selectedClass
    ? students.filter(s => s.studentClass === selectedClass)
    : students;

  filterStudent.innerHTML = '<option value="">All Students</option>';
  filteredStudents.forEach(student => {
    const option = document.createElement("option");
    option.value = student.id;
    option.textContent = student.name;
    filterStudent.appendChild(option);
  });
}

// Returns only students for the current page
function getPageStudents() {
  const start = (currentPage - 1) * studentsPerPage;
  const end = start + studentsPerPage;
  return students.slice(start, end);
}

// Returns students matching search + class filter
function getFilteredStudents() {
  const searchValue = searchInput.value.toLowerCase();
  const classValue = classFilter.value;

  return students.filter(student => {
    const matchesSearch =
      student.name.toLowerCase().includes(searchValue) ||
      student.email.toLowerCase().includes(searchValue) ||
      student.studentClass.toLowerCase().includes(searchValue) ||
      student.paymentStatus.toLowerCase().includes(searchValue);

    const matchesClass = classValue === "" || student.studentClass === classValue;

    return matchesSearch && matchesClass;
  });
}

// Updates class filter dropdown with unique classes
function updateClassFilter() {
  const uniqueClasses = [...new Set(students.map(s => s.studentClass))];
  classFilter.innerHTML = '<option value="">All Classes</option>';
  uniqueClasses.forEach(className => {
    const option = document.createElement("option");
    option.value = className;
    option.textContent = className;
    classFilter.appendChild(option);
  });
}

// Updates Previous/Next buttons and page info text
function updatePagination() {
  const totalPages = Math.ceil(students.length / studentsPerPage);
  pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
  prevBtn.disabled = currentPage === 1;
  nextBtn.disabled = currentPage === totalPages;
}

// ═══════════════════════════════════════════════════════════════════
// TEACHERS — CRUD functions
// ═══════════════════════════════════════════════════════════════════

function onEditTeacher(teacher) {
  if (!isAdmin()) {
    showToast("Only admins can edit teachers!", "error");
    return;
  }

  teacherName.value = teacher.name;
  teacherEmail.value = teacher.email;
  teacherUsername.value = teacher.username;
  teacherPassword.value = teacher.password;
  teacherDepartment.value = teacher.department || "";
  teacherContractStatus.value = teacher.contractStatus || "Permanent";
  teacherCourseWorkload.value = teacher.courseWorkload || 0;

  editingTeacherId = teacher.id;
  addTeacherBtn.textContent = "Update Teacher";
  showToast("Editing teacher...", "info");
}

async function onDeleteTeacher(teacherId) {
  if (!isAdmin()) {
    showToast("Only admins can delete teachers!", "error");
    return;
  }

  const confirmed = await showConfirm("Delete Teacher", "Are you sure you want to delete this teacher?");
  if (!confirmed) return;

  const updatedTeachers = teachers.filter(t => t.id !== teacherId);
  teachers.length = 0;
  teachers.push(...updatedTeachers);
  saveTeachers(teachers);
  renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher);
  showToast("Teacher deleted!", "error");
}

// ═══════════════════════════════════════════════════════════════════
// EXAMS — CRUD functions
// ═══════════════════════════════════════════════════════════════════

function onEditExam(exam) {
  examName.value = exam.name;
  examSubject.value = exam.subject;
  examDate.value = exam.date;
  examTotalMarks.value = exam.totalMarks;

  editingExamId = exam.id;
  addExamBtn.textContent = "Update Exam";
  showToast("Editing exam...", "info");
}

async function onDeleteExam(examId) {
  const confirmed = await showConfirm("Delete Exam", "Are you sure you want to delete this exam?");
  if (!confirmed) return;

  const updatedExams = exams.filter(e => e.id !== examId);
  exams.length = 0;
  exams.push(...updatedExams);
  saveExams(exams);
  renderExams(examsContainer, exams, onDeleteExam, onEditExam);
  showToast("Exam deleted!", "error");
}

// ═══════════════════════════════════════════════════════════════════
// ATTENDANCE — functions
// ═══════════════════════════════════════════════════════════════════

function updateAttendanceStudentDropdown() {
  attendanceStudent.innerHTML = '<option value="">Select Student</option>';
  students.forEach(student => {
    const option = document.createElement("option");
    option.value = student.id;
    option.textContent = student.name;
    attendanceStudent.appendChild(option);
  });
}

function renderAttendanceRecords(studentId) {
  const studentAttendance = attendance.filter(a => a.studentId === parseInt(studentId));
  const student = students.find(s => s.id === parseInt(studentId));
  renderAttendance(attendanceContainer, studentAttendance, student.name, onDeleteAttendance, onEditAttendance);
  updateAttendanceStats(studentId);
}

function updateAttendanceStats(studentId) {
  const percentage = getAttendancePercentage(attendance, parseInt(studentId));
  overallAttendance.textContent = percentage + "%";
}

function onEditAttendance(record) {
  attendanceDate.value = record.date;
  attendanceSubject.value = record.subject;
  attendanceStatus.value = record.status;

  editingAttendanceId = record.id;
  addAttendanceBtn.textContent = "Update Attendance";
  showToast("Editing attendance...", "info");
}

async function onDeleteAttendance(attendanceId) {
  const confirmed = await showConfirm("Delete Attendance", "Are you sure you want to delete this record?");
  if (!confirmed) return;

  const updatedAttendance = attendance.filter(a => a.id !== attendanceId);
  attendance.length = 0;
  attendance.push(...updatedAttendance);
  saveAttendance(attendance);
  renderAttendanceRecords(attendanceStudent.value);
  showToast("Attendance deleted!", "error");
}

// ═══════════════════════════════════════════════════════════════════
// COURSES — functions
// ═══════════════════════════════════════════════════════════════════

function updateCourseTeacherDropdown() {
  courseTeacher.innerHTML = '<option value="">Select Teacher</option>';
  teachers.forEach(teacher => {
    const option = document.createElement("option");
    option.value = teacher.id;
    option.textContent = teacher.name;
    courseTeacher.appendChild(option);
  });
}

function onEditCourse(course) {
  courseName.value = course.name;
  courseCode.value = course.code;
  courseDescription.value = course.description;
  courseCredits.value = course.credits;
  courseSemester.value = course.semester;
  courseTeacher.value = course.teacher;

  editingCourseId = course.id;
  addCourseBtn.textContent = "Update Course";
  showToast("Editing course...", "info");
}

async function onDeleteCourse(courseId) {
  const confirmed = await showConfirm("Delete Course", "Are you sure you want to delete this course?");
  if (!confirmed) return;

  const updatedCourses = courses.filter(c => c.id !== courseId);
  courses.length = 0;
  courses.push(...updatedCourses);
  saveCourses(courses);
  renderCourses(coursesContainer, courses, teachers, onDeleteCourse, onEditCourse);
  showToast("Course deleted!", "error");
}

// ═══════════════════════════════════════════════════════════════════
// ENROLLMENT — modal for enrolling students in courses
// ═══════════════════════════════════════════════════════════════════
function openEnrollmentModal(course) {
  const modalHTML = `
    <div id="enrollmentModal" class="modal-overlay">
      <div class="modal-content">
        <h2>Enroll Students — ${course.name}</h2>
        <div class="modal-body">
          <div class="left-panel">
            <h3>Available Students</h3>
            <input type="text" id="searchEnrollStudent" placeholder="Search students...">
            <div id="availableStudents"></div>
          </div>
          <div class="right-panel">
            <h3>Enrolled Students</h3>
            <div id="enrolledStudents"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button id="closeEnrollmentModal">Close</button>
        </div>
      </div>
    </div>
  `;

  enrollmentModalContainer.innerHTML = modalHTML;

  const availableDiv = document.querySelector("#availableStudents");
  const enrolledDiv = document.querySelector("#enrolledStudents");
  const searchField = document.querySelector("#searchEnrollStudent");
  const closeBtn = document.querySelector("#closeEnrollmentModal");

  import("./enrollment-ui.js").then(module => {
    module.renderEnrolledStudents(enrolledDiv, course, students, studentId => {
      onRemoveStudentFromCourse(course, studentId);
    });
  });

  function renderAvailable(filter = "") {
    availableDiv.innerHTML = "";
    const available = students.filter(s =>
      !course.students.includes(s.id) &&
      s.name.toLowerCase().includes(filter.toLowerCase())
    );

    if (available.length === 0) {
      availableDiv.innerHTML = "<p style='text-align:center;color:#999;'>No more students</p>";
      return;
    }

    available.forEach(student => {
      const div = document.createElement("div");
      div.className = "available-student-item";
      div.innerHTML = `<span>${student.name}</span><button class="enrollBtn" data-student-id="${student.id}">+ Enroll</button>`;
      div.querySelector(".enrollBtn").addEventListener("click", () => onEnrollStudent(course, student.id));
      availableDiv.appendChild(div);
    });
  }

  renderAvailable();
  searchField.addEventListener("input", () => renderAvailable(searchField.value));
  closeBtn.addEventListener("click", () => document.querySelector("#enrollmentModal").remove());
  document.querySelector("#enrollmentModal").classList.add("active");
}

function onEnrollStudent(course, studentId) {
  if (!course.addStudent(studentId)) return;

  saveCourses(courses);

  const enrolledDiv = document.querySelector("#enrolledStudents");
  const searchField = document.querySelector("#searchEnrollStudent");
  renderEnrolledStudents(enrolledDiv, course, students, id => onRemoveStudentFromCourse(course, id));

  const availableDiv = document.querySelector("#availableStudents");
  availableDiv.innerHTML = "";
  students.filter(s => !course.students.includes(s.id) && s.name.toLowerCase().includes(searchField.value.toLowerCase()))
    .forEach(student => {
      const div = document.createElement("div");
      div.className = "available-student-item";
      div.innerHTML = `<span>${student.name}</span><button class="enrollBtn">+ Enroll</button>`;
      div.querySelector(".enrollBtn").addEventListener("click", () => onEnrollStudent(course, student.id));
      availableDiv.appendChild(div);
    });

  showToast(`${students.find(s => s.id === studentId).name} enrolled!`, "success");
}

async function onRemoveStudentFromCourse(course, studentId) {
  const confirmed = await showConfirm("Remove Student", "Remove this student from the course?");
  if (!confirmed) return;

  course.removeStudent(studentId);
  saveCourses(courses);

  const enrolledDiv = document.querySelector("#enrolledStudents");
  const searchField = document.querySelector("#searchEnrollStudent");
  const availableDiv = document.querySelector("#availableStudents");

  renderEnrolledStudents(enrolledDiv, course, students, id => onRemoveStudentFromCourse(course, id));

  availableDiv.innerHTML = "";
  students.filter(s => !course.students.includes(s.id) && s.name.toLowerCase().includes(searchField.value.toLowerCase()))
    .forEach(student => {
      const div = document.createElement("div");
      div.className = "available-student-item";
      div.innerHTML = `<span>${student.name}</span><button class="enrollBtn">+ Enroll</button>`;
      div.querySelector(".enrollBtn").addEventListener("click", () => onEnrollStudent(course, student.id));
      availableDiv.appendChild(div);
    });

  showToast("Student removed!", "error");
}

// ═══════════════════════════════════════════════════════════════════
// REPORTS — generate and export
// ═══════════════════════════════════════════════════════════════════
function generateAndRenderReport() {
  const summary = reportGenerator.generateDashboardSummary();
  renderReportsSummary(reportsContainer, summary);
}

function exportReportToExcel() {
  const summary = reportGenerator.generateDashboardSummary();

  const sheets = {
    "Overview": [
      ["SYSTEM OVERVIEW"],
      ["Metric", "Value"],
      ["Total Students", summary.students],
      ["Total Teachers", summary.teachers],
      ["Total Courses", summary.courses],
      ["Total Exams", summary.exams],
      ["Overall Attendance %", summary.overallAttendance],
      [],
      ["PAYMENT STATUS"],
      ["Status", "Count"],
      ["Paid", summary.paymentStats.paid],
      ["Pending", summary.paymentStats.pending],
    ],
    "By Class": [
      ["Class", "Count"],
      ...Object.entries(summary.studentsByClass).map(([cls, count]) => [cls, count])
    ],
    "By Department": [
      ["Department", "Count"],
      ...Object.entries(summary.teachersByDept).map(([dept, count]) => [dept, count])
    ],
    "Courses": [
      ["Course", "Enrolled"],
      ...Object.entries(summary.studentsPerCourse).map(([code, count]) => [code, count])
    ]
  };

  const wb = XLSX.utils.book_new();
  Object.keys(sheets).forEach(sheetName => {
    const ws = XLSX.utils.aoa_to_sheet(sheets[sheetName]);
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
  });

  XLSX.writeFile(wb, `School-Report-${Date.now()}.xlsx`);
  showToast("Report exported as Excel!", "success");
}

// ═══════════════════════════════════════════════════════════════════
// ANALYTICS — chart + stats
// ═══════════════════════════════════════════════════════════════════
function updateAnalytics(students) {
  const distribution = getGradeDistribution(students);
  const stats = getStudentPerformanceStats(students);

  document.querySelector("#totalStudentsStats").textContent = stats.totalStudents;
  document.querySelector("#avgGradeStats").textContent = stats.averageGrade;
  document.querySelector("#totalGradesStats").textContent = stats.totalGrades;

  const ctx = document.querySelector("#gradeDistributionChart");
  if (window.gradeChart) window.gradeChart.destroy();

  window.gradeChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["A (90-100)", "B (80-89)", "C (70-79)", "D (60-69)", "F (0-59)"],
      datasets: [{
        label: "Number of Students",
        data: [distribution.A, distribution.B, distribution.C, distribution.D, distribution.F],
        backgroundColor: ["#10B981", "#3B82F6", "#F59E0B", "#EF4444", "#6B7280"],
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });
}

// ═══════════════════════════════════════════════════════════════════
// CONFIRMATION MODAL — replaces browser window.confirm()
// ═══════════════════════════════════════════════════════════════════
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

// ═══════════════════════════════════════════════════════════════════
// BULK DELETE — select multiple students and delete them
// ═══════════════════════════════════════════════════════════════════

function getSelectedStudentIds() {
  const checkboxes = document.querySelectorAll('#students input[type="checkbox"]:checked');
  return Array.from(checkboxes).map(cb => parseInt(cb.dataset.studentId));
}

function updateDeleteSelectedButton() {
  const selectedIds = getSelectedStudentIds();
  const deleteBtn = document.querySelector("#deleteSelectedBtn");
  if (!deleteBtn) return;

  if (selectedIds.length > 0) {
    deleteBtn.style.display = "block";
    deleteBtn.textContent = `🗑 Delete ${selectedIds.length} Selected`;
  } else {
    deleteBtn.style.display = "none";
  }
}

const selectAllCheckbox = document.querySelector("#selectAllStudents");
if (selectAllCheckbox) {
  selectAllCheckbox.addEventListener("change", () => {
    const checkboxes = document.querySelectorAll('#students input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
    updateDeleteSelectedButton();
  });
}

const deleteSelectedBtn = document.querySelector("#deleteSelectedBtn");
if (deleteSelectedBtn) {
  deleteSelectedBtn.addEventListener("click", async () => {
    const selectedIds = getSelectedStudentIds();
    if (selectedIds.length === 0) { showToast("No students selected!", "error"); return; }

    const confirmed = await showConfirm("Delete Selected", `Delete ${selectedIds.length} selected students?`);
    if (!confirmed) return;

    document.querySelectorAll('#students input[type="checkbox"]:checked')
      .forEach(cb => cb.closest("tr")?.classList.add("fadeOut"));

    setTimeout(() => {
      const updated = students.filter(s => !selectedIds.includes(s.id));
      students.length = 0;
      students.push(...updated);

      saveStudents(students);
      renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);

      const totalPages = Math.ceil(students.length / studentsPerPage);
      if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;

      updateCounter(students);
      updateChart(students);
      updateClassFilter();
      updatePagination();
      updateDashboardStats(students);

      if (selectAllCheckbox) selectAllCheckbox.checked = false;
      updateDeleteSelectedButton();

      showToast(`${selectedIds.length} students deleted!`, "success");
    }, 400);
  });
}

// ═══════════════════════════════════════════════════════════════════
// DELETE BY CLASS — modal to delete all students in a class
// ═══════════════════════════════════════════════════════════════════

const deleteByClassBtn = document.querySelector("#deleteByClassBtn");
if (deleteByClassBtn) {
  deleteByClassBtn.addEventListener("click", () => {
    const uniqueClasses = [...new Set(students.map(s => s.studentClass))];
    if (uniqueClasses.length === 0) { showToast("No students to delete!", "error"); return; }

    let classOptions = '<option value="">-- Choose a class --</option>';
    uniqueClasses.forEach(cls => { classOptions += `<option value="${cls}">${cls}</option>`; });

    const modal = document.createElement("div");
    modal.className = "confirm-overlay active";
    modal.innerHTML = `
      <div class="confirm-modal" style="text-align:left; width:380px;">
        <h3 style="margin-bottom:16px;">🗑 Delete All Students in Class</h3>
        <label style="display:block; margin-bottom:8px; font-weight:600;">Select Class:</label>
        <select id="deleteClassSelect" style="width:100%; padding:10px; border:1px solid #E5E7EB; border-radius:8px; margin-bottom:20px;">
          ${classOptions}
        </select>
        <div class="confirm-buttons">
          <button id="cancelDeleteClass"  class="confirm-cancel">Cancel</button>
          <button id="confirmDeleteClass" class="confirm-ok">🗑 Delete All</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    modal.querySelector("#cancelDeleteClass").addEventListener("click", () => {
      modal.remove();
    });

    modal.querySelector("#confirmDeleteClass").addEventListener("click", () => {
      const selectedClass = modal.querySelector("#deleteClassSelect").value;

      if (!selectedClass) { showToast("Please select a class!", "error"); return; }

      const deleteCount = students.filter(s => s.studentClass === selectedClass).length;

      if (deleteCount === 0) { showToast("No students in this class!", "error"); return; }

      modal.remove();

      const confirmModal = document.createElement("div");
      confirmModal.className = "confirm-overlay active";
      confirmModal.innerHTML = `
        <div class="confirm-modal">
          <h3>Delete Class ${selectedClass}?</h3>
          <p>This will delete <strong>${deleteCount}</strong> students permanently.</p>
          <div class="confirm-buttons">
            <button id="cancelFinal"  class="confirm-cancel">Cancel</button>
            <button id="confirmFinal" class="confirm-ok">🗑 Yes, Delete</button>
          </div>
        </div>
      `;
      document.body.appendChild(confirmModal);

      confirmModal.querySelector("#cancelFinal").addEventListener("click", () => {
        confirmModal.remove();
      });

      confirmModal.querySelector("#confirmFinal").addEventListener("click", () => {
        document.querySelectorAll(`tr[data-class="${selectedClass}"]`)
          .forEach(row => row.classList.add("fadeOut"));

        setTimeout(() => {
          const updated = students.filter(s => s.studentClass !== selectedClass);
          students.length = 0;
          students.push(...updated);

          saveStudents(students);
          renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);

          currentPage = 1;
          updateCounter(students);
          updateChart(students);
          updateClassFilter();
          updatePagination();
          updateDashboardStats(students);

          confirmModal.remove();
          showToast(`${deleteCount} students in class ${selectedClass} deleted!`, "success");
        }, 400);
      });
    });

    modal.addEventListener("click", e => { if (e.target === modal) modal.remove(); });
  });
}

// ═══════════════════════════════════════════════════════════════════
// EVENT LISTENERS — buttons, inputs, navigation
// ═══════════════════════════════════════════════════════════════════

// ── Add Student ──
addBtn.addEventListener("click", () => {
  if (!validation(nameInput, emailInput, classInput, students)) return;

  if (editingStudentId === null) {
    const student = new Student(Date.now(), nameInput.value, emailInput.value, classInput.value, paymentInput.value);
    students.push(student);
    showToast("Student added successfully!", "success");
  } else {
    const studentToEdit = students.find(s => s.id === editingStudentId);
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
  updateClassFilterGrades();
  updatePagination();
  updateDashboardStats(students);
  renderGradesPage(students, filterStudent);

  nameInput.value = "";
  emailInput.value = "";
  classInput.value = "";
  paymentInput.value = "Paid";
});

// ── Add Teacher ──
addTeacherBtn.addEventListener("click", () => {
  if (!isAdmin()) { showToast("Only admins can add teachers!", "error"); return; }

  const name = teacherName.value.trim();
  const email = teacherEmail.value.trim();
  const username = teacherUsername.value.trim();
  const password = teacherPassword.value.trim();
  const department = teacherDepartment.value.trim();
  const contractStatus = teacherContractStatus.value;
  const courseWorkload = parseInt(teacherCourseWorkload.value) || 0;

  if (!name || !email || !username || !password) {
    showToast("Please fill all required fields!", "error"); return;
  }

  if (editingTeacherId === null) {
    const teacher = new Teacher(Date.now(), name, email, username, password, department, contractStatus, courseWorkload);
    teachers.push(teacher);
    showToast("Teacher added successfully!", "success");
  } else {
    const t = teachers.find(t => t.id === editingTeacherId);
    t.name = name;
    t.email = email;
    t.username = username;
    t.password = password;
    t.department = department;
    t.contractStatus = contractStatus;
    t.courseWorkload = courseWorkload;
    editingTeacherId = null;
    addTeacherBtn.textContent = "Add Teacher";
    showToast("Teacher updated!", "info");
  }

  saveTeachers(teachers);
  renderTeachers(teachersContainer, teachers, onDeleteTeacher, onEditTeacher);
  updateCourseTeacherDropdown();

  teacherName.value = "";
  teacherEmail.value = "";
  teacherUsername.value = "";
  teacherPassword.value = "";
  teacherDepartment.value = "";
  teacherContractStatus.value = "Permanent";
  teacherCourseWorkload.value = "";
});

// ── Grades page: class filter ──
classFilterGrades.addEventListener("change", () => {
  updateStudentFilterByClass();
  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
});

// ── Grades page: student filter ──
filterStudent.addEventListener("change", () => {
  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
});

// ── Add Exam ──
addExamBtn.addEventListener("click", () => {
  const name = examName.value.trim();
  const subject = examSubject.value.trim();
  const date = examDate.value;
  const totalMarks = Number(examTotalMarks.value);

  if (!name || !subject || !date || isNaN(totalMarks) || totalMarks <= 0) {
    showToast("Please fill all fields correctly!", "error"); return;
  }

  if (editingExamId === null) {
    const exam = new Exam(
      Date.now(), name, subject, date, totalMarks,
      Math.floor(totalMarks * 0.4),
      localStorage.getItem("username")
    );
    exams.push(exam);
    showToast("Exam added successfully!", "success");
  } else {
    const e = exams.find(e => e.id === editingExamId);
    e.name = name;
    e.subject = subject;
    e.date = date;
    e.totalMarks = totalMarks;
    e.passingScore = Math.floor(totalMarks * 0.4);
    editingExamId = null;
    addExamBtn.textContent = "Add Exam";
    showToast("Exam updated!", "info");
  }

  saveExams(exams);
  renderExams(examsContainer, exams, onDeleteExam, onEditExam);

  examName.value = "";
  examSubject.value = "";
  examDate.value = "";
  examTotalMarks.value = "";
});

// ── Add Attendance ──
addAttendanceBtn.addEventListener("click", () => {
  const studentId = attendanceStudent.value;
  const date = attendanceDate.value;
  const subject = attendanceSubject.value.trim();
  const status = attendanceStatus.value;

  if (!studentId || !date || !subject) {
    showToast("Please fill all fields!", "error"); return;
  }

  if (editingAttendanceId === null) {
    const record = new Attendance(Date.now(), parseInt(studentId), date, status, subject);
    attendance.push(record);
    showToast("Attendance marked!", "success");
  } else {
    const r = attendance.find(a => a.id === editingAttendanceId);
    r.date = date;
    r.status = status;
    r.subject = subject;
    editingAttendanceId = null;
    addAttendanceBtn.textContent = "Mark Attendance";
    showToast("Attendance updated!", "info");
  }

  saveAttendance(attendance);
  renderAttendanceRecords(studentId);

  attendanceDate.value = "";
  attendanceSubject.value = "";
  attendanceStatus.value = "Present";
});

// ── Add Course ──
addCourseBtn.addEventListener("click", () => {
  const name = courseName.value.trim();
  const code = courseCode.value.trim();
  const description = courseDescription.value.trim();
  const credits = parseInt(courseCredits.value);
  const semester = courseSemester.value.trim();
  const teacherId = parseInt(courseTeacher.value);

  if (!name || !code || !description || isNaN(credits) || !semester || isNaN(teacherId)) {
    showToast("Please fill all fields!", "error"); return;
  }

  if (editingCourseId === null) {
    const course = new Course(Date.now(), name, code, description, teacherId, credits, semester);
    courses.push(course);
    showToast("Course added successfully!", "success");
  } else {
    const c = courses.find(c => c.id === editingCourseId);
    c.name = name;
    c.code = code;
    c.description = description;
    c.credits = credits;
    c.semester = semester;
    c.teacher = teacherId;
    editingCourseId = null;
    addCourseBtn.textContent = "Add Course";
    showToast("Course updated!", "info");
  }

  saveCourses(courses);
  renderCourses(coursesContainer, courses, teachers, onDeleteCourse, onEditCourse);

  courseName.value = "";
  courseCode.value = "";
  courseDescription.value = "";
  courseCredits.value = "";
  courseSemester.value = "";
  courseTeacher.value = "";
});

// ── Add Grade ──
addGradeBtn.addEventListener("click", () => {
  const selectedStudentId = filterStudent.value;
  const subject = gradeSubject.value.trim();
  const score = Number(gradeScore.value);

  if (!selectedStudentId) { showToast("Please select a student!", "error"); return; }

  const student = students.find(s => s.id === parseInt(selectedStudentId));
  if (!canAddGrades(student)) { showToast("You cannot add grades to this student!", "error"); return; }
  if (!subject) { showToast("Please enter a subject name!", "error"); return; }
  if (isNaN(score) || score < 0 || score > 100) { showToast("Score must be between 0-100!", "error"); return; }

  student.grades.push({ subject, score });
  saveStudents(students);

  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
  updateDashboardStats(students);

  gradeSubject.value = "";
  gradeScore.value = "";
  showToast("Grade added successfully!", "success");
});

// ── Grades page: delete grade button (event delegation) ──
document.addEventListener("click", async (e) => {
  if (!e.target.classList.contains("deleteGradeBtn")) return;

  const confirmed = await showConfirm("Delete Grade", "Are you sure you want to delete this grade?");
  if (!confirmed) return;

  const row = e.target.closest("tr");
  const studentName = row.cells[0].textContent;
  const scoreText = row.cells[3].textContent;
  const student = students.find(s => s.name === studentName);
  const gradeIndex = student.grades.findIndex(g => g.score == scoreText);
  student.grades.splice(gradeIndex, 1);

  saveStudents(students);
  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
  updateDashboardStats(students);
  showToast("Grade deleted!", "error");
});



// ── Courses page: open enrollment modal ──
document.addEventListener("click", (e) => {
  if (!e.target.classList.contains("enrollStudentsBtn")) return;
  const courseId = parseInt(e.target.closest("tr").dataset.courseId);
  const course = courses.find(c => c.id === courseId);
  openEnrollmentModal(course);
});

// ── Attendance: re-render when student changes ──
attendanceStudent.addEventListener("change", () => {
  if (attendanceStudent.value) {
    renderAttendanceRecords(attendanceStudent.value);
  } else {
    attendanceContainer.innerHTML = "";
    overallAttendance.textContent = "0%";
  }
});

// ── Export report button ──
exportReportBtn.addEventListener("click", () => {
  exportReportToExcel();
});

// ── Sort by name ──
sortBtn.addEventListener("click", () => {
  students.sort((a, b) => a.name.localeCompare(b.name));
  saveStudents(students);
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updateCounter(students);
  updateChart(students);
  updateClassFilter();
  updatePagination();
  showToast("Sorted by name!", "info");
});

// ── Sort by payment ──
sortPayment.addEventListener("change", () => {
  const sortValue = sortPayment.value;
  students.sort((a, b) => {
    if (sortValue === "Paid") return a.paymentStatus < b.paymentStatus ? -1 : 1;
    if (sortValue === "Pending") return a.paymentStatus > b.paymentStatus ? -1 : 1;
    return 0;
  });
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
  showToast("Sorted by payment!", "info");
});

// ── Search input ──
searchInput.addEventListener("input", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit, onAddGrade, onRowClick);
});

// ── Class filter dropdown ──
classFilter.addEventListener("change", () => {
  renderStudents(studentsContainer, getFilteredStudents(), onDelete, onEdit, onAddGrade, onRowClick);
});

// ── Pagination: Previous ──
prevBtn.addEventListener("click", () => {
  currentPage--;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
});

// ── Pagination: Next ──
nextBtn.addEventListener("click", () => {
  currentPage++;
  renderStudents(studentsContainer, getPageStudents(), onDelete, onEdit, onAddGrade, onRowClick);
  updatePagination();
});

// ── Clear All ──
document.querySelector("#clearAllBtn")?.addEventListener("click", clearAll);

// ── Export CSV ──
exportBtn.addEventListener("click", exportCSV);

// ── Dark Mode toggle ──
darkBtn.addEventListener("click", () => {
  document.body.classList.toggle("dark");
});

// ── Logout ──
logoutBtn.addEventListener("click", () => {
  localStorage.removeItem("isLoggedIn");
  localStorage.removeItem("username");
  window.location.href = "login.html";
  showToast("Logged out!", "info");
});

// ── Close student detail modal ──
document.querySelector("#closeModal").addEventListener("click", () => {
  document.querySelector("#modalOverlay").classList.remove("active");
});
// ═══════════════════════════════════════════════════════════════════
// EDIT GRADE MODAL — handle editing grades
// ═══════════════════════════════════════════════════════════════════

// Store current editing grade info
let editingGradeInfo = {
  studentId: null,
  gradeIndex: null
};

// ── Open edit grade modal ──
document.addEventListener("click", (e) => {
  if (!e.target.classList.contains("editGradeBtn")) return;

  const studentId = parseInt(e.target.dataset.studentId);
  const gradeIndex = parseInt(e.target.dataset.gradeIndex);
  const studentName = e.target.dataset.studentName;
  const subject = e.target.dataset.subject;
  const score = e.target.dataset.score;

  // Store for later use
  editingGradeInfo.studentId = studentId;
  editingGradeInfo.gradeIndex = gradeIndex;

  // Fill modal with current data
  document.querySelector("#editGradeStudent").value = studentName;
  document.querySelector("#editGradeSubject").value = subject;
  document.querySelector("#editGradeScore").value = score;

  // Show modal
  document.querySelector("#editGradeModalOverlay").classList.add("active");

  // Focus on score input (ready to edit)
  document.querySelector("#editGradeScore").focus();
  document.querySelector("#editGradeScore").select();
});

// ── Save edited grade ──
document.querySelector("#editGradeSaveBtn").addEventListener("click", () => {
  const { studentId, gradeIndex } = editingGradeInfo;
  const newScore = parseInt(document.querySelector("#editGradeScore").value);

  // Validation
  if (isNaN(newScore) || newScore < 0 || newScore > 100) {
    showToast("Score must be between 0-100!", "error");
    return;
  }

  // Find student and update grade
  const student = students.find(s => s.id === studentId);
  if (!student || !student.grades[gradeIndex]) {
    showToast("Grade not found!", "error");
    return;
  }

  // Update score
  const oldScore = student.grades[gradeIndex].score;
  student.grades[gradeIndex].score = newScore;

  // Save to localStorage
  saveStudents(students);

  // Refresh table
  updateGradesTable(students, classFilterGrades, filterStudent, gradesTableBody);
  updateGradesStats(students, filterStudent, avgGrade, highestGrade, lowestGrade);
  updateDashboardStats(students);

  // Close modal
  document.querySelector("#editGradeModalOverlay").classList.remove("active");

  // Show success message
  showToast(`Grade updated: ${oldScore} → ${newScore}`, "success");
});

// ── Cancel edit ──
document.querySelector("#editGradeCancelBtn").addEventListener("click", () => {
  document.querySelector("#editGradeModalOverlay").classList.remove("active");
});

// ── Close when clicking outside modal ──
document.querySelector("#editGradeModalOverlay").addEventListener("click", (e) => {
  if (e.target.id === "editGradeModalOverlay") {
    document.querySelector("#editGradeModalOverlay").classList.remove("active");
  }
});
// ── Sidebar navigation ──
document.querySelectorAll(".nav-item").forEach(item => {
  item.addEventListener("click", (e) => {
    e.preventDefault();
    document.querySelectorAll(".nav-item").forEach(nav => nav.classList.remove("active"));
    document.querySelectorAll(".section").forEach(sec => sec.classList.remove("active"));
    item.classList.add("active");
    document.querySelector(`#section-${item.dataset.section}`).classList.add("active");
  });
});
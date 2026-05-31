// course-ui.js

export function renderCourses(container, courses, teachers, onDelete, onEdit) {
    container.innerHTML = "";

    if (courses.length === 0) {
        container.innerHTML = "<tr><td colspan='7' style='text-align:center'>No courses yet</td></tr>";
        return;
    }

    courses.forEach((course) => {
        const row = document.createElement("tr");
        row.dataset.courseId = course.id;

        // Get teacher name
        const teacher = teachers.find(t => t.id === course.teacher);
        const teacherName = teacher ? teacher.name : "Unassigned";

        row.innerHTML = `
      <td>${course.code}</td>
      <td>${course.name}</td>
      <td>${course.description}</td>
      <td>${course.credits}</td>
      <td>${course.semester}</td>
      <td><span class="student-count">${course.getStudentCount()}</span></td>
      <td>${teacherName}</td>
      <td>
        <button class="editCourseBtn">Edit</button>
        <button class="deleteCourseBtn">Delete</button>
        <button class="enrollStudentsBtn">Enroll</button>
      </td>
    `;

        // Edit button
        row.querySelector(".editCourseBtn").addEventListener("click", () => {
            onEdit(course);
        });

        // Delete button
        row.querySelector(".deleteCourseBtn").addEventListener("click", () => {
            onDelete(course.id);
        });

        container.appendChild(row);
    });
}

// Get courses taught by a teacher
export function getCoursesByTeacher(courses, teacherId) {
    return courses.filter(c => c.teacher === teacherId);
}

// Get courses enrolled by a student
export function getCoursesByStudent(courses, studentId) {
    return courses.filter(c => c.students.includes(studentId));
}
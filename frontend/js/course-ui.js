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
    <button class="editCourseBtn" data-id="${course.id}" style="padding: 6px 12px; background: rgba(37, 99, 235, 0.08); color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">✏️ Edit</button>
<button class="enrollStudentsBtn" data-id="${course.id}" style="padding: 6px 12px; background: rgba(34, 197, 94, 0.08); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">👥 Enroll</button>
<button class="deleteCourseBtn" data-id="${course.id}" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.08); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">🗑️ Delete</button>



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
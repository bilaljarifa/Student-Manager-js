// enrollment-ui.js

export function renderEnrolledStudents(container, course, students, onRemove) {
    container.innerHTML = "";

    if (course.students.length === 0) {
        container.innerHTML = "<p style='text-align:center; color: #999;'>No students enrolled</p>";
        return;
    }

    const enrolledList = document.createElement("div");
    enrolledList.className = "enrolled-students-list";

    course.students.forEach((studentId) => {
        const student = students.find(s => s.id === studentId);
        if (!student) return;

        const studentDiv = document.createElement("div");
        studentDiv.className = "enrolled-student-item";
        studentDiv.innerHTML = `
      <span>${student.name} (${student.email})</span>
      <button class="removeStudentBtn" data-student-id="${studentId}">Remove</button>
    `;

        studentDiv.querySelector(".removeStudentBtn").addEventListener("click", () => {
            onRemove(studentId);
        });

        enrolledList.appendChild(studentDiv);
    });

    container.appendChild(enrolledList);
}
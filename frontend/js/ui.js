  import { getAverage, getStatus } from "./grades.js";

  export function renderStudents(
    studentsContainer,
    students,
    onDelete,
    onEdit,
    onAddGrade,
    onRowClick
  ) {
    studentsContainer.innerHTML = "";
    if (students.length === 0) {
      studentsContainer.innerHTML = `
        <tr>
          <td colspan="8" id="emptyState">
            <p><img 
    src="https://img.icons8.com/?size=100&id=37839&format=png&color=000000" 
    alt="icon"
    width="50"
  /></p>
            <p>No students found</p>
            <p>Add your first student to get started!</p>
          </td>
        </tr>
      `;
      return;
    }

    students.forEach((student, index) => {

      const average = getAverage(student.grades);
      const status = getStatus(average);

      const row = document.createElement("tr");
      row.dataset.id = student.id;

      row.innerHTML = `
        <td>${index + 1}</td>
        <td>${student.name}</td>
        <td>${student.email}</td>
        <td>${student.studentClass}</td>
        <td>
          <span class="status ${student.paymentStatus === "Paid" ? "paid" : "pending"}">
            ${student.paymentStatus}
          </span>
        </td>
        <td>${average}</td>
        <td>${status}</td>
        <td>
          <button class="editBtn">Edit</button>
          <button class="deleteBtn">Delete</button>
        </td>
      `;

      // EVENTS
      row.querySelector(".deleteBtn").addEventListener("click", () => {
        onDelete(student.id);
      });

      row.querySelector(".editBtn").addEventListener("click", () => {
        onEdit(student);
      });

      // ROW CLICK — open modal
      row.addEventListener("click", (e) => {
        if (e.target.tagName === "BUTTON") return;
        onRowClick(student);
      });

      studentsContainer.appendChild(row);
    });
  }
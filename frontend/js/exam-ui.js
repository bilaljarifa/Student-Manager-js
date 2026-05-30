// exam-ui.js

export function renderExams(examsContainer, exams, onDelete, onEdit) {
    examsContainer.innerHTML = "";

    exams.forEach((exam, index) => {
        const row = document.createElement("tr");
        row.dataset.id = exam.id;

        row.innerHTML = `
      <td>${index + 1}</td>
      <td>${exam.name}</td>
      <td>${exam.subject}</td>
      <td>${exam.date}</td>
      <td>${exam.totalMarks}</td>
      <td>
        <button class="editBtn">Edit</button>
        <button class="deleteBtn">Delete</button>
      </td>
    `;

        row.querySelector(".editBtn").addEventListener("click", () => {
            onEdit(exam);
        });

        row.querySelector(".deleteBtn").addEventListener("click", () => {
            onDelete(exam.id);
        });

        examsContainer.appendChild(row);
    });
}
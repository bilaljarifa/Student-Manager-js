// ═══════════════════════════════════════════════════════════════════
// EXAM UI — render exams table with all columns
// ═══════════════════════════════════════════════════════════════════

export function renderExams(examsContainer, exams, onDelete, onEdit) {
    examsContainer.innerHTML = "";

    if (exams.length === 0) {
        examsContainer.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px; color: #64748B;">
                    📭 No exams found
                </td>
            </tr>
        `;
        return;
    }

    exams.forEach((exam, index) => {
        const row = document.createElement("tr");
        row.dataset.id = exam.id;

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${exam.name || "-"}</td>
            <td>${exam.subject || "-"}</td>
            <td>${exam.class || "-"}</td>
            <td>${exam.date || "-"}</td>
            <td>${exam.totalMarks || 0}</td>
            <td>${exam.time || "-"}</td>
            <td>
                <button class="editBtn" style="background:#4F46E5; color:white; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px;">Edit</button>
                <button class="deleteBtn" style="background:#EF4444; color:white; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; margin-left:4px;">Delete</button>
            </td>
        `;

        // Edit button listener
        row.querySelector(".editBtn").addEventListener("click", () => {
            onEdit(exam);
        });

        // Delete button listener
        row.querySelector(".deleteBtn").addEventListener("click", () => {
            onDelete(exam.id);
        });

        examsContainer.appendChild(row);
    });
}
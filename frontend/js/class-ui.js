// ═══════════════════════════════════════════════════════════════════
// CLASS UI — render classes table
// ═══════════════════════════════════════════════════════════════════

export function renderClasses(classesContainer, classes, teachers, onDelete, onEdit) {
    classesContainer.innerHTML = "";

    if (classes.length === 0) {
        classesContainer.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #64748B;">
                    📭 No classes found
                </td>
            </tr>
        `;
        return;
    }

    classes.forEach((cls, index) => {
        const teacher = teachers.find(t => t.id === cls.teacher);
        const teacherName = teacher ? teacher.name : "-";

        const row = document.createElement("tr");
        row.dataset.id = cls.id;

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${cls.name || "-"}</td>
            <td>${cls.section || "-"}</td>
            <td>${teacherName}</td>
            <td>${cls.getStudentCount()}</td>
            <td>${cls.createdAt}</td>
            <td>
                <button class="editBtn" style="background:#4F46E5; color:white; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px;">Edit</button>
                <button class="deleteBtn" style="background:#EF4444; color:white; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; margin-left:4px;">Delete</button>
            </td>
        `;

        // Edit button listener
        row.querySelector(".editBtn").addEventListener("click", () => {
            onEdit(cls);
        });

        // Delete button listener
        row.querySelector(".deleteBtn").addEventListener("click", () => {
            onDelete(cls.id);
        });

        classesContainer.appendChild(row);
    });
}
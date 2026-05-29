// teacher-ui.js

import { getAverage, getStatus } from "./grades.js";

export function renderTeachers(teachersContainer, teachers, onDelete, onEdit) {
    teachersContainer.innerHTML = "";

    if (teachers.length === 0) {
        teachersContainer.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #64748B;">
                    📭 No teachers found
                </td>
            </tr>
        `;
        return;
    }

    teachers.forEach((teacher, index) => {
        const row = document.createElement("tr");
        row.dataset.id = teacher.id;

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${teacher.name}</td>
            <td>${teacher.username}</td>
            <td>${teacher.teacherClass || "-"}</td>
            <td>${teacher.room || "-"}</td>
            <td>${teacher.schedule || "-"}</td>
            <td>
                <button class="editBtn">Edit</button>
                <button class="deleteBtn">Delete</button>
            </td>
        `;

        // Edit button
        row.querySelector(".editBtn").addEventListener("click", () => {
            onEdit(teacher);
        });

        // Delete button
        row.querySelector(".deleteBtn").addEventListener("click", () => {
            onDelete(teacher.id);
        });

        teachersContainer.appendChild(row);
    });
}
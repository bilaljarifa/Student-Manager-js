

export function renderAttendance(container, attendanceRecords, studentName, onDelete, onEdit) {
    container.innerHTML = "";

    if (attendanceRecords.length === 0) {
        container.innerHTML = "<tr><td colspan='4' style='text-align:center'>No attendance records</td></tr>";
        return;
    }

    attendanceRecords.forEach((record) => {
        const row = document.createElement("tr");
        row.className = `attendance-${record.status.toLowerCase()}`;
        row.dataset.id = record.id;

        row.innerHTML = `
      <td>${studentName}</td>
      <td>${record.formatDate()}</td>
      <td>${record.subject}</td>
      <td><span class="status-badge ${record.status.toLowerCase()}">${record.status}</span></td>
      <td>
        <button class="editAttendanceBtn">Edit</button>
        <button class="deleteAttendanceBtn">Delete</button>
      </td>
    `;

        // Edit button
        row.querySelector(".editAttendanceBtn").addEventListener("click", () => {
            onEdit(record);
        });

        // Delete button
        row.querySelector(".deleteAttendanceBtn").addEventListener("click", () => {
            onDelete(record.id);
        });

        container.appendChild(row);
    });
}

// Get attendance percentage for a student
export function getAttendancePercentage(attendance, studentId) {
    const studentAttendance = attendance.filter(a => a.studentId === studentId);
    if (studentAttendance.length === 0) return 0;

    const presentCount = studentAttendance.filter(a => a.isPresent()).length;
    return Math.round((presentCount / studentAttendance.length) * 100);
}
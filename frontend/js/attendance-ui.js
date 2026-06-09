// ═══════════════════════════════════════════════════════════════════
// ATTENDANCE CLASS — Track student attendance
// ═══════════════════════════════════════════════════════════════════

export class Attendance {
    constructor(id, studentId, classId, date, status, subject = "General") {
        this.id = id;
        this.studentId = studentId;
        this.classId = classId;
        this.date = date;              // Format: "2025-06-08"
        this.status = status;          // "Present", "Absent", "Late", "Excused"
        this.subject = subject;        // Subject/Class name
    }

    // ── Format date for display ──
    formatDate() {
        try {
            const date = new Date(this.date + "T00:00:00");
            return date.toLocaleDateString("en-US", { 
                weekday: "short", 
                year: "numeric", 
                month: "short", 
                day: "numeric" 
            });
        } catch (error) {
            console.error("Error formatting date:", this.date, error);
            return this.date;  // Fallback to raw date
        }
    }

    // ── Check if present ──
    isPresent() {
        return this.status === "Present";
    }

    // ── Check if absent ──
    isAbsent() {
        return this.status === "Absent";
    }

    // ── Check if late ──
    isLate() {
        return this.status === "Late";
    }

    // ── Check if excused ──
    isExcused() {
        return this.status === "Excused";
    }

    // ── Get status display ──
    getStatusDisplay() {
        const icons = {
            "Present": "✅",
            "Absent": "❌",
            "Late": "⏱️",
            "Excused": "📝"
        };
        return `${icons[this.status] || "?"} ${this.status}`;
    }
}

export function renderAttendance(container, attendanceRecords, students, onDelete, onEdit) {
    container.innerHTML = "";

    if (attendanceRecords.length === 0) {
        container.innerHTML = "<tr><td colspan='5' style='text-align:center; padding:20px;'>No attendance records</td></tr>";
        return;
    }

    attendanceRecords.forEach((record) => {
        // Get student name
        const student = students.find(s => s.id === record.studentId);
        const studentName = student ? student.name : "Unknown Student";

        const row = document.createElement("tr");
        row.className = `attendance-${record.status.toLowerCase()}`;
        row.dataset.id = record.id;

        row.innerHTML = `
            <td>${studentName}</td>
            <td>${record.formatDate()}</td>
            <td>${record.subject}</td>
            <td><span class="status ${record.status.toLowerCase()}">${record.status}</span></td>
            <td>
                <button class="editAttendanceBtn" data-id="${record.id}" style="padding: 6px 12px; background: rgba(37, 99, 235, 0.08); color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer; margin-right: 4px;">✏️ Edit</button>
                <button class="deleteAttendanceBtn" data-id="${record.id}" style="padding: 6px 12px; background: rgba(239, 68, 68, 0.08); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 4px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">🗑️ Delete</button>
            </td>
        `;

        // Edit button ✅ FIXED: .editAttendanceBtn
        const editBtn = row.querySelector(".editAttendanceBtn");
        if (editBtn) {
            editBtn.addEventListener("click", () => {
                onEdit(record);
            });
        }

        // Delete button ✅ FIXED: .deleteAttendanceBtn (was .delete-attendance-btn)
        const deleteBtn = row.querySelector(".deleteAttendanceBtn");
        if (deleteBtn) {
            deleteBtn.addEventListener("click", () => {
                onDelete(record.id);
            });
        }

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

// Get attendance stats
export function getAttendanceStats(attendance) {
    const total = attendance.length;
    const present = attendance.filter(a => a.isPresent()).length;
    const absent = attendance.filter(a => a.isAbsent()).length;
    const late = attendance.filter(a => a.isLate()).length;
    const excused = attendance.filter(a => a.isExcused()).length;

    return {
        total,
        present,
        absent,
        late,
        excused,
        presentRate: total > 0 ? Math.round((present / total) * 100) : 0
    };
}
// attendance.js

export class Attendance {
    constructor(id, studentId, date, status, subject = "General") {
        this.id = id;
        this.studentId = studentId;    // Which student
        this.date = date;              // YYYY-MM-DD format
        this.status = status;          // "Present", "Absent", "Late", "Excused"
        this.subject = subject;        // Optional: which class/subject
    }

    // ── METHODS ──────────────────────
    
    isPresent() {
        return this.status === "Present";
    }

    isAbsent() {
        return this.status === "Absent";
    }

    formatDate() {
        const date = new Date(this.date + "T00:00:00");
        return date.toLocaleDateString("en-US", { 
            weekday: "short", 
            year: "numeric", 
            month: "short", 
            day: "numeric" 
        });
    }

    toHTML() {
        return `
            <tr class="attendance-${this.status.toLowerCase()}">
                <td>${this.formatDate()}</td>
                <td>${this.subject}</td>
                <td><span class="status-badge ${this.status.toLowerCase()}">${this.status}</span></td>
                <td>
                    <button class="edit-attendance-btn" data-id="${this.id}">Edit</button>
                    <button class="delete-attendance-btn" data-id="${this.id}">Delete</button>
                </td>
            </tr>
        `;
    }
}
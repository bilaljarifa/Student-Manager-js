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
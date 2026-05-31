// course.js

export class Course {
    constructor(id, name, code, description, teacher, credits, semester) {
        this.id = id;
        this.name = name;              // "Mathematics", "Physics", etc.
        this.code = code;              // "MATH101", "PHY202"
        this.description = description; // Course details
        this.teacher = teacher;        // Teacher ID who teaches it
        this.credits = credits;        // Course credits (3, 4, etc.)
        this.semester = semester;      // "Fall 2025", "Spring 2026"
        this.students = [];            // Array of student IDs enrolled
    }

    // ── METHODS ──────────────────────

    addStudent(studentId) {
        if (!this.students.includes(studentId)) {
            this.students.push(studentId);
            return true;
        }
        return false;  // Already enrolled
    }

    removeStudent(studentId) {
        this.students = this.students.filter(id => id !== studentId);
    }

    getStudentCount() {
        return this.students.length;
    }

    isFull(maxCapacity = 50) {
        return this.students.length >= maxCapacity;
    }

    formatInfo() {
        return `${this.code} - ${this.name}`;
    }

    toHTML() {
        return `
            <tr data-course-id="${this.id}">
                <td>${this.code}</td>
                <td>${this.name}</td>
                <td>${this.description}</td>
                <td>${this.credits}</td>
                <td>${this.semester}</td>
                <td>${this.getStudentCount()}</td>
                <td>
                    <button class="editCourseBtn">Edit</button>
                    <button class="deleteCourseBtn">Delete</button>
                </td>
            </tr>
        `;
    }
}

export class Class {
    constructor(id, name, section, teacher, students = []) {
        this.id = id;                    // Unique ID (timestamp)
        this.name = name;                // "Class 7", "Class 4"
        this.section = section;          // "Science", "Math", "English"
        this.teacher = teacher;          // Teacher ID
        this.students = students;        // Array of student IDs
        this.createdAt = new Date().toLocaleDateString();
    }

    // Add student to class
    addStudent(studentId) {
        if (!this.students.includes(studentId)) {
            this.students.push(studentId);
            return true;
        }
        return false;
    }

    // Remove student from class
    removeStudent(studentId) {
        this.students = this.students.filter(id => id !== studentId);
    }

    // Get student count
    getStudentCount() {
        return this.students.length;
    }

    // Check if student is in class
    hasStudent(studentId) {
        return this.students.includes(studentId);
    }
}
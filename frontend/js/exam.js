export class Exam {
    constructor(id, name, subject, classId, date, totalMarks, passingScore, time, createdBy, type = "FINAL") {
        this.id = id;
        this.name = name;
        this.subject = subject;
        this.classId = classId;  // ← CHANGED: now uses CLASS ID instead of string
        this.date = date;
        this.totalMarks = totalMarks;
        this.passingScore = passingScore;
        this.time = time;
        this.createdBy = createdBy;
        this.type = type;
        this.assignedTo = [];
        this.results = [];
    }

    // Add result for a student
    addResult(studentId, score) {
        const existingResult = this.results.find(r => r.studentId === studentId);

        if (existingResult) {
            // Update existing result
            existingResult.score = score;
            existingResult.status = score >= this.passingScore ? "PASS" : "FAIL";
        } else {
            // Add new result
            this.results.push({
                id: Date.now(),
                studentId: studentId,
                score: score,
                status: score >= this.passingScore ? "PASS" : "FAIL",
                date: new Date().toLocaleDateString()
            });
        }
    }

    // Get result for specific student
    getStudentResult(studentId) {
        return this.results.find(r => r.studentId === studentId);
    }

    // Get pass rate
    getPassRate() {
        if (this.results.length === 0) return 0;
        const passed = this.results.filter(r => r.status === "PASS").length;
        return ((passed / this.results.length) * 100).toFixed(1);
    }
}
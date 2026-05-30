export class Exam {
    constructor(id, name, subject, date, totalMarks, passingScore, createdBy) {
        this.id = id;                    // Unique exam ID
        this.name = name;                // Exam name (Math Final, Physics Quiz, etc.)
        this.subject = subject;          // Subject (Mathematics, Science, etc.)
        this.date = date;                // Exam date (YYYY-MM-DD format)
        this.totalMarks = totalMarks;    // Total marks (100, 50, etc.)
        this.passingScore = passingScore;// Passing score (40, 50, etc.)
        this.createdBy = createdBy;      // Teacher ID who created it
        this.assignedTo = [];            // Array of student IDs assigned to this exam
        this.results = [];               // Array of exam results { studentId, score }
    }
}
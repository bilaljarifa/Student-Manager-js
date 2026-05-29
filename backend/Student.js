export class Student {
    constructor(id, name, email, studentClass, paymentStatus, teacherId = null) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.studentClass = studentClass;
        this.paymentStatus = paymentStatus;
        this.grades = [];
        this.teacherId = teacherId;
    }
}
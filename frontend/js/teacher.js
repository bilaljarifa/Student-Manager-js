export class Teacher {
    constructor(id, name, email, username, password, department = "", contractStatus = "Permanent", courseWorkload = 0) {
        this.id = id;
        this.name = name;
        this.email = email;
        this.username = username;
        this.password = password;
        this.role = "TEACHER";
        this.department = department;           
        this.contractStatus = contractStatus;   
        this.courseWorkload = courseWorkload;   
    }
}
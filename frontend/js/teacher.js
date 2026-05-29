export class Teacher {
    constructor(id, name, username, password, classes = [], schedule = "", classroom = "") {
        this.id = id;
        this.name = name;
        this.username = username;
        this.password = password;
        this.classes = classes;       
        this.schedule = schedule;     
        this.classroom = classroom;    
    }
}
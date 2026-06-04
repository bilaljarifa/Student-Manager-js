export class Teacher {
  constructor(id, name, email, username, password, department, contractStatus, courseWorkload, classes = []) {
    this.id = id;
    this.name = name;
    this.email = email;
    this.username = username;
    this.password = password;
    this.department = department;
    this.contractStatus = contractStatus;
    this.courseWorkload = courseWorkload;
    this.classes = classes;  
  }
}
export class Teacher {
  constructor(id, name, email, username, password, department, contractStatus, courseWorkload, classIds = []) {
    this.id = id;
    this.name = name;
    this.email = email;
    this.username = username;
    this.password = password;
    this.department = department;
    this.contractStatus = contractStatus;
    this.courseWorkload = courseWorkload;
    this.classIds = classIds;  // ← CHANGED: now stores CLASS IDs instead of names
  }

  // ── METHODS ──────────────────────

  // Add a class to this teacher
  addClass(classId) {
    if (!this.classIds.includes(classId)) {
      this.classIds.push(classId);
      return true;
    }
    return false;
  }

  // Remove a class from this teacher
  removeClass(classId) {
    this.classIds = this.classIds.filter(id => id !== classId);
  }

  // Check if teacher teaches a class
  teachesClass(classId) {
    return this.classIds.includes(classId);
  }

  // Get number of classes taught
  getClassCount() {
    return this.classIds.length;
  }
}
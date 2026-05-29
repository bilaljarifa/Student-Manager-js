// ── ROLE MANAGEMENT ────────────────────────────────────────

// 1️⃣ GET CURRENT USER
export function getCurrentUser() {
    return {
        username: localStorage.getItem("username"),
        role: localStorage.getItem("userRole"),
        userId: localStorage.getItem("userId")
    };
}

// 2️⃣ CHECK IF ADMIN
export function isAdmin() {
    return localStorage.getItem("userRole") === "ADMIN";
}

// 3️⃣ CHECK IF TEACHER
export function isTeacher() {
    return localStorage.getItem("userRole") === "TEACHER";
}

// 4️⃣ GET CURRENT TEACHER ID
export function getCurrentTeacherId() {
    return parseInt(localStorage.getItem("userId"));
}

// 5️⃣ FILTER STUDENTS BY ROLE
export function filterStudentsByRole(students) {
    if (isAdmin()) {
        // Admin sees ALL students
        return students;
    }

    if (isTeacher()) {
        // Teacher sees ONLY their students
        const teacherId = getCurrentTeacherId();
        return students.filter(s => s.teacherId === teacherId);
    }

    return [];
}

// 6️⃣ CAN TEACHER DELETE STUDENT?
export function canDeleteStudent() {
    // Only ADMIN can delete
    return isAdmin();
}

// 7️⃣ CAN TEACHER EDIT STUDENT?
export function canEditStudent() {
    // Only ADMIN can edit student info
    return isAdmin();
}

// 8️⃣ CAN TEACHER ADD GRADES?
export function canAddGrades(student) {
    if (isAdmin()) {
        // Admin can add grades to anyone
        return true;
    }

    if (isTeacher()) {
        // Teacher can only add grades to their students
        const teacherId = getCurrentTeacherId();
        return student.teacherId === teacherId;
    }

    return false;
}
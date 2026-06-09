// ═══════════════════════════════════════════════════════════════════
// CLASS STORAGE — save and load classes from localStorage
// ═══════════════════════════════════════════════════════════════════

import { Class } from "./Class.js";

// Save classes to localStorage
export function saveClasses(classes) {
    localStorage.setItem("classes", JSON.stringify(classes));
}

// Load classes from localStorage
export function loadClasses() {
    const data = localStorage.getItem("classes");
    if (!data) return [];

    const parsed = JSON.parse(data);

    // Convert back to Class instances with methods
    return parsed.map(cls => {
        const classInstance = new Class(cls.id, cls.name, cls.section, cls.teacher, cls.students);
        classInstance.createdAt = cls.createdAt || new Date().toLocaleDateString();
        return classInstance;
    });
}
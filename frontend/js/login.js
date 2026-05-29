import { loadTeachers } from "./storage.js";

const ADMIN_USERNAME = "admin";
const ADMIN_PASSWORD = "admin123";

const usernameInput = document.querySelector("#usernameInput");
const passwordInput = document.querySelector("#passwordInput");
const loginBtn = document.querySelector("#loginBtn");
const loginError = document.querySelector("#loginError");

loginBtn.addEventListener("click", () => {
 const username = usernameInput.value.trim();//    trim emove space befor and after 
  const password = passwordInput.value.trim(); 

  // Validate no emty input 
  if (!username || !password) {
    loginError.textContent = "Please enter username and password";
    return;
  }

  // ─────────────────────────────────────
  // CHECK ADMIN LOGIN
  // ─────────────────────────────────────
  if (username === ADMIN_USERNAME && password === ADMIN_PASSWORD) {
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("username", username);
    localStorage.setItem("userRole", "ADMIN");  // ← Store role
    localStorage.setItem("userId", "admin");    // ← Store ID
    window.location.href = "index.html";
    return;
  }

  // ─────────────────────────────────────
  // CHECK TEACHER LOGIN
  // ─────────────────────────────────────
  const teachers = loadTeachers();

  const teacher = teachers.find(t => t.username === username && t.password === password);

  if (teacher) {
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("username", teacher.username);
    localStorage.setItem("userRole", "TEACHER");  // ← Store role
    localStorage.setItem("userId", teacher.id);   // ← Store teacher ID
    window.location.href = "index.html";
    return;
  }

  // ─────────────────────────────────────
  // NO MATCH
  // ─────────────────────────────────────
  loginError.textContent = "Wrong username or password!";
});
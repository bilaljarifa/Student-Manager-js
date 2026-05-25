const ADMIN_USERNAME = "admin";
const ADMIN_PASSWORD = "admin123";

const usernameInput = document.querySelector("#usernameInput");
const passwordInput = document.querySelector("#passwordInput");
const loginBtn = document.querySelector("#loginBtn");
const loginError = document.querySelector("#loginError");

loginBtn.addEventListener("click", () => {
    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();

    if (username === ADMIN_USERNAME && password === ADMIN_PASSWORD) {
        localStorage.setItem("isLoggedIn", "true");
        localStorage.setItem("username", username);
        window.location.href = "index.html";
    } else {
        loginError.textContent = "Wrong username or password!";
        loginError.style.color = "red";
    }
});
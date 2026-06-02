import { showToast } from "./toast.js";

function showError(input, message) {
     // add red border + shake
     input.classList.add("input-error", "shake");

     // remove shake after animation finishes so it can retrigger
     input.addEventListener("animationend", () => {
          input.classList.remove("shake");
     }, { once: true }); // ← runs only one time

     // show message under the input
     const error = document.createElement("span");
     error.classList.add("error-message");
     error.textContent = message;
     input.insertAdjacentElement("afterend", error);

     // clear error when user starts typing
     input.addEventListener("input", () => {
          input.classList.remove("input-error");
          error.remove();
     }, { once: true });
}

function clearErrors() {
     document.querySelectorAll(".input-error").forEach(el => el.classList.remove("input-error"));
     document.querySelectorAll(".error-message").forEach(el => el.remove());
}

export function validation(nameInput, emailInput, classInput, students) {
     clearErrors();

     if (nameInput.value === "") {
          showError(nameInput, "Name is required!");
          return false;
     }

     const nameRegex = /^[a-zA-Z\s]+$/;
     if (!nameRegex.test(nameInput.value)) {
          showError(nameInput, "Name can only contain letters!");
          return false;
     }

     if (nameInput.value.length < 4) {
          showError(nameInput, "Name must be at least 4 characters!");
          return false;
     }

     if (emailInput.value === "") {
          showError(emailInput, "Email is required!");
          return false;
     }

     const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
     if (!emailRegex.test(emailInput.value)) {
          showError(emailInput, "Please enter a valid email!");
          return false;
     }

     if (classInput.value === "") {
          showError(classInput, "Class is required!");
          return false;
     }

     const isDuplicate = students.some((student) => {
          return student.email === emailInput.value;
     });
     if (isDuplicate) {
          showError(emailInput, "Email already exists!");
          return false;
     }

     return true;
}
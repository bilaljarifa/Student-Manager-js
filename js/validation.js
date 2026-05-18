import { showToast } from "./toast.js";

export function validation(name, email, studentClass) {

     if (name === "" || email === "" || studentClass === "") {
          showToast("Please fill all fields!", "error");
          return false;
     }

     const nameRegex = /^[a-zA-Z\s]+$/;
     if (!nameRegex.test(name)) {
          showToast("Name can only contain letters!", "error");
          return false;
     }
     if (name.length < 3) {
          showToast("Name must be at least 3 characters!", "error");
          return false;
     }

     const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
     if (!emailRegex.test(email)) {
          showToast("Please enter a valid email!", "error");
          return false;
     }

     return true;
}
export function showToast(message, type = "success") {
    const container = document.querySelector("#toast-container");

    // create toast element
    const toast = document.createElement("div");
    toast.classList.add("toast", type);
    toast.textContent = message;

    // add to container
    container.appendChild(toast);

    // remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
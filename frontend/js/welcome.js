export function displayWelcome() {
    const username = localStorage.getItem("username");
    const welcomeText = document.querySelector("#welcomeText");
    const dateText = document.querySelector("#dateText");

    // get current time
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    // good morning/afternoon/evening based on hour
    let greeting = "Good morning";
    if (hour >= 12) greeting = "Good afternoon";
    if (hour >= 18) greeting = "Good evening";

    // format time (HH:MM)
    const formattedTime = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;

    // set welcome message with time
    welcomeText.textContent = `${greeting}, ${username}! 👋`;

    // format date with time
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = now.toLocaleDateString('en-US', options);
    dateText.textContent = `${formattedDate} at ${formattedTime}`;
}

// now = current date/time from computer
// options = how we want to format it
// toLocaleDateString = applies the options
// result = "Tuesday, May 26, 2026"
//  combine with time = "Tuesday, May 26, 2026 at 20:30"
//  put in HTML = dateText shows the message
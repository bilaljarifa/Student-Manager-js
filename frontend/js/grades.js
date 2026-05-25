// ── GRADES CALCULATION FUNCTIONS ───────────────────

// FUNCTION: getAverage
// PURPOSE: Calculate the average of an array of grades
// INPUT: array of numbers [85, 90, 78]
// OUTPUT: string "84.33"
export function getAverage(grades) {
    // if no grades exist, return 0
    if (grades.length === 0) return 0;

    // use reduce to add all grades together
    const total = grades.reduce((sum, grade) => {
        return sum + grade;
    }, 0);

    // divide total by number of grades, round to 2 decimals
    return (total / grades.length).toFixed(2);
}

// ──────────────────────────────────────────────────

// FUNCTION: getStatus
// PURPOSE: Return a status string based on the average grade
// INPUT: number 85
// OUTPUT: string "Good"
export function getStatus(average) {
    // convert to number in case it's a string
    const avg = parseFloat(average);

    // return status based on average
    if (avg >= 90) return "Excellent";
    if (avg >= 75) return "Good";
    if (avg >= 60) return "Pass";
    return "Fail";
}
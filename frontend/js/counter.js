export function updateCounter(students) {

    const counter = document.querySelector("#counter");
    counter.textContent = students.length;

    const paidCount = students.filter((student) => {
        return student.paymentStatus === "Paid";
    }).length;
    const paid = document.querySelector("#paid");
    paid.textContent = paidCount;


    const pendingCount = students.filter((student) => {
        return student.paymentStatus === "Pending";
    }).length;
    const pending = document.querySelector("#pending");
    pending.textContent = pendingCount;

    let percentage;
    if (students.length === 0) {
        percentage = 0;
    } else {
        percentage = (paidCount / students.length) * 100;


    }
    let percentagePending;
    if (students.length === 0) {
        percentagePending = 0;
    } else {
        percentagePending = (pendingCount / students.length) * 100;


    }
    const percentageEl = document.querySelector("#percentage");
    percentageEl.textContent = percentage.toFixed(2) + "%";
    const pendingPercentageEl = document.querySelector("#pendingPercentage");
    pendingPercentageEl.textContent = percentagePending.toFixed(2) + "%";

} 
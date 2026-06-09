// ═══════════════════════════════════════════════════════════════════
// COUNTER — Update student count (safe version)
// ═══════════════════════════════════════════════════════════════════

export function updateCounter(students) {
    // NOTE: Dashboard stats are now handled by dashboardStats.js
    // This function is kept for backwards compatibility

    // Only update elements if they exist
    const counter = document.querySelector("#counter");
    if (counter) {
        counter.textContent = students.length;
    }

    const paid = document.querySelector("#paid");
    if (paid) {
        const paidCount = students.filter(s => s.paymentStatus === "Paid").length;
        paid.textContent = paidCount;
    }

    const pending = document.querySelector("#pending");
    if (pending) {
        const pendingCount = students.filter(s => s.paymentStatus === "Pending").length;
        pending.textContent = pendingCount;
    }

    const percentageEl = document.querySelector("#percentage");
    if (percentageEl) {
        const paidCount = students.filter(s => s.paymentStatus === "Paid").length;
        const percentage = students.length > 0 ? (paidCount / students.length) * 100 : 0;
        percentageEl.textContent = percentage.toFixed(2) + "%";
    }

    const pendingPercentageEl = document.querySelector("#pendingPercentage");
    if (pendingPercentageEl) {
        const pendingCount = students.filter(s => s.paymentStatus === "Pending").length;
        const percentagePending = students.length > 0 ? (pendingCount / students.length) * 100 : 0;
        pendingPercentageEl.textContent = percentagePending.toFixed(2) + "%";
    }

    console.log("✅ Counter updated:", students.length + " students");
}
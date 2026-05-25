let chartInstance = null;

export function updateChart(students) {
    const paidCount = students.filter((student) => {
        return student.paymentStatus === "Paid";
    }).length;

    const pendingCount = students.filter((student) => {
        return student.paymentStatus === "Pending";
    }).length;

    const ctx = document.querySelector("#paymentChart");

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: "pie",
        data: {
            labels: ["students Paid", "students Pending"],
            datasets: [{
                data: [paidCount, pendingCount],
                backgroundColor: ["#00c749", "#f50b0b"],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }

        }
    });
}
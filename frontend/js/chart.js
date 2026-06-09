// ═══════════════════════════════════════════════════════════════════
// CHART.JS — Payment Status Chart (Pie/Doughnut)
// ═══════════════════════════════════════════════════════════════════

export function updateChart(students) {
    const paidCount = students.filter(s => s.paymentStatus === "Paid").length;
    const pendingCount = students.filter(s => s.paymentStatus === "Pending").length;

    const ctx = document.querySelector("#paymentChart");
    
    // Destroy existing chart if it exists
    if (window.paymentChart && window.paymentChart.destroy) {
        window.paymentChart.destroy();
    }

    // Create new chart
    window.paymentChart = new Chart(ctx, {
        type: "doughnut",  // or "pie" for a full circle
        data: {
            labels: ["Paid", "Pending"],
            datasets: [{
                data: [paidCount, pendingCount],
                backgroundColor: [
                    "#22C55E",  // Green for Paid
                    "#F59E0B"   // Amber for Pending
                ],
                borderColor: [
                    "#DCFCE7",  // Light green border
                    "#FEF3C7"   // Light amber border
                ],
                borderWidth: 2,
                borderRadius: 8,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 13,
                            weight: "600"
                        },
                        color: getComputedStyle(document.documentElement).getPropertyValue("--text") || "#1E293B",
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: "circle"
                    }
                },
                tooltip: {
                    backgroundColor: "rgba(0, 0, 0, 0.8)",
                    padding: 12,
                    borderRadius: 8,
                    titleFont: {
                        size: 13,
                        weight: "bold",
                        family: "'Inter', sans-serif"
                    },
                    bodyFont: {
                        size: 12,
                        family: "'Inter', sans-serif"
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || "";
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} students (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    console.log("✅ Payment chart updated:", { paid: paidCount, pending: pendingCount });
}
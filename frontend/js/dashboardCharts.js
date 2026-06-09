// ═══════════════════════════════════════════════════════════════════
// DASHBOARD CHARTS — Grade Level + GPA Distribution
// ═══════════════════════════════════════════════════════════════════

import { getAverage } from "./grades.js";

export function updateDashboardCharts(students) {
    updateGradeLevelChart(students);
    updateGPADistributionChart(students);
}

// ── STUDENTS BY GRADE LEVEL (Bar Chart) ──
function updateGradeLevelChart(students) {
    const gradeData = {
        "9th": 0,
        "10th": 0,
        "11th": 0,
        "12th": 0
    };

    // Count students by grade level (extract from class name)
    students.forEach(student => {
        const className = student.studentClass || "";
        
        if (className.includes("9")) gradeData["9th"]++;
        else if (className.includes("10")) gradeData["10th"]++;
        else if (className.includes("11")) gradeData["11th"]++;
        else if (className.includes("12")) gradeData["12th"]++;
    });

    const ctx = document.querySelector("#gradesLevelChart");
    
    if (window.gradeLevelChart && window.gradeLevelChart.destroy) {
        window.gradeLevelChart.destroy();
    }

    window.gradeLevelChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["9th", "10th", "11th", "12th"],
            datasets: [{
                label: "Number of Students",
                data: [
                    gradeData["9th"],
                    gradeData["10th"],
                    gradeData["11th"],
                    gradeData["12th"]
                ],
                backgroundColor: "#3B82F6",
                borderColor: "#3B82F6",
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 40,
                maxBarThickness: 60
            }]
        },
        options: {
            indexAxis: undefined,
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgba(0, 0, 0, 0.8)",
                    padding: 10,
                    borderRadius: 6,
                    titleFont: { size: 12, weight: "bold" },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + " students";
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.floor(value);
                        }
                    },
                    grid: {
                        color: "rgba(0, 0, 0, 0.05)"
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    console.log("✅ Grade level chart updated:", gradeData);
}

// ── GPA DISTRIBUTION (Doughnut Chart) ──
function updateGPADistributionChart(students) {
    const gpaData = {
        "3.5-4.0": 0,
        "3.0-3.4": 0,
        "2.5-2.9": 0,
        "2.0-2.4": 0,
        "Below 2.0": 0
    };

    // Calculate GPA for each student and categorize
    students.forEach(student => {
        if (student.grades && student.grades.length > 0) {
            const scores = student.grades.map(g => 
                typeof g === "object" ? g.score : g
            );
            const avg = getAverage(scores);
            const gpa = (avg / 100) * 4.0; // Convert to 0-4.0 scale

            if (gpa >= 3.5) gpaData["3.5-4.0"]++;
            else if (gpa >= 3.0) gpaData["3.0-3.4"]++;
            else if (gpa >= 2.5) gpaData["2.5-2.9"]++;
            else if (gpa >= 2.0) gpaData["2.0-2.4"]++;
            else gpaData["Below 2.0"]++;
        }
    });

    const ctx = document.querySelector("#gpaDistributionChart");
    
    if (window.gpaDistributionChart && window.gpaDistributionChart.destroy) {
        window.gpaDistributionChart.destroy();
    }

    window.gpaDistributionChart = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["3.5-4.0", "3.0-3.4", "2.5-2.9", "2.0-2.4", "Below 2.0"],
            datasets: [{
                data: [
                    gpaData["3.5-4.0"],
                    gpaData["3.0-3.4"],
                    gpaData["2.5-2.9"],
                    gpaData["2.0-2.4"],
                    gpaData["Below 2.0"]
                ],
                backgroundColor: [
                    "#10B981", // Green - Excellent
                    "#3B82F6", // Blue - Good
                    "#F59E0B", // Amber - Average
                    "#EF4444", // Red - Below
                    "#6B7280"  // Gray - Poor
                ],
                borderColor: "transparent",
                borderRadius: 6,
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
                        font: { size: 12, weight: "500" },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: "circle"
                    }
                },
                tooltip: {
                    backgroundColor: "rgba(0, 0, 0, 0.8)",
                    padding: 10,
                    borderRadius: 6,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || "";
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ${value} students (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    console.log("✅ GPA distribution chart updated:", gpaData);
}
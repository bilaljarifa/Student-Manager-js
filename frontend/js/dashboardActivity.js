// ═══════════════════════════════════════════════════════════════════
// DASHBOARD ACTIVITY — Recent Activity Feed
// ═══════════════════════════════════════════════════════════════════

export function updateActivityFeed(students, grades, attendance, courses) {
    const activities = [];

    // ── COLLECT ALL ACTIVITIES ──

    // 1. Recent grades
    students.forEach(student => {
        if (student.grades && student.grades.length > 0) {
            student.grades.forEach((grade, idx) => {
                activities.push({
                    type: "grade",
                    name: student.name,
                    action: `Grade updated - ${grade.subject}: ${grade.score}`,
                    time: "Just now",
                    color: "#22C55E", // Green
                    icon: "✓"
                });
            });
        }
    });

    // 2. Recent attendance
    attendance.slice(-5).forEach(record => {
        const student = students.find(s => s.id === record.studentId);
        if (student) {
            activities.push({
                type: "attendance",
                name: student.name,
                action: `Attendance marked - ${record.status}`,
                time: new Date(record.date).toLocaleDateString(),
                color: record.status === "Present" ? "#F59E0B" : "#EF4444",
                icon: record.status === "Present" ? "✓" : "✕"
            });
        }
    });

    // 3. Recent course enrollments
    courses.slice(-5).forEach(course => {
        if (course.students && course.students.length > 0) {
            const studentName = students.find(s => s.id === course.students[course.students.length - 1])?.name;
            if (studentName) {
                activities.push({
                    type: "enrollment",
                    name: studentName,
                    action: `Enrolled in ${course.name}`,
                    time: "Just now",
                    color: "#A855F7", // Purple
                    icon: "📚"
                });
            }
        }
    });

    // ── SORT BY TIME (most recent first) ──
    activities.sort((a, b) => {
        const timeOrder = { "Just now": 0, "Today": 1 };
        return (timeOrder[a.time] || 2) - (timeOrder[b.time] || 2);
    });

    // ── LIMIT TO 10 MOST RECENT ──
    const recentActivities = activities.slice(0, 10);

    // ── RENDER ACTIVITIES ──
    const feedContainer = document.querySelector("#activityFeed");
    if (!feedContainer) return;

    feedContainer.innerHTML = "";

    if (recentActivities.length === 0) {
        feedContainer.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <p>No recent activity</p>
            </div>
        `;
        return;
    }

    recentActivities.forEach(activity => {
        const activityEl = document.createElement("div");
        activityEl.className = "activity-item";
        activityEl.innerHTML = `
            <div class="activity-dot" style="background-color: ${activity.color};"></div>
            <div class="activity-content">
                <div class="activity-name">${activity.name}</div>
                <div class="activity-action">${activity.action}</div>
                <div class="activity-time">${activity.time}</div>
            </div>
        `;
        feedContainer.appendChild(activityEl);
    });

    console.log("✅ Activity feed updated:", recentActivities.length + " activities");
}
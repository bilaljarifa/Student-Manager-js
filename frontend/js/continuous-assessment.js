export function getContinuousAverage(grades) {
    if (grades.length === 0) return 0;
    const total = grades.reduce((sum, g) => sum + g.score, 0);
    return (total / grades.length).toFixed(2);
}

export function getGradesByType(grades) {
    return {
        tp: grades.filter(g => g.subject.includes("TP")),
        td: grades.filter(g => g.subject.includes("TD")),
        practical: grades.filter(g => g.subject.includes("Practical")),
        other: grades.filter(g => !g.subject.includes("TP") && !g.subject.includes("TD") && !g.subject.includes("Practical"))
    };
}
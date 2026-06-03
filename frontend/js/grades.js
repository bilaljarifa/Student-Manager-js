// ═══════════════════════════════════════════════════════════════════
// GRADES CALCULATION FUNCTIONS
// ═══════════════════════════════════════════════════════════════════

/**
 * FUNCTION: getAverage
 * PURPOSE: Calculate the average of an array of grades
 * INPUT: array of numbers [85, 90, 78]
 * OUTPUT: number (rounded to 2 decimals) → 84.33
 * USAGE: getAverage([85, 90, 78]) → 84.33
 */
export function getAverage(grades) {
    // Handle empty grades array
    if (!grades || grades.length === 0) return 0;

    // Sum all grades
    const total = grades.reduce((sum, grade) => {
        const score = typeof grade === "object" ? grade.score : grade;
        return sum + (isNaN(score) ? 0 : score);
    }, 0);

    // Return average rounded to 2 decimals
    const average = total / grades.length;
    return parseFloat(average.toFixed(2));
}

// ─────────────────────────────────────────────────────────────────

/**
 * FUNCTION: getStatus
 * PURPOSE: Return a professional status category based on average grade
 * INPUT: number (85) or string ("85")
 * OUTPUT: string ("Excellent" | "Good" | "Satisfactory" | "Needs Improvement")
 * 
 * GRADING SCALE:
 * ├─ 90-100: Excellent (A) — Outstanding performance
 * ├─ 80-89:  Good (B)      — Above average performance
 * ├─ 70-79:  Satisfactory (C) — Meets expectations
 * ├─ 60-69:  Needs Improvement (D) — Below average
 * └─ 0-59:   Failing (F)   — Does not meet minimum standards
 * 
 * USAGE: getStatus(85) → "Good"
 */
export function getStatus(average) {
    // Convert to number if string
    const avg = typeof average === "string" ? parseFloat(average) : average;

    // Handle invalid input
    if (isNaN(avg)) return "No Data";

    // Return status based on grade ranges (professional scale)
    if (avg >= 90) return "Excellent";
    if (avg >= 80) return "Good";
    if (avg >= 70) return "Satisfactory";
    if (avg >= 60) return "Needs Improvement";
    return "Failing";
}

// ─────────────────────────────────────────────────────────────────

/**
 * FUNCTION: getGradeColor
 * PURPOSE: Return color code for status badge (for UI)
 * INPUT: string ("Excellent" | "Good" | etc)
 * OUTPUT: hex color code
 * USAGE: Used in student dashboard/reports for color-coded badges
 */
export function getGradeColor(status) {
    const colors = {
        "Excellent": "#10B981",           // Green
        "Good": "#3B82F6",                 // Blue
        "Satisfactory": "#F59E0B",         // Amber
        "Needs Improvement": "#EF4444",    // Red
        "Failing": "#991B1B",              // Dark Red
        "No Data": "#6B7280"               // Gray
    };
    return colors[status] || "#6B7280";
}

// ─────────────────────────────────────────────────────────────────

/**
 * FUNCTION: getStatusBadge
 * PURPOSE: Return formatted status badge with emoji + text
 * INPUT: number (average grade)
 * OUTPUT: string with emoji + status
 * USAGE: getStatusBadge(85) → "⭐ Good"
 */
export function getStatusBadge(average) {
    const status = getStatus(average);
    const badges = {
        "Excellent": "⭐ Excellent",
        "Good": "✅ Good",
        "Satisfactory": "📊 Satisfactory",
        "Needs Improvement": "⚠️ Needs Improvement",
        "Failing": "❌ Failing",
        "No Data": "❓ No Data"
    };
    return badges[status] || status;
}

// ─────────────────────────────────────────────────────────────────

/**
 * FUNCTION: calculateGPA
 * PURPOSE: Calculate GPA on 4.0 scale (professional standard)
 * INPUT: number (average grade 0-100)
 * OUTPUT: number (0.0 - 4.0)
 * 
 * CONVERSION:
 * ├─ 90-100 → 4.0
 * ├─ 80-89  → 3.0
 * ├─ 70-79  → 2.0
 * ├─ 60-69  → 1.0
 * └─ 0-59   → 0.0
 */
export function calculateGPA(average) {
    const avg = typeof average === "string" ? parseFloat(average) : average;
    
    if (isNaN(avg)) return 0.0;
    if (avg >= 90) return 4.0;
    if (avg >= 80) return 3.0;
    if (avg >= 70) return 2.0;
    if (avg >= 60) return 1.0;
    return 0.0;
}
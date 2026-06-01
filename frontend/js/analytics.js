// ── GRADE DISTRIBUTION ANALYTICS ──────────────────────

export function getGradeDistribution(students) {
  // Range: A (90-100), B (80-89), C (70-79), D (60-69), F (<60)
  const distribution = {
    A: 0,  // 90-100
    B: 0,  // 80-89
    C: 0,  // 70-79
    D: 0,  // 60-69
    F: 0   // 0-59
  };

  students.forEach(student => {
    if (student.grades.length === 0) return;

    const scores = student.grades.map(g => g.score);
    const average = scores.reduce((sum, s) => sum + s, 0) / scores.length;

    if (average >= 90) distribution.A++;
    else if (average >= 80) distribution.B++;
    else if (average >= 70) distribution.C++;
    else if (average >= 60) distribution.D++;
    else distribution.F++;
  });

  return distribution;
}

export function getStudentPerformanceStats(students) {
  let totalStudents = students.length;
  let studentsWithGrades = 0;
  let totalGrades = 0;
  let sumAllGrades = 0;

  students.forEach(student => {
    if (student.grades.length > 0) {
      studentsWithGrades++;
      const scores = student.grades.map(g => g.score);
      totalGrades += scores.length;
      sumAllGrades += scores.reduce((sum, s) => sum + s, 0);
    }
  });

  const averageGrade = totalGrades > 0 ? (sumAllGrades / totalGrades).toFixed(2) : 0;

  return {
    totalStudents,
    studentsWithGrades,
    averageGrade,
    totalGrades
  };
}
// reports-ui.js

export function renderReportsSummary(container, report) {
    const html = `
    <div class="reports-grid">
      
      <div class="report-card">
        <h3>📊 System Overview</h3>
        <div class="stat-item">
          <span>Total Students</span>
          <span class="stat-value">${report.students}</span>
        </div>
        <div class="stat-item">
          <span>Total Teachers</span>
          <span class="stat-value">${report.teachers}</span>
        </div>
        <div class="stat-item">
          <span>Total Courses</span>
          <span class="stat-value">${report.courses}</span>
        </div>
        <div class="stat-item">
          <span>Total Exams</span>
          <span class="stat-value">${report.exams}</span>
        </div>
      </div>

      <div class="report-card">
        <h3>💰 Payment Status</h3>
        <div class="stat-item">
          <span>Paid</span>
          <span class="stat-value" style="color: #10B981;">${report.paymentStats.paid}</span>
        </div>
        <div class="stat-item">
          <span>Pending</span>
          <span class="stat-value" style="color: #EF4444;">${report.paymentStats.pending}</span>
        </div>
        <div class="stat-item">
          <span>Paid %</span>
          <span class="stat-value">${Math.round((report.paymentStats.paid / report.paymentStats.total) * 100)}%</span>
        </div>
      </div>

      <div class="report-card">
        <h3>📝 Exam Status</h3>
        <div class="stat-item">
          <span>Upcoming Exams</span>
          <span class="stat-value" style="color: #3B82F6;">${report.examStats.upcoming}</span>
        </div>
        <div class="stat-item">
          <span>Completed Exams</span>
          <span class="stat-value" style="color: #8B5CF6;">${report.examStats.completed}</span>
        </div>
      </div>

      <div class="report-card">
        <h3>📅 Attendance</h3>
        <div class="stat-item">
          <span>Overall Attendance %</span>
          <span class="stat-value" style="color: #10B981;">${report.overallAttendance}%</span>
        </div>
      </div>

    </div>

    <div class="reports-details">
      
      <div class="report-section">
        <h3>Students by Class</h3>
        <table class="report-table">
          <thead>
            <tr>
              <th>Class</th>
              <th>Count</th>
            </tr>
          </thead>
          <tbody>
            ${Object.entries(report.studentsByClass).map(([cls, count]) => `
              <tr>
                <td>${cls}</td>
                <td><strong>${count}</strong></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

      <div class="report-section">
        <h3>Teachers by Department</h3>
        <table class="report-table">
          <thead>
            <tr>
              <th>Department</th>
              <th>Count</th>
            </tr>
          </thead>
          <tbody>
            ${Object.entries(report.teachersByDept).map(([dept, count]) => `
              <tr>
                <td>${dept}</td>
                <td><strong>${count}</strong></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

      <div class="report-section">
        <h3>Students Enrolled per Course</h3>
        <table class="report-table">
          <thead>
            <tr>
              <th>Course Code</th>
              <th>Students</th>
            </tr>
          </thead>
          <tbody>
            ${Object.entries(report.studentsPerCourse).map(([code, count]) => `
              <tr>
                <td>${code}</td>
                <td><strong>${count}</strong></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

    </div>
  `;

    container.innerHTML = html;
}
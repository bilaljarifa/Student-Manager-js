# 🎓 Student Manager

A full-featured, browser-based School Management System built with **Vanilla HTML, CSS & JavaScript** on the frontend and a lightweight **Node.js + Express + MongoDB** backend.

---

## 📸 Overview

Student Manager is a comprehensive school administration tool that lets **Admins** and **Teachers** manage students, grades, attendance, classes, courses, exams, and analytics — all from a clean, modern web interface.

---

## ✨ Features

| Module | Capabilities |
|---|---|
| 🔐 **Authentication** | Role-based login (Admin & Teacher) with localStorage session |
| 📊 **Dashboard** | Live stats: total students, paid/pending payments, passing/failing counts, payment chart |
| 👨‍🎓 **Students** | Add, edit, delete, search, filter by class, sort by name/payment, bulk delete, CSV export, pagination |
| 📝 **Grades** | Add/edit/delete grades per subject, filter by class & student, score validation (0–100) |
| 👨‍🏫 **Teachers** | Full CRUD for teachers with department, contract status, class assignments & course workload |
| 🏫 **Classes** | Create class divisions, assign teachers, track enrolled students |
| 📋 **Exams** | Schedule exams with name, subject, class, date, time & total marks |
| 📅 **Attendance** | Mark attendance as Present / Absent / Late / Excused per student per date |
| 📚 **Courses** | Create courses with code, description, credits, semester & teacher assignment; enroll students |
| 📈 **Reports** | Summary reports with PDF export |
| 📊 **Analytics** | Grade distribution chart, average grade, total students & grades statistics |
| 🌙 **Dark Mode** | Toggle dark/light theme |
| 🔔 **Toast Notifications** | Real-time feedback on every action |

---

## 🗂 Project Structure

```
student-manager/
├── frontend/
│   ├── index.html          # Main app shell (all sections/pages)
│   ├── login.html          # Login page
│   ├── style.css           # All styling (dark mode, responsive, components)
│   └── js/
│       ├── app.js          # Main entry point & section routing
│       ├── login.js        # Auth logic (Admin + Teacher login)
│       ├── storage.js      # LocalStorage CRUD helpers
│       ├── student.js      # Student class model
│       ├── ui.js           # Shared UI utilities
│       ├── validation.js   # Form validation helpers
│       ├── toast.js        # Toast notification service
│       ├── counter.js      # Dashboard counters
│       ├── dashboardStats.js
│       ├── chart.js        # Payment chart
│       ├── analytics.js    # Analytics & grade distribution chart
│       ├── grades.js       # Grades logic
│       ├── grades-page.js  # Grades page rendering
│       ├── teacher.js      # Teacher data logic
│       ├── teacher-ui.js   # Teacher page rendering
│       ├── class.js        # Class data logic
│       ├── class-ui.js     # Class page rendering
│       ├── Classstorage.js # Class-specific localStorage helpers
│       ├── exam.js         # Exam data logic
│       ├── exam-ui.js      # Exam page rendering
│       ├── attendance.js   # Attendance data logic
│       ├── attendance-ui.js# Attendance page rendering
│       ├── course.js       # Course data logic
│       ├── course-ui.js    # Course page rendering
│       ├── enrollment-ui.js# Course enrollment modal
│       ├── reports.js      # Reports data logic
│       ├── reports-ui.js   # Reports page rendering
│       ├── role-management.js # Role-based access control
│       ├── notification.js # Notification data
│       ├── notification-service.js
│       ├── continuous-assessment.js
│       └── welcome.js      # Welcome message & date display
└── backend/
    ├── server.js           # Express server (REST API)
    ├── config.js           # MongoDB connection (Mongoose)
    ├── Student.js          # Student model (ES module class)
    └── package.json
```

---

## 🚀 Getting Started

### Prerequisites

- A modern web browser (Chrome, Firefox, Edge)
- [Node.js](https://nodejs.org/) v16+ *(only needed if using the backend)*

### Option 1 — Frontend Only (Recommended for Quick Start)

The frontend works entirely in the browser using **localStorage** for data persistence. No backend or database is required.

1. Clone or download the project.
2. Open `frontend/login.html` in your browser.
3. Log in with the demo credentials below.

> **Tip:** Use the VS Code [Live Server](https://marketplace.visualstudio.com/items?itemName=ritwickdey.LiveServer) extension for a smoother development experience.

### Option 2 — With Backend (Node.js + MongoDB)

1. Navigate to the backend folder:
   ```bash
   cd backend
   ```

2. Install dependencies:
   ```bash
   npm install express mongoose
   ```

3. Update the MongoDB connection string in `config.js`:
   ```js
   const MONGO_URI = "your-mongodb-atlas-connection-string";
   ```

4. Start the server:
   ```bash
   node server.js
   ```
   The API will be available at `http://localhost:5000`.

---

## 🔐 Authentication

The app uses a simple role-based login system stored in `localStorage`.

| Role | Username | Password | Access |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Full access to all sections |
| **Teacher** | *(set by admin)* | *(set by admin)* | Limited to their assigned students & classes |

Teachers are created by the Admin in the **Teachers** section and can log in with the username and password the Admin assigns.

---

## 🛠 Tech Stack

### Frontend
| Technology | Purpose |
|---|---|
| HTML5 | Page structure & semantic markup |
| Vanilla CSS | Styling, dark mode, responsive design |
| Vanilla JavaScript (ES Modules) | All app logic, routing, data management |
| [Chart.js](https://www.chartjs.org/) | Payment & grade distribution charts |
| [Bootstrap Icons](https://icons.getbootstrap.com/) | UI icons |
| [SheetJS (xlsx)](https://sheetjs.com/) | CSV/Excel export |
| localStorage | Client-side data persistence |

### Backend
| Technology | Purpose |
|---|---|
| Node.js | Runtime |
| Express.js | REST API server |
| Mongoose | MongoDB ODM |
| MongoDB Atlas | Cloud database |

---

## 📡 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/students` | Get all students |
| `GET` | `/api/students/:id` | Get a single student by ID |

> **Note:** The backend is in early development. The frontend currently uses localStorage for all data management.

---

## 📱 Responsive Design

The app is fully responsive with breakpoints for:
- **Desktop** (1024px+) — full sidebar + content layout
- **Tablet** (768px–1024px) — adjusted paddings
- **Mobile** (< 768px) — stacked login layout, collapsible elements

---

## 🗺 Roadmap / Planned Features

- [ ] Full backend integration (replace localStorage with MongoDB)
- [ ] Password hashing for teacher accounts
- [ ] Student self-service portal
- [ ] Email notifications
- [ ] More detailed PDF report generation
- [ ] Student photo upload

---

## 📄 License

This project is licensed under the **ISC License**.

---

## 👤 Author

**Bilal Jarifa**
- GitHub: [@bilaljarifa](https://github.com/bilaljarifa)

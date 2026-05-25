const express = require('express');
const connectDB = require('./config');
const Student = require('./Student');

const app = express();
app.use(express.json());

// Connect to MongoDB
connectDB();

// fake data for now (we'll use MongoDB later)
let students = [
    { id: 1, name: "Bilal", email: "bilal@school.com", studentClass: "Math", paymentStatus: "Paid", grades: [85, 90, 88] },
    { id: 2, name: "Sara", email: "sara@school.com", studentClass: "Science", paymentStatus: "Pending", grades: [92, 88, 95] }
];

// GET all students
app.get('/api/students', (req, res) => {
    res.json(students);
});

// GET single student by id
app.get('/api/students/:id', (req, res) => {
    const student = students.find(student => student.id === parseInt(req.params.id));
    if (!student) {
        return res.status(404).json({ message: "Student not found" });
    }
    res.json(student);
});

const PORT = 5000;
app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
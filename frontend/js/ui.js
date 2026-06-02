import { getAverage, getStatus } from "./grades.js";

export function renderStudents(
  studentsContainer,
  students,
  onDelete,
  onEdit,
  onAddGrade,
  onRowClick,
  startIndex = 0
) {
  studentsContainer.innerHTML = "";

  if (students.length === 0) {
    studentsContainer.innerHTML = `
      <tr>
        <td colspan="9" id="emptyState">
          <p>No students found</p>
          <p>Add your first student to get started!</p>
        </td>
      </tr>
    `;
    return;
  }

  students.forEach((student, index) => {
    const average = getAverage(student.grades);
    const status = getStatus(average);

    const row = document.createElement("tr");
    row.dataset.id = student.id;
    row.dataset.class = student.studentClass;  // FOR DELETE BY CLASS

    row.innerHTML = `
  <td><input type="checkbox" data-student-id="${student.id}"></td>
  <td>${startIndex + index + 1}</td>
  <td>${student.name}</td>
  <td>${student.email}</td>
  <td>${student.studentClass}</td>
  <td>
    <span class="status ${student.paymentStatus === "Paid" ? "paid" : "pending"}">
      ${student.paymentStatus}
    </span>
  </td>
  <td>${average}</td>
  <td>${status}</td>
  <td>
    <button class="editBtn">✏️ Edit</button>
    <button class="deleteBtn">🗑 Delete</button>
  </td>
`;
    // DELETE BUTTON
    row.querySelector(".deleteBtn").addEventListener("click", () => {
      onDelete(student.id);
    });

    // EDIT BUTTON
    row.querySelector(".editBtn").addEventListener("click", () => {
      onEdit(student);
    });

  
    // ROW CLICK — open modal
    row.addEventListener("click", (e) => {
      if (e.target.tagName === "BUTTON") return;
      if (e.target.tagName === "INPUT") return;  // Don't click modal when clicking checkbox
      onRowClick(student);
    });

    studentsContainer.appendChild(row);
  });

  // ATTACH CHECKBOX LISTENERS
  attachCheckboxListeners();
}

// ── CHECKBOX LISTENERS ──────────────────────────────────────────
function attachCheckboxListeners() {
  const checkboxes = document.querySelectorAll('#students input[type="checkbox"]');

  checkboxes.forEach(checkbox => {
    checkbox.addEventListener("change", () => {
      updateDeleteSelectedButton();

      const selectAllCheckbox = document.querySelector("#selectAllStudents");
      const allCheckboxes = document.querySelectorAll('#students input[type="checkbox"]');

      if (allCheckboxes.length > 0) {
        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
      }
    });
  });
}

// ── UPDATE DELETE BUTTON ────────────────────────────────────────
export function updateDeleteSelectedButton() {
  const selectedIds = getSelectedStudentIds();
  const deleteBtn = document.querySelector("#deleteSelectedBtn");

  if (!deleteBtn) return;

  if (selectedIds.length > 0) {
    deleteBtn.style.display = "block";
    deleteBtn.textContent = `🗑 Delete ${selectedIds.length} Selected`;
  } else {
    deleteBtn.style.display = "none";
  }
}

// ── GET SELECTED STUDENT IDS ────────────────────────────────────
export function getSelectedStudentIds() {
  const checkboxes = document.querySelectorAll('#students input[type="checkbox"]:checked');
  return Array.from(checkboxes).map(checkbox => parseInt(checkbox.dataset.studentId));
}
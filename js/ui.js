export function renderStudents(studentsContainer, students, onDelete, onEdit) {

  // clear container
  studentsContainer.innerHTML = "";

  students.forEach((student) => {

    // CREATE elements
    const card = document.createElement("div");
    const name = document.createElement("h3");
    const email = document.createElement("p");
    const studentClass = document.createElement("p");
    const status = document.createElement("span");
    const deleteBtn = document.createElement("button");
    const editBtn = document.createElement("button");

    // FILL with data
    card.classList.add("student-card");
    name.textContent = student.name;
    email.textContent = student.email;
    studentClass.textContent = `Class: ${student.studentClass}`;
    status.textContent = student.paymentStatus;
    status.classList.add("status");
    deleteBtn.textContent = "Delete";
    editBtn.textContent = "Edit";
    card.dataset.id = student.id;

    // payment status color
    if (student.paymentStatus === "Paid") {
      status.classList.add("paid");
    } else {
      status.classList.add("pending");
    }

    // DELETE button click
    deleteBtn.addEventListener("click", () => {
      onDelete(student.id);
    });

    // EDIT button click
    editBtn.addEventListener("click", () => {
      onEdit(student);
    });

    // BUILD the card
    card.appendChild(name);
    card.appendChild(email);
    card.appendChild(studentClass);
    card.appendChild(status);
    card.appendChild(editBtn);
    card.appendChild(deleteBtn);

    // PUT on screen
    studentsContainer.appendChild(card);

  });

}
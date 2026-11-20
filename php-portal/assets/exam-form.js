const examClass = document.querySelector('#exam-class');
const examSubject = document.querySelector('#exam-subject');

if (examClass && examSubject) {
    const filterSubjects = () => {
        for (const option of examSubject.querySelectorAll('[data-classes]')) {
            const available = option.dataset.classes.split(',').includes(examClass.value);
            if (!available && option.selected) examSubject.value = '';
            option.disabled = !available;
            option.hidden = !available;
        }
    };
    examClass.addEventListener('change', filterSubjects);
    filterSubjects();
}

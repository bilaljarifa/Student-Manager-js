const markPresent = document.querySelector('#mark-present');
if (markPresent) {
    markPresent.hidden = false;
    markPresent.addEventListener('click', () => {
        for (const input of markPresent.form.querySelectorAll('input[value="present"]')) {
            input.checked = true;
        }
    });
}

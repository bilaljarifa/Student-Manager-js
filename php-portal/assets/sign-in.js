const passwordToggle = document.querySelector('.password-toggle');
const passwordField = document.querySelector('#password');

if (passwordToggle && passwordField) {
    passwordToggle.hidden = false;
    passwordToggle.addEventListener('click', () => {
        const reveal = passwordField.type === 'password';
        passwordField.type = reveal ? 'text' : 'password';
        passwordToggle.textContent = reveal ? 'Hide' : 'Show';
        passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
}

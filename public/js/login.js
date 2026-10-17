const toggle = document.querySelector('.password-toggle');
const password = document.querySelector('#password');
if (toggle && password) {
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.textContent = visible ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-pressed', String(visible));
    });
}

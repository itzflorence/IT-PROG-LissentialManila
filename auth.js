document.querySelectorAll('.toggle-password').forEach(icon => {
    const targetId = icon.getAttribute('data-target');
    const input = document.getElementById(targetId);

    function showPassword() {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }

    function hidePassword() {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }

    // Mouse
    icon.addEventListener('mousedown', showPassword);
    icon.addEventListener('mouseup', hidePassword);
    icon.addEventListener('mouseleave', hidePassword);

    // Touch devices
    icon.addEventListener('touchstart', showPassword);
    icon.addEventListener('touchend', hidePassword);
    icon.addEventListener('touchcancel', hidePassword);
});
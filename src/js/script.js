const menuBtn = document.getElementById('mobile-menu');
const menu = document.getElementById('menu-admin');
// const mainContent = document.querySelector('body#admin-body main');

menuBtn.addEventListener('click', () => {
    menu.classList.toggle('active');
});
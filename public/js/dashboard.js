const menuButton = document.querySelector('.menu-toggle');
const sidebar = document.querySelector('#sidebar');
const backdrop = document.querySelector('.sidebar-backdrop');
const mobile = window.matchMedia('(max-width: 700px)');

if (menuButton && sidebar && backdrop) {
    document.body.classList.add('js-ready');
    menuButton.hidden = false;

    const setMenu = (open, returnFocus = false) => {
        document.body.classList.toggle('menu-open', open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        backdrop.hidden = !open || !mobile.matches;
        sidebar.inert = mobile.matches && !open;

        if (returnFocus) menuButton.focus();
        if (open) sidebar.querySelector('a').focus();
    };

    menuButton.addEventListener('click', () => {
        setMenu(menuButton.getAttribute('aria-expanded') !== 'true');
    });
    backdrop.addEventListener('click', () => setMenu(false, true));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
            setMenu(false, true);
        }
        if (event.key === 'Tab' && mobile.matches && menuButton.getAttribute('aria-expanded') === 'true') {
            const links = [...sidebar.querySelectorAll('a, button, summary')].filter((item) => item.getClientRects().length > 0);
            const first = links[0];
            const last = links[links.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    sidebar.querySelectorAll('nav a').forEach((link) => {
        link.addEventListener('click', () => {
            sidebar.querySelectorAll('nav a').forEach((item) => {
                item.classList.remove('active');
                item.removeAttribute('aria-current');
            });
            link.classList.add('active');
            link.setAttribute('aria-current', 'location');
            if (mobile.matches) setMenu(false, true);
        });
    });
    mobile.addEventListener('change', () => setMenu(false));
    setMenu(false);
}

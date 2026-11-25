document.addEventListener('DOMContentLoaded', () => {
    const instances = new Set();
    let activeInstance = null;
    let sequence = 0;

    const isEligible = (select) => select instanceof HTMLSelectElement
        && !select.dataset.uiSelectReady
        && !select.multiple
        && select.size <= 1
        && !select.matches('.curriculum-skill-select, .curriculum-indicator-select, [data-native-select]');

    const enhance = (select) => {
        if (!isEligible(select)) return;

        select.dataset.uiSelectReady = 'true';
        const wrapper = document.createElement('div');
        wrapper.className = 'ui-select';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ui-select-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const value = document.createElement('span');
        value.className = 'ui-select-value';
        trigger.append(value);

        const menu = document.createElement('div');
        menu.id = `ui-select-menu-${++sequence}`;
        menu.className = 'ui-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        trigger.setAttribute('aria-controls', menu.id);

        select.before(wrapper);
        wrapper.append(select, trigger);
        document.body.append(menu);
        select.classList.add('ui-select-native');

        let optionButtons = [];

        const close = (restoreFocus = false) => {
            if (menu.hidden) return;
            menu.hidden = true;
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            if (activeInstance === instance) activeInstance = null;
            if (restoreFocus) trigger.focus();
        };

        const positionMenu = () => {
            if (menu.hidden || !trigger.isConnected) return;
            const rect = trigger.getBoundingClientRect();
            const edge = 12;
            const gap = 7;
            const below = Math.max(100, window.innerHeight - rect.bottom - gap - edge);
            const above = Math.max(100, rect.top - gap - edge);
            const openAbove = below < 210 && above > below;
            const available = openAbove ? above : below;
            const width = Math.min(Math.max(rect.width, 220), window.innerWidth - edge * 2);
            const left = Math.min(Math.max(edge, rect.left), window.innerWidth - width - edge);

            menu.style.width = `${width}px`;
            menu.style.maxHeight = `${Math.min(360, available)}px`;
            menu.style.left = `${left}px`;
            menu.style.top = openAbove
                ? `${Math.max(edge, rect.top - Math.min(menu.scrollHeight, available) - gap)}px`
                : `${Math.min(window.innerHeight - edge, rect.bottom + gap)}px`;
        };

        const focusOption = (direction = 0) => {
            const enabled = optionButtons.filter((button) => !button.disabled);
            if (!enabled.length) return;
            const current = enabled.indexOf(document.activeElement);
            const selected = enabled.findIndex((button) => button.classList.contains('is-selected'));
            const base = current >= 0 ? current : Math.max(selected, 0);
            const next = direction === 0
                ? base
                : (base + direction + enabled.length) % enabled.length;
            enabled[next].focus();
        };

        const choose = (option) => {
            if (option.disabled) return;
            select.value = option.value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            refresh();
            close(true);
        };

        const createOptionButton = (option, groupDisabled = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ui-select-option';
            button.textContent = option.textContent.trim();
            button.disabled = option.disabled || groupDisabled;
            button.classList.toggle('is-selected', option.selected);
            button.classList.toggle('is-placeholder', option.value === '');
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', String(option.selected));
            button.addEventListener('click', () => choose(option));
            button.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    focusOption(event.key === 'ArrowDown' ? 1 : -1);
                } else if (event.key === 'Home' || event.key === 'End') {
                    event.preventDefault();
                    const enabled = optionButtons.filter((item) => !item.disabled);
                    (event.key === 'Home' ? enabled[0] : enabled.at(-1))?.focus();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    close(true);
                } else if (event.key === 'Tab') {
                    close();
                }
            });
            optionButtons.push(button);
            return button;
        };

        const renderOptions = () => {
            menu.replaceChildren();
            optionButtons = [];

            [...select.children].forEach((child) => {
                if (child instanceof HTMLOptGroupElement) {
                    const group = document.createElement('section');
                    group.className = 'ui-select-group';
                    const label = document.createElement('strong');
                    label.className = 'ui-select-group-label';
                    label.textContent = child.label;
                    group.append(label);
                    [...child.children].forEach((option) => {
                        if (!option.hidden) group.append(createOptionButton(option, child.disabled));
                    });
                    menu.append(group);
                } else if (child instanceof HTMLOptionElement && !child.hidden) {
                    menu.append(createOptionButton(child));
                }
            });

            if (!optionButtons.length) {
                const empty = document.createElement('p');
                empty.className = 'ui-select-empty';
                empty.textContent = 'No hay opciones disponibles.';
                menu.append(empty);
            }
        };

        const refresh = () => {
            const selected = select.selectedOptions[0];
            value.textContent = selected?.textContent.trim() || 'Selecciona una opción';
            trigger.disabled = select.disabled;
            wrapper.classList.toggle('is-disabled', select.disabled);
            wrapper.classList.toggle('is-placeholder', !select.value);
            wrapper.classList.remove('is-invalid');
            trigger.setAttribute('aria-required', String(select.required));
            renderOptions();
            if (!menu.hidden) positionMenu();
        };

        const open = () => {
            if (trigger.disabled) return;
            if (activeInstance && activeInstance !== instance) activeInstance.close();
            refresh();
            activeInstance = instance;
            menu.hidden = false;
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            positionMenu();
            requestAnimationFrame(() => {
                positionMenu();
                menu.querySelector('.ui-select-option.is-selected:not(:disabled)')?.scrollIntoView({ block: 'nearest' });
            });
        };

        const toggle = () => menu.hidden ? open() : close();
        const instance = {
            select,
            wrapper,
            menu,
            close,
            refresh,
            contains: (target) => wrapper.contains(target) || menu.contains(target),
            destroy: () => {
                optionObserver.disconnect();
                menu.remove();
                instances.delete(instance);
                if (activeInstance === instance) activeInstance = null;
            },
        };

        trigger.addEventListener('click', toggle);
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (menu.hidden) open();
                requestAnimationFrame(() => focusOption(event.key === 'ArrowDown' ? 1 : -1));
            } else if (event.key === 'Escape' && !menu.hidden) {
                event.preventDefault();
                close();
            }
        });
        select.addEventListener('change', refresh);
        select.addEventListener('invalid', () => {
            wrapper.classList.add('is-invalid');
            requestAnimationFrame(() => trigger.focus());
        });

        const optionObserver = new MutationObserver(refresh);
        optionObserver.observe(select, {
            attributes: true,
            attributeFilter: ['disabled', 'required'],
            childList: true,
            subtree: true,
        });

        instances.add(instance);
        refresh();
    };

    const scan = (root) => {
        if (root instanceof HTMLSelectElement) enhance(root);
        if (root instanceof Element || root instanceof Document) {
            root.querySelectorAll('select').forEach(enhance);
        }
    };

    document.addEventListener('click', (event) => {
        if (activeInstance && !activeInstance.contains(event.target)) activeInstance.close();
    });
    document.addEventListener('reset', () => {
        window.setTimeout(() => instances.forEach((instance) => instance.refresh()));
    }, true);
    window.addEventListener('resize', () => activeInstance && activeInstance.refresh());
    window.addEventListener('scroll', (event) => {
        if (activeInstance && !activeInstance.menu.contains(event.target)) {
            const rect = activeInstance.wrapper.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) activeInstance.close();
            else activeInstance.refresh();
        }
    }, true);

    const documentObserver = new MutationObserver((records) => {
        records.forEach((record) => record.addedNodes.forEach((node) => scan(node)));
        instances.forEach((instance) => {
            if (!instance.select.isConnected) instance.destroy();
        });
    });
    documentObserver.observe(document.body, { childList: true, subtree: true });
    scan(document);
});

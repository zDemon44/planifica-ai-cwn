document.addEventListener('DOMContentLoaded', () => {
    if (window.flatpickr) {
        window.flatpickr.localize(window.flatpickr.l10ns.es);

        document.querySelectorAll('[data-calendar]').forEach((input) => {
            window.flatpickr(input, {
                altInput: true,
                altFormat: 'd/m/Y',
                dateFormat: 'Y-m-d',
                allowInput: true,
                disableMobile: true,
                monthSelectorType: 'dropdown',
                minDate: input.min || null,
                maxDate: input.max || null,
            });
        });
    }

    document.querySelectorAll('[data-suspension-form]').forEach((form) => {
        const type = form.querySelector('[data-suspension-type]');
        const endField = form.querySelector('[data-end-date-field]');
        const endInput = endField?.querySelector('input');
        const startLabel = form.querySelector('[data-start-date-label]');

        if (!type || !endField || !endInput || !startLabel) return;

        const updateType = () => {
            const isRange = type.value === 'rango';
            endField.hidden = !isRange;
            endInput.required = isRange;
            startLabel.textContent = isRange ? 'Fecha inicial *' : 'Fecha *';
            form.classList.toggle('is-single-day', !isRange);

            if (!isRange) {
                if (endInput._flatpickr) endInput._flatpickr.clear();
                else endInput.value = '';
            }
        };

        type.addEventListener('change', updateType);
        updateType();
    });
});

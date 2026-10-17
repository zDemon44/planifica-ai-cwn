document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-schedule-form]');
    if (!form) return;
    const blocks = form.querySelector('[data-blocks]');
    const template = document.querySelector('[data-block-template]');
    const section = form.querySelector('#seccion_id');
    const recessNote = form.querySelector('[data-recess-note]');
    const updateRecess = () => {
        const recess = section?.options[section.selectedIndex]?.dataset.recreo;
        if (!recessNote) return;
        recessNote.hidden = !recess;
        recessNote.textContent = recess ? `Recreo institucional: ${recess}. No agregues clases dentro de esta franja.` : '';
    };
    const renumber = () => blocks.querySelectorAll('[data-block]').forEach((block, index) => {
        block.querySelectorAll('[name], [data-name]').forEach((field) => {
            const key = field.dataset.name || field.name.match(/\[([^\]]+)\]$/)?.[1];
            if (key) field.name = `bloques[${index}][${key}]`;
        });
        block.querySelector('[data-remove-block]').disabled = blocks.children.length === 1;
    });
    form.querySelector('[data-add-block]').addEventListener('click', () => {
        blocks.append(template.content.cloneNode(true));
        renumber();
    });
    blocks.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-block]');
        if (button && blocks.children.length > 1) {
            button.closest('[data-block]').remove();
            renumber();
        }
    });
    section?.addEventListener('change', updateRecess);
    updateRecess();
    renumber();
});

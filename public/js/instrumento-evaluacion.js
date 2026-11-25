/**
 * Cuadro del instrumento de evaluación en la planificación guardada:
 * cuenta las marcas de cada columna en la fila «Totales».
 */
document.addEventListener('change', event => {
    const cuadro = event.target.closest?.('.instrument-mark')?.closest('[data-instrumento]');
    if (!cuadro) return;

    const marcadas = [...cuadro.querySelectorAll('.instrument-mark:checked')];
    cuadro.querySelectorAll('[data-total-nivel]').forEach(total => {
        total.textContent = marcadas.length
            ? marcadas.filter(marca => marca.value === total.dataset.totalNivel).length
            : '';
    });
});

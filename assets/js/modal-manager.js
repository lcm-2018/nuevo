/**
 * Sistema de gestión de z-index para modales anidados de Bootstrap 5
 * Asegura que cada modal y su backdrop tengan el z-index correcto
 */
(function () {
    'use strict';

    let modalCount = 0;
    const BASE_MODAL_ZINDEX = 1050;
    const BASE_BACKDROP_ZINDEX = 1040;
    const ZINDEX_INCREMENT = 20;

    // Escuchar cuando se muestra un modal
    document.addEventListener('show.bs.modal', function (event) {
        const modal = event.target;
        modalCount++;

        // Calcular z-index para este modal
        const modalZIndex = BASE_MODAL_ZINDEX + (modalCount * ZINDEX_INCREMENT);
        const backdropZIndex = BASE_BACKDROP_ZINDEX + (modalCount * ZINDEX_INCREMENT);

        // Aplicar z-index al modal
        modal.style.zIndex = modalZIndex;

        // Guardar el índice en el modal para usarlo al ocultar
        modal.setAttribute('data-modal-index', modalCount);

        // Esperar a que el backdrop se cree
        setTimeout(function () {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            const lastBackdrop = backdrops[backdrops.length - 1];

            if (lastBackdrop) {
                lastBackdrop.style.zIndex = backdropZIndex;
                lastBackdrop.setAttribute('data-backdrop-index', modalCount);
            }
        }, 10);
    });

    // Escuchar cuando se oculta un modal
    document.addEventListener('hidden.bs.modal', function (event) {
        const modal = event.target;
        const modalIndex = parseInt(modal.getAttribute('data-modal-index') || '0');

        // Buscar y eliminar el backdrop correspondiente
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function (backdrop) {
            const backdropIndex = parseInt(backdrop.getAttribute('data-backdrop-index') || '0');
            if (backdropIndex === modalIndex) {
                backdrop.remove();
            }
        });

        // Decrementar el contador solo si es el último modal
        if (modalIndex === modalCount) {
            modalCount--;
        }

        // Si no quedan modales abiertos, resetear el contador
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
            modalCount = 0;

            // Limpiar cualquier backdrop restante
            const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
            remainingBackdrops.forEach(function (backdrop) {
                backdrop.remove();
            });

            // Restaurar scroll del body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    });

    // Asegurar que el body tenga la clase modal-open cuando hay modales
    document.addEventListener('shown.bs.modal', function () {
        if (!document.body.classList.contains('modal-open')) {
            document.body.classList.add('modal-open');
        }
    });
})();

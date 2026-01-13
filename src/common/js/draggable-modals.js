/**
 * Draggable Modals for Bootstrap 5.3
 * Permite arrastrar modales de Bootstrap usando el elemento h5 del header como handle.
 * 
 * Este script reemplaza la funcionalidad de jQuery UI draggable para Bootstrap 5.
 * Se aplica automáticamente a todos los modales que contengan un h5 en el header.
 * Soporta contenido cargado dinámicamente vía AJAX.
 * 
 * Uso: Solo incluir este script después de Bootstrap 5
 */

(function () {
    'use strict';

    /**
     * Clase que maneja la funcionalidad de arrastrar un modal
     */
    class DraggableModal {
        constructor(modalElement, handleElement) {
            this.modal = modalElement;
            this.modalDialog = modalElement.querySelector('.modal-dialog');
            this.handle = handleElement;

            if (!this.modalDialog || !this.handle) {
                return;
            }

            // Evitar inicialización duplicada en el mismo handle
            if (this.handle.hasAttribute('data-draggable-initialized')) {
                return;
            }
            this.handle.setAttribute('data-draggable-initialized', 'true');

            this.isDragging = false;
            this.startX = 0;
            this.startY = 0;
            this.initialLeft = 0;
            this.initialTop = 0;

            // Bind de métodos para poder remover listeners después
            this.boundOnMouseDown = this.onMouseDown.bind(this);
            this.boundOnMouseMove = this.onMouseMove.bind(this);
            this.boundOnMouseUp = this.onMouseUp.bind(this);
            this.boundOnTouchStart = this.onTouchStart.bind(this);
            this.boundOnTouchMove = this.onTouchMove.bind(this);
            this.boundOnTouchEnd = this.onTouchEnd.bind(this);
            this.boundResetPosition = this.resetPosition.bind(this);

            this.init();
        }

        init() {
            // Aplicar estilos al handle para indicar que es arrastrable
            this.handle.style.cursor = 'move';
            this.handle.style.userSelect = 'none';

            // Eventos del mouse
            this.handle.addEventListener('mousedown', this.boundOnMouseDown);
            document.addEventListener('mousemove', this.boundOnMouseMove);
            document.addEventListener('mouseup', this.boundOnMouseUp);

            // Eventos táctiles para dispositivos móviles
            this.handle.addEventListener('touchstart', this.boundOnTouchStart, { passive: false });
            document.addEventListener('touchmove', this.boundOnTouchMove, { passive: false });
            document.addEventListener('touchend', this.boundOnTouchEnd);

            // Resetear posición cuando el modal se oculta
            this.modal.addEventListener('hidden.bs.modal', this.boundResetPosition);
        }

        /**
         * Obtener la posición actual del modal dialog
         */
        getCurrentPosition() {
            const style = window.getComputedStyle(this.modalDialog);
            const matrix = new DOMMatrix(style.transform);
            return {
                left: matrix.m41 || 0,
                top: matrix.m42 || 0
            };
        }

        /**
         * Evento mousedown - Inicia el arrastre
         */
        onMouseDown(e) {
            // Solo botón izquierdo del mouse
            if (e.button !== 0) return;

            e.preventDefault();
            this.startDrag(e.clientX, e.clientY);
        }

        /**
         * Evento touchstart - Inicia el arrastre en dispositivos táctiles
         */
        onTouchStart(e) {
            if (e.touches.length !== 1) return;

            e.preventDefault();
            const touch = e.touches[0];
            this.startDrag(touch.clientX, touch.clientY);
        }

        /**
         * Inicia el proceso de arrastre
         */
        startDrag(clientX, clientY) {
            this.isDragging = true;
            this.startX = clientX;
            this.startY = clientY;

            const currentPos = this.getCurrentPosition();
            this.initialLeft = currentPos.left;
            this.initialTop = currentPos.top;

            // Agregar clase para indicar que se está arrastrando
            this.modal.classList.add('modal-dragging');
            this.modalDialog.style.transition = 'none';
        }

        /**
         * Evento mousemove - Mueve el modal
         */
        onMouseMove(e) {
            if (!this.isDragging) return;

            e.preventDefault();
            this.moveDrag(e.clientX, e.clientY);
        }

        /**
         * Evento touchmove - Mueve el modal en dispositivos táctiles
         */
        onTouchMove(e) {
            if (!this.isDragging) return;
            if (e.touches.length !== 1) return;

            e.preventDefault();
            const touch = e.touches[0];
            this.moveDrag(touch.clientX, touch.clientY);
        }

        /**
         * Realiza el movimiento del modal
         */
        moveDrag(clientX, clientY) {
            const deltaX = clientX - this.startX;
            const deltaY = clientY - this.startY;

            const newLeft = this.initialLeft + deltaX;
            const newTop = this.initialTop + deltaY;

            this.modalDialog.style.transform = `translate(${newLeft}px, ${newTop}px)`;
        }

        /**
         * Evento mouseup - Termina el arrastre
         */
        onMouseUp() {
            this.endDrag();
        }

        /**
         * Evento touchend - Termina el arrastre en dispositivos táctiles
         */
        onTouchEnd() {
            this.endDrag();
        }

        /**
         * Finaliza el proceso de arrastre
         */
        endDrag() {
            if (!this.isDragging) return;

            this.isDragging = false;
            this.modal.classList.remove('modal-dragging');
            this.modalDialog.style.transition = '';
        }

        /**
         * Resetea la posición del modal al centro
         */
        resetPosition() {
            this.modalDialog.style.transform = '';
        }
    }

    /**
     * Busca y retorna el elemento handle dentro de un modal
     * @param {HTMLElement} modal - El elemento modal
     * @returns {HTMLElement|null} - El elemento handle o null
     */
    function findHandle(modal) {
        // Prioridad de búsqueda (de más específico a más general):
        // 1. h5 o h6 dentro de .rounded-top (estructura específica del proyecto)
        // 2. h5 o h6 con clase .card-header (estructura de cards)
        // 3. h5 o h6 dentro de .modal-header (estructura tradicional)
        // 4. .modal-title dentro de .modal-header
        // 5. Primer h5 o h6 dentro de .modal-body (fallback genérico)
        return modal.querySelector('.rounded-top h5, .rounded-top h6') ||
            modal.querySelector('h5.card-header, h6.card-header') ||
            modal.querySelector('.modal-header h5, .modal-header h6') ||
            modal.querySelector('.modal-header .modal-title') ||
            modal.querySelector('.modal-body h5, .modal-body h6');
    }

    /**
     * Intenta inicializar el arrastre en un modal
     * @param {HTMLElement} modal - El elemento modal
     */
    function tryInitDraggable(modal) {
        const handle = findHandle(modal);
        if (handle && !handle.hasAttribute('data-draggable-initialized')) {
            new DraggableModal(modal, handle);
            return true;
        }
        return false;
    }

    /**
     * Configura la observación de un modal para contenido dinámico
     * @param {HTMLElement} modal - El elemento modal
     */
    function setupModalObserver(modal) {
        // Si ya tiene observer, no crear otro
        if (modal.hasAttribute('data-draggable-observer')) {
            return;
        }
        modal.setAttribute('data-draggable-observer', 'true');

        const observer = new MutationObserver(function (mutations, obs) {
            if (tryInitDraggable(modal)) {
                obs.disconnect();
                modal.removeAttribute('data-draggable-observer');
            }
        });

        observer.observe(modal, {
            childList: true,
            subtree: true
        });

        // Desconectar el observer cuando el modal se cierra
        modal.addEventListener('hidden.bs.modal', function () {
            observer.disconnect();
            modal.removeAttribute('data-draggable-observer');
        }, { once: true });
    }

    /**
     * Inicializa la funcionalidad de arrastre para todos los modales
     */
    function initDraggableModals() {
        // Escuchar cuando se muestra cualquier modal
        document.addEventListener('shown.bs.modal', function (e) {
            const modal = e.target;

            // Intentar inicializar inmediatamente
            if (!tryInitDraggable(modal)) {
                // Si no se pudo inicializar, configurar observer
                setupModalObserver(modal);
            }

            // También intentar con delays para contenido AJAX
            // que puede cargarse después del shown event
            setTimeout(function () {
                tryInitDraggable(modal);
            }, 100);

            setTimeout(function () {
                tryInitDraggable(modal);
            }, 300);

            setTimeout(function () {
                tryInitDraggable(modal);
            }, 500);
        });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDraggableModals);
    } else {
        initDraggableModals();
    }

    // Exponer la clase y función para uso manual si es necesario
    window.DraggableModal = DraggableModal;
    window.initModalDraggable = tryInitDraggable;

})();

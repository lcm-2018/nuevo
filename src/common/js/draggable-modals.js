/**
 * Draggable Modals for Bootstrap 5.3
 * Permite arrastrar modales de Bootstrap usando el elemento h5 del header como handle.
 * 
 * Este script reemplaza la funcionalidad de jQuery UI draggable para Bootstrap 5.
 * Se aplica automáticamente a todos los modales que contengan un h5 en el .modal-header
 * 
 * Uso: Solo incluir este script después de Bootstrap 5
 */

(function () {
    'use strict';

    /**
     * Clase que maneja la funcionalidad de arrastrar un modal
     */
    class DraggableModal {
        constructor(modalElement) {
            this.modal = modalElement;
            this.modalDialog = modalElement.querySelector('.modal-dialog');
            this.handle = modalElement.querySelector('.modal-header h5, .modal-header .modal-title');

            if (!this.modalDialog || !this.handle) {
                return;
            }

            this.isDragging = false;
            this.startX = 0;
            this.startY = 0;
            this.initialLeft = 0;
            this.initialTop = 0;

            this.init();
        }

        init() {
            // Aplicar estilos al handle para indicar que es arrastrable
            this.handle.style.cursor = 'move';
            this.handle.style.userSelect = 'none';

            // Eventos del mouse
            this.handle.addEventListener('mousedown', this.onMouseDown.bind(this));
            document.addEventListener('mousemove', this.onMouseMove.bind(this));
            document.addEventListener('mouseup', this.onMouseUp.bind(this));

            // Eventos táctiles para dispositivos móviles
            this.handle.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: false });
            document.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
            document.addEventListener('touchend', this.onTouchEnd.bind(this));

            // Resetear posición cuando el modal se oculta
            this.modal.addEventListener('hidden.bs.modal', this.resetPosition.bind(this));
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
     * Inicializa la funcionalidad de arrastre para todos los modales
     */
    function initDraggableModals() {
        // Escuchar cuando se muestra cualquier modal
        document.addEventListener('shown.bs.modal', function (e) {
            const modal = e.target;

            // Solo inicializar si no tiene ya la instancia
            if (!modal.draggableModalInstance) {
                modal.draggableModalInstance = new DraggableModal(modal);
            }
        });

        // También inicializar modales que ya podrían estar en el DOM
        document.querySelectorAll('.modal').forEach(function (modal) {
            if (!modal.draggableModalInstance) {
                // Se inicializará cuando se muestre el modal
            }
        });
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDraggableModals);
    } else {
        initDraggableModals();
    }

    // Exponer la clase para uso manual si es necesario
    window.DraggableModal = DraggableModal;

})();

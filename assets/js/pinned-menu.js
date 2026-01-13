/**
 * Menú Fijo/Anclado (Pinned Menu)
 * 
 * Este script maneja la funcionalidad de anclar/desanclar el menú lateral.
 * El estado se persiste en localStorage para mantener la preferencia del usuario.
 * También guarda el estado de los collapses abiertos para restaurarlos al navegar.
 */

(function () {
    'use strict';

    const STORAGE_KEY_PINNED = 'menuPinned';
    const STORAGE_KEY_COLLAPSES = 'menuCollapses';
    const STORAGE_KEY_SCROLL = 'menuScrollPosition';

    /**
     * Inicializa el sistema de menú anclado
     */
    function initPinnedMenu() {
        const btnPin = document.getElementById('btnPinMenu');
        const offcanvas = document.getElementById('offcanvasNavbar');

        if (!btnPin || !offcanvas) {
            console.warn('PinnedMenu: Elementos del menú no encontrados');
            return;
        }

        // Restaurar estado guardado
        restoreMenuState();

        // Event listener para el botón de anclar
        btnPin.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            togglePinnedState();
        });

        // Guardar estado de collapses al hacer clic en ellos
        document.querySelectorAll('#offcanvasNavbar [data-bs-toggle="collapse"]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                // Pequeño delay para que Bootstrap actualice las clases
                setTimeout(saveCollapsesState, 100);
            });
        });

        // Guardar posición de scroll del menú
        const offcanvasBody = offcanvas.querySelector('.offcanvas-body');
        if (offcanvasBody) {
            offcanvasBody.addEventListener('scroll', debounce(function () {
                localStorage.setItem(STORAGE_KEY_SCROLL, offcanvasBody.scrollTop);
            }, 200));
        }

        // Guardar estado antes de navegar
        document.querySelectorAll('#offcanvasNavbar a[href]:not([href="javascript:void(0)"])').forEach(function (link) {
            link.addEventListener('click', function () {
                saveCollapsesState();
            });
        });
    }

    /**
     * Alterna el estado de anclado del menú
     */
    function togglePinnedState() {
        const body = document.body;
        const isPinned = body.classList.contains('menu-pinned');
        const offcanvas = document.getElementById('offcanvasNavbar');

        if (isPinned) {
            // Desanclar: quitar clase y ocultar offcanvas
            body.classList.remove('menu-pinned');
            localStorage.setItem(STORAGE_KEY_PINNED, 'false');

            // Rehabilitar el comportamiento normal del offcanvas
            enableOffcanvasNormalBehavior(offcanvas);

            // Ocultar el offcanvas removiendo la clase show
            offcanvas.classList.remove('show');

            updatePinButtonTooltip(false);
        } else {
            // Anclar: agregar clase
            body.classList.add('menu-pinned');
            localStorage.setItem(STORAGE_KEY_PINNED, 'true');

            // Asegurar que el offcanvas esté visible
            offcanvas.classList.add('show');

            // Deshabilitar el focus trap del offcanvas para permitir interacción con inputs
            disableOffcanvasFocusTrap(offcanvas);

            // Restaurar collapses abiertos
            restoreCollapsesState();

            updatePinButtonTooltip(true);
        }
    }
    /**
     * Deshabilita el focus trap del offcanvas cuando está anclado
     * Esto permite que los inputs fuera del offcanvas sean interactivos
     */
    function disableOffcanvasFocusTrap(offcanvas) {
        // Remover el atributo aria-modal que indica que es un modal
        offcanvas.removeAttribute('aria-modal');
        offcanvas.removeAttribute('role');

        // Remover el tabindex negativo que puede causar problemas
        offcanvas.removeAttribute('tabindex');

        // Agregar atributos data-bs para prevenir comportamiento modal
        offcanvas.setAttribute('data-bs-backdrop', 'false');
        offcanvas.setAttribute('data-bs-keyboard', 'false');
        offcanvas.setAttribute('data-bs-focus', 'false');

        // NO destruir la instancia de Bootstrap ya que causa errores
        // En su lugar, solo ocultamos el backdrop y limpiamos el body

        // Asegurar que el body no tenga estilos de overflow restrictivos
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.classList.remove('offcanvas-open');

        // Remover cualquier propiedad que Bootstrap haya agregado al body
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        // Remover el backdrop si existe
        const backdrop = document.querySelector('.offcanvas-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    /**
     * Rehabilita el comportamiento normal del offcanvas cuando se desancla
     */
    function enableOffcanvasNormalBehavior(offcanvas) {
        // Restaurar atributos necesarios para el offcanvas
        offcanvas.setAttribute('tabindex', '-1');

        // Mantener los atributos data-bs originales del HTML
        offcanvas.setAttribute('data-bs-scroll', 'true');
        offcanvas.setAttribute('data-bs-backdrop', 'false');
        offcanvas.setAttribute('data-bs-focus', 'false');
    }

    /**
     * Restaura el estado del menú desde localStorage
     */
    function restoreMenuState() {
        const isPinned = localStorage.getItem(STORAGE_KEY_PINNED) === 'true';
        const offcanvas = document.getElementById('offcanvasNavbar');

        // Solo aplicar en pantallas grandes
        if (window.innerWidth < 992) {
            return;
        }

        if (isPinned) {
            document.body.classList.add('menu-pinned');
            offcanvas.classList.add('show');

            // Usar un delay más largo para asegurar que Bootstrap esté completamente inicializado
            // antes de deshabilitar el focus trap
            setTimeout(function () {
                disableOffcanvasFocusTrap(offcanvas);

                // Remover backdrop si existe
                const backdrop = document.querySelector('.offcanvas-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }, 100);

            // Restaurar collapses después de un pequeño delay
            setTimeout(restoreCollapsesState, 150);

            // Restaurar posición de scroll
            setTimeout(restoreScrollPosition, 200);

            updatePinButtonTooltip(true);
        }
    }

    /**
     * Guarda el estado de los collapses abiertos
     */
    function saveCollapsesState() {
        const openCollapses = [];
        document.querySelectorAll('#offcanvasNavbar .collapse.show').forEach(function (collapse) {
            if (collapse.id) {
                openCollapses.push(collapse.id);
            }
        });
        localStorage.setItem(STORAGE_KEY_COLLAPSES, JSON.stringify(openCollapses));
    }

    /**
     * Restaura el estado de los collapses
     */
    function restoreCollapsesState() {
        const storedCollapses = localStorage.getItem(STORAGE_KEY_COLLAPSES);
        if (!storedCollapses) return;

        try {
            const openCollapses = JSON.parse(storedCollapses);
            openCollapses.forEach(function (collapseId) {
                const collapse = document.getElementById(collapseId);
                if (collapse) {
                    collapse.classList.add('show');

                    // Actualizar aria-expanded del toggle
                    const toggle = document.querySelector('[data-bs-toggle="collapse"][href="#' + collapseId + '"]') ||
                        document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#' + collapseId + '"]');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                }
            });
        } catch (e) {
            console.warn('PinnedMenu: Error restaurando collapses', e);
        }
    }

    /**
     * Restaura la posición de scroll del menú
     */
    function restoreScrollPosition() {
        const scrollTop = localStorage.getItem(STORAGE_KEY_SCROLL);
        if (scrollTop) {
            const offcanvasBody = document.querySelector('#offcanvasNavbar .offcanvas-body');
            if (offcanvasBody) {
                offcanvasBody.scrollTop = parseInt(scrollTop, 10);
            }
        }
    }

    /**
     * Actualiza el tooltip del botón de pin
     */
    function updatePinButtonTooltip(isPinned) {
        const btnPin = document.getElementById('btnPinMenu');
        if (btnPin) {
            btnPin.title = isPinned ? 'Desanclar menú' : 'Anclar menú';
        }
    }

    /**
     * Función debounce para optimizar eventos
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Manejar cambios de tamaño de ventana
     */
    function handleResize() {
        const isPinned = localStorage.getItem(STORAGE_KEY_PINNED) === 'true';
        const offcanvas = document.getElementById('offcanvasNavbar');

        if (window.innerWidth < 992) {
            // En móviles, quitar el estado anclado visualmente pero mantener la preferencia
            document.body.classList.remove('menu-pinned');
            offcanvas.classList.remove('show');
            // Restaurar comportamiento normal para móviles
            enableOffcanvasNormalBehavior(offcanvas);
        } else if (isPinned) {
            // En escritorio, restaurar si estaba anclado
            document.body.classList.add('menu-pinned');
            offcanvas.classList.add('show');

            // Deshabilitar focus trap para permitir interacción con inputs
            disableOffcanvasFocusTrap(offcanvas);

            // Remover backdrop
            const backdrop = document.querySelector('.offcanvas-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    }

    // Event listener para cambio de tamaño
    window.addEventListener('resize', debounce(handleResize, 250));

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPinnedMenu);
    } else {
        initPinnedMenu();
    }

})();

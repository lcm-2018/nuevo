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

    // Variable para controlar si el menú está fijado
    let isMenuPinned = false;

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

        // Configurar el interceptor de focus ANTES de restaurar el estado
        setupFocusInterceptor();

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
     * Configura un interceptor para prevenir que Bootstrap capture el focus
     * cuando el menú está en modo fijado
     */
    function setupFocusInterceptor() {
        // Interceptar el evento focusin a nivel de documento
        // Bootstrap usa este evento para su focus trap
        document.addEventListener('focusin', function (e) {
            if (!isMenuPinned) return;

            const offcanvas = document.getElementById('offcanvasNavbar');
            if (!offcanvas) return;

            // Si el focus está fuera del offcanvas, permitirlo
            // (Bootstrap normalmente lo redirigiría al offcanvas)
            if (!offcanvas.contains(e.target)) {
                // Detener la propagación para que Bootstrap no lo capture
                e.stopImmediatePropagation();
            }
        }, true); // Usar capture phase para ejecutar antes que Bootstrap

        // También interceptar keydown para prevenir que ESC cierre el menú fijado
        document.addEventListener('keydown', function (e) {
            if (!isMenuPinned) return;

            // Prevenir que ESC cierre el menú cuando está fijado
            if (e.key === 'Escape') {
                const offcanvas = document.getElementById('offcanvasNavbar');
                if (offcanvas && offcanvas.classList.contains('show')) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                }
            }
        }, true);
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
            isMenuPinned = false;

            // Ocultar el offcanvas
            offcanvas.classList.remove('show');

            // Restaurar el tabindex
            offcanvas.setAttribute('tabindex', '-1');

            updatePinButtonTooltip(false);
        } else {
            // Anclar: agregar clase
            body.classList.add('menu-pinned');
            localStorage.setItem(STORAGE_KEY_PINNED, 'true');
            isMenuPinned = true;

            // Asegurar que el offcanvas esté visible
            offcanvas.classList.add('show');

            // Limpiar comportamientos de Bootstrap que bloquean inputs
            cleanupForPinnedMode(offcanvas);

            // Restaurar collapses abiertos
            restoreCollapsesState();

            updatePinButtonTooltip(true);
        }
    }

    /**
     * Limpia los estilos y atributos que bloquean la interacción
     * cuando el menú está en modo fijado
     */
    function cleanupForPinnedMode(offcanvas) {
        // Remover atributos ARIA que indican comportamiento modal
        offcanvas.removeAttribute('aria-modal');
        offcanvas.removeAttribute('role');

        // Remover el tabindex para que no capture el focus
        offcanvas.removeAttribute('tabindex');

        // Remover el backdrop si existe
        const backdrop = document.querySelector('.offcanvas-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        // Limpiar estilos del body
        cleanupBodyStyles();
    }

    /**
     * Limpia los estilos que Bootstrap aplica al body
     */
    function cleanupBodyStyles() {
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.body.classList.remove('offcanvas-open');
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
            isMenuPinned = true;
            document.body.classList.add('menu-pinned');
            offcanvas.classList.add('show');

            // Limpiar comportamientos de Bootstrap con un delay
            // para asegurar que Bootstrap haya terminado su inicialización
            setTimeout(function () {
                cleanupForPinnedMode(offcanvas);
            }, 50);

            // Seguir limpiando periódicamente por si Bootstrap reinicializa
            setTimeout(function () {
                cleanupForPinnedMode(offcanvas);
            }, 200);

            setTimeout(function () {
                cleanupForPinnedMode(offcanvas);
            }, 500);

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
            isMenuPinned = false;

            // Restaurar tabindex para móviles
            offcanvas.setAttribute('tabindex', '-1');
        } else if (isPinned) {
            // En escritorio, restaurar si estaba anclado
            document.body.classList.add('menu-pinned');
            offcanvas.classList.add('show');
            isMenuPinned = true;

            // Limpiar comportamientos de Bootstrap
            cleanupForPinnedMode(offcanvas);
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

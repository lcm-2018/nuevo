/**
 * Funciones para el módulo de Nómina Electrónica
 * @version 2.0
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    initSoporteNE();
});

/**
 * Inicializa los eventos para los botones de soporte de nómina electrónica
 */
function initSoporteNE() {
    bindSoporteNEButtons();
}

/**
 * Vincula eventos click a los botones de soporte NE
 */
function bindSoporteNEButtons() {
    document.querySelectorAll('.soporteNE').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const idSoporte = this.getAttribute('data-id') || this.getAttribute('value');
            if (idSoporte) {
                verSoporteNE(idSoporte);
            }
        });
    });
}

/**
 * Descarga o visualiza el soporte de nómina electrónica en PDF
 * @param {string|number} idSoporte - ID del soporte a descargar
 */
function verSoporteNE(idSoporte) {
    const host = document.getElementById('host')?.value || '';

    if (!idSoporte) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se especificó el soporte a descargar',
            confirmButtonColor: '#16a085'
        });
        return;
    }

    // Mostrar indicador de carga
    showLoading(true);

    // Construir URL del reporte
    const urlReporte = `${host}/src/nomina/electronica/php/soporte_pdf.php?id=${idSoporte}`;

    // Abrir en nueva ventana/pestaña
    const ventana = window.open(urlReporte, '_blank');

    // Ocultar loading después de un momento
    setTimeout(function () {
        showLoading(false);

        if (!ventana || ventana.closed || typeof ventana.closed === 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Ventana bloqueada',
                html: 'El navegador bloqueó la ventana emergente.<br>Por favor, permita las ventanas emergentes para este sitio.',
                confirmButtonColor: '#16a085'
            });
        }
    }, 1500);
}

/**
 * Muestra u oculta el overlay de carga
 * @param {boolean} show - true para mostrar, false para ocultar
 */
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        if (show) {
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
        } else {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
        }
    }
}

/**
 * Función para recargar los DataTables cuando sea necesario
 */
function reloadDataTables() {
    document.querySelectorAll('.dataTableMes').forEach(function (table) {
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().ajax.reload();
        }
    });
}

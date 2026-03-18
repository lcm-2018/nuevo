/**
 * funciones.js - Módulo de Certificaciones de Nómina
 * Flujo:
 *   1. El usuario busca y selecciona un TERCERO (vía Awesomplete de common.js)
 *   2. Define rango de fechas INICIA / TERMINA
 *   3. Hace clic en el botón PDF o Word de la tarjeta deseada
 *   4. Se valida y se abre el reporte en nueva pestaña vía POST
 */

'use strict';

// ============================================================
// INICIALIZAR AWESOMPLETE PARA EL CAMPO TERCERO
// Usa la misma infraestructura que el resto del sistema
// (consultaTercero.php → getTerceros → [{label, id, cedula}])
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const inputTercero = document.getElementById('buscaTercero');
    if (!inputTercero) return;

    const hostUrl = ValueInput('host');

    inicializarAwesomplete(
        inputTercero,
        hostUrl + '/src/common/php/controladores/consultaTercero.php',
        '#id_tercero',   // selector del hidden que guarda el id
        true             // incluirCedula = true → muestra "NOMBRE - NIT"
    );

    // Si el usuario borra el campo, limpiar el id oculto
    inputTercero.addEventListener('input', function () {
        if (this.value.trim() === '') {
            _limpiarTercero();
        }
    });
});

// ============================================================
// CLICK EN LOS BOTONES PDF / WORD de las tarjetas
// ============================================================
const gridCerts = document.getElementById('gridCerts');
if (gridCerts) {
    gridCerts.addEventListener('click', function (event) {
        const btn = event.target.closest('.btn-generar-cert');
        if (!btn) return;

        // --- Validar tercero ---
        const id_tercero = document.getElementById('id_tercero')?.value || '0';
        const buscaTercero = document.getElementById('buscaTercero');
        if (id_tercero === '0' || id_tercero === '') {
            if (buscaTercero) {
                buscaTercero.classList.add('is-invalid');
                buscaTercero.focus();
            }
            mjeError('Tercero requerido', 'Debe buscar y seleccionar un tercero de la lista.');
            return;
        }
        if (buscaTercero) buscaTercero.classList.remove('is-invalid');

        // --- Validar fechas ---
        const fechaInicia = document.getElementById('fechaInicia')?.value || '';
        const fechaTermina = document.getElementById('fechaTermina')?.value || '';

        if (!fechaInicia) {
            document.getElementById('fechaInicia')?.classList.add('is-invalid');
            mjeError('Fecha requerida', 'Ingrese la fecha de inicio.');
            return;
        }
        document.getElementById('fechaInicia')?.classList.remove('is-invalid');

        if (!fechaTermina) {
            document.getElementById('fechaTermina')?.classList.add('is-invalid');
            mjeError('Fecha requerida', 'Ingrese la fecha de terminación.');
            return;
        }
        document.getElementById('fechaTermina')?.classList.remove('is-invalid');

        if (fechaTermina < fechaInicia) {
            document.getElementById('fechaTermina')?.classList.add('is-invalid');
            mjeError('Rango inválido', 'La fecha de terminación no puede ser anterior a la de inicio.');
            return;
        }
        document.getElementById('fechaTermina')?.classList.remove('is-invalid');

        // --- Datos de la tarjeta ---
        const id_cert = btn.dataset.idCert;
        const formato = btn.dataset.formato;   // 'pdf' o 'word'

        // --- Enviar a la ruta del reporte ---
        _abrirReporte(id_cert, formato, id_tercero, fechaInicia, fechaTermina);
    });
}

// ============================================================
// Abrir reporte vía POST en nueva pestaña
// ============================================================
function _abrirReporte(id_cert, formato, id_tercero, fecha_ini, fecha_fin) {
    const rutas = {
        1: '../php/reportes/form220.php',
        2: '../php/reportes/laboral_basica.php',
        3: '../php/reportes/laboral_detallada.php',
        4: '../php/reportes/laboral_nomina.php',
    };

    const ruta = rutas[parseInt(id_cert)];
    if (!ruta) {
        mjeError('Error', 'No existe el reporte para este tipo de certificado.');
        return;
    }

    ImprimirReporte(ruta, {
        id_tercero: id_tercero,
        fecha_ini: fecha_ini,
        fecha_fin: fecha_fin,
        id_cert: id_cert,
        formato: formato     // 'pdf' o 'word' — el reporte decide la cabecera de respuesta
    });
}

// ============================================================
// Limpiar selección de tercero
// ============================================================
function _limpiarTercero() {
    const hidId = document.getElementById('id_tercero');
    if (hidId) hidId.value = '0';
}

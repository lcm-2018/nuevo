const plus = '<i class="fa-solid fa-plus fa-lg"></i>';
const modales = document.querySelectorAll('.modal');
const opCaracterJS = Number(document.getElementById('opc_caracter_js').value);
const opPtoJS = Number(document.getElementById('opc_pto_js').value);
const setdom =
    "<'row mb-1'<'col-sm-4'l><'col-sm-4 text-center btn-reg'B><'col-sm-4'f>>" +
    "t" +
    "<'row'<'col-sm-6'i><'col-sm-6 d-flex justify-content-end'p>>";

function mostrarOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('d-none');
}

function ocultarOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('d-none');
}

function NumberMiles(inputElement) {
    inputElement.addEventListener('focus', function (e) {
        e.target.select();
    });

    inputElement.addEventListener('input', function (e) {
        e.target.value = e.target.value
            .replace(/\D/g, "")
            .replace(/([0-9])([0-9]{2})$/, "$1.$2")
            .replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ",");
    });
}

function CleanNumber(valor) {
    valor = valor.replace(/\./g, '');
    valor = valor.replace(',', '.');
    return valor;
}

const MuestraError = (campo, mensaje) => {
    var input = document.getElementById(campo);
    input.focus();
    input.classList.add('is-invalid');
    mjeError(mensaje);
};

const LimpiaInvalid = () => {
    var inputs = document.querySelectorAll('.is-invalid');
    inputs.forEach(function (input) {
        input.classList.remove('is-invalid');
    });
};

const ValueInput = (campo) => {
    var input = document.getElementById(campo);
    if (!input) {
        console.log(`Input con ID '${campo}' no encontrado.`);
    }
    return input.value;
};

const InputValue = (campo, valor) => {
    document.getElementById(campo).value = valor;
};
const Serializa = (...formularios) => {
    const datos = new FormData();

    formularios.forEach((formularioID) => {
        const form = document.getElementById(formularioID);

        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');

            for (const input of inputs) {
                if (!input.name) continue;
                if (input.type === 'radio' && input.checked) {
                    datos.append(input.name, input.value);
                } else if (input.type !== 'radio') {
                    if (input.type === 'file') {
                        const file = input.files[0];
                        datos.append(input.name, file);
                    } else if (input.type === 'password') {
                        datos.append(input.name, hex_sha512(input.value));
                    } else if (input.type === 'checkbox') {
                        if (input.checked) {
                            datos.append(input.name, input.value);
                        } else {
                            console.log(`Checkbox con nombre '${input.name}' no está seleccionado.`);
                        }
                    } else {
                        datos.append(input.name, input.value);
                    }
                }
            }
        } else {
            //console.log(`Formulario con ID '${formularioID}' no encontrado.`);
        }
    });

    return datos;
};

const SendPost = async (url, data) => {
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: data
        });
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return await response.json();
    } catch (error) {
        console.error('Error en la petición:', error);
        return { status: 'error', msg: 'Error en la petición' };
    }
};

/**
 * 
 * @param {string} url Ruta del servidor
 * @param {string} accion Opcion para nuevo o actualizar
 * @param {number} datos  ID de registro o array de datos
 * @param {string} modal Nombre del modal
 * @param {string} body  Nombre del body del modal
 * @param {string} idTam  Nombre del contenedor del modal para cambiar tamaño
 * @param {string} tam Tamaño definido para el modal
 */

const VerFormulario = (url, accion, datos, modal, body, idTam, tam) => {
    var data = new FormData();
    data.append('action', accion);
    if (typeof datos === 'object') {
        for (const key in datos) {
            if (datos.hasOwnProperty(key)) {
                data.append(key, datos[key]);
            }
        }
    } else {
        data.append('id', datos);
    }
    SendPost(url, data).then((response) => {
        if (response.status === 'ok') {
            const dialog = document.getElementById(idTam);
            if (dialog) {
                dialog.classList.remove('modal-lg', 'modal-xl', 'modal-sm', 'modal-md', 'modal-fullscreen');
                if (tam != '') {
                    dialog.classList.add(tam);
                }
                document.getElementById(body).innerHTML = '';
                document.getElementById(body).innerHTML = response.msg;
                const modalInstance = new bootstrap.Modal('#' + modal);
                modalInstance.show();
            } else {
                mjeError('Modal no encontrado');
            }
        } else {
            mjeError(response.msg);
        }
    }).finally(() => {
        ocultarOverlay();
    });
};

const ImprimirReporte = (url, datos) => {
    var form = document.createElement("form");
    form.action = url;
    form.method = "POST";
    form.target = "_blank";
    for (const key in datos) {
        if (datos.hasOwnProperty(key)) {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = datos[key];
            form.appendChild(input);
        }
    }
    document.body.appendChild(form);
    form.submit();
};

const EliminaRegistro = (url, id, tabla, action = 'del') => {
    Swal.fire(Confir).then((result) => {
        if (result.isConfirmed) {
            mostrarOverlay();
            var data = new FormData();
            data.append('action', action);
            if (typeof id === 'object') {
                for (const key in id) {
                    if (id.hasOwnProperty(key)) {
                        data.append(key, id[key]);
                    }
                }
            } else {
                data.append('id', id);
            }
            SendPost(url, data).then((response) => {
                if (response.status === 'ok') {
                    mje('Proceso realizado correctamente!');
                    $("#" + tabla).DataTable().ajax.reload(null, false);
                } else {
                    mjeError('Error!', response.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
        }
    });
}
const Confir = {
    title: "!Confirmar¡, Esta acción no se puede deshacer",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#00994C",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si!",
    cancelButtonText: "NO",
}

const SubmitPost = (url, name, valor) => {
    var form = document.createElement("form");
    form.action = url;
    form.method = "POST";

    var input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = valor;

    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}
/**
* @param { string } selector - Selector de la tabla a inicializar.
* @param { string } urlDatos - URL para obtener los datos de la tabla.
* @param { Array } columnas - Definición de las columnas de la tabla.
* @param { Array } botones - Botones a incluir en la tabla.
* @param { Object } otros - Otras opciones de configuración para DataTable.
* @param { Object } data - Datos adicionales a enviar al servidor function (d) {... }.
* @param { boolean } busca - Indica si se debe habilitar la búsqueda.
* @returns { DataTable } - Instancia de DataTable inicializada.
*/
function crearDataTable(selector, urlDatos, columnas, botones, otros, data = {}, busca = true) {
    const tableElement = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    if (tableElement && !tableElement.parentElement.classList.contains('table-responsive')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        tableElement.parentElement.insertBefore(wrapper, tableElement);
        wrapper.appendChild(tableElement);
    }

    return new DataTable(selector, {
        language: dataTable_es,
        serverSide: true,
        processing: true,
        searching: busca,
        searchDelay: 1000000,
        scrollX: true,
        ajax: {
            url: urlDatos,
            type: 'POST',
            dataType: 'json',
            data: typeof data === 'function' ? data : function (d) {
                return Object.assign(d, data);
            }
        },
        columns: columnas,
        dom:
            "<'row mb-1'<'col-sm-4'l><'col-sm-4 text-center btn-reg'B><'col-sm-4'f>>" +
            "t" +
            "<'row'<'col-sm-6'i><'col-sm-6 d-flex justify-content-end'p>>",
        buttons: botones,
        order: [[0, "desc"]],
        orderCellsTop: true,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'TODO'],
        ],
        ...otros
    });
}

function BuscaDataTable(tabla) {
    const container = tabla.table().container();
    if (!container) {
        console.warn('Contenedor de tabla no disponible aún.');
        return;
    }

    const inputBusqueda = container.querySelector('input[type="search"]');

    if (inputBusqueda) {
        inputBusqueda.removeEventListener('keydown', inputBusqueda._enterListener || (() => { }));

        const listener = function (event) {
            if (event.key === 'Enter') {
                tabla.search(this.value).draw();
            }
        };

        inputBusqueda.addEventListener('keydown', listener);
        inputBusqueda._enterListener = listener;
    }
}

function mje(titulo, html, timer = 2000) {
    Swal.fire({
        title: titulo,
        icon: "success",
        showConfirmButton: true,
        timer: timer,
        html: html,
    });
}

function mjeError(titulo, texto, html, timer = 2000) {
    Swal.fire({
        title: titulo,
        text: texto,
        icon: "error",
        showConfirmButton: true,
        timer: timer,
        html: html,
    });
}

function mjeAlert(titulo, texto, html, timer = 2000) {
    Swal.fire({
        title: titulo,
        text: texto,
        icon: "warning",
        showConfirmButton: true,
        timer: timer,
        html: html,
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const intervalId = setInterval(() => {
        const inputUser = document.getElementById('BuscaUsuario');

        if (inputUser) {
            clearInterval(intervalId);
            inicializarAwesomplete(inputUser, ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', '#id_user', false, null, 'list');
        }
    }, 100);
});

document.addEventListener('DOMContentLoaded', () => {
    var modal = document.getElementById('modalForms');
    if (!modal) {
        modal = document.getElementById('divModalForms');
        if (!modal) {
            return;
        }

    }
    modal.addEventListener('shown.bs.modal', () => {
        const intervalId = setInterval(() => {
            const inputTercero = document.getElementById('buscaTercero');
            const inputUsuario = document.getElementById('buscaUser');
            const inputsRubro = document.querySelectorAll('.buscaRubro');
            const inputCuenta = document.getElementById('buscaCuenta');
            const txtBuscaEmpleado = document.getElementById('txtBuscaEmpleado');

            if (inputTercero) {
                clearInterval(intervalId);
                inicializarAwesomplete(inputTercero, ValueInput('host') + '/src/common/php/controladores/consultaTercero.php', '#id_tercero', true);
            }

            if (inputsRubro.length > 0) {
                clearInterval(intervalId);
                inputsRubro.forEach(input => {
                    const idTarget = input.getAttribute('data-target');
                    const tipoTarget = input.getAttribute('data-tipo-target');
                    inicializarAwesomplete(input, ValueInput('host') + '/src/common/php/controladores/consultaRubro.php', idTarget, false, tipoTarget);
                });
            }

            if (inputCuenta) {
                clearInterval(intervalId);
                inicializarAwesomplete(inputCuenta, ValueInput('host') + '/src/common/php/controladores/consultaCuenta.php', '#idCtaCtb', false, '#tipoCta');
            }

            if (txtBuscaEmpleado) {
                clearInterval(intervalId);
                inicializarAwesomplete(txtBuscaEmpleado, ValueInput('host') + '/src/nomina/horas_extra/php/controladores/horas_extra.php', '#id_empleado', false, null, 'list');
            }

            if (inputUsuario) {
                clearInterval(intervalId);
                inicializarAwesomplete(inputUsuario, ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', '#id_usuario', false, null, 'list');
            }
        }, 100);
    });
});

function inicializarAwesomplete(inputElement, endpoint, idTargetSelector, incluirCedula = false, tipoTargetSelector = null, action = '') {
    const awesomplete = new Awesomplete(inputElement, {
        autoFirst: true,
        minChars: 2
    });

    // Mapa para asociar label con tipo
    const tipoMap = new Map();

    inputElement.addEventListener('input', () => {
        const query = inputElement.value.trim();
        if (query.length < 2) return;

        fetch(`${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ search: query, action: action })
        })
            .then(res => res.json())
            .then(data => {
                const opciones = data.map(item => {
                    const label = incluirCedula ? `${item.label} - ${item.cedula}` : item.label;
                    tipoMap.set(label, item.tipo); // Guardamos tipo por label
                    return {
                        label: label,
                        value: item.id
                    };
                });
                awesomplete.list = opciones;
            })
            .catch(error => console.log(`Error en la búsqueda (${endpoint}):`, error));
    });

    inputElement.addEventListener('awesomplete-selectcomplete', event => {
        const label = event.text.label;
        const value = event.text.value;
        const tipo = tipoMap.get(label); // Obtenemos el tipo desde el mapa

        inputElement.value = label;

        if (idTargetSelector) {
            const idTarget = document.querySelector(idTargetSelector);
            if (idTarget) idTarget.value = value;
        }

        if (tipoTargetSelector) {
            const tipoTarget = document.querySelector(tipoTargetSelector);
            if (tipoTarget && tipo !== undefined) tipoTarget.value = tipo;
        }
    });
}

function FiltraDatos(table) {
    table.ajax.reload(null, false);
}

function LimpiarFiltro(table) {
    const inputs = document.querySelectorAll('#filterRow input, #filterRow select, #filterRow textarea');
    const select = document.querySelectorAll('#filterRow select');
    inputs.forEach(input => input.value = '');
    select.forEach(select => select.value = '1');
    table.ajax.reload(null, false);
}

function CargaCombos(input, combo, id = 0) {
    const data = new FormData();
    data.append('action', combo);
    data.append('id', id);
    SendPost(ValueInput('host') + '/src/common/php/controladores/combos.php', data).then((response) => {
        if (response.status === 'ok') {
            const inputElement = document.getElementById(input);
            inputElement.innerHTML = response.msg;
        } else {
            mjeError('Error!', response.msg);
        }
    });
}

const HiddenInputs = (input) => {
    document.getElementById(input).classList.add('d-none');
}

const ShowInputs = (input) => {
    document.getElementById(input).classList.remove('d-none');
}

function ActivarTab(panelId) {
    const selectorBoton = `[data-bs-target="#${panelId}"]`;
    const botonPestanaEl = document.querySelector(selectorBoton);
    if (botonPestanaEl) {
        const pestanaBootstrap = bootstrap.Tab.getOrCreateInstance(botonPestanaEl);
        pestanaBootstrap.show();
    } else {
        console.error(`Error: No se encontró el botón de la pestaña para el panel con ID "${panelId}".`);
    }
}

const DiasRangoFechas = (fechaInicio, fechaFin, input) => {
    var dias = 0;
    if (fechaInicio && fechaFin && fechaFin != '' && fechaInicio != '' && fechaInicio <= fechaFin) {
        dias = Math.ceil((new Date(fechaFin) - new Date(fechaInicio)) / (1000 * 60 * 60 * 24)) + 1;
    }
    document.getElementById(input).value = dias;
}

const SelectAll = (maestro) => {
    const estado = maestro.checked ? 'checked' : '';
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = estado;
    });
};

document.addEventListener('click', function (e) {
    if (e.target && e.target.closest('table.table')) {
        const fila = e.target.closest('tr');
        if (fila) {
            // Quitar la clase de todas las filas
            const filas = fila.parentElement.querySelectorAll('tr');
            filas.forEach(f => {
                f.classList.remove('row-selected');
            });
            // Agregar la clase solo a la fila clickeada
            fila.classList.add('row-selected');
        }
    }
});

const vigencia = document.getElementById('slcVigencia');
if (vigencia) {
    vigencia.addEventListener('change', function () {
        var valor = this.value;
        var texto = this.options[this.selectedIndex].text;
        var url = ValueInput('host') + '/src/usuarios/general/php/vigencia.php';
        var data = new FormData();
        data.append('texto', texto);
        data.append('id', valor);
        SendPost(url, data).then((response) => {
            if (response.status === 'ok') {
                mje('Vigencia cambiada a ' + texto);
                setTimeout(() => {
                    location.reload();
                }, 200);
            } else {
                mjeError('Error!', response.msg);
            }
        });

    });
}

document.querySelectorAll('.opcion_personalizado').forEach(function (element) {
    element.addEventListener('click', function () {
        const id = this.getAttribute('txt_id_opcion');

        const form = document.createElement('form');
        form.action = ValueInput('host') + '/src/inf_generales/php/inf_personalizados/index.php';
        form.method = 'post';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_opcion';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
});

var verifica_vacio = function (objeto, msg = "") {
    var error = 0;
    if (objeto.val().trim() == "") {
        objeto.addClass('is-invalid');
        objeto.focus();
        error = 1;
        if (msg != "") {
            mjeError('Error!', msg);
        }
    }
    return error;
};

var verifica_valmax = function (objeto, val = 500, msg = "") {
    var error = 0;
    if (parseInt(objeto.val()) > val) {
        objeto.addClass('is-invalid');
        objeto.focus();
        error = 1;
        if (msg != "") {
            mjeError('Error!', msg);
        }
    }
    return error;
};
var CambiaEstado = (url, id, tabla) => {
    mostrarOverlay();
    var data = new FormData();
    data.append('action', 'estado');
    data.append('id', id);
    SendPost(url, data).then((response) => {
        if (response.status === 'ok') {
            mje('Proceso realizado correctamente!');
            tabla.ajax.reload(null, false);
        } else {
            mjeError('Error!', response.msg);
        }
    }).finally(() => {
        ocultarOverlay();
    });
};

document.addEventListener('keydown', function (event) {
    if (event.key === 'F2') {
        document.querySelectorAll('.hide').forEach(function (element) {
            element.classList.toggle('d-none');
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    var modalElementList = document.querySelectorAll('.modal');
    modalElementList.forEach(function (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            var openModals = document.querySelectorAll('.modal.show');

            const BASE_ZINDEX = 1055;
            const ZINDEX_INCREMENT = 20;
            var newZIndex = BASE_ZINDEX + (openModals.length * ZINDEX_INCREMENT);

            this.style.zIndex = newZIndex;

            var backdrop = document.querySelector('.modal-backdrop.show:last-child');
            if (backdrop) {
                backdrop.style.zIndex = newZIndex - 1;
            }
        });
    });
});

const DownloadFile = (name) => {
    const form = document.createElement('form');
    form.action = ValueInput('host') + '/src/common/xlsx/download_formato.php';
    form.method = 'post';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'nom_file';
    input.value = name;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
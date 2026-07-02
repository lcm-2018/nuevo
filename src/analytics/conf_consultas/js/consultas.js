/* =====================================================
   DATATABLE CONSULTAS
===================================================== */
const peReg = document.querySelector('#peReg')?.value;

const tableConsultas = crearDataTable(
    '#tb_consultas',
    'listar_consultas.php',
    [
        { data: 'id_consulta' },
        { data: 'titulo_consulta' },
        { data: 'tipo_bdatos' },
        { data: 'tipo_informe' },
        { data: 'tipo_consulta' },
        { data: 'tipo_acceso' },
        { data: 'estado' },
        { data: 'botones' }
    ],
    (peReg == 1 ? [{
        text: plus,
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Nuevo registro',
        action: function () {
            mostrarOverlay();
            
            fetch('frm_reg_consultas.php', { method: 'POST' })
            .then(r => r.text())
            .then(html => {
                const tam = document.getElementById('divTamModalForms');
                tam.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
                tam.classList.add('modal-xxl');
                document.getElementById('divForms').innerHTML = html;
                
                // Eliminar script si existe para evitar duplicados
                let oldScript = document.getElementById('script_modal');
                if (oldScript) { oldScript.remove();  }
                // Crear nuevo script 
                let script = document.createElement('script');
                script.id = 'script_modal';
                script.src =  '../js/consulta_param.js?v=' + new Date().getTime();

                // Cuando termine de cargar abrir el modal
                script.onload = function () {
                    const modal = new bootstrap.Modal(document.getElementById('divModalForms'));
                    modal.show();    
                    cargarTablaConsultaParam();
                };
                document.body.appendChild(script);
            })
            .finally(() => ocultarOverlay());
        }
    }] : []), {
        pageLength: 10,
        order: [[0, 'desc']],
        searching: false,
        ajax: {
            url: 'listar_consultas.php',
            type: 'POST',
            data: function (d) {
                d.titulo = document.getElementById('txt_titulo_filtro').value;
                d.estado = document.getElementById('sl_estado_filtro').value;
            }
        },
        columnDefs: [
            { className: 'text-wrap', targets: [1, 2] },
            { orderable: false, targets: 6 }
        ]
    }
);

// Cuando la tabla termina de cargar
tableConsultas.on('init', function () {
    BuscaDataTable(tableConsultas);
});

//  FILTROS
document.getElementById('btn_buscar_filtro').addEventListener('click', () => {
    tableConsultas.ajax.reload(null, false);
});

document.querySelectorAll('.filtro').forEach(input => {
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            tableConsultas.ajax.reload(null, false);
        }
    });
});

document.getElementById('divForms').addEventListener('change', function (event) {
    const sl_tipo_bdatos = event.target.closest('#sl_tipo_bdatos');
    const sl_tipo_acceso = event.target.closest('#sl_tipo_acceso');
    if (!sl_tipo_bdatos && !sl_tipo_acceso) return;

    if (sl_tipo_bdatos){
        let btn_basesdatos = document.getElementById('btn_basesdatos');    
        let sl_tipo_informe = document.getElementById('sl_tipo_informe');
        let sl_tipo_consulta = document.getElementById('sl_tipo_consulta');   
        btn_basesdatos.disabled = (sl_tipo_bdatos.value != '2');
        sl_tipo_informe.disabled = (sl_tipo_bdatos.value != '2');
        sl_tipo_informe.value = '';
        sl_tipo_consulta.disabled = (sl_tipo_bdatos.value != '2');        
        sl_tipo_consulta.value = '';
    }    
    if (sl_tipo_acceso){
        let btn_usuarios = document.getElementById('btn_usuarios');    
        btn_usuarios.disabled = (sl_tipo_acceso.value != '2');
    }    
});

/* =====================================================
   GUARDAR FORMULARIO
===================================================== */
document.getElementById('divForms').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_guardar');
    if (!boton) return;
    event.preventDefault();
    LimpiaInvalid();

    let error = 0;
    let titulo_consulta = document.querySelector('#txt_titulo_consulta');
    let detalle_consulta = document.querySelector('#txt_detalle_consulta');
    let consulta_sql = document.querySelector('#txt_consulta_sql');
    let estado = document.querySelector('#sl_estado');
    let tipo_bdatos = document.querySelector('#sl_tipo_bdatos');
    let tipo_informe = document.querySelector('#sl_tipo_informe');
    let tipo_consulta = document.querySelector('#sl_tipo_consulta');
    let tipo_acceso = document.querySelector('#sl_tipo_acceso');

    error += verifica_vacio(titulo_consulta);
    error += verifica_vacio(detalle_consulta);
    error += verifica_vacio(consulta_sql);    
    error += verifica_vacio(estado);        
    error += verifica_vacio(tipo_bdatos);
    if (tipo_bdatos.value == 2) {
        error += verifica_vacio(tipo_informe);
        error += verifica_vacio(tipo_consulta);
    }
    error += verifica_vacio(tipo_acceso);
        
    if (error > 0) {
        mjeError('Los datos resaltados son obligatorios');
        return;
    }

    mostrarOverlay();
    let data = Serializa('frm_reg_consultas');
    data.append('oper', 'add');

    SendPost('editar_consultas.php', data).then(r => {
        if (r.mensaje === 'ok') {            
            tableConsultas.ajax.reload(null, false);               
            document.querySelector('#id_consulta').value = r.id;
            //bootstrap.Modal.getInstance(document.getElementById('divModalForms')).hide();
            mje('Proceso realizado correctamente');
        } else {
            mjeError(r.mensaje);
        }
    })
    .finally(() => {
        ocultarOverlay();
    });
});

/* ======================================================
   EDITAR / ELIMINAR 
====================================================== */
function editarRegistro(id) {
    mostrarOverlay();
    fetch('frm_reg_consultas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ id })
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalForms');
        tam.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        tam.classList.add('modal-xxl');
        document.getElementById('divForms').innerHTML = html;
        
        // Eliminar script si existe para evitar duplicados
        let oldScript = document.getElementById('script_modal');
        if (oldScript) { oldScript.remove();  }
        // Crear nuevo script 
        let script = document.createElement('script');
        script.id = 'script_modal';
        script.src =  '../js/consulta_param.js?v=' + new Date().getTime();

        // Cuando termine de cargar abrir el modal
        script.onload = function () {
            const modal = new bootstrap.Modal(document.getElementById('divModalForms'));
            modal.show();    
            cargarTablaConsultaParam();
        };
        document.body.appendChild(script);
    })
    .finally(() => ocultarOverlay());
}

function eliminarRegistro(id) {
    Swal.fire({
        title: "¿Está seguro de eliminar el registro?",
        text: "No podrá revertir esta acción",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Si, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {

        if (!result.isConfirmed) return;
        mostrarOverlay();

        fetch('editar_consultas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
            body: new URLSearchParams({id: id, oper: 'del' })
        })
        .then(r => r.json())
        .then(r => {
            if (r.mensaje === 'ok') {
                tableConsultas.ajax.reload(null, false);
                mje("Proceso realizado correctamente");                    
            } else {
                mjeError(r.mensaje);
            }
        })
        .catch(() => {
            mjeError('Ocurrió un error');
        })
        .finally(() => ocultarOverlay());
    });
}        

document.getElementById('tb_consultas').addEventListener('click', function (event) {
    const btnEditar = event.target.closest('.btn_editar');
    const btnEliminar = event.target.closest('.btn_eliminar');

    if (btnEditar) editarRegistro(btnEditar.getAttribute('value'));
    if (btnEliminar) eliminarRegistro(btnEliminar.getAttribute('value'));
});

/* ======================================================
   IMPRIMIR
====================================================== */
document.getElementById('btn_imprime_filtro').addEventListener('click', function () {
    tableConsultas.ajax.reload(null, false);
    mostrarOverlay();
    fetch('imp_consultas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            nombre: document.getElementById('txt_titulo_filtro').value,
            estado: document.getElementById('sl_estado_filtro').value
        })
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalImp');
        tam.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        tam.classList.add('modal-xxl');

        document.getElementById('divImp').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('divModalImp'));
        modal.show();        
    })
    .finally(() => ocultarOverlay());
});

/* ======================================================
   SELECCIONAR BASES DE DATOS
====================================================== */
document.getElementById('divForms').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_basesdatos');
    if (!boton) return;
    mostrarOverlay();

    let id_consulta = document.getElementById('id_consulta').value;
    fetch('frm_reg_consulta_bdatos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded'  },
        body: new URLSearchParams({ id_consulta: id_consulta})
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalReg');
        tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
        tam.classList.add('modal-lg');
        document.getElementById('divFormsReg').innerHTML = html;

        // Eliminar script si existe para evitar duplicados
        let oldScript = document.getElementById('script_modal');
        if (oldScript) { oldScript.remove();  }
        // Crear nuevo script 
        let script = document.createElement('script');
        script.id = 'script_modal';
        script.src =  '../js/consulta_bdatos.js?v=' + new Date().getTime();

        // Cuando termine de cargar abrir el modal
        script.onload = function () {
            const modal = new bootstrap.Modal(document.getElementById('divModalReg'));
            modal.show();
            cargarTablaConsultaBDatos();
        };
        document.body.appendChild(script);
    })
    .finally(() => ocultarOverlay());
});

// Activar / Inactivar base de datos para la consulta
document.getElementById('divFormsReg').addEventListener('click', function (event) {
    const btnActivar = event.target.closest('.btn_activar');
    const btnInactivar = event.target.closest('.btn_inactivar');

    if (!btnActivar && !btnInactivar) return;
    mostrarOverlay();
    
    let id_consulta = document.getElementById('id_consulta').value;
    let id_bdatos = 0, oper = '';
    if (btnActivar){
        id_bdatos=btnActivar.getAttribute('value');
        oper = 'add_bd';
    }
    if (btnInactivar) {
        id_bdatos=btnInactivar.getAttribute('value');
        oper = 'del_bd';
    }

    fetch('editar_consultas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
        body: new URLSearchParams({id_consulta: id_consulta, id_bdatos: id_bdatos, oper: oper })
    })
    .then(r => r.json())
    .then(r => {
        if (r.mensaje === 'ok') {            
            if (oper === 'add_bd') {
                btnActivar.classList.remove('btn_activar');
                btnActivar.classList.add('btn_inactivar');
                btnActivar.title = 'Inactivar';
                const icono = btnActivar.querySelector('span');
                icono.classList.remove('fa-toggle-off','text-secondary');
                icono.classList.add('fa-toggle-on','text-success');        
            } else {
                btnInactivar.classList.remove('btn_inactivar');
                btnInactivar.classList.add('btn_activar');
                btnInactivar.title = 'Activar';
                const icono = btnInactivar.querySelector('span');
                icono.classList.remove('fa-toggle-on','text-success');
                icono.classList.add('fa-toggle-off','text-secondary');
            }
        } else {
            mjeError(r.mensaje);
        }
    })
    .catch(() => {
        mjeError('Ocurrió un error');
    })
    .finally(() => ocultarOverlay());
});

/* ======================================================
SELECCIONAR USUARIOS
====================================================== */
document.getElementById('divForms').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_usuarios');    
    if (!boton) return;
    mostrarOverlay();

    let id_consulta = document.getElementById('id_consulta').value;
    fetch('frm_reg_consulta_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded'  },
        body: new URLSearchParams({ id_consulta: id_consulta})
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalReg');
        tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
        tam.classList.add('modal-lg');
        document.getElementById('divFormsReg').innerHTML = html;

        // Eliminar script si existe para evitar duplicados
        let oldScript = document.getElementById('script_modal');
        if (oldScript) { oldScript.remove();  }
        // Crear nuevo script 
        let script = document.createElement('script');
        script.id = 'script_modal';
        script.src =  '../js/consulta_user.js?v=' + new Date().getTime();

        // Cuando termine de cargar abrir el modal
        script.onload = function () {
            const modal = new bootstrap.Modal(document.getElementById('divModalReg'));
            modal.show();
            cargarTablaConsultaUser();
        };
        document.body.appendChild(script);
    })
    .finally(() => ocultarOverlay());
});

 // Autocompletar Usuarios del Sistema
$('#divFormsReg').on("input", "#txt_usuario_sistema", function () {
    $(this).autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "../../common/php/cargar_usuariosistema_ls.php",
                dataType: "json",
                type: 'POST',
                data: { term: request.term }
            }).done(function (data) {
                response(data);
            });
        },
        minLength: 2,
        select: function (event, ui) {
            $('#id_txt_usuario_sistema').val(ui.item.id);
        }
    });
});

// Guardar usuario seleccionado
document.getElementById('divFormsReg').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_add_usuario');
    if (!boton) return;
    event.preventDefault();
    LimpiaInvalid();

    let error = 0;
    let usuario_sistema = document.querySelector('#txt_usuario_sistema');
    let id_usuario_sistema = document.querySelector('#id_txt_usuario_sistema');
    error += verifica_vacio_2(id_usuario_sistema,usuario_sistema);    
        
    if (error > 0) {
        mjeError('Los datos resaltados son obligatorios');
        return;
    }

    let id_consulta = document.querySelector('#id_consulta_us').value;
    let id_usuario = id_usuario_sistema.value;    

    let data = new FormData();
    data.append('id_consulta', id_consulta);
    data.append('id_usuario', id_usuario);
    data.append('oper', 'add_usr');

    SendPost('editar_consultas.php', data).then(r => {
        if (r.mensaje === 'ok') {            
            cargarTablaConsultaUser();            
            mje('Proceso realizado correctamente');
        } else {
            mjeError(r.mensaje);
        }
    }).finally(() => {
        ocultarOverlay();
    });
});

// Borrar usuario de sistema asignado a la consulta
document.getElementById('divFormsReg').addEventListener('click', function (event) {
    const btnEliminar = event.target.closest('.btn_eliminar_us');
    if (!btnEliminar) return;
    mostrarOverlay();
    
    let id_consulta = document.getElementById('id_consulta_us').value;
    let id_usuario = btnEliminar.getAttribute('value');

    fetch('editar_consultas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
        body: new URLSearchParams({id_consulta: id_consulta, id_usuario: id_usuario, oper: 'del_usr' })
    })
    .then(r => r.json())
    .then(r => {        if (r.mensaje === 'ok') {            
            cargarTablaConsultaUser();            
            mje('Proceso realizado correctamente');
        } else {
            mjeError(r.mensaje);
        }
    })
    .catch(() => {
        mjeError('Ocurrió un error');
    })
    .finally(() => ocultarOverlay());
});

/* ======================================================
PARAMETROS DE CONSULTA
====================================================== */
document.getElementById('divFormsReg').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_guardar_parametro');
    if (!boton) return;
    event.preventDefault();
    LimpiaInvalid();

    let error = 0;
    let parametro = document.querySelector('#txt_parametro');
    let etiqueta = document.querySelector('#txt_etiqueta');    
    let tipo = document.querySelector('#sl_tip_parametro');    

    error += verifica_vacio(parametro);
    error += verifica_vacio(etiqueta);    
    error += verifica_vacio(tipo);        
        
    if (error > 0) {
        mjeError('Los datos resaltados son obligatorios');
        return;
    }

    mostrarOverlay();
    let id_consulta = document.getElementById('id_consulta').value;
    let data = Serializa('frm_reg_parametro');
    data.append('id_consulta', id_consulta);
    data.append('oper', 'add_param');

    SendPost('editar_consultas.php', data).then(r => {
        if (r.mensaje === 'ok') {            
            cargarTablaConsultaParam();
            document.querySelector('#id_parametro').value = r.id;
            bootstrap.Modal.getInstance(document.getElementById('divModalReg')).hide();
            mje('Proceso realizado correctamente');
        } else {
            mjeError(r.mensaje);
        }
    })
    .finally(() => {
        ocultarOverlay();
    });
});

/* ======================================================
   EDITAR / ELIMINAR PARAMETROS
====================================================== */
function editarParametro(id) {
    mostrarOverlay();
    fetch('frm_reg_consulta_param.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ id })
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalReg');
        tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
        tam.classList.add('modal-lg');

        document.getElementById('divFormsReg').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('divModalReg'));
        modal.show(); 
    })
    .finally(() => ocultarOverlay());
}

function eliminarParametro(id) {
    Swal.fire({
        title: "¿Está seguro de eliminar el registro?",
        text: "No podrá revertir esta acción",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Si, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result) => {

        if (!result.isConfirmed) return;
        mostrarOverlay();
        
        fetch('editar_consultas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
            body: new URLSearchParams({id_parametro: id, oper: 'del_param' })
        })
        .then(r => r.json())
        .then(r => {
            if (r.mensaje === 'ok') {
                cargarTablaConsultaParam();
                mje("Proceso realizado correctamente");                    
            } else {
                mjeError(r.mensaje);
            }
        })
        .catch(() => {
            mjeError('Ocurrió un error');
        })
        .finally(() => ocultarOverlay());
    });
}        

document.getElementById('divForms').addEventListener('click', function (event) {
    const btnEditar = event.target.closest('.btn_editar_param');
    const btnEliminar = event.target.closest('.btn_eliminar_param');

    if (btnEditar) editarParametro(btnEditar.getAttribute('value'));
    if (btnEliminar) eliminarParametro(btnEliminar.getAttribute('value'));
});

/* ======================================================
   INSERTAR PARAMETROS EN LA CONSULTA SQL
====================================================== */
let controlActivo = null;
let posicionCursor = 0;

document.getElementById('divForms').addEventListener('click', function (e) {
    if (e.target.id === 'txt_consulta_sql' || e.target.id === 'txt_consulta_sql_group') {
        controlActivo = e.target;
        posicionCursor = e.target.selectionStart;
    }
});

document.getElementById('divForms').addEventListener('keyup', function (e) {
    if (e.target.id === 'txt_consulta_sql' || e.target.id === 'txt_consulta_sql_group') {
        controlActivo = e.target;
        posicionCursor = e.target.selectionStart;
    }
});

function insertarTextoEnControlActivo(texto) {
    if (!controlActivo) {
        mjeError('Seleccione primero una posición en una consulta SQL');
        return;
    }
    let valor = controlActivo.value;
    controlActivo.value = valor.substring(0, posicionCursor) + texto + valor.substring(posicionCursor);
    posicionCursor += texto.length;
    controlActivo.focus();
    controlActivo.selectionStart = posicionCursor;
    controlActivo.selectionEnd = posicionCursor;
}

document.getElementById('divForms').addEventListener('dblclick', function (e) {    
    const fila = e.target.closest('#tb_consulta_param tbody tr');    
    if (!fila) return;
    let table = $('#tb_consulta_param').DataTable();
    let data = table.row(fila).data();    
    if (!data) return;
    let parametro = data.tipo==1 || data.tipo==3 ? `'[@${data.parametro}]'` : `[@${data.parametro}]`;
    insertarTextoEnControlActivo(parametro);
});


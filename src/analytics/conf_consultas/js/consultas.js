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
                const modal = new bootstrap.Modal(document.getElementById('divModalForms'));
                modal.show();
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
        const modal = new bootstrap.Modal(document.getElementById('divModalForms'));
        modal.show();        
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

    let id_consulta = document.getElementById('id_consulta');
    fetch('frm_reg_consulta_bdatos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded'  },
        body: new URLSearchParams({ id: id_consulta.value})
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
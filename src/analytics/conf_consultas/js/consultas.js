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

/* =====================================================
   CUANDO LA TABLA TERMINA DE CARGAR
===================================================== */

tableConsultas.on('init', function () {
    BuscaDataTable(tableConsultas);
});

/* =====================================================
   FILTROS
===================================================== */

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

/* =====================================================
   GUARDAR FORMULARIO
===================================================== */

document.getElementById('divForms').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_guardar');
    
    if (!boton) return;

    event.preventDefault();

    LimpiaInvalid();

    let error = 0;
    error += verifica_vacio(document.querySelector('#txt_titulo_consulta'));
    error += verifica_vacio(document.querySelector('#txt_detalle_consulta'));
    error += verifica_vacio(document.querySelector('#sl_tipo_bdatos'));
    error += verifica_vacio(document.querySelector('#txt_consulta_sql'));
    error += verifica_vacio(document.querySelector('#txt_consulta_sql_group'));
    error += verifica_vacio(document.querySelector('#sl_tipo_informe'));
    error += verifica_vacio(document.querySelector('#sl_tipo_consulta'));
    error += verifica_vacio(document.querySelector('#sl_tipo_acceso'));
    error += verifica_vacio(document.querySelector('#sl_estado'));
    
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

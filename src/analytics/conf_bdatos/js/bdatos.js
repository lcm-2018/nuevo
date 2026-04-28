/* =====================================================
   DATATABLE BDATOS
===================================================== */
const peReg = document.querySelector('#peReg')?.value;

const tableBdatos = crearDataTable(
    '#tb_bdatos',
    'listar_bdatos.php',
    [
        { data: 'id_entidad' },
        { data: 'nombre_entidad' },
        { data: 'descri_entidad' },
        { data: 'ip_servidor' },
        { data: 'nombre_bd' },
        { data: 'puerto_bd' },
        { data: 'estado' },
        { data: 'botones' }
    ],
    (peReg == 1 ? [{
        text: plus,
        className: 'btn btn-success btn-sm shadow',
        titleAttr: 'Nuevo registro',
        action: function () {
            mostrarOverlay();
            
            fetch('frm_reg_bdatos.php', { method: 'POST' })
            .then(r => r.text())
            .then(html => {
                const tam = document.getElementById('divTamModalForms');
                tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
                tam.classList.add('modal-lg');

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
            url: 'listar_bdatos.php',
            type: 'POST',
            data: function (d) {
                d.nombre = document.getElementById('txt_nombre_filtro').value;
                d.estado = document.getElementById('sl_estado_filtro').value;
            }
        },
        columnDefs: [
            { className: 'text-wrap', targets: [1, 2] },
            { orderable: false, targets: 7 }
        ]
    }
);

/* =====================================================
   CUANDO LA TABLA TERMINA DE CARGAR
===================================================== */

tableBdatos.on('init', function () {
    BuscaDataTable(tableBdatos);
});

/* =====================================================
   FILTROS
===================================================== */

document.getElementById('btn_buscar_filtro').addEventListener('click', () => {
    tableBdatos.ajax.reload(null, false);
});

document.querySelectorAll('.filtro').forEach(input => {
    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            tableBdatos.ajax.reload(null, false);
        }
    });
});

/* ======================================================
   EDITAR / ELIMINAR 
====================================================== */

function editarRegistro(id) {
    mostrarOverlay();
    fetch('frm_reg_bdatos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ id })
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalForms');
        tam.classList.remove('modal-sm', 'modal-xl', 'modal-xxl');
        tam.classList.add('modal-lg');

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

        fetch('editar_bdatos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
            body: new URLSearchParams({id: id, oper: 'del' })
        })
        .then(r => r.json())
        .then(r => {
            if (r.mensaje === 'ok') {
                tableBdatos.ajax.reload(null, false);
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

document.getElementById('tb_bdatos').addEventListener('click', function (event) {
    const btnEditar = event.target.closest('.btn_editar');
    const btnEliminar = event.target.closest('.btn_eliminar');

    if (btnEditar) editarRegistro(btnEditar.getAttribute('value'));
    if (btnEliminar) eliminarRegistro(btnEliminar.getAttribute('value'));
});

/* =====================================================
   VERIFICAR CONEXION
===================================================== */

document.getElementById('divForms').addEventListener('click', function (event) {
    const boton = event.target.closest('#btn_testear');
    if (!boton) return;

    event.preventDefault();

    LimpiaInvalid();

    let error = 0;
    error += verifica_vacio(document.querySelector('#txt_ip_servidor'));
    error += verifica_vacio(document.querySelector('#txt_nom_bd'));
    error += verifica_vacio(document.querySelector('#txt_usr_bd'));
    error += verifica_vacio(document.querySelector('#txt_pws_bd'));
    error += verifica_vacio(document.querySelector('#txt_pto_bd'));    
    
    if (error > 0) {
        mjeError('Los datos resaltados son obligatorios');
        return;
    }

    mostrarOverlay();
    let data = Serializa('frm_reg_bdatos');
    data.append('oper', 'test');

    SendPost('editar_bdatos.php', data).then(r => {
        if (r.mensaje === 'ok') {                                    
            mje('Conexión exitosa al Servidor de base de datos MySQL');
        } else {
            mjeError(r.mensaje, 0);
        }
    })
    .finally(() => {
        ocultarOverlay();
    });
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
    error += verifica_vacio(document.querySelector('#txt_nom_entidad'));
    error += verifica_vacio(document.querySelector('#txt_ip_servidor'));
    error += verifica_vacio(document.querySelector('#txt_nom_bd'));
    error += verifica_vacio(document.querySelector('#txt_usr_bd'));
    error += verifica_vacio(document.querySelector('#txt_pws_bd'));
    error += verifica_vacio(document.querySelector('#txt_pto_bd'));
    error += verifica_vacio(document.querySelector('#sl_estado'));
    
    if (error > 0) {
        mjeError('Los datos resaltados son obligatorios');
        return;
    }

    mostrarOverlay();
    let data = Serializa('frm_reg_bdatos');
    data.append('oper', 'add');

    SendPost('editar_bdatos.php', data).then(r => {        
        if (r.mensaje === 'ok') {     
            document.querySelector('#id_entidad').value = r.id;      
            tableBdatos.ajax.reload(null, false);            
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
    tableBdatos.ajax.reload(null, false);
    mostrarOverlay();
    fetch('imp_bdatos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            nombre: document.getElementById('txt_nombre_filtro').value,
            estado: document.getElementById('sl_estado_filtro').value
        })
    })
    .then(r => r.text())
    .then(html => {
        const tam = document.getElementById('divTamModalImp');
        tam.classList.remove('modal-sm', 'modal-lg');
        tam.classList.add('modal-xl');

        document.getElementById('divImp').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('divModalImp'));
        modal.show();        
    })
    .finally(() => ocultarOverlay());
});

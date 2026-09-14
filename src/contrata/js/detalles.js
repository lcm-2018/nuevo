(function ($) {
    "use strict";

    $(document).ready(function () {
        // Inicializar DataTable
        if ($('#table_contratos').length > 0) {
            var tableContratos = $('#table_contratos').DataTable({
                ajax: {
                    url: 'php/controladores/contratos.php',
                    type: 'POST',
                    data: function (d) {
                        d.action = 'list';
                    },
                    dataSrc: function (json) {
                        if (json.status === 'ok') {
                            return json.data;
                        } else {
                            console.error(json.msg);
                            return [];
                        }
                    }
                },
                columns: [
                    { data: 'id_contrato' },
                    { data: 'numero_contrato' },
                    { data: 'id_tercero' },
                    { 
                        data: 'estado',
                        render: function(data, type, row) {
                            let badge = 'secondary';
                            if(data === 'Borrador') badge = 'warning';
                            if(data === 'Aprobado') badge = 'primary';
                            if(data === 'Firmado') badge = 'success';
                            if(data === 'Anulado') badge = 'danger';
                            return `<span class="badge bg-${badge}">${data}</span>`;
                        }
                    },
                    { data: 'fecha_inicio' },
                    { data: 'valor' },
                    {
                        data: null,
                        render: function (data, type, row) {
                            let buttons = `<button class="btn btn-sm btn-outline-info rounded-circle me-1 btn-ver-historial" data-id="${row.id_contrato}" title="Historial y Diff">
                                            <i class="fas fa-history"></i>
                                           </button>`;
                                           
                            buttons += `<button class="btn btn-sm btn-outline-secondary rounded-circle me-1 btn-plantilla" data-id="${row.id_contrato}" title="Usar como Plantilla">
                                            <i class="far fa-copy"></i>
                                        </button>`;
                            
                            if (row.estado !== 'Firmado' && row.estado !== 'Anulado') {
                                buttons += `<button class="btn btn-sm btn-outline-primary rounded-circle me-1 btn-editar" data-id="${row.id_contrato}" title="Editar">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>`;
                                
                                if (row.estado === 'Aprobado') {
                                    buttons += `<button class="btn btn-sm btn-outline-success rounded-circle me-1 btn-firmar" data-id="${row.id_contrato}" title="Firmar Electrónicamente">
                                                    <i class="fas fa-file-signature"></i>
                                                </button>`;
                                }
                            }
                            
                            buttons += `<button class="btn btn-sm btn-outline-danger rounded-circle btn-pdf" data-id="${row.id_contrato}" title="Descargar PDF">
                                            <i class="far fa-file-pdf"></i>
                                        </button>`;
                            
                            return `<div class="d-flex justify-content-center">${buttons}</div>`;
                        }
                    }
                ],
                dom: "<'row mb-1'<'col-sm-4'l><'col-sm-4 text-center btn-reg'B><'col-sm-4'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-6'i><'col-sm-6 d-flex justify-content-end'p>>",
                buttons: [
                    {
                        text: '<i class="fas fa-plus fa-lg"></i>',
                        className: 'btn btn-success btn-sm shadow text-center',
                        titleAttr: 'Agregar Nuevo Contrato',
                        action: function ( e, dt, node, config ) {
                            $('#modalContrato').modal('show');
                        }
                    }
                ],
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "TODO"]],
                order: [[0, 'desc']],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
                    lengthMenu: "_MENU_ filas",
                    paginate: {
                        first: "«",
                        previous: "‹",
                        next: "›",
                        last: "»"
                    }
                },
                pagingType: "full_numbers"
            });
        }
    });

})(jQuery);

// Funciones globales para filtros
function FiltraDatos(tabla) {
    if (tabla) {
        tabla.columns(1).search($('#filter_Nodoc').val());
        tabla.columns(2).search($('#filter_Tercero').val());
        
        let statusVal = $('#filter_Status').val();
        tabla.columns(3).search(statusVal == '2' ? '' : statusVal);
        
        tabla.columns(4).search($('#filter_Fecha').val());
        tabla.columns(5).search($('#filter_Valor').val());
        
        tabla.draw();
    }
}

function LimpiarFiltro(tabla) {
    if (tabla) {
        $('#filter_Nodoc, #filter_Tercero, #filter_Fecha, #filter_Valor').val('');
        $('#filter_Status').val('2');
        tabla.search('').columns().search('').draw();
    }
}

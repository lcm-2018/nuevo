(function ($) {
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    $(document).ready(function () {
        $('#tb_consultas').DataTable({
            language: dataTable_es,
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: 'listar_consultas.php',
                type: 'POST',
                dataType: 'json',
                data: function (data) {
                    data.id = $('#txt_id_filtro').val();
                    data.nombre = $('#txt_titulo_filtro').val();
                }
            },
            columns: [
                { 'data': 'id_consulta' }, //Index=0
                { 'data': 'titulo_consulta' },
                { 'data': 'botones' }
            ],
            order: [
                [1, "ASC"]
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
        });

        $('.bttn-plus-dt span').html('<span class="icon-dt fas fa-plus-circle fa-lg"></span>');
        $('#tb_consultas').wrap('<div class="overflow"/>');
    });

    //Buascar registros
    $('#btn_buscar_filtro').on("click", function () {
        $('#tb_consultas').DataTable().ajax.reload(null, false);
    });

    $('.filtro').keypress(function (e) {
        if (e.keyCode == 13) {
            $('#tb_consultas').DataTable().ajax.reload(null, false);
        }
    });
    
    $('#tb_consultas').on('click', 'tr', function () {
        var tabla = $('#tb_consultas').DataTable();
        var fila = tabla.row(this).data();
        var id = fila.id_consulta;
        
        if (id) {
            $.ajax({
                url: "datos_consulta.php",
                dataType: "json",
                type: 'POST',
                data: { id: id }
            }).done(function (data) {
                $('#txt_id_consulta').val(data.id_consulta);
                $('#txt_detalle_consulta').html(
                    '<b style="color:#800000;">' + data.nom_consulta + '</b><br><br>' +
                    '<b style="color:#000080;">Descripción</b><br>' + data.des_consulta + '<br><br>' +
                    '<b style="color:#000080;">Tipos de Bases de Datos :</b>' + data.tipo_bdatos + '<br>' +
                    '<b style="color:#000080;">Tipo de Informe :</b>' + data.tipo_informe + '<br>' +
                    '<b style="color:#000080;">Tipo de Consulta :</b>' + data.tipo_consulta + '<br>' +
                    '<b style="color:#000080;">Tipo de Acceso :</b>' + data.tipo_acceso
                );
                
                $('#frm_parametros').html('');
                let parametros = data.parametros, i = 0, str = '';
                if (parametros.length > 0) {                    
                    parametros.forEach(function(p){
                        str = '<label class="small">&nbsp;' + p.etiqueta + '</label>';    
                        switch(p.tipo){
                            case '2':
                                str +='<input type="number" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm numberint" title="' + p.descripcion + '"/>';
                                break;
                            case '3':
                                str +='<input type="date" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm" title="' + p.descripcion + '"/>';
                                break;
                            default:
                                str +='<input type="text" id="' + p.id_parametro + '" data-parametro="' + p.parametro + '" data-tipo="' + p.tipo + '" class="form-control form-control-sm" title="' + p.descripcion + '"/>';
                        }
                        $('#frm_parametros').append(str);
                    });
                } 
            });
        }
    });

    /* -------------------------------------------------------
    EJECUTAR LA CONSULTA ANALÍTICA
    --------------------------------------------------------- */
    
    function Parametros(parametro, tipo, valor) {
        this.parametro = parametro;
        this.tipo = tipo;
        this.valor = valor;
    }

    $('#btn_ejecutar_consulta').on("click", function () {      
        if($('#txt_id_consulta').val() == ''){
            mjeError('Debe seleccionar una Consulta Analítica');
            return;
        }       
        $('.is-invalid').removeClass('is-invalid'); 
        $('#dv_resultado').html('');
        let error = 0;
        let parametros = new Array();

        $('#frm_parametros input').each(function () {
            let parametro = $(this)
            if (parametro.attr('type') === 'number' || parametro.attr('type') === 'date'){
                error += verifica_vacio(this);
            }
            let par_val = parametro.val().trim();
            let par_non = parametro.attr('data-parametro');
            let par_tip = parametro.attr('data-tipo');

            par_val = /[a-zA-Z]/.test(par_val) && par_val.includes(",") ? "\'" + par_val.replace(/,/g, "\',\'") + "\'" : par_val;

            let ObjParametro = new Parametros(par_non, par_tip, par_val);
            parametros[parametros.length] = ObjParametro;
        });

        if (error >= 1) {
            mjeError('Los datos resaltados son obligatorios');
            return;
        }    
        $.ajax({
            url: 'ejecutar_consulta.php',
            type: 'POST',
            data: 'id=' + $('#txt_id_consulta').val() + '&parametros=' + JSON.stringify(parametros) 
        }).done(function (data) {
            $('#dv_resultado').html(data);
            $('#divModalEspera').fadeOut(0);
            setTimeout(function () { $('#divModalEspera').modal('hide'); }, 1000);
        }).fail(function () {
            alert('Ocurrió un error');
        });
    });

    //Editar un registro Articulo
    $('#tb_consultas').on('click', '.btn_acceder', function () {
        let id = $(this).attr('value');
        $.post("frm_visu_consulta.php", { id: id }, function (he) {
            $('#divTamModalForms').addClass('modal-xl');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

})(jQuery);
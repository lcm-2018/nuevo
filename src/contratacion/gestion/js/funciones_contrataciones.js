(function ($) {
    //Superponer modales
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function () {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });
    var showError = function (id) {
        $('#' + id).focus();
        $('#e' + id).show();
        setTimeout(function () {
            $('#e' + id).fadeOut(600);
        }, 800);
    };
    var bordeError = function (p) {
        $('#' + p).css("border", "2px solid #F5B7B1");
        $('#' + p).css('box-shadow', '0 0 4px 3px pink');
    };
    var reloadtable = function (nom) {
        $(document).ready(function () {
            var table = $('#' + nom).DataTable();
            table.ajax.reload();
        });
    };
    var confdel = function (i, t) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '../../nomina/empleados/eliminar/confirdel.php',
            data: { id: i, tip: t }
        }).done(function (res) {
            $('#divModalConfDel').modal('show');
            $('#divMsgConfdel').html(res.msg);
            $('#divBtnsModalDel').html(res.btns);
        });
        return false;
    };
    function FormAreaUser(id = 0) {
        $('.is-invalid').removeClass('is-invalid');
        if ($('#id_user').val() == '0') {
            $('#BuscaUsuario').addClass('is-invalid');
            $('#BuscaUsuario').focus();
            mjeError('¡Debe seleccionar un usuario para asignar el área!');
            return false;
        }
        $.post("datos/registrar/form_area.php", { id: id, id_user: $('#id_user').val() }, function (he) {
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').removeClass('modal-sm');
            $('#divTamModalForms').removeClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    }
    function FormRelacionUser(id = 0) {
        $('.is-invalid').removeClass('is-invalid');
        if ($('#id_user').val() == '0') {
            $('#BuscaUsuario').addClass('is-invalid');
            $('#BuscaUsuario').focus();
            mjeError('¡Debe seleccionar un usuario para asignar el área!');
            return false;
        }
        $.post("datos/registrar/form_relacion.php", { id: id, id_user: $('#id_user').val() }, function (he) {
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').removeClass('modal-sm');
            $('#divTamModalForms').removeClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    }
    $(document).ready(function () {
        let id_t = $('#id_tercero').val();
        //dataTable Modalidad
        $('#tableModalidad').DataTable({
            "bFilter": false,
            "bInfo": false,
            "bLengthChange": false,
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("datos/registrar/formadd_modalidad.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_modalidad.php',
                type: 'POST',
                dataType: 'json',
            },
            "columns": [
                { 'data': 'modalidad' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": -1
        });
        $('#tableModalidad').wrap('<div class="overflow" />');
        $('#tableAreaUser').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    FormAreaUser();
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_area.php',
                type: 'POST',
                dataType: 'json',
                data: function (d) {
                    d.id_user = $('#id_user').val();
                }
            },
            "columns": [
                { 'data': 'id' },
                { 'data': 'area' },
                { 'data': 'estado' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": 10
        });
        $('#tableAreaUser').wrap('<div class="overflow" />');
        $('#tableRelacion').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    FormRelacionUser();
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_relacion.php',
                type: 'POST',
                dataType: 'json',
                data: function (d) {
                    d.id_user = $('#id_user').val();
                }
            },
            "columns": [
                { 'data': 'id' },
                { 'data': 'documento' },
                { 'data': 'nombre' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": 10
        });
        $('#tableRelacion').wrap('<div class="overflow" />');
        //dataTable Tipo de bien o servicio
        $('#tableTipoBnSv').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("datos/registrar/formadd_tipo_bn_sv.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_tipo_bien_servicio.php',
                type: 'POST',
                dataType: 'json',
            },
            "columns": [
                { 'data': 'tipo_compra' },
                { 'data': 'tipo_bs' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"],
                [1, "asc"],
                [2, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": -1
        });
        $('#tableTipoBnSv').wrap('<div class="overflow" />');
        //dataTable Tipo de bien o servicio
        $('#tableBnSv').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("datos/registrar/formadd_bn_sv.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_bien_servicio.php',
                type: 'POST',
                dataType: 'json',
            },
            "columns": [
                { 'data': 'tipo_compra' },
                { 'data': 'tipo_bs' },
                { 'data': 'bn_servicio' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"],
                [1, "asc"],
                [2, "asc"],
                [3, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": 25
        });
        $('#tableBnSv').wrap('<div class="overflow" />');
        $('#tableFormCtt').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("datos/cargar/formatos_ctt.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-lg');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_formatos_ctt.php',
                type: 'POST',
                dataType: 'json',
            },
            "columns": [
                { 'data': 'id' },
                { 'data': 'formato' },
                { 'data': 'tp_ctt' },
                { 'data': 'botones' },
            ],
            "order": [
                [0, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": -1
        });
        $('#tableFormCtt').wrap('<div class="overflow" />');
        $('.bttn-plus-dt span').html('<span class="icon-dt fas fa-plus-circle fa-lg"></span>');
    });
    $('#modificarAreasUser').on('click', '.editar', function () {
        let id_rel = $(this).attr('value');
        FormAreaUser(id_rel);
    });
    $('#modificarRelaciones').on('click', '.editar', function () {
        let id_relacion = $(this).attr('value');
        FormRelacionUser(id_relacion);
    });
    $('#modificarAreasUser').on('click', '.borrar', function () {
        let id_rel = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar registro?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_area_user.php',
                    data: { id: id_rel },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableAreaUser').DataTable().ajax.reload(null, false);
                            mje('Registro eliminado correctamente');
                        } else {
                            mjeError(r);
                        }
                    },
                }).always(function () {
                    ocultarOverlay();
                });
            }
        });
    });
    $('#modificarRelaciones').on('click', '.borrar', function () {
        let id_rel = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar registro?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_relacion_user.php',
                    data: { id: id_rel },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableRelacion').DataTable().ajax.reload(null, false);
                            mje('Registro eliminado correctamente');
                        } else {
                            mjeError(r);
                        }
                    },
                }).always(function () {
                    ocultarOverlay();
                });
            }
        });
    });
    //Guarda area de usuario
    $('#divForms').on('click', '#btnGuardaAreaUser', function (e) {
        e.preventDefault();
        if ($('#slcAreaUser').val() === '0') {
            $('#slcAreaUser').addClass('is-invalid');
            $('#slcAreaUser').focus();
            mjeError('¡Debe seleccionar un área para el usuario!');
        } else {
            mostrarOverlay();
            let datos = $('#formAreaUser').serialize();
            $.ajax({
                type: 'POST',
                url: 'actualizar/up_area_user.php',
                data: datos,
                success: function (r) {
                    if (r === 'ok') {
                        $('#tableAreaUser').DataTable().ajax.reload(null, false);
                        $('#divModalForms').modal('hide');
                        mje('Registro guardado correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    $('#divForms').on('click', '#btnGuardaRelacionUser', function (e) {
        e.preventDefault();
        if ($('#buscaUser').val() === '' || $('#id_usuario').val() === '0') {
            $('#buscaUser').addClass('is-invalid');
            $('#buscaUser').focus();
            mjeError('¡Debe seleccionar un usuario!');
        } else {
            mostrarOverlay();
            let datos = $('#formRelacionUser').serialize();
            $.ajax({
                type: 'POST',
                url: 'actualizar/up_relacion_user.php',
                data: datos,
                success: function (r) {
                    if (r === 'ok') {
                        $('#tableRelacion').DataTable().ajax.reload(null, false);
                        $('#divModalForms').modal('hide');
                        mje('Registro guardado correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Agregar modalidad contratacion
    $('#divForms').on('click', '#btnAddModalidad', function (e) {
        e.preventDefault();
        if ($('#txtModalidad').val() === '') {
            mjeError('¡Modalidad de contratación no puede ser Vacía!');
        } else {
            mostrarOverlay();
            let datos = $('#txtModalidad').val();
            $.ajax({
                type: 'POST',
                url: 'registrar/new_modalidad.php',
                data: { datos: datos },
                success: function (r) {
                    if (r === '1') {
                        $('#tableModalidad').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Modalidad de contratación agregada correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Borrar modalidad confirmar
    $('#modificarModalidades').on('click', '.borrar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar registro?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_modalidad.php',
                    data: { id: id },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableModalidad').DataTable().ajax.reload();
                            mje("Modalidad de contratación eliminada correctamente");
                        } else {
                            mjeError(r);
                        }
                    }
                });
            }
        });
    });
    //Agregar tipo de bien o servicio
    $('#divForms').on('click', '#btnAddTipoBnSv', function (e) {
        e.preventDefault();
        if ($('#slcTipoCompra').val() === '0') {
            mjeError('¡Debe selecionar tipo de compra!');
        } else if ($('#txtTipoBnSv').val() === '') {
            mjeError('¡Tipo de bien o servicio no puede ser Vacío!');
        } else if ($('#txtObjPre').val() === '') {
            mjeError('¡Objeto predefinido no puede ser Vacío!');
        } else {
            mostrarOverlay();
            let datos = $('#formAddTipoBnSv').serialize();
            $.ajax({
                type: 'POST',
                url: 'registrar/new_tipo_bn_sv.php',
                data: datos,
                success: function (r) {
                    if (r === '1') {
                        $('#tableTipoBnSv').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Tipo de bien o servicio Agregado Correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Actualizar tipo de bien o servicio -> formulario
    $('#modificarTipoBnSvs').on('click', '.editar', function () {
        let idtbs = $(this).attr('value');
        $.post("datos/actualizar/up_tipo_bn_sv.php", { idtbs: idtbs }, function (he) {
            $('#divTamModalForms').removeClass('modal-sm');
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });
    //Actualizar datos tipo de bien o servicio
    $('#divForms').on('click', '#btnUpTipoBnSv', function (e) {
        e.preventDefault();
        if ($('#txtTipoContrato').val() === '') {
            mjeError('¡Tipo de bien o servicio no puede ser Vacío!');
        } else if ($('#txtObjPre').val() === '') {
            mjeError('¡Objeto predefinido no puede ser Vacío!');
        } else {
            mostrarOverlay();
            let datos = $('#formActualizaBnSv').serialize() + '&id_tbs=' + $('#id_tbs').val();
            $.ajax({
                type: 'POST',
                url: 'actualizar/up_datos_tipo_bn_sv.php',
                data: datos,
                success: function (r) {
                    if (r === '1') {
                        $('#tableTipoBnSv').DataTable().ajax.reload(null, false);
                        $('#divModalForms').modal('hide');
                        mje('Datos Actualizados Correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Borrar tipo de bien o servicio
    $('#modificarTipoBnSvs').on('click', '.borrar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar registro?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_tipo_bn_sv.php',
                    data: { id: id },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableTipoBnSv').DataTable().ajax.reload();
                            mje("Tipo de bien o servicio eliminado correctamente");
                        } else {
                            mjeError(r);
                        }
                    }
                });
            }
        });
    });
    //Agregar bien o servicio
    $('#divForms').on('click', '#btnAddBnSv', function (e) {
        e.preventDefault();
        if ($('#slcTipoBnSv').val() === '0') {
            mjeError('¡Debe selecionar tipo de bien o servicio!');
        } else if ($('#txtBnSv').val() === '') {
            mjeError('¡Bien o servicio no puede ser Vacío!');
        } else {
            mostrarOverlay();
            let datos = $('#formAddBnSv').serialize();
            $.ajax({
                type: 'POST',
                url: 'registrar/new_bn_sv.php',
                data: datos,
                success: function (r) {
                    if (r == '1') {
                        $('#tableBnSv').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Bien o servicio Agregado Correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Actualizar bien o servicio -> formulario
    $('#modificarBnSvs').on('click', '.editar', function () {
        let idbs = $(this).attr('value');
        $.post("datos/actualizar/up_bn_sv.php", { idbs: idbs }, function (he) {
            $('#divTamModalForms').removeClass('modal-sm');
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });
    $('#collapsemodConfig').on('click', '#awesomplete_list_1', function () {
        $('#tableAreaUser').DataTable().ajax.reload(null, false);
        $('#tableRelacion').DataTable().ajax.reload(null, false);

    });
    //Actualizar datos de bien o servicio
    $('#divForms').on('click', '#btnUpBnSv', function (e) {
        e.preventDefault();
        if ($('#txtBnSv').val() === '') {
            mjeError('¡Bien o servicio no puede ser Vacío!');
        } else {
            mostrarOverlay();
            let datos = $('#formActualizaBnSv').serialize();
            $.ajax({
                type: 'POST',
                url: 'actualizar/up_datos_bn_sv.php',
                data: datos,
                success: function (r) {
                    if (r === '1') {
                        $('#tableBnSv').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Datos Actualizados Correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    //Borrar bien o servicio
    $('#modificarBnSvs').on('click', '.borrar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar registro?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_bn_sv.php',
                    data: { id: id },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableBnSv').DataTable().ajax.reload();
                            mje("Tipo de bien o servicio eliminado correctamente");
                        } else {
                            mjeError(r);
                        }
                    }
                });
            }
        });
    });

    $('#btnExcelHomolgBnSv').on('click', function () {
        let id = '1';
        $('<form action="datos/listar/homologa_bn_sv.php" method="post"><input type="hidden" name="id" value="' + id + '" /></form>')
            .appendTo('body').submit();
    });
    $('#btnExcelHomolgEscHonor').on('click', function () {
        let id = '1';
        $('<form action="datos/listar/homologa_esc_honor.php" method="post"><input type="hidden" name="id" value="' + id + '" /></form>')
            .appendTo('body').submit();
    });
    $('.subirHomologacion').on('click', function () {
        let tipo = $(this).attr('text');
        $.post("datos/cargar/homologacion.php", { tipo: tipo }, function (he) {
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').removeClass('modal-lg');
            //$('#divTamModalForms').addClass('modal-sm');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });
    $('#subirFormatos').on('click', function () {
        alert('Subir formatos');
    });
    $('#divModalForms').on('click', '#btnGuardaHomologacion', function (e) {
        e.preventDefault();
        let tipo = $(this).attr('text');
        if ($('#fileHomologacion').val() === '') {
            mjeError('¡Debe elegir un archivo!');
        } else {
            let archivo = $('#fileHomologacion').val();
            let ext = archivo.substring(archivo.lastIndexOf(".")).toLowerCase();
            if (ext !== '.csv') {
                mjeError('¡Solo se permite documentos .csv!');
                return false;
            } else if ($('#fileHomologacion')[0].files[0].size > 2097152) {
                mjeError('¡Documento debe tener un tamaño menor a 2Mb!');
                return false;
            }
            let datos = new FormData();
            datos.append('fileHomologacion', $('#fileHomologacion')[0].files[0]);
            datos.append('tipo', tipo);
            mostrarOverlay();
            $.ajax({
                type: 'POST',
                url: 'registrar/new_homologacion.php',
                contentType: false,
                data: datos,
                processData: false,
                cache: false,
                success: function (r) {
                    if (r == '1') {
                        $('#divModalForms').modal('hide');
                        mje('Proceso realizado Correctamente');
                    } else {
                        mjeError(r);
                    }
                }
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    $('#divModalForms').on('click', '#btnGuardaFormatoCtt', function (e) {
        e.preventDefault();
        $('.is-invalid').removeClass('is-invalid');
        if ($('#slcTipoFormato').val() === '0') {
            $('#slcTipoFormato').addClass('is-invalid');
            $('#slcTipoFormato').focus();
            mjeError('¡Debe seleccionar un tipo de formato!');
        } else if ($('#slcTipoBnSv').val() === '0') {
            $('#slcTipoBnSv').addClass('is-invalid');
            $('#slcTipoBnSv').focus();
            mjeError('¡Debe seleccionar un tipo de bien o servicio!');
        } else if ($('#fileContratacion').val() === '') {
            $('#fileContratacion').addClass('is-invalid');
            $('#fileContratacion').focus();
            mjeError('¡Debe elegir un archivo!');
        } else {
            let archivo = $('#fileContratacion').val();
            let ext = archivo.substring(archivo.lastIndexOf(".")).toLowerCase();
            if (ext !== '.docx') {
                mjeError('¡Solo se permite documentos .docx!');
                return false;
            } else if ($('#fileContratacion')[0].files[0].size > 10485760) {
                mjeError('¡Documento debe tener un tamaño menor a 10Mb!');
                return false;
            }
            mostrarOverlay();
            let datos = new FormData();
            datos.append('fileContratacion', $('#fileContratacion')[0].files[0]);
            datos.append('slcTipoFormato', $('#slcTipoFormato').val());
            datos.append('slcTipoBnSv', $('#slcTipoBnSv').val());
            $.ajax({
                type: 'POST',
                url: 'registrar/new_formato.php',
                contentType: false,
                data: datos,
                processData: false,
                cache: false,
                success: function (r) {
                    if (r == 'ok') {
                        $('#tableFormCtt').DataTable().ajax.reload(null, false);
                        $('#divModalForms').modal('hide');
                        mje('Proceso realizado Correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });
    $('#tableFormCtt').on('click', '.borrar', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar el documento?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_formato.php',
                    data: { id: id },
                    success: function (r) {
                        if (r == 'ok') {
                            $('#tableFormCtt').DataTable().ajax.reload(null, false);
                            mje('Documento eliminado correctamente');
                        } else {
                            mjeError(r);
                        }
                    }
                });
            }
        });
    });
    $('#tableFormCtt').on('click', '.descargar', function () {
        let id = $(this).attr('value');
        $('<form action="datos/descargar/formato_ctt.php" method="post"><input type="hidden" name="id" value="' + id + '" /></form>')
            .appendTo('body').submit();
    });
    $('#btnDownloadVarsCtt').on('click', function () {
        window.location.href = 'datos/listar/variables_contratacion.php';
    });
    $(document).ready(function () {
        var maxBnSv = 100;
        var inputHTML = `
        <div class="input-group input-group-sm mb-3">
            <input name="txtBnSv[]" type="text" class="form-control" aria-label="Sizing example input">
            <a href="javascript:void(0);" class="btn btn-outline-danger btn_removeBnSv" title="Quitar">
                <span class="fas fa-minus-circle fa-lg"></span>
            </a>
        </div>`;

        var x = 1;

        $('#divForms').on('click', '#celdaPR .btn_addBnSv', function () {
            x = $('#content_inputs .input-group').length; // sincroniza
            if (x < maxBnSv) {
                $('#content_inputs').append(inputHTML);
            }
            return false;
        });

        $('#divForms').on('click', '.btn_removeBnSv', function (e) {
            e.preventDefault();
            $(this).closest('.input-group').remove();
            return false;
        });
    });

    // ==================== CRUD FIRMAS ====================

    // DataTable Firmas
    $(document).ready(function () {
        $('#tableFirmasCtt').DataTable({
            dom: setdom,
            buttons: [{
                text: '<span class="fa-solid fa-plus fa-lg"></span>',
                className: 'btn btn-success btn-sm shadow',
                action: function (e, dt, node, config) {
                    $.post("datos/registrar/formadd_firmas.php", function (he) {
                        $('#divTamModalForms').removeClass('modal-xl');
                        $('#divTamModalForms').removeClass('modal-sm');
                        $('#divTamModalForms').addClass('modal-lg');
                        $('#divModalForms').modal('show');
                        $("#divForms").html(he);
                    });
                }
            }],
            language: dataTable_es,
            "ajax": {
                url: 'datos/listar/datos_firmas.php',
                type: 'POST',
                dataType: 'json',
            },
            "columns": [
                { 'data': 'id' },
                { 'data': 'variable' },
                { 'data': 'responsable' },
                { 'data': 'botones' },
            ],
            "order": [
                [1, "asc"]
            ],
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'TODO'],
            ],
            "pageLength": -1
        });
        $('#tableFirmasCtt').wrap('<div class="overflow" />');
    });

    // Agregar firma
    $('#divForms').on('click', '#btnAddFirma', function (e) {
        e.preventDefault();
        $('.is-invalid').removeClass('is-invalid');

        if ($('#txtNomVariable').val() === '') {
            $('#txtNomVariable').addClass('is-invalid');
            $('#txtNomVariable').focus();
            mjeError('¡El nombre de la variable no puede estar vacío!');
        } else if ($('#id_tercero').val() === '0' || $('#buscaTercero').val() === '') {
            $('#buscaTercero').addClass('is-invalid');
            $('#buscaTercero').focus();
            mjeError('¡Debe seleccionar un responsable!');
        } else if ($('#txtCargo').val() === '') {
            $('#txtCargo').addClass('is-invalid');
            $('#txtCargo').focus();
            mjeError('¡El cargo no puede estar vacío!');
        } else if ($('#fileFirma').val() === '') {
            $('#fileFirma').addClass('is-invalid');
            $('#fileFirma').focus();
            mjeError('¡Debe cargar una imagen PNG!');
        } else {
            let archivo = $('#fileFirma').val();
            let ext = archivo.substring(archivo.lastIndexOf(".")).toLowerCase();
            if (ext !== '.png') {
                $('#fileFirma').addClass('is-invalid');
                mjeError('¡Solo se permiten imágenes PNG!');
                return false;
            } else if ($('#fileFirma')[0].files[0].size > 2097152) {
                $('#fileFirma').addClass('is-invalid');
                mjeError('¡La imagen debe tener un tamaño menor a 2MB!');
                return false;
            }

            mostrarOverlay();
            let datos = new FormData($('#formAddFirma')[0]);

            $.ajax({
                type: 'POST',
                url: 'registrar/new_firma.php',
                contentType: false,
                data: datos,
                processData: false,
                cache: false,
                success: function (r) {
                    if (r === '1') {
                        $('#tableFirmasCtt').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Firma registrada correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });

    // Editar firma -> formulario
    $('#modificaFirmasCtt').on('click', '.editarFirma', function () {
        let idFirma = $(this).attr('value');
        $.post("datos/actualizar/up_firma.php", { idFirma: idFirma }, function (he) {
            $('#divTamModalForms').removeClass('modal-sm');
            $('#divTamModalForms').removeClass('modal-xl');
            $('#divTamModalForms').addClass('modal-lg');
            $('#divModalForms').modal('show');
            $("#divForms").html(he);
        });
    });

    // Actualizar datos de firma
    $('#divForms').on('click', '#btnUpFirma', function (e) {
        e.preventDefault();
        $('.is-invalid').removeClass('is-invalid');

        if ($('#txtNomVariable').val() === '') {
            $('#txtNomVariable').addClass('is-invalid');
            $('#txtNomVariable').focus();
            mjeError('¡El nombre de la variable no puede estar vacío!');
        } else if ($('#slcTercero').val() === '0') {
            $('#slcTercero').addClass('is-invalid');
            $('#slcTercero').focus();
            mjeError('¡Debe seleccionar un responsable!');
        } else if ($('#txtCargo').val() === '') {
            $('#txtCargo').addClass('is-invalid');
            $('#txtCargo').focus();
            mjeError('¡El cargo no puede estar vacío!');
        } else {
            // Validar imagen solo si se está cargando una nueva
            if ($('#fileFirma').val() !== '') {
                let archivo = $('#fileFirma').val();
                let ext = archivo.substring(archivo.lastIndexOf(".")).toLowerCase();
                if (ext !== '.png') {
                    $('#fileFirma').addClass('is-invalid');
                    mjeError('¡Solo se permiten imágenes PNG!');
                    return false;
                } else if ($('#fileFirma')[0].files[0].size > 2097152) {
                    $('#fileFirma').addClass('is-invalid');
                    mjeError('¡La imagen debe tener un tamaño menor a 2MB!');
                    return false;
                }
            }

            mostrarOverlay();
            let datos = new FormData($('#formActualizaFirma')[0]);

            $.ajax({
                type: 'POST',
                url: 'actualizar/up_datos_firma.php',
                contentType: false,
                data: datos,
                processData: false,
                cache: false,
                success: function (r) {
                    if (r === '1') {
                        $('#tableFirmasCtt').DataTable().ajax.reload();
                        $('#divModalForms').modal('hide');
                        mje('Datos actualizados correctamente');
                    } else {
                        mjeError(r);
                    }
                },
            }).always(function () {
                ocultarOverlay();
            });
        }
    });

    // Borrar firma
    $('#modificaFirmasCtt').on('click', '.borrarFirma', function () {
        let id = $(this).attr('value');
        Swal.fire({
            title: "¿Confirma que desea eliminar la firma?",
            text: "Se eliminará la firma y su imagen asociada",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#00994C",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si!",
            cancelButtonText: "NO",
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarOverlay();
                $.ajax({
                    type: 'POST',
                    url: 'eliminar/del_firma.php',
                    data: { id: id },
                    success: function (r) {
                        if (r === '1') {
                            $('#tableFirmasCtt').DataTable().ajax.reload();
                            mje("Firma eliminada correctamente");
                        } else {
                            mjeError(r);
                        }
                    },
                }).always(function () {
                    ocultarOverlay();
                });
            }
        });
    });

})(jQuery);
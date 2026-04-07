
//VERIFICA SI UN OBJETO ES VACIÓ Y LO RESALTA. SE PUEDE ENVIAR MESAJE COMO PARAMETRO PARA VISUALIZARSE
var verifica_vacio = function (objeto, msg = "") {
    let error = 0;
    if (!objeto || objeto.value.trim() === "") {
        objeto.classList.add('is-invalid');
        objeto.focus();
        error = 1;
        if (msg !== "") {
            mjeError(msg);
        }
    } else {
        objeto.classList.remove('is-invalid');
    }
    return error;
};

//VERIFICA SI UN OBJETO ES VACIÓ Y RESALTA OTRO OBJETO RELACIONADO. SE PUEDE ENVIAR MESAJE COMO PARAMETRO PARA VISUALIZARSE
var verifica_vacio_2 = function (objeto1, objeto2, msg = "") {
    let error = 0;
    if (!objeto1 || (objeto1.value || "").trim() === "") {
        objeto2.classList.add('is-invalid');
        objeto2.focus();
        error = 1;
        if (msg !== "") {
            mjeError(msg);
        }
    } else {
        objeto2.classList.remove('is-invalid');
    }
    return error;
};

$(function () {
    //Dato numerico
    $("#divModalForms,#divModalReg,#divModalBus").on("input", ".number", function () {
        var that = $(this);
        that.val(that.val().replace(/[^0-9]/g, ''));
        if (isNaN(that.val())) {
            e.preventDefault();
        }
    });

    //Dato numerico entero >=0
    $("#divModalForms,#divModalReg,#divModalBus").on("input", ".numberint", function () {
        var that = $(this);
        that.val(that.val().replace(/[^0-9]/g, ''));
        if (that.val().substring(0, 1).trim() == '0') {
            that.val('0');
        }
        if (isNaN(that.val())) {
            e.preventDefault();
        }
    });

    //Dato numerico flotante
    $("#divModalForms,#divModalReg,#divModalBus").on("input", ".numberfloat", function () {
        let val = $(this).val();
        val = val.replace(/[^0-9\.\-]/g, '');
        if ((val.match(/\-/g) || []).length > 1) {
            val = '-' + val.replace(/\-/g, '');
        }
        if (val.indexOf('-') > 0) {
            val = '-' + val.replace(/\-/g, '');
        }

        let parts = val.split('.');
        if (parts.length > 2) {
            val = parts[0] + '.' + parts[1];
        }

        if (val.length > 1 && val[0] === '0' && val[1] !== '.' && val[1] !== undefined) {
            val = parseFloat(val).toString();
        } else if (val.length > 2 && val[0] === '-' && val[1] === '0' && val[2] !== '.') {
            val = '-' + parseFloat(val).toString();
        }
        if (val !== '-' && isNaN(parseFloat(val))) {
            val = '';
        }
        $(this).val(val);
    });

    //Dato letras, numeros, y -
    $("#divModalForms,#divModalReg,#divModalBus").on("input", ".valcode", function () {
        var that = $(this);
        that.val(that.val().replace(/[^0-9a-zA-Z\-]/g, ''));
        if (isNaN(that.val())) {
            e.preventDefault();
        }
    });

    //Boton de Imprimir de formulario Impresión
    $('#divModalImp').on('click', '#btnImprimir', function () {
        function imprSelec() {
            var div = $('#areaImprimir').html();
            var ventimp = window.open(' ', '');
            ventimp.document.write('<!DOCTYPE html><html><head><title>Imprimir</title></head><body>');
            ventimp.document.write('<div>' + div + '</div>');
            ventimp.document.write('</body></html>');
            ventimp.print();
            ventimp.close();
        }
        $('#divModalForms .collapse').addClass('show');
        imprSelec();
    });

    function toBase64(str) {
        return window.btoa(
            new TextEncoder().encode(str)
                .reduce((acc, byte) => acc + String.fromCharCode(byte), '')
        );
    }
    //Boton de Excel de formulario Impresión
    $('#divModalImp').on('click', '#btnExcelEntrada', function () {
        let encoded = toBase64($('#areaImprimir').html());
        $('<form action="' + ValueInput('host') + '/src/financiero/reporte_excel.php" method="post"><input type="hidden" name="xls" value="' + encoded + '" /></form>').appendTo('body').submit();
    });
});

//Funcion para validar numero enteros
function number_int(that) {
    that.val(that.val().replace(/[^0-9]/g, ''));
    if (that.val().substring(0, 1).trim() == '0') {
        that.val('0');
    }
    if (isNaN(that.val())) {
        e.preventDefault();
    }
}
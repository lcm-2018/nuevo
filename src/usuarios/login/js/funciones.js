document.addEventListener('DOMContentLoaded', function () {
    var btnLogin = document.getElementById('btnLoginUser');
    btnLogin.addEventListener('click', function (e) {
        e.preventDefault();
        LimpiaInvalid();
        if (ValueInput('txtUser') == '') {
            MuestraError('txtUser', 'Usuario requerido');
        } else if (ValueInput('pssClave') == '') {
            MuestraError('pssClave', 'Contraseña requerida');
        } else {
            var data = Serializa('formLogin');
            SendPost('src/usuarios/login/php/valida_login.php', data).then(he => {
                if (he.status == 'ok') {
                    window.location = "src/inicio.php";
                } else {
                    mjeError(he.msg);
                }
            });
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    var btnLogin = document.getElementById('btnLoginUser');

    function ejecutarLogin(e) {
        e.preventDefault();
        LimpiaInvalid();

        if (ValueInput('txtUser') === '') {
            MuestraError('txtUser', 'Usuario requerido');
        } else if (ValueInput('pssClave') === '') {
            MuestraError('pssClave', 'Contraseña requerida');
        } else {
            var data = Serializa('formLogin');
            mostrarOverlay();
            SendPost('src/usuarios/login/php/valida_login.php', data).then(he => {
                if (he.status === 'ok') {
                    window.location = "src/inicio.php";
                } else {
                    mjeError(he.msg);
                }
            }).finally(() => {
                ocultarOverlay();
            });
        }
    }

    // Click en el botón
    btnLogin.addEventListener('click', ejecutarLogin);

    // Presionar Enter en cualquier campo del formulario
    document.getElementById('formLogin').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            ejecutarLogin(e);
        }
    });
});
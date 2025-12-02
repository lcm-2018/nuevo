const perfilUsuario = document.getElementById('perfilUsuario');
const cambiaClave = document.getElementById('cambiaClave');

if (perfilUsuario) {
    perfilUsuario.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', 'form1', 0, 'modalDefault', 'bodyDefault', 'tamDefault', 'modal-xl');
    });
}

if (cambiaClave) {
    cambiaClave.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', 'form2', 0, 'modalDefault', 'bodyDefault', 'tamDefault', 'modal-sm');
    });
}
document.getElementById('modalDefault').addEventListener('click', function (event) {
    const boton = event.target;
    if (!boton) return;

    // No prevenir el comportamiento por defecto para todos los clicks
    // (esto permitía que radios/checkboxes no funcionaran). Sólo
    // evitamos el comportamiento por defecto en acciones concretas.
    LimpiaInvalid();
    switch (boton.id) {
        case 'btnGuardaCambiaClave':
            event.preventDefault();
            var pss = hex_sha512(ValueInput('claveActual'));
            if (ValueInput('claveActual') == '') {
                MuestraError('claveActual', 'Ingrese la clave actual');
            } else if (ValueInput('passAnt') == '') {
                MuestraError('claveActual', 'La clave actual es incorrecta');
            } else if (ValueInput('passAnt') != pss) {
                MuestraError('claveActual', 'La clave actual es incorrecta');
            } else if (ValueInput('nuevaClave') == '') {
                MuestraError('nuevaClave', 'Ingrese la nueva clave');
            } else if (ValueInput('nuevaClave').length < 8) {
                MuestraError('nuevaClave', 'La nueva clave debe tener al menos 8 caracteres');
            } else if (ValueInput('confirmarClave') == '') {
                MuestraError('confirmarClave', 'Confirme la nueva clave');
            } else if (ValueInput('nuevaClave') != ValueInput('confirmarClave')) {
                MuestraError('confirmarClave', 'La nueva clave y la confirmación no coinciden');
            } else {
                var data = Serializa('formCambiaClave');
                data.append('action', 'pass');
                mostrarOverlay();
                SendPost(ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', data).then((response) => {
                    if (response.status === 'ok') {
                        mje('Guardado correctamente!');
                        $('#modalDefault').modal('hide');
                    } else {
                        mjeError('Error!', response.msg);
                    }
                }).finally(() => {
                    ocultarOverlay();
                });
            }
            break;
    }
});
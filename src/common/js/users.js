const perfilUsuario = document.getElementById('perfilUsuario');

if (perfilUsuario) {
    perfilUsuario.addEventListener('click', function () {
        mostrarOverlay();
        VerFormulario(ValueInput('host') + '/src/usuarios/login/php/controladores/usuarios.php', 'form1', 0, 'modalDefault', 'bodyDefault', 'tamDefault', '');
    });
}
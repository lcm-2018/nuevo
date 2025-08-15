<?php
session_start();
session_destroy();
include_once 'config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$content = <<<HTML
<div class="col-md-8 col-lg-6 col-xxl-3 shadow-lg">
    <div class="card mb-0">
        <a href="{$host}/index.php" class="text-nowrap logo-img text-center d-block py-3 w-100">
            <img src="{$host}/assets/images/logoFinanciero.png" class="card-img-top border-bottom shadow-sm px-2 pb-2" alt="logo sistema financiero">
        </a>
        <div class="card-body">
            <form id="formLogin">
                <div class="mb-3">
                    <label for="txtUser" class="form-label text-secondary">USUARIO</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="txtUser" name="txtUser" placeholder="Ingrese su usuario" required>
                        <span class="input-group-text bg-success"><i class="far fa-user fa-lg text-white"></i></span>
                    </div>
                    
                </div>
                <div class="mb-4">
                    <label for="pssClave" class="form-label text-secondary">CONTRASEÑA</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="pssClave" name="pssClave" placeholder="Ingrese su contraseña" required>
                        <a class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye-slash fa-lg"></i> 
                        </a>
                        <span class="input-group-text bg-warning"><i class="fas fa-key fa-lg text-white"></i></span>
                    </div>
                </div>
            </form>
            <div class="text-center pt-2">
                <button class="btn btn-primary py-8 mb-4" id="btnLoginUser"><i class="fa-solid fa-door-open px-2"></i></i>Ingresar</button>
            </div>
        </div>
    </div>
</div>
<script>
    const togglePassword = document.getElementById('togglePassword');
    const pssClave = document.getElementById('pssClave');
    const eyeIcon = togglePassword.querySelector('i');

    // Cuando mantienes presionado
    togglePassword.addEventListener('mousedown', function () {
        pssClave.setAttribute('type', 'text');
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    });

    // Cuando sueltas el botón o el mouse sale del área
    togglePassword.addEventListener('mouseup', function () {
        pssClave.setAttribute('type', 'password');
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    });

    togglePassword.addEventListener('mouseleave', function () {
        pssClave.setAttribute('type', 'password');
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
});
</script>
HTML;
$plantilla = new Plantilla($content, 1);
$plantilla->addScriptFile("{$host}/src/usuarios/login/js/sha.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/usuarios/login/js/funciones.js?v=" . date("YmdHis"));
echo $plantilla->render();

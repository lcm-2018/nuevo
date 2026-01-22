<?php
session_start();

if (isset($_SESSION['user'])) {
    session_destroy();
    session_unset();
}

include_once 'config/autoloader.php';

use Config\Clases\Plantilla;

$host = Plantilla::getHost();

$content = <<<HTML
<style>
    /* ===== LOGIN CARD STYLES ===== */
    .login-card {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        box-shadow: 
            0 25px 50px -12px rgba(0, 0, 0, 0.15),
            0 0 0 1px rgba(0, 0, 0, 0.05);
        padding: 0;
        max-width: 420px;
        width: 100%;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
    }
    
    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 
            0 35px 60px -15px rgba(0, 0, 0, 0.2),
            0 0 0 1px rgba(0, 0, 0, 0.08);
    }
    
    /* Header con logo */
    .login-header {
        background: linear-gradient(135deg, #1a659d 0%, #16a085 100%);
        padding: 2rem 2rem 1.5rem;
        text-align: center;
        position: relative;
    }
    
    .login-header::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 0;
        right: 0;
        height: 40px;
        background: linear-gradient(135deg, #1a659d 0%, #16a085 100%);
        clip-path: ellipse(60% 100% at 50% 0%);
    }
    
    .login-logo {
        max-width: 200px;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        transition: transform 0.3s ease;
    }
    
    .login-logo:hover {
        transform: scale(1.05);
    }
    
    .login-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.85rem;
        margin-top: 0.5rem;
        font-weight: 300;
        letter-spacing: 0.5px;
    }
    
    /* Cuerpo del formulario */
    .login-body {
        padding: 2.5rem 2rem 2rem;
    }
    
    .login-title {
        color: #1a659d;
        font-size: 1.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .login-title::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #16a085, #48C9B0);
        border-radius: 2px;
    }
    
    /* Grupos de input mejorados */
    .form-group-login {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .form-label-login {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .input-icon-left {
        position: absolute;
        left: 1rem;
        color: #16a085;
        font-size: 1.1rem;
        z-index: 5;
        transition: all 0.3s ease;
    }
    
    .form-control-login {
        width: 100%;
        padding: 0.9rem 3rem 0.9rem 3rem;
        font-size: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: #1f2937;
    }
    
    .form-control-login::placeholder {
        color: #9ca3af;
    }
    
    .form-control-login:focus {
        outline: none;
        border-color: #16a085;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(22, 160, 133, 0.15);
    }
    
    .input-wrapper:focus-within .input-icon-left {
        color: #16a085;
        transform: scale(1.1);
    }
    
    /* Botón de toggle password */
    .btn-toggle-password {
        position: absolute;
        right: 1rem;
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        z-index: 5;
    }
    
    .btn-toggle-password:hover {
        color: #16a085;
        background: rgba(22, 160, 133, 0.1);
    }
    
    /* Botón de login premium */
    .btn-login {
        width: 100%;
        padding: 1rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        color: #ffffff;
        background: linear-gradient(135deg, #16a085 0%, #1a659d 100%);
        border: none;
        border-radius: 12px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 15px rgba(22, 160, 133, 0.4);
        margin-top: 0.5rem;
    }
    
    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(22, 160, 133, 0.5);
    }
    
    .btn-login:hover::before {
        left: 100%;
    }
    
    .btn-login:active {
        transform: translateY(0);
    }
    
    .btn-login i {
        margin-right: 0.5rem;
        transition: transform 0.3s ease;
    }
    
    .btn-login:hover i {
        transform: translateX(3px);
    }
    
    /* Footer de la tarjeta */
    .login-footer {
        text-align: center;
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
    }
    
    .login-footer-text {
        color: #6b7280;
        font-size: 0.8rem;
        margin: 0;
    }
    
    /* Animación de entrada */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive */
    @media (max-width: 480px) {
        .login-card {
            margin: 1rem;
            border-radius: 20px;
        }
        
        .login-header {
            padding: 1.5rem 1.5rem 1rem;
        }
        
        .login-body {
            padding: 2rem 1.5rem 1.5rem;
        }
        
        .login-logo {
            max-width: 160px;
        }
    }
</style>

<div class="login-card">
    <!-- Header con logo -->
    <div class="login-header">
        <a href="{$host}/index.php">
            <img src="{$host}/assets/images/logoFinanciero.png" alt="Logo Sistema Financiero" class="login-logo">
        </a>
        <p class="login-subtitle">Sistema de Gestión Financiera</p>
    </div>
    
    <!-- Cuerpo del formulario -->
    <div class="login-body">
        <h2 class="login-title">Bienvenido</h2>
        
        <form id="formLogin">
            <!-- Campo de usuario -->
            <div class="form-group-login">
                <label for="txtUser" class="form-label-login">
                    <i class="fas fa-user me-1"></i> Usuario
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon-left"></i>
                    <input 
                        type="text" 
                        class="form-control-login" 
                        id="txtUser" 
                        name="txtUser" 
                        placeholder="Ingrese su usuario" 
                        autocomplete="username"
                        required
                    >
                </div>
            </div>
            
            <!-- Campo de contraseña -->
            <div class="form-group-login">
                <label for="pssClave" class="form-label-login">
                    <i class="fas fa-lock me-1"></i> Contraseña
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon-left"></i>
                    <input 
                        type="password" 
                        class="form-control-login" 
                        id="pssClave" 
                        name="pssClave" 
                        placeholder="Ingrese su contraseña"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="btn-toggle-password" id="togglePassword" title="Mostrar/Ocultar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <!-- Botón de login -->
            <button type="button" class="btn-login" id="btnLoginUser">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>
        </form>
    </div>
    
    <!-- Footer -->
    <div class="login-footer">
        <p class="login-footer-text">
            <i class="fas fa-shield-alt me-1"></i>
            Acceso seguro y protegido
        </p>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const pssClave = document.getElementById('pssClave');
    const eyeIcon = togglePassword.querySelector('i');

    // Toggle password visibility on click
    togglePassword.addEventListener('click', function(e) {
        e.preventDefault();
        const type = pssClave.getAttribute('type') === 'password' ? 'text' : 'password';
        pssClave.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });

    // Efecto de focus en los inputs
    document.querySelectorAll('.form-control-login').forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-group-login').classList.add('focused');
        });
        input.addEventListener('blur', function() {
            this.closest('.form-group-login').classList.remove('focused');
        });
    });

    // Submit con Enter
    document.getElementById('formLogin').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btnLoginUser').click();
        }
    });
</script>
HTML;
$plantilla = new Plantilla($content, 1);
$plantilla->addScriptFile("{$host}/src/usuarios/login/js/sha.js?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/usuarios/login/js/funciones.js?v=" . date("YmdHis"));
echo $plantilla->render();

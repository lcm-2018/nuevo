<?php

namespace Config\Clases;

use Config\Clases\Sesion;
use Src\Common\Php\Clases\Combos;
use Src\Common\Php\Clases\Permisos;

class Plantilla
{

    private $title;      // Almacena el título de la página
    private $content;    // Almacena el contenido HTML específico de la página
    private $additionalCss = []; // Array para almacenar etiquetas <link> o <style> adicionales
    private $additionalScripts = []; // Array para almacenar etiquetas <script> adicionales
    private $modals = []; // Array para almacenar el código HTML de los modales
    private $baseHtml;   // Almacena la estructura HTML fija de la plantilla
    private $navbar; // Almacena la barra de navegación de la plantilla
    private $plantilla1; // Almacena la plantilla 1 de la plantilla
    private $plantilla2; // Almacena la plantilla 2 de la plantilla

    /**
     * Constructor de la clase Plantilla.
     * Inicializa la plantilla con el título y el contenido, y define la estructura base.
     *
     * @param string $title El título que aparecerá en la pestaña del navegador.
     * @param string $content El contenido HTML específico de esta página.
     */
    public function __construct($content, $pl)
    {
        $this->title = "Contable";
        $this->content = $content;
        $host = self::getHost();
        $id_rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 0;
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;
        $opciones = (new Permisos)->PermisoOpciones($id_user);
        $caracter = Sesion::Caracter();
        $nombre_usuario = Sesion::User();
        $vigencias = Combos::getVigencias(Sesion::IdVigencia());
        $vigencias = str_replace('<option value="0" class="text-muted">-- Seleccionar --</option>', '', $vigencias);
        $pto = Sesion::Pto();
        $empresa = $id_rol == 1 ? '<li><a class="dropdown-item sombra" href="javascript:void(0)">Perfil de Empresa</a></li>' : '';
        $cierre = $id_rol == 1 ? '<li><a class="dropdown-item sombra" href="javascript:void(0)" id="cierrePeriodo">Cierre de Periodo</a></li>' : '';
        //Esta opción se habilitará cuando se implemente la funcionalidad de gestión de empresas
        $empresa = '';
        $users = $id_rol == 1 ? '<li><a class="dropdown-item sombra" href="' . $host . '/src/usuarios/general/php/index.php">Gestión de Usuarios</a></li>
            <li><a class="dropdown-item sombra" href="' . $host . '/src/usuarios/general/php/roles.php">Roles de Usuarios</a></li>
            <li><hr class="dropdown-divider"></li>' : '';
        $docs = $id_rol == 1 ||  (new Permisos)->PermisosUsuario($opciones, 6001, 0) ? '<li><a class="dropdown-item sombra" href="' . $host . '/src/documentos/php/index.php">Gestión Documental</a></li>' : '';
        $opciones_user =
            <<<HTML
            <li><a class="dropdown-item sombra" href="javascript:void(0)" id="perfilUsuario">Perfil de Usuario</a></li>
            <li><a class="dropdown-item sombra" href="javascript:void(0)" id="cambiaClave">Cambiar Contraseña</a></li>
            {$empresa}
            <li><hr class="dropdown-divider"></li>
            {$users}
            {$cierre}
            {$docs}
            <li><a class="dropdown-item sombra" href="javascript:void(0)" id="fechaSesion">Fecha de Sesión</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item sombra" href="{$host}/index.php">Cerrar Sesión</a></li>
HTML;
        if ($id_rol == 1) {
            $btnVigencia = '<a class="dropdown-item sombra" href="javascript:void(0)" id="btnVigencia">Vigencia</a>';
        } else {
            $btnVigencia = 'Vigencia';
        }
        $menu = (new Menu())->render();
        $this->navbar =
            <<<HTML
                <nav style="background: linear-gradient(135deg, #1a659d 0%, #16a085 100%) !important; border-bottom: 5px solid #16a085 !important;" class="navbar fixed-top text-white" data-navbarbg="skin6">
                    <div class="container-fluid d-flex justify-content-between align-items-center">
                        <!-- Elemento Izquierda: Botón Menú -->
                        <div class="flex-shrink-0">
                            <button style="box-shadow: none;border: 1px solid #adb5bd !important;" class="navbar-toggler bg-light border-0 p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                        <!-- Elemento Central: Usuario y Vigencia -->
                        <div class="text-center flex-grow-1">
                            <span class="fw-bold">{$nombre_usuario}</span> | 
                            <span class="fw-bold d-inline-flex align-items-center">
                                {$btnVigencia}
                                <select class="form-select form-select-sm rounded-pill ms-1" id="slcVigencia" style="width: 100px; display: inline-block; padding: 0.1rem 0.4rem; font-size: 0.7rem;">
                                    {$vigencias}
                                </select>
                            </span>
                        </div>
                        <!-- Elemento Derecha: Logo y Menú de Usuario -->
                        <div class="d-flex align-items-center flex-shrink-0">
                            <a class="navbar-brand d-flex align-items-center p-0 me-3" href="{$host}/src/inicio.php">
                                <img src="{$host}/assets/images/logoFinanciero.png" alt="logo sistema financiero" width="150">
                            </a>
                            <div class="dropdown">
                                <a class="dropdown-toggle no-caret d-flex align-items-center p-0 text-success" href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                                    <i class="fas fa-user-circle fa-2x text-white"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    {$opciones_user}
                                </ul>
                            </div>
                        </div>
                        {$menu}
                    </div>
                </nav>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        document.querySelectorAll('.dropdown-submenu > a').forEach(function(element) {
                            element.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();

                                let submenu = this.nextElementSibling;
                                let icon = this.querySelector('.rotate-icon');

                                if (submenu && submenu.classList.contains('dropdown-menu')) {
                                    submenu.classList.toggle('show');

                                    // Rota el ícono si existe
                                    if (icon) {
                                        icon.classList.toggle('rotated');
                                    }

                                    // Cierra los demás submenús abiertos y resetea íconos
                                    this.closest('.dropdown-menu').querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(menu => {
                                        if (menu !== submenu) {
                                            menu.classList.remove('show');

                                            let siblingIcon = menu.parentElement.querySelector('.rotate-icon');
                                            if (siblingIcon) siblingIcon.classList.remove('rotated');
                                        }
                                    });
                                }
                            });
                        });

                        document.addEventListener('click', function(e) {
                            document.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(menu => {
                                menu.classList.remove('show');
                                let icon = menu.parentElement.querySelector('.rotate-icon');
                                if (icon) icon.classList.remove('rotated');
                            });
                        });

                        document.querySelectorAll('.collapse').forEach(function(collapseEl) {
                            collapseEl.addEventListener('show.bs.collapse', function(event) {
                                event.stopPropagation();
                                const parent = this.parentElement;
                                if (!parent) return;

                                const siblingsContainer = parent.parentElement;
                                if (!siblingsContainer) return;
                                const openSiblings = siblingsContainer.querySelectorAll('.collapse.show');

                                for (const sibling of openSiblings) {
                                    if (sibling !== this) {
                                        const collapseInstance = bootstrap.Collapse.getInstance(sibling);
                                        if (collapseInstance) {
                                            collapseInstance.hide();
                                        }
                                    }
                                }
                            });
                        });
                    });
                </script>
    HTML;
        $this->plantilla1 =
            <<<HTML
                <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
                    <div style="background-color: #1a659d !important;" class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
                        {content}
                    </div>
                </div>
    HTML;
        $this->plantilla2 =
            <<<HTML
                <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
                $this->navbar
                    <div class="position-relative overflow-hidden text-bg-light min-vh-90 d-flex px-1" style="margin-top: 60px;">
                        {content}
                    </div>
                </div>
    HTML;
        $plantilla = $pl == 1 ? $this->plantilla1 : $this->plantilla2;
        $modalDefault = self::getModal('modalDefault', 'tamDefault', 'bodyDefault');
        $script_user = $id_rol == 1 ? '</script><script src="' . $host . '/src/usuarios/general/js/cierre.js"></script>' : '';

        $this->baseHtml =
            <<<HTML
                <!doctype HTML>
                <html lang="es">
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>{title}</title>
                        <link rel="shortcut icon" type="image/png" href="{$host}/assets/images/favicon.png" />
                        <link rel="stylesheet" href="{$host}/assets/css/bootstrap.min.css">
                        <link rel="stylesheet" href="{$host}/assets/css/dataTables.bootstrap5.min.css">
                        <link rel="stylesheet" href="{$host}/assets/css/buttons.bootstrap5.css">
                        <link rel="stylesheet" href="{$host}/assets/css/font-awesome.min.css">
                        <link rel="stylesheet" href="{$host}/assets/css/style.css">
                        {additional_css}
                    </head>
                <body>
                    <input type="hidden" id="host" value="{$host}">
                    <input type="hidden" id="opc_caracter_js" value="{$caracter}">
                    <input type="hidden" id="opc_pto_js" value="{$pto}">
                    <div id="loadingOverlay" style="z-index: 9999;" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center">
                        <div class="text-center text-white">
                            <div class="spinner-grow text-light" role="status">
                                <img src="{$host}/assets/images/favicon.png" alt="logo sistema financiero">
                            </div>
                            <div>Procesando...</div>
                        </div>
                    </div>
                    {$modalDefault}
                    $plantilla
                    {modals}
                    <script src="{$host}/assets/js/awesomplete.min.js"></script>
                    <script src="{$host}/src/common/js/common.js"></script>
                    <script src="{$host}/src/common/js/idioma.js"></script>
                    {$script_user}
                    <script src="{$host}/src/common/js/users.js"></script>
                    <script src="{$host}/src/usuarios/login/js/sha.js"></script>
                    <script src="{$host}/assets/js/jquery.js"></script>
                    <script src="{$host}/assets/js/bootstrap.bundle.min.js"></script>
                    <script src="{$host}/assets/js/dataTables.js"></script>
                    <script src="{$host}/assets/js/dataTables.bootstrap5.min.js"></script>
                    <script src="{$host}/assets/js/dataTables.buttons.js"></script>
                    <script src="{$host}/assets/js/buttons.bootstrap5.js"></script>
                    <script src="{$host}/assets/js/sweetalert.js"></script>
                    <script src="{$host}/assets/js/modal-manager.js"></script>
                    <script src="{$host}/assets/js/all.min.js"></script>
                    {additional_scripts} 
                </body>
                </html>
    HTML;
    }

    /**
     * Añade una etiqueta <link> para un archivo CSS adicional.
     *
     * @param string $href La ruta del archivo CSS.
     */
    public function addCssFile($href)
    {
        // Usamos htmlspecialchars para escapar caracteres especiales en la URL por seguridad
        $this->additionalCss[] = '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
    }

    /**
     * Añade código CSS en línea dentro de una etiqueta <style>.
     *
     * @param string $cssCode El código CSS a añadir.
     */
    public function addCssInline($cssCode)
    {
        // Nota: Ten cuidado si $cssCode proviene de entrada de usuario,
        // puede requerir sanitización adicional.
        $this->additionalCss[] = '<style>' . $cssCode . '</style>';
    }


    /**
     * Añade una etiqueta <script> para un archivo JavaScript adicional.
     * Los scripts se añadirán antes del cierre de la etiqueta </body>.
     *
     * @param string $src La ruta del archivo JS.
     */
    public function addScriptFile($src)
    {
        $this->additionalScripts[] = '<script src="' . htmlspecialchars($src) . '"></script>';
    }

    /**
     * Añade código JavaScript en línea dentro de una etiqueta <script>.
     * Los scripts se añadirán antes del cierre de la etiqueta </body>.
     *
     * @param string $jsCode El código JS a añadir.
     */
    public function addScriptInline($jsCode)
    {
        // Nota: Ten cuidado si $jsCode proviene de entrada de usuario,
        // puede requerir sanitización adicional.
        $this->additionalScripts[] = '<script>' . $jsCode . '</script>';
    }


    /**
     * Añade el código HTML de un modal.
     * Los modales se añadirán antes del cierre de la etiqueta </body>,
     * usualmente después del contenido principal pero antes de los scripts.
     *
     * @param string $modalHtml El código HTML del modal.
     */
    public function addModal($modalHtml)
    {
        $this->modals[] = $modalHtml;
    }


    /**
     * Genera y devuelve el código HTML completo de la página.
     * Reemplaza los marcadores de posición en la estructura base con
     * el título, contenido, CSS, scripts y modales adicionales.
     *
     * @return string El código HTML completo de la página.
     */
    public function render()
    {
        $additionalCssString = implode("\n    ", $this->additionalCss);
        $additionalScriptsString = implode("\n    ", $this->additionalScripts);
        $modalsString = implode("\n", $this->modals);

        $placeholders = [
            '{title}',
            '{content}',
            '{additional_css}',
            '{additional_scripts}',
            '{modals}'
        ];

        $values = [
            $this->title,
            $this->content,
            $additionalCssString,
            $additionalScriptsString,
            $modalsString
        ];

        // Usamos str_replace para hacer el reemplazo en la cadena de la plantilla base
        $finalHtml = str_replace($placeholders, $values, $this->baseHtml);

        return $finalHtml;
    }

    public static function getHost()
    {
        return '/' . explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];
    }

    public static function getModal($id, $tam, $body)
    {
        return <<<HTML
            <div class="modal" id="{$id}" tabindex="-1" data-bs-keyboard="true" data-bs-backdrop="static">
                <div id="{$tam}" class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body" id="{$body}">
                        </div>
                    </div>
                </div>
            </div>
    HTML;
    }
}

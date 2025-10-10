<?php

namespace Config\Clases;

use Config\Clases\Sesion;

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
        $caracter = Sesion::Caracter();
        $pto = Sesion::Pto();
        $this->navbar =
            <<<HTML
                <nav style="background-color: #1a659d !important; border-bottom: 5px solid #16a085 !important;" class="navbar fixed-top text-white" data-navbarbg="skin6">
                    <div class="container-fluid">
                        <button class="navbar-toggler bg-light border-0 p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="dropdown">
                            <a class="nav-link navbar-brand d-flex align-items-center p-0" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{$host}/assets/images/logoFinanciero.png" alt="logo sistema financiero" width="150">
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Opción 1</a></li>
                                <li><a class="dropdown-item" href="#">Opción 2</a></li>
                            </ul>
                        </div>
                        <div style="background-color: #eafaf1;" class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                            <div class="offcanvas-header">
                                <h6 class="offcanvas-title" id="offcanvasNavbarLabel">MENÚ PRINCIPAL</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                            </div>
                            <div class="offcanvas-body fs-6">
                                <ul class="btn-toggle-nav list-unstyled fw-normal">
                                    <li>
                                        <a href="#nomina-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                            <span class="d-flex align-items-center text-primary">
                                                <i class="fas fa-table fa-lg me-2"></i> Nómina
                                            </span>
                                            <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                        </a>
                                        <div class="collapse shadow" id="nomina-collapse">
                                            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                <li>
                                                    <a href="#general-collapse" class="nav-link text-secondary d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-primary">
                                                            <i class="fas fa-tags fa-sm me-2"></i> General
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="general-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/nomina/configuracion/php/index.php" class="nav-link text-primary p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{$host}/src/nomina/empleados/php/index.php" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-users me-2 fa-fw"></i> Empleados
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{$host}/src/nomina/horas_extra/php/index.php" class="nav-link text-info p-1 sombra">
                                                                <i class="fas fa-user-clock me-2 fa-fw"></i> Horas Extra
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <li>
                                                    <a href="#liquidacion-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-success">
                                                            <i class="fas fa-file-invoice-dollar fa-sm me-2"></i> Liquidación
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="liquidacion-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/nomina/liquidacion/php/index.php" class="nav-link text-primary p-1 sombra">
                                                                <i class="far fa-calendar-alt me-2 fa-fw"></i> Liquidar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{$host}/src/nomina/liquidado/php/index.php" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-check-double me-2 fa-fw"></i> Liquidado
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <li>
                                                    <a href="#reporte-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-info">
                                                            <i class="fas fa-file-invoice-dollar fa-sm me-2"></i> Reportes
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="reporte-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="#" class="nav-link text-primary p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Soporte NE
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-users me-2 fa-fw"></i> Certificados
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-info p-1 sombra">
                                                                <i class="fas fa-user-clock me-2 fa-fw"></i> Informes
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-muted p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </ul>
                                        </div>
                                    </li>

                                    <li>
                                        <a href="#terceros-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                            <span class="d-flex align-items-center text-success">
                                                <i class="fas fa-users fa-lg me-2"></i> Terceros
                                            </span>
                                            <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                        </a>
                                        <div class="collapse shadow" id="terceros-collapse">
                                            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                <li>
                                                    <a href="#otro-collapse" class="nav-link text-secondary d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-success">
                                                            <i class="fas fa-tags fa-sm me-2"></i> General
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="otro-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/nomina/configuracion/php/index.php" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-warning p-1 sombra">
                                                                <i class="fas fa-users me-2 fa-fw"></i> Empleados
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-primary p-1 sombra">
                                                                <i class="fas fa-user-clock me-2 fa-fw"></i> Horas Extra
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <li>
                                                    <a href="#sd-collapse" class="nav-link text-secondary d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-warning">
                                                            <i class="fas fa-file-invoice-dollar fa-sm me-2"></i> Liquidación
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-primary"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="sd-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/nomina/configuracion/php/index.php" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{$host}/src/nomina/empleados/php/index.php" class="nav-link text-warning p-1 sombra">
                                                                <i class="fas fa-users me-2 fa-fw"></i> Empleados
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" class="nav-link text-primary p-1 sombra">
                                                                <i class="fas fa-user-clock me-2 fa-fw"></i> Horas Extra
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <li>
                                                    <a href="#" class="nav-link text-info p-1 sombra">
                                                        <i class="fas fa-caret-right me-2 fa-fw"></i> Subopcion 4
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <a href="#contratacion-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                            <span class="d-flex align-items-center text-info">
                                                <i class="fas fa-users fa-lg me-2"></i> Contratación
                                            </span>
                                            <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                        </a>
                                        <div class="collapse shadow" id="contratacion-collapse">
                                            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                <li>
                                                    <a href="{$host}/src/contratacion/gestion/lista_tipos.php" class="nav-link text-primary p-1 sombra">
                                                        <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{$host}/src/contratacion/adquisiciones/lista_adquisiciones.php" class="nav-link text-success p-1 sombra">
                                                        <i class="fas fa-shopping-bag me-2 fa-fw"></i> Compras
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="nav-link text-info p-1 sombra">
                                                        <i class="fas fa-ticket-alt me-2 fa-fw"></i> No obligados
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <a href="#dash-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                            <span class="d-flex align-items-center text-primary">
                                                <i class="fas fa-table fa-lg me-2"></i> Dashboard
                                            </span>
                                            <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                        </a>
                                        <div class="collapse" id="dash-collapse">
                                            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                <li>
                                                    <a href="#dash_general-collapse" class="nav-link text-secondary d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-primary">
                                                            <i class="fas fa-tags fa-sm me-2"></i> General
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse" id="dash_general-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/configuracion/php/index.php" class="nav-link text-primary p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                                            </a>
                                                        </li>                                                        
                                                    </ul>
                                                </div>
                                                <li>
                                                    <a href="#dash_ejecucion-collapse" class="nav-link d-flex justify-content-between align-items-center p-1 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                                        <span class="d-flex align-items-center text-success">
                                                            <i class="fas fa-file-invoice-dollar fa-sm me-2"></i> Ejecución
                                                        </span>
                                                        <i class="fas fa-chevron-circle-right ms-auto collapse-icon text-muted"></i> 
                                                    </a>
                                                </li>
                                                <div class="collapse shadow" id="dash_ejecucion-collapse">
                                                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                                        <li>
                                                            <a href="{$host}/src/personalizados/php/index.php" class="nav-link text-success p-1 sombra">
                                                                <i class="fas fa-cogs me-2 fa-fw"></i> Personalizados
                                                            </a>
                                                        </li>                                                        
                                                    </ul>
                                                </div>

                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
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
                    <div id="loadingOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center" style="z-index: 2000;">
                        <div class="text-center text-white">
                            <div class="spinner-grow text-light" role="status">
                                <img src="{$host}/assets/images/favicon.png" alt="logo sistema financiero">
                            </div>
                            <div>Procesando...</div>
                        </div>
                    </div>
                    $plantilla
                    {modals}
                    <script src="{$host}/assets/js/awesomplete.min.js"></script>
                    <script src="{$host}/src/common/js/common.js"></script>
                    <script src="{$host}/src/common/js/idioma.js"></script>
                    <script src="{$host}/assets/js/jquery.js"></script>
                    <script src="{$host}/assets/js/bootstrap.bundle.min.js"></script>
                    <script src="{$host}/assets/js/dataTables.js"></script>
                    <script src="{$host}/assets/js/dataTables.bootstrap5.min.js"></script>
                    <script src="{$host}/assets/js/dataTables.buttons.js"></script>
                    <script src="{$host}/assets/js/buttons.bootstrap5.js"></script>
                    <script src="{$host}/assets/js/sweetalert.js"></script>
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

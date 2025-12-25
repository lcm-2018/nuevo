<?php

namespace Config\Clases;

use Src\Common\Php\Clases\Permisos;

class Menu
{
    private $conexion;
    private $id_rol;
    private $id_user;
    private $permisos;
    private $opciones;
    private $modulos;
    private $host;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion();

        $this->id_rol   = $_SESSION['rol'] ?? null;
        $this->id_user  = $_SESSION['id_user'] ?? null;
        $this->permisos = new Permisos();
        $this->host     = Plantilla::getHost();

        // Cargamos los datos una sola vez para todos los métodos
        $this->opciones = $this->permisos->PermisoOpciones($this->id_user);
        $modulosData    = $this->permisos->getPermisosModulos($this->id_user);
        $this->modulos  = array_column($modulosData, 'estado', 'id_modulo');
    }

    /**
     * Renderiza el contenedor principal del menú
     */
    public function render()
    {
        return <<<HTML
        <div style="background-color: #eafaf1;" class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">
            <div class="offcanvas-header text-white" style="background: linear-gradient(135deg, #1a659d 0%, #16a085 100%) !important; border-bottom: 5px solid #16a085 !important;">
                <h6 class="offcanvas-title"><strong>MENÚ PRINCIPAL</strong></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body fs-6">
                <ul class="btn-toggle-nav list-unstyled fw-normal">
                    {$this->getMenuNomina()}
                    {$this->getMenuTerceros()}
                    {$this->getMenuContratacion()}
                    {$this->getMenuPresupuesto()}
                    {$this->getMenuContabilidad()}
                    {$this->getMenuTesoreria()}
                    {$this->getMenuAlmacen()}
                    {$this->getMenuActivosFijos()}
                    {$this->getMenuFinanciero()}
                    {$this->getMenuDashboard()}
                </ul>
            </div>
        </div>
        HTML;
    }

    /**
     * MODULO NOMINA (51)
     */
    private function getMenuNomina(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[51] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // --- SECCIÓN: GENERAL ---
        $nom_configuracion = $this->renderSubOption(5101, 'fas fa-cogs', 'Configuración', 'nomina/configuracion', 'text-primary');
        $nom_empleados     = $this->renderSubOption(5102, 'fas fa-users', 'Empleados', 'nomina/empleados', 'text-success');
        $nom_horas_extra   = $this->renderSubOption(5103, 'fas fa-user-clock', 'Horas Extra', 'nomina/horas_extra', 'text-info');

        $nom_general = '';
        if ($nom_configuracion || $nom_empleados || $nom_horas_extra) {
            $nom_general = $this->wrapCollapse('general-collapse', 'fas fa-tags', 'General', "{$nom_configuracion}{$nom_empleados}{$nom_horas_extra}", 'text-primary');
        }

        // --- SECCIÓN: LIQUIDACIÓN ---
        $nom_liquidar  = $this->renderSubOption(5104, 'far fa-calendar-alt', 'Liquidar', 'nomina/liquidacion', 'text-primary');
        $nom_liquidado = $this->renderSubOption(5105, 'fas fa-check-double', 'Liquidado', 'nomina/liquidado', 'text-success');

        $nom_liquidacion = '';
        if ($nom_liquidar || $nom_liquidado) {
            $nom_liquidacion = $this->wrapCollapse('liquidacion-collapse', 'fas fa-file-invoice-dollar', 'Liquidación', "{$nom_liquidar}{$nom_liquidado}", 'text-success');
        }

        // --- SECCIÓN: REPORTES ---
        $nom_reportes = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5106, 0) || $this->id_rol == 1) {
            $nom_reportes = $this->wrapCollapse('reporte-collapse', 'fas fa-file-invoice-dollar', 'Reportes', "
                <li><a href='javascript:void(0)' class='nav-link text-primary px-1 py-2 sombra'><i class='fas fa-cogs me-2 fa-fw'></i> Soporte NE</a></li>
                <li><a href='javascript:void(0)' class='nav-link text-success px-1 py-2 sombra'><i class='fas fa-users me-2 fa-fw'></i> Certificados</a></li>
                <li><a href='javascript:void(0)' class='nav-link text-info px-1 py-2 sombra'><i class='fas fa-user-clock me-2 fa-fw'></i> Informes</a></li>
                <li><a href='javascript:void(0)' class='nav-link text-muted px-1 py-2 sombra opcion_personalizado' txt_id_opcion='5199'><i class='fas fa-cogs me-2 fa-fw'></i> Inf. Personalizados</a></li>
            ", 'text-info');
        }

        // 2. Retorno del bloque completo de Nómina
        return <<<HTML
        <li>
            <a href="#nomina-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                <span class="d-flex align-items-center">
                    <div class="menu-icon-wrapper gradient-nomina">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="menu-text">Nómina</span>
                </span>
                <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
            </a>
            <div class="collapse shadow rounded-3" id="nomina-collapse">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                    {$nom_general}
                    {$nom_liquidacion}
                    {$nom_reportes}
                </ul>
            </div>
        </li>
        HTML;
    }

    /**
     * HELPER: Renderiza una opción de lista <li> verificando permisos
     */
    private function renderSubOption(int $id_opcion, string $icon, string $label, string $relativePath, string $colorClass): string
    {
        if ($this->permisos->PermisosUsuario($this->opciones, $id_opcion, 0) || $this->id_rol == 1) {
            // Si la ruta no termina en .php, asumimos que es una carpeta y agregamos el estándar
            $url = (strpos($relativePath, '.php') !== false)
                ? "{$this->host}/src/{$relativePath}"
                : "{$this->host}/src/{$relativePath}/php/index.php";

            return <<<HTML
        <li>
            <a href="{$url}" class="nav-link {$colorClass} px-1 py-2 sombra">
                <i class="{$icon} me-2 fa-fw"></i> {$label}
            </a>
        </li>
        HTML;
        }
        return '';
    }

    /**
     * HELPER: Envuelve contenido en un sub-colapsable
     */
    private function wrapCollapse(string $id, string $icon, string $label, string $content, string $colorClass): string
    {
        return <<<HTML
            <li>
                <a href="#{$id}" class="nav-link d-flex justify-content-between align-items-center px-1 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                    <span class="d-flex align-items-center {$colorClass}">
                        <i class="{$icon} fa-sm me-2"></i> {$label}
                    </span>
                    <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                </a>
                <div class="collapse shadow rounded-3" id="{$id}">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                        {$content}
                    </ul>
                </div>
            </li>
        HTML;
    }
    /**
     * MODULO TERCEROS (52)
     */
    private function getMenuTerceros(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[52] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para mostrar el contenedor principal
        if (!($this->permisos->PermisosUsuario($this->opciones, 5201, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5202, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5299, 0) ||
            $this->id_rol == 1)) {
            return '';
        }

        // --- OPCIÓN: CONFIGURACIÓN (5201) ---
        // Nota: Pasamos la ruta completa relativa a /src/
        $ter_configuracion = $this->renderSubOption(5201, 'fas fa-cogs', 'Configuración', 'terceros/set/configuracion.php', 'text-primary');

        // --- OPCIÓN: GESTIÓN (5202) ---
        $ter_gestion = $this->renderSubOption(5202, 'fas fa-users', 'Gestión', 'terceros/gestion/listterceros.php', 'text-success');

        // --- OPCIÓN: INFORMES PERSONALIZADOS (5299) ---
        // Como esta opción es un javascript:void(0), la manejamos manualmente o con lógica especial
        $ter_personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5299, 0) || $this->id_rol == 1) {
            $ter_personalizados = <<<HTML
            <li>
                <a href="javascript:void(0)" class="nav-link text-info px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5299">
                    <i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados
                </a>
            </li>
        HTML;
        }

        // 3. Retorno del bloque completo de Terceros
        return
            <<<HTML
                <li>
                    <a href="#terceros-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-terceros">
                                <i class="fas fa-address-book"></i>
                            </div>
                            <span class="menu-text">Terceros</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="terceros-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$ter_configuracion}
                            {$ter_gestion}
                            {$ter_personalizados}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO CONTRATACIÓN (53)
     */
    private function getMenuContratacion(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[53] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!($this->permisos->PermisosUsuario($this->opciones, 5301, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5302, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5303, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5399, 0) ||
            $this->id_rol == 1)) {
            return '';
        }

        // --- OPCIONES USANDO EL HELPER ---
        $cont_configuracion = $this->renderSubOption(5301, 'fas fa-cogs', 'Configuración', 'contratacion/gestion/lista_tipos.php', 'text-primary');
        $cont_compras       = $this->renderSubOption(5302, 'fas fa-shopping-bag', 'Compras', 'contratacion/adquisiciones/lista_adquisiciones.php', 'text-success');
        $cont_no_obligados  = $this->renderSubOption(5303, 'fas fa-ticket-alt', 'No obligados', 'contratacion/no_obligados/listar_facturas.php', 'text-info');

        // --- OPCIÓN ESPECIAL JAVASCRIPT ---
        $cont_personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5399, 0) || $this->id_rol == 1) {
            $cont_personalizados = <<<HTML
            <li>
                <a href="javascript:void(0)" class="nav-link text-muted px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5399">
                    <i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados
                </a>
            </li>
        HTML;
        }

        // 3. Retorno de la estructura HTML
        return
            <<<HTML
                <li>
                    <a href="#contratacion-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-contratacion">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <span class="menu-text">Contratación</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="contratacion-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$cont_configuracion}
                            {$cont_compras}
                            {$cont_no_obligados}
                            {$cont_personalizados}
                        </ul>
                    </div>
                </li>
            HTML;
    }
    /**
     * MODULO PRESUPUESTO (54)
     */
    private function getMenuPresupuesto(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[54] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!($this->permisos->PermisosUsuario($this->opciones, 5401, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5402, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5499, 0) ||
            $this->id_rol == 1)) {
            return '';
        }

        // --- OPCIONES USANDO EL HELPER ---
        // El helper detectará el ".php" y construirá la ruta: host/src/presupuesto/lista_presupuestos.php
        $ppto_gestion  = $this->renderSubOption(5401, 'fas fa-cogs', 'Gestión', 'presupuesto/lista_presupuestos.php', 'text-primary');
        $ppto_informes = $this->renderSubOption(5402, 'fas fa-shopping-bag', 'Informes', 'presupuesto/lista_informes_presupuesto.php', 'text-success');

        // --- OPCIÓN ESPECIAL JAVASCRIPT ---
        $ppto_personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5499, 0) || $this->id_rol == 1) {
            $ppto_personalizados = <<<HTML
            <li>
                <a href="javascript:void(0)" class="nav-link text-info px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5499">
                    <i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados
                </a>
            </li>
        HTML;
        }

        // 3. Retorno de la estructura HTML
        return
            <<<HTML
                <li>
                    <a href="#ppto-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-presupuesto">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <span class="menu-text">Presupuesto</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="ppto-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$ppto_gestion}
                            {$ppto_informes}
                            {$ppto_personalizados}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO FINANCIERO (61)
     * Este módulo es un acceso directo, no contiene submenús colapsables.
     */
    private function getMenuFinanciero(): string
    {
        // 1. Verificación global del módulo 61
        if (!($this->modulos[61] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permiso para la opción 6101 (Informes Financieros)
        if ($this->permisos->PermisosUsuario($this->opciones, 6101, 0) || $this->id_rol == 1) {
            return <<<HTML
        <li>
            <a href="{$this->host}/src/financiero/informes/lista_informes_financiero.php" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra">
                <span class="d-flex align-items-center">
                    <div class="menu-icon-wrapper gradient-financiero">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="menu-text">Informes Financieros</span>
                </span>
            </a>
        </li>
        HTML;
        }

        return '';
    }

    /**
     * MODULO CONTABILIDAD (55)
     */
    private function getMenuContabilidad(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[55] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!(
            $this->permisos->PermisosUsuario($this->opciones, 5501, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5502, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5503, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5599, 0) ||
            $this->id_rol == 1
        )) {
            return '';
        }

        // --- OPCIONES PRINCIPALES ---
        $movimientos    = $this->renderSubOption(5501, 'fas fa-copy', 'Movimientos', 'contabilidad/lista_documentos_mov.php', 'text-primary');
        $facturacion    = $this->renderSubOption(5502, 'fas fa-file-invoice', 'Facturación', 'contabilidad/lista_documentos_invoice.php', 'text-success');
        $informes       = $this->renderSubOption(5503, 'far fa-file', 'Informes', 'contabilidad/informes/lista_informes_contabilidad.php', 'text-muted');

        // Opción especial con JS
        $personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5599, 0) || $this->id_rol == 1) {
            $personalizados = <<<HTML
            <li>
                <a href="javascript:void(0)" class="nav-link text-secondary px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5599">
                    <i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados
                </a>
            </li>
        HTML;
        }

        // --- SUBMENÚ "MÁS" (Sección anidada) ---
        // Esta sección se construye manualmente para respetar las rutas específicas
        $mas_items = <<<HTML
        <li>
            <a href="{$this->host}/src/contabilidad/lista_plan_cuentas.php" class="nav-link text-primary px-1 py-2 sombra">
                <i class="fas fa-book me-2 fa-fw"></i> PUC
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/lista_documentos_fuente.php" class="nav-link text-success px-1 py-2 sombra">
                <i class="fas fa-file-invoice me-2 fa-fw"></i> Documentos
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/lista_impuestos.php" class="nav-link text-info px-1 py-2 sombra">
                <i class="fas fa-folder-open me-2 fa-fw"></i> Impuestos
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/php/cuentas_fac/index.php" class="nav-link text-muted px-1 py-2 sombra">
                <i class="fas fa-calculator me-2 fa-fw"></i> Ctas. Facturación
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/php/centro_costos/index.php" class="nav-link text-warning px-1 py-2 sombra">
                <i class="fas fa-kaaba me-2 fa-fw"></i> Centros de Costo
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/php/subgrupos/index.php" class="nav-link text-secondary px-1 py-2 sombra">
                <i class="fas fa-layer-group me-2 fa-fw"></i> Subgrupos
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/php/tipos_orden_egreso/index.php" class="nav-link text-muted px-1 py-2 sombra">
                <i class="fas fa-sign-out-alt me-2 fa-fw"></i> Tipo Orden Egreso
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/contabilidad/php/tipos_orden_ingreso/index.php" class="nav-link text-success-emphasis px-1 py-2 sombra">
                <i class="fas fa-sign-in-alt me-2 fa-fw"></i> Tipo Orden Ingreso
            </a>
        </li>
        HTML;

        // Usamos el helper wrapCollapse para la sección "Más"
        $conta_mas = $this->wrapCollapse('mas-collapse', 'fas fa-bars', 'Mas', $mas_items, 'text-warning');

        // 3. Retorno del bloque completo de Contabilidad
        return
            <<<HTML
                <li>
                    <a href="#conta-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-contabilidad">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <span class="menu-text">Contabilidad</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="conta-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$movimientos}
                            {$facturacion}
                            {$informes}
                            {$personalizados}
                            {$conta_mas}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO TESORERÍA (56)
     */
    private function getMenuTesoreria(): string
    {
        // 1. Verificación global del módulo 56
        if (!($this->modulos[56] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!(
            $this->permisos->PermisosUsuario($this->opciones, 5601, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5602, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5603, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5604, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5605, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5699, 0) ||
            $this->id_rol == 1
        )) {
            return '';
        }

        // --- OPCIONES CON JAVASCRIPT PERSONALIZADO ---
        // Mantenemos los atributos text="x" y la clase "tesoreria" para que tu JS funcione
        $tes_pagos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5601, 0) || $this->id_rol == 1) {
            $tes_pagos = '<li><a href="javascript:void(0)" text="1" class="nav-link text-primary px-1 py-2 sombra tesoreria"><i class="far fa-credit-card me-2 fa-fw"></i> Pagos</a></li>';
        }

        $tes_recaudos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5602, 0) || $this->id_rol == 1) {
            $tes_recaudos = '<li><a href="javascript:void(0)" text="2" class="nav-link text-success px-1 py-2 sombra tesoreria"><i class="fas fa-hand-holding-dollar me-2 fa-fw"></i> Recaudos</a></li>';
        }

        $tes_traslados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5603, 0) || $this->id_rol == 1) {
            $tes_traslados = '<li><a href="javascript:void(0)" text="3" class="nav-link text-info px-1 py-2 sombra tesoreria"><i class="fas fa-sync-alt me-2 fa-fw"></i> Traslados</a></li>';
        }

        $tes_caja_menor = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5604, 0) || $this->id_rol == 1) {
            $tes_caja_menor = '<li><a href="javascript:void(0)" text="4" class="nav-link text-muted px-1 py-2 sombra tesoreria"><i class="fas fa-cash-register me-2 fa-fw"></i> Caja Menor</a></li>';
        }

        // --- OPCIÓN INFORMES (LINK NORMAL) ---
        $tes_informes = $this->renderSubOption(5605, 'far fa-file', 'Informes', 'tesoreria/lista_informes_tesoreria.php', 'text-warning');

        // --- SUBMENÚ "MÁS" (Sección anidada) ---
        $mas_items = <<<HTML
        <li>
            <a href="{$this->host}/src/tesoreria/conciliacion_bancaria.php" class="nav-link text-primary px-1 py-2 sombra">
                <i class="fas fa-magnifying-glass-dollar me-2 fa-fw"></i> Conciliaciones
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/tesoreria/lista_cuentas_banco.php" class="nav-link text-success px-1 py-2 sombra">
                <i class="fas fa-building-columns me-2 fa-fw"></i> Cuentas
            </a>
        </li>
        <li>
            <a href="{$this->host}/src/tesoreria/lista_chequeras_gen.php.php" class="nav-link text-info px-1 py-2 sombra">
                <i class="fas fa-money-check-dollar me-2 fa-fw"></i> Chequeras
            </a>
        </li>
        HTML;

        $tes_mas = $this->wrapCollapse('mastes-collapse', 'fas fa-bars', 'Mas', $mas_items, 'text-secondary');

        // --- OPCIÓN PERSONALIZADOS ---
        $tes_personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5699, 0) || $this->id_rol == 1) {
            $tes_personalizados = '<li><a href="javascript:void(0)" class="nav-link text-info px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5699"><i class="fas fa-cogs me-2 fa-fw"></i> Inf. Personalizados</a></li>';
        }

        // 3. Retorno del bloque completo de Tesorería
        return
            <<<HTML
                <li>
                    <a href="#tesoreria-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-tesoreria">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <span class="menu-text">Tesorería</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="tesoreria-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$tes_pagos}
                            {$tes_recaudos}
                            {$tes_traslados}
                            {$tes_caja_menor}
                            {$tes_informes}
                            {$tes_mas}
                            {$tes_personalizados}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO ALMACÉN (50)
     */
    private function getMenuAlmacen(): string
    {
        // 1. Verificación global del módulo
        if (!($this->modulos[50] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!(
            $this->permisos->PermisosUsuario($this->opciones, 5001, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5002, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5003, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5004, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5099, 0) ||
            $this->id_rol == 1
        )) {
            return '';
        }

        // --- SECCIÓN: GENERAL (5001) ---
        $alm_general = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5001, 0) || $this->id_rol == 1) {
            $gen_items =
                <<<HTML
                    <li><a href="{$this->host}/src/almacen/php/centrocosto_areas/index.php?var=3" class="nav-link text-primary px-1 py-2 sombra"><i class="fa fa-sitemap me-2 fa-fw"></i> Áreas</a></li>
                    <li><a href="{$this->host}/src/almacen/php/pres_comercial/index.php?var=3" class="nav-link text-success px-1 py-2 sombra"><i class="fas fa-ticket-alt me-2 fa-fw"></i> Presentación Comercial</a></li>
                    <li><a href="{$this->host}/src/almacen/php/marcas/index.php?var=3" class="nav-link text-info px-1 py-2 sombra"><i class="fab fa-staylinked me-2 fa-fw"></i> Marcas</a></li>
                    <li><a href="{$this->host}/src/almacen/php/laboratorios/index.php?var=3" class="nav-link text-warning px-1 py-2 sombra"><i class="fas fa-bong me-2 fa-fw"></i> Laboratorios</a></li>
                    <li><a href="{$this->host}/src/almacen/php/articulos/index.php?var=3" class="nav-link text-danger px-1 py-2 sombra"><i class="far fa-list-alt me-2 fa-fw"></i> Artículos</a></li>
                HTML;
            $alm_general = $this->wrapCollapse('almacen-general-collapse', 'fa fa-tags', 'General', $gen_items, 'text-primary');
        }

        // --- SECCIÓN: PEDIDOS (5002) ---
        $alm_pedidos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5002, 0) || $this->id_rol == 1) {
            $ped_items =
                <<<HTML
                    <li><a href="{$this->host}/src/almacen/php/pedidos_alm/index.php" class="nav-link text-primary px-1 py-2 sombra"><i class="fas fa-kaaba me-2 fa-fw"></i> Almacén</a></li>
                    <li><a href="{$this->host}/src/almacen/php/pedidos_bod/index.php" class="nav-link text-success px-1 py-2 sombra"><i class="fas fa-coins me-2 fa-fw"></i> Bodega</a></li>
                    <li><a href="{$this->host}/src/almacen/php/pedidos_cec/index.php" class="nav-link text-info px-1 py-2 sombra"><i class="fa fa-th-large me-2 fa-fw"></i> Dependencia</a></li>
                    <li><a href="{$this->host}/src/almacen/php/pedidos_spsr/index.php" class="nav-link text-warning px-1 py-2 sombra"><i class="fas fa-coins me-2 fa-fw"></i> Bodega SPSR</a></li>
                HTML;
            $alm_pedidos = $this->wrapCollapse('almacen-pedidos-collapse', 'fa fa-pencil-square-o', 'Pedidos', $ped_items, 'text-success');
        }

        // --- SECCIÓN: MOVIMIENTOS (5003) ---
        $alm_movimientos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5003, 0) || $this->id_rol == 1) {
            $mov_items =
                <<<HTML
                    <li><a href="{$this->host}/src/almacen/php/ingresos/index.php" class="nav-link text-primary px-1 py-2 sombra"><i class="fas fa-door-open me-2 fa-fw"></i> Ingresos</a></li>
                    <li><a href="{$this->host}/src/almacen/php/egresos/index.php" class="nav-link text-success px-1 py-2 sombra"><i class="fas fa-sign-out-alt me-2 fa-fw"></i> Egresos</a></li>
                    <li><a href="{$this->host}/src/almacen/php/traslados/index.php" class="nav-link text-info px-1 py-2 sombra"><i class="fas fa-exchange-alt me-2 fa-fw"></i> Traslados</a></li>
                    <li><a href="{$this->host}/src/almacen/php/traslados_spsr/index.php" class="nav-link text-warning px-1 py-2 sombra"><i class="fas fa-dolly-flatbed me-2 fa-fw"></i> Traslados SPSR</a></li>
                    <li><a href="{$this->host}/src/almacen/php/recalcular_kardex/index.php" class="nav-link text-danger px-1 py-2 sombra"><i class="fa fa-cogs me-2 fa-fw"></i> Recalcula Mtos.</a></li>
                HTML;
            $alm_movimientos = $this->wrapCollapse('almacen-movimientos-collapse', 'fas fa-sliders', 'Movimientos', $mov_items, 'text-info');
        }

        // --- SECCIÓN: REPORTES (5004) ---
        $alm_reportes = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5004, 0) || $this->id_rol == 1) {
            $rep_items =
                <<<HTML
                    <li><a href="{$this->host}/src/almacen/php/existencia_articulo/index.php" class="nav-link text-primary px-1 py-2 sombra"><i class="fas fa-chart-bar me-2 fa-fw"></i> Ex. General</a></li>
                    <li><a href="{$this->host}/src/almacen/php/existencia_lote/index.php" class="nav-link text-success px-1 py-2 sombra"><i class="fas fa-chart-bar me-2 fa-fw"></i> Ex. Detallada</a></li>
                    <li><a href="{$this->host}/src/almacen/php/existencia_fecha/index.php" class="nav-link text-info px-1 py-2 sombra"><i class="fas fa-chart-bar me-2 fa-fw"></i> Ex. a una Fecha</a></li>
                    <li><a href="{$this->host}/src/almacen/php/movimiento_periodo/index.php" class="nav-link text-warning px-1 py-2 sombra"><i class="fas fa-chart-bar me-2 fa-fw"></i> Mov. Periodo</a></li>
                HTML;
            $alm_reportes = $this->wrapCollapse('almacen-reportes-collapse', 'fa fa-map-o', 'Reportes', $rep_items, 'text-warning');
        }

        // --- PERSONALIZADOS (5099) ---
        $alm_personalizados = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5099, 0) || $this->id_rol == 1) {
            $alm_personalizados =
                <<<HTML
                    <li>
                        <a href="javascript:void(0)" class="nav-link text-danger px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5099">
                            <i class="fas fa-cogs me-2 fa-fw"></i> Personalizados
                        </a>
                    </li>
                HTML;
        }

        // 3. Retorno de la estructura HTML final
        return
            <<<HTML
                <li>
                    <a href="#almacen-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-almacen">
                                <i class="fas fa-box"></i>
                            </div>
                            <span class="menu-text">Almacén</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="almacen-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$alm_general}
                            {$alm_pedidos}
                            {$alm_movimientos}
                            {$alm_reportes}
                            {$alm_personalizados}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO ACTIVOS FIJOS (57)
     */
    private function getMenuActivosFijos(): string
    {
        // 1. Verificación global del módulo 57
        if (!($this->modulos[57] > 0 || $this->id_rol == 1)) {
            return '';
        }

        // 2. Verificación de permisos para el contenedor principal
        if (!(
            $this->permisos->PermisosUsuario($this->opciones, 5701, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5702, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5703, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5704, 0) ||
            $this->permisos->PermisosUsuario($this->opciones, 5799, 0) ||
            $this->id_rol == 1
        )) {
            return '';
        }

        // --- SECCIÓN: GENERAL (5701) ---
        $af_general = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5701, 0) || $this->id_rol == 1) {
            $gen_items =
                <<<HTML
                    <li><a href="{$this->host}/src/activos_fijos/php/marcas/index.php?var=3" class="nav-link text-primary px-1 py-2 sombra"><i class="fab fa-staylinked me-2 fa-fw"></i> Marcas</a></li>
                    <li><a href="{$this->host}/src/activos_fijos/php/articulos/index.php?var=3" class="nav-link text-success px-1 py-2 sombra"><i class="far fa-list-alt me-2 fa-fw"></i> Artículos</a></li>
                HTML;
            $af_general = $this->wrapCollapse('af-general-collapse', 'fa fa-tags', 'General', $gen_items, 'text-primary');
        }

        // --- SECCIÓN: PEDIDOS (5702) ---
        $af_pedidos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5702, 0) || $this->id_rol == 1) {
            $ped_items =
                <<<HTML
                    <li><a href="{$this->host}/src/activos_fijos/php/pedidos/index.php" class="nav-link text-primary px-1 py-2 sombra"><i class="fas fa-chalkboard me-2 fa-fw"></i> Activos Fijos</a></li>
                HTML;
            $af_pedidos = $this->wrapCollapse('af-pedidos-collapse', 'fa fa-pencil-square-o', 'Pedidos', $ped_items, 'text-success');
        }

        // --- SECCIÓN: MOVIMIENTOS (5703) ---
        $af_movimientos = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5703, 0) || $this->id_rol == 1) {
            $mov_items =
                <<<HTML
                    <li><a href="{$this->host}/src/activos_fijos/php/ingresos/index.php?var=3" class="nav-link text-primary px-1 py-2 sombra"><i class="fas fa-door-open me-2 fa-fw"></i> Ingresos</a></li>
                    <li><a href="{$this->host}/src/activos_fijos/php/traslados/index.php?var=3" class="nav-link text-success px-1 py-2 sombra"><i class="fas fa-luggage-cart me-2 fa-fw"></i> Traslados</a></li>
                    <li><a href="{$this->host}/src/activos_fijos/php/bajas/index.php?var=3" class="nav-link text-info px-1 py-2 sombra"><i class="fas fa-level-down-alt me-2 fa-fw"></i> Dar de baja</a></li>
                HTML;
            $af_movimientos = $this->wrapCollapse('af-movimientos-collapse', 'fas fa-sliders', 'Movimientos', $mov_items, 'text-info');
        }

        // --- SECCIÓN: MANTENIMIENTO (5704) ---
        $af_mantenimiento = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5704, 0) || $this->id_rol == 1) {
            $mant_items =
                <<<HTML
                    <li><a href="{$this->host}/src/activos_fijos/php/hojavida/index.php?var=3" class="nav-link text-primary px-1 py-2 sombra"><i class="fa fa-newspaper-o me-2 fa-fw"></i> Hoja de Vida</a></li>
                    <li><a href="{$this->host}/src/activos_fijos/php/mantenimientos/index.php?var=3" class="nav-link text-success px-1 py-2 sombra"><i class="fa fa-calendar-check-o me-2 fa-fw"></i> Registros</a></li>
                    <li><a href="{$this->host}/src/activos_fijos/php/mantenimiento_prog/index.php?var=3" class="nav-link text-info px-1 py-2 sombra"><i class="fas fa-sort-amount-down-alt me-2 fa-fw"></i> Progreso</a></li>
                HTML;
            $af_mantenimiento = $this->wrapCollapse('af-mantenimiento-collapse', 'fas fa-cogs', 'Mantenimiento', $mant_items, 'text-warning');
        }

        // --- SECCIÓN: REPORTES (5799) ---
        $af_reportes = '';
        if ($this->permisos->PermisosUsuario($this->opciones, 5799, 0) || $this->id_rol == 1) {
            $rep_items =
                <<<HTML
                    <li><a href="javascript:void(0)" class="nav-link text-danger px-1 py-2 sombra opcion_personalizado" txt_id_opcion="5799"><i class="fas fa-chart-bar me-2 fa-fw"></i> Personalizados</a></li>
                HTML;
            $af_reportes = $this->wrapCollapse('af-reportes-collapse', 'fa fa-map-o', 'Reportes', $rep_items, 'text-danger');
        }

        // 3. Retorno del bloque completo de Activos Fijos
        return
            <<<HTML
                <li>
                    <a href="#af-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-activos">
                                <i class="fas fa-building"></i>
                            </div>
                            <span class="menu-text">Activos Fijos</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse shadow rounded-3" id="af-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 pt-2 small ps-4 pe-3">
                            {$af_general}
                            {$af_pedidos}
                            {$af_movimientos}
                            {$af_mantenimiento}
                            {$af_reportes}
                        </ul>
                    </div>
                </li>
            HTML;
    }

    /**
     * MODULO DASHBOARD
     * Este módulo se muestra siempre (sin validación de permisos por ahora)
     */
    private function getMenuDashboard(): string
    {
        return
            <<<HTML
                <li>
                    <a href="#dash-collapse" class="nav-link d-flex justify-content-between align-items-center px-2 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <div class="menu-icon-wrapper gradient-dashboard">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <span class="menu-text">Dashboard</span>
                        </span>
                        <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                    </a>
                    <div class="collapse" id="dash-collapse">
                        <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                            <li>
                                <a href="#dash_general-collapse" class="nav-link text-secondary d-flex justify-content-between align-items-center px-1 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                    <span class="d-flex align-items-center text-primary-emphasis">
                                        <i class="fas fa-tags fa-sm me-2"></i> General
                                    </span>
                                    <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                                </a>
                            </li>
                            <div class="collapse" id="dash_general-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                    <li>
                                        <a href="{$this->host}/src/configuracion/php/index.php" class="nav-link text-primary px-1 py-2 sombra">
                                            <i class="fas fa-cogs me-2 fa-fw"></i> Configuración
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <li>
                                <a href="#dash_ejecucion-collapse" class="nav-link d-flex justify-content-between align-items-center px-1 py-2 sombra" data-bs-toggle="collapse" aria-expanded="false">
                                    <span class="d-flex align-items-center text-success">
                                        <i class="fas fa-file-invoice-dollar fa-sm me-2"></i> Ejecución
                                    </span>
                                    <i class="fas fa-chevron-right fa-xs ms-auto collapse-icon text-muted"></i> 
                                </a>
                            </li>
                            <div class="collapse shadow" id="dash_ejecucion-collapse">
                                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small ps-4">
                                    <li>
                                        <a href="{$this->host}/src/personalizados/php/index.php" class="nav-link text-success px-1 py-2 sombra">
                                            <i class="fas fa-cogs me-2 fa-fw"></i> Personalizados
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </ul>
                    </div>
                </li>
            HTML;
    }
}

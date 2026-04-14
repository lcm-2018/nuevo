<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../index.php");
    exit();
}

include_once '../../../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Nomina\Certificaciones\Php\Clases\Certificados;

$host = Plantilla::getHost();

$Certificados = new Certificados();
$tipos = $Certificados->getTiposCertificado();

// Iconos y colores por id_cert
$config_cert = [
    1 => ['icon' => 'fa-file-invoice-dollar', 'color' => '#1a6eb5', 'gradient' => 'linear-gradient(135deg,#1a6eb5 0%,#3498db 100%)'],
    2 => ['icon' => 'fa-id-card',             'color' => '#16a085', 'gradient' => 'linear-gradient(135deg,#16a085 0%,#1abc9c 100%)'],
    3 => ['icon' => 'fa-list-alt',            'color' => '#7d3c98', 'gradient' => 'linear-gradient(135deg,#7d3c98 0%,#9b59b6 100%)'],
    4 => ['icon' => 'fa-money-check-alt',     'color' => '#c0392b', 'gradient' => 'linear-gradient(135deg,#c0392b 0%,#e74c3c 100%)'],
];
$default_conf = ['icon' => 'fa-certificate', 'color' => '#566573', 'gradient' => 'linear-gradient(135deg,#566573 0%,#839192 100%)'];

// Construir tarjetas
$cards_html = '';
if (empty($tipos)) {
    $cards_html = <<<HTML
    <div class="col-12 text-center py-4">
        <i class="fas fa-exclamation-circle fa-2x text-muted mb-2 d-block"></i>
        <p class="text-muted small">No hay tipos de certificado en <code>nom_tipo_certificado</code>.</p>
    </div>
    HTML;
} else {
    foreach ($tipos as $tipo) {
        $id   = $tipo['id_cert'];
        $desc = mb_strtoupper($tipo['descripcion']);
        $conf = $config_cert[$id] ?? $default_conf;
        $icon = $conf['icon'];
        $grad = $conf['gradient'];
        $clr  = $conf['color'];

        // Botones según tipo de certificado
        if ($id == 1) {
            // Form 220: Imprimir (HTML en browser) + Excel (Consolidado)
            $btns_html =
                '<button type="button" class="btn-generar-cert btn flex-fill btn-sm btn-outline-danger"'
                . ' data-id-cert="' . $id . '" data-desc="' . $desc . '" data-formato="pdf" title="Imprimir">'
                . '<i class="fas fa-print me-1"></i> Imprimir</button>'
                . '<button type="button" class="btn-generar-cert btn flex-fill btn-sm btn-outline-success"'
                . ' data-id-cert="' . $id . '" data-desc="' . $desc . '" data-formato="excel" title="Descargar Consolidado Excel">'
                . '<i class="fas fa-file-excel me-1"></i> Excel</button>';
        } else {
            // Otros certificados: PDF + Word (comportamiento original)
            $btns_html =
                '<button type="button" class="btn-generar-cert btn flex-fill btn-sm btn-outline-danger"'
                . ' data-id-cert="' . $id . '" data-desc="' . $desc . '" data-formato="pdf" title="Generar PDF">'
                . '<i class="fas fa-file-pdf me-1"></i> PDF</button>'
                . '<button type="button" class="btn-generar-cert btn flex-fill btn-sm btn-outline-primary"'
                . ' data-id-cert="' . $id . '" data-desc="' . $desc . '" data-formato="word" title="Generar Word">'
                . '<i class="fas fa-file-word me-1"></i> Word</button>';
        }

        $cards_html .=
            '<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3 cert-col">' .
            '<div class="cert-card shadow" data-id-cert="' . $id . '" style="border-top: 4px solid ' . $clr . ';">' .
            '<div class="cert-card-header" style="background: ' . $grad . ';">' .
            '<i class="fas ' . $icon . ' cert-icon"></i>' .
            '<div class="cert-glow"></div>' .
            '</div>' .
            '<div class="cert-card-body">' .
            '<h6 class="cert-title">' . $desc . '</h6>' .
            '<p class="text-muted small mb-0">Certificado #' . $id . '</p>' .
            '</div>' .
            '<div class="cert-card-footer d-flex gap-1">' .
            $btns_html .
            '</div>' .
            '</div>' .
            '</div>';
    }
}

$num = count($tipos);
$anio = date('Y');

$content = <<<HTML
<style>
    /* ---- TARJETAS ---- */
    .cert-card {
        background: #fff; border-radius: 10px; overflow: hidden;
        transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.22s;
        height: 100%;
    }
    .cert-card:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 28px rgba(0,0,0,0.13) !important;
    }
    .cert-card-header {
        position: relative; height: 80px;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .cert-icon {
        font-size: 2.2rem; color: rgba(255,255,255,0.95);
        position: relative; z-index: 2; transition: transform 0.28s;
    }
    .cert-card:hover .cert-icon { transform: scale(1.18) rotate(-8deg); }
    .cert-glow {
        position: absolute; width: 90px; height: 90px;
        background: rgba(255,255,255,0.1); border-radius: 50%;
        bottom: -32px; right: -22px; pointer-events: none;
    }
    .cert-card-body { padding: 9px 12px 4px; }
    .cert-title { font-size: 0.82rem; font-weight: 700; color: #2c3e50; line-height: 1.3; margin-bottom: 1px; }
    .cert-card-footer { padding: 4px 10px 10px; }
    .cert-card-footer .btn { border-radius: 6px; font-size: 0.76rem; transition: opacity 0.15s; }
    .cert-card-footer .btn:hover { opacity: 0.85; }

    /* ---- PANEL DE FILTROS ---- */
    .filtros-cert {
        background: #f8f9fa;
        border: 1px solid #e2e6ea;
        border-radius: 8px;
        padding: 14px 16px 10px;
        margin-bottom: 16px;
    }
    .filtros-cert label { font-size: 0.78rem; font-weight: 600; color: #495057; margin-bottom: 2px; }
    .filtros-cert .form-control,
    .filtros-cert .form-select {
        font-size: 0.84rem;
    }
    .filtros-cert .seccion-titulo {
        font-size: 0.72rem;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 10px;
        padding-bottom: 4px;
    }

    #noResultsCert { display: none; }
</style>

<div class="card w-100">
    <div class="card-header bg-sofia text-white">
        <a class="btn btn-xs me-1 p-0" title="Regresar" href="{$host}/src/inicio.php">
            <i class="fas fa-arrow-left fa-lg"></i>
        </a>
        <b>CERTIFICACIONES DE NÓMINA</b>
    </div>
    <div class="card-body p-2 bg-wiev">

        <!-- ====== PANEL DE PARÁMETROS ====== -->
        <div class="filtros-cert">
            <div class="seccion-titulo"><i class="fas fa-sliders-h me-1"></i>Parámetros de Generación</div>
            <div class="row g-2 align-items-end flex-wrap">

                <!-- TERCERO -->
                <div class="col-md-5 col-sm-12">
                    <label for="buscaTercero" class="small fw-bold">TERCERO / EMPLEADO</label>
                    <input type="text"
                           id="buscaTercero"
                           name="buscaTercero"
                           class="form-control form-control-sm bg-input"
                           placeholder="Buscar por nombre o NIT/cédula..."
                           autocomplete="off">
                    <input type="hidden" id="id_tercero" value="0">
                </div>

                <!-- FECHA INICIA -->
                <div class="col-md-2 col-sm-5">
                    <label for="fechaInicia" class="small fw-bold">INICIA</label>
                    <input type="date" id="fechaInicia" class="form-control form-control-sm bg-input"
                           value="{$anio}-01-01">
                </div>

                <!-- FECHA TERMINA -->
                <div class="col-md-2 col-sm-5">
                    <label for="fechaTermina" class="small fw-bold">TERMINA</label>
                    <input type="date" id="fechaTermina" class="form-control form-control-sm bg-input"
                           value="{$anio}-12-31">
                </div>

                <!-- INFO RÁPIDA -->
                <div class="col-md-3 col-sm-12 text-end">
                    <small class="text-muted" style="font-size:0.73rem; line-height:1.8;">
                        <i class="fas fa-info-circle me-1"></i>Seleccione tercero y fechas,
                        luego elija el certificado.
                    </small>
                </div>

            </div>
        </div>
        <!-- FIN PANEL PARÁMETROS -->

        <!-- ====== TARJETAS DE CERTIFICADOS ====== -->
        <div class="row" id="gridCerts">
            {$cards_html}
        </div>
        <div class="col-12 text-center py-3" id="noResultsCert">
            <i class="fas fa-search-minus fa-2x text-muted mb-2 d-block"></i>
            <p class="text-muted small">No hay certificados configurados.</p>
        </div>

    </div>
</div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addScriptFile("{$host}/src/nomina/certificaciones/js/funciones.js?v=" . date("YmdHis"));
echo $plantilla->render();

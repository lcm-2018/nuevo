<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-sm-12 ">
        <div class="card border-0 shadow-sm">
            <h5 class="card-header bg-white border-bottom small"><?= 'FORMULARIOS EXÓGENA DIAN'; ?></h5>
            <div class="card-body bg-light">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">

                    <!-- Formulario 1001 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="bg-primary text-white text-center py-4 position-relative">
                                <i class="fas fa-money-check-alt fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 1001</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Pagos o abonos en cuenta y retenciones practicadas</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(1001)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario 1007 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="bg-success text-white text-center py-4 position-relative">
                                <i class="fas fa-hand-holding-usd fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 1007</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Ingresos recibidos</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(1007)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario 1008 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="text-white text-center py-4 position-relative" style="background-color: #6f42c1;">
                                <i class="fas fa-file-invoice fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 1008</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Saldo de cuentas por cobrar al 31 de diciembre</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(1008)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario 1009 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="bg-danger text-white text-center py-4 position-relative">
                                <i class="fas fa-file-invoice-dollar fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 1009</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Saldo de cuentas por pagar al 31 de diciembre</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(1009)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario 1012 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="text-white text-center py-4 position-relative" style="background-color: #0f766e;">
                                <i class="fas fa-university fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 1012</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Informaci&oacute;n de declaraciones tributarias, acciones y aportes, inversiones, cuentas de ahorro y corrientes</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(1012)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario 2276 -->
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="bg-warning text-dark text-center py-4 position-relative">
                                <i class="fas fa-briefcase fa-3x position-relative" style="z-index: 1;"></i>
                                <div class="position-absolute rounded-circle" style="right: -20px; bottom: -20px; width: 100px; height: 100px; background: rgba(0,0,0,0.05);"></div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold mb-1">FORMULARIO 2276</h6>
                                <p class="card-text small text-muted" style="min-height: 40px;">Información de rentas de trabajo y pensiones</p>
                            </div>
                            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                                <button class="btn btn-outline-success w-100 btn-sm fw-bold" onclick="exportarExogenaCsv(2276)">
                                    <i class="fas fa-file-excel me-2"></i> Generar CSV
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div id="areaImprimir" class="table-responsive px-2 d-none">
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}

$vigencia = $_SESSION['vigencia'];
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `ctt_estudios_previos`.`id_est_prev`
                , `ctt_estudios_previos`.`id_compra`
                , `ctt_estudios_previos`.`fec_ini_ejec`
                , `ctt_estudios_previos`.`fec_fin_ejec`
                , `ctt_estudios_previos`.`val_contrata`
                , `tb_forma_pago_compras`.`descripcion`
                , `ctt_estudios_previos`.`id_supervisor`
                , `tb_terceros`.`nit_tercero`
                , `tb_terceros`.`nom_tercero`
            FROM
                `ctt_estudios_previos`
            INNER JOIN `tb_forma_pago_compras` 
                ON (`ctt_estudios_previos`.`id_forma_pago` = `tb_forma_pago_compras`.`id_form_pago`)
            LEFT JOIN `tb_terceros`
                ON (`ctt_estudios_previos`.`id_supervisor` = `tb_terceros`.`id_tercero_api`)
            WHERE `id_compra` = '$id_adq'";
    $rs = $cmd->query($sql);
    $estudio_prev = $rs->fetch();
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$est_prev = isset($estudio_prev) ? $estudio_prev['id_est_prev'] : 0;
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT
                `seg_garantias_compra`.`id_est_prev`
                ,`seg_garantias_compra`.`id_poliza`
                , `tb_polizas`.`descripcion`
                , `tb_polizas`.`porcentaje`
            FROM
                `seg_garantias_compra`
            INNER JOIN `tb_polizas` 
                ON (`seg_garantias_compra`.`id_poliza` = `tb_polizas`.`id_poliza`)
            WHERE `seg_garantias_compra`.`id_est_prev` = '$est_prev'";
    $rs = $cmd->query($sql);
    $garantias = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$val_contrata = pesos($estudio_prev['val_contrata']);
$garantias_html = '';
foreach ($garantias as $g) {
    $garantias_html .= '<li>' . $g['descripcion'] . ' ' . $g['porcentaje'] . '%</li>';
}
$nit_tercero = number_format($estudio_prev['nit_tercero'], 0, ',', '.');
$nom_tercero = mb_strtoupper($estudio_prev['nom_tercero']);
$editar = $borrar = null;
if ($adquisicion['estado'] <= 6) {
    if ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
        $editar = '<a value="' . $est_prev . '" class="btn btn-outline-primary btn-xs rounded-circle shadow me-1 editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
    }
    if ($permisos->PermisosUsuario($opciones, 5302, 4) || $id_rol == 1) {
        $borrar = '<a value="' . $est_prev . '" class="btn btn-outline-danger btn-xs rounded-circle shadow borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
    }
}
$tb_html =
    <<<HTML
<div class="overflow">
    <table class="table table-striped table-bordered table-sm nowrap table-hover shadow" style="width:100%">
        <thead>
            <tr>
                <th colspan="2" class="text-center bg-sofia">Plazo de ejecución</th>
                <th rowspan="2" class="text-center bg-sofia">Valor contrato</th>
                <th rowspan="2" class="text-center bg-sofia">Forma de Pago</th>
                <th rowspan="2" class="text-center bg-sofia">Garantías / Pólizas</th>
                <th colspan="2" class="text-center bg-sofia">Supervisor</th>
                <th rowspan="2" class="text-center bg-sofia">Acciones</th>
            </tr>
            <tr>
                <th class="text-center bg-sofia">Fecha Inicial</th>
                <th class="text-center bg-sofia">Fecha Final</th>
                <th class="text-center bg-sofia">No. Documento</th>
                <th class="text-center bg-sofia">Nombre</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{$estudio_prev['fec_ini_ejec']}</td>
                <td>{$estudio_prev['fec_fin_ejec']}</td>
                <td class="text-end">{$val_contrata}</td>
                <td>{$estudio_prev['descripcion']}</td>
                <td>
                    {$garantias_html}
                </td>
                <td class="text-end">{$nit_tercero}</td>
                <td>{$nom_tercero}</td>
                <td id="modificarEstPrev">
                    <div class="text-center">
                        {$editar}{$borrar}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
HTML;

<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include '../../config/autoloader.php';
include '../financiero/consultas.php';

$id_caja = isset($_POST['id_caja'])  ? $_POST['id_caja'] : exit('Acceso no permitido');
$id_detalle = isset($_POST['id_detalle']) ? $_POST['id_detalle'] : 0;


$cmd = \Config\Clases\Conexion::getConexion();


$fecha_cierre = fechaCierre($_SESSION['vigencia'], 56, $cmd);
$fecha = fechaSesion($_SESSION['vigencia'], $_SESSION['id_user'], $cmd);
$fecha_max = date("Y-m-d", strtotime($_SESSION['vigencia'] . '-12-31'));

try {
    $sql = "SELECT `id_caja_concptos`,`concepto` FROM `tes_caja_conceptos` WHERE `estado`= 1 ORDER BY `concepto` ASC";
    $rs = $cmd->query($sql);
    $conceptos = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `tes_caja_const`.`id_caja_const`
                , `tes_caja_const`.`valor_total`
                , IFNULL(`t1`.`valor`,0) AS `valor` 
            FROM
                `tes_caja_const`
                LEFT JOIN
                (SELECT
                    `id_caja_const`
                    , SUM(`valor`) AS `valor`
                FROM `tes_caja_rubros`
                GROUP BY `id_caja_const`) AS `t1` 
                    ON (`t1`.`id_caja_const` = `tes_caja_const`.`id_caja_const`)
            WHERE `tes_caja_const`.`id_caja_const` = $id_caja";
    $rs = $cmd->query($sql);
    $valores = $rs->fetch(PDO::FETCH_ASSOC);
    if ($valores) {
        $max = $valores['valor_total'] - $valores['valor'];
    } else {
        $max = 0;
    }
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
try {
    $sql = "SELECT
                `tes_caja_rubros`.`id_caja_rubros`
                , `tes_caja_rubros`.`id_caja_const`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , `pto_cargue`.`tipo_dato`
                , `tes_caja_rubros`.`id_rubro_gasto`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `ctb_pgcp`.`tipo_dato`
                , `tes_caja_rubros`.`id_caja_concepto`
                , `tes_caja_rubros`.`valor`
                , `tes_caja_conceptos`.`concepto`
            FROM
                `tes_caja_rubros`
                INNER JOIN `pto_cargue` 
                    ON (`tes_caja_rubros`.`id_rubro_gasto` = `pto_cargue`.`id_cargue`)
                INNER JOIN `ctb_pgcp` 
                    ON (`tes_caja_rubros`.`id_cta_contable` = `ctb_pgcp`.`id_pgcp`)
                INNER JOIN `tes_caja_conceptos` 
                    ON (`tes_caja_rubros`.`id_caja_concepto` = `tes_caja_conceptos`.`id_caja_concptos`)
            WHERE `tes_caja_rubros`.`id_caja_const` = $id_caja";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

//detalle 
try {
    $sql = "SELECT
                `tes_caja_rubros`.`id_caja_rubros`
                , `tes_caja_rubros`.`id_caja_const`
                , `pto_cargue`.`cod_pptal`
                , `pto_cargue`.`nom_rubro`
                , `pto_cargue`.`tipo_dato` AS `tipo_dato_rubro`
                , `tes_caja_rubros`.`id_rubro_gasto`
                , `ctb_pgcp`.`cuenta`
                , `ctb_pgcp`.`nombre`
                , `ctb_pgcp`.`tipo_dato` AS `tipo_dato_cta`
                , `tes_caja_rubros`.`id_cta_contable`
                , `tes_caja_rubros`.`id_caja_concepto`
                , `tes_caja_rubros`.`valor`
                , `tes_caja_conceptos`.`concepto`
            FROM
                `tes_caja_rubros`
                INNER JOIN `pto_cargue` 
                    ON (`tes_caja_rubros`.`id_rubro_gasto` = `pto_cargue`.`id_cargue`)
                INNER JOIN `ctb_pgcp` 
                    ON (`tes_caja_rubros`.`id_cta_contable` = `ctb_pgcp`.`id_pgcp`)
                INNER JOIN `tes_caja_conceptos` 
                    ON (`tes_caja_rubros`.`id_caja_concepto` = `tes_caja_conceptos`.`id_caja_concptos`)
            WHERE (`tes_caja_rubros`.`id_caja_rubros` = $id_detalle)";
    $rs = $cmd->query($sql);
    $detalle = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $sql = "SELECT
                `id_pto` as `id`
            FROM
                `pto_presupuestos`
            WHERE (`id_vigencia` = $id_vigencia
                AND `id_tipo` = 2)
            LIMIT 1";
    $rs = $cmd->query($sql);
    $pto = $rs->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

if (empty($detalle)) {
    $detalle = [
        'id_caja_rubros' => 0,
        'id_caja_const' => $id_caja,
        'cod_pptal' => '',
        'nom_rubro' => '',
        'tipo_dato_rubro' => 0,
        'id_rubro_gasto' => 0,
        'cuenta' => '',
        'nombre' => '',
        'tipo_dato_cta' => 'M',
        'id_cta_contable' => 0,
        'id_caja_concepto' => 0,
        'concepto' => '',
        'valor' => $max
    ];
}
?>
<script>
    $('#tableRubrosCaja').DataTable({
        language: dataTable_es,
        "order": [
            [0, "desc"]
        ]
    });
    $('#tableRubrosCaja').wrap('<div class="overflow" />');
</script>
<div class="shadow">
    <div class="card-header text-center py-2" style="background-color: #16a085 !important;">
        <h5 class="mb-0" style="color: white;">RUBROS DE CAJA MENOR </h5>
    </div>
    <div class="p-3">
        <form id="formAddRubrosCaja">
            <input type="hidden" id="id_pto_movto" value="<?= $pto['id']; ?>">
            <input type="hidden" id="id_caja_rubros" name="id_caja_rubros" value="<?php echo $detalle['id_caja_rubros']; ?>">
            <input type="hidden" id="id_caja" name="id_caja" value="<?php echo $id_caja ?>">
            <div class="row mb-2">
                <div class="col-md-6">
                    <label for="slcConcepto" class="small">TIPO DE GASTO</label>
                    <select name="slcConcepto" id="slcConcepto" class="form-control form-control-sm bg-input">
                        <option value="0" <?php echo $detalle['id_caja_concepto'] == '0' ? 'selected' : '' ?>>--Seleccione--</option>
                        <?php
                        foreach ($conceptos as $cp) {
                            $slc = $detalle['id_caja_concepto'] == $cp['id_caja_concptos'] ? 'selected' : '';
                            echo '<option value="' . $cp['id_caja_concptos'] . '"' . $slc . '>' . mb_strtoupper($cp['concepto']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="numValor" class="small">Valor</label>
                    <input type="number" name="numValor" id="numValor" class="form-control form-control-sm bg-input" value="<?php echo $detalle['valor']; ?>" min="0" max="<?= $max; ?>">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <label for="rubroCod" class="small">RUBRO PRESUPUESTAL</label>
                    <input type="text" name="rubroCod" id="rubroCod" class="form-control form-control-sm bg-input" value="<?php echo $detalle['cod_pptal'] != '' ? $detalle['cod_pptal'] . ' - ' . $detalle['nom_rubro'] : ''; ?>">
                    <input type="hidden" name="id_rubroCod" id="id_rubroCod" class="form-control form-control-sm bg-input" value="<?php echo $detalle['id_rubro_gasto']; ?>">
                    <input type="hidden" id="tipoRubro" value="<?php echo $detalle['tipo_dato_rubro']; ?>">
                </div>
                <div class="col-md-6">
                    <label for="codigoCta" class="small">CUENTA CONTABLE</label>
                    <input type="text" name="codigoCta" id="codigoCta" class="form-control form-control-sm bg-input" value="<?php echo $detalle['cuenta'] != '' ? $detalle['cuenta'] . ' - ' . $detalle['nombre'] : ''; ?>">
                    <input type="hidden" name="id_codigoCta" id="id_codigoCta" class="form-control form-control-sm bg-input" value="<?php echo $detalle['id_cta_contable']; ?>">
                    <input type="hidden" id="tipoDato" value="<?php echo $detalle['tipo_dato_cta']; ?>">
                </div>
            </div>
        </form>
        <table id="tableRubrosCaja" class="table table-striped table-bordered table-sm table-hover shadow" style="width: 100%">
            <thead>
                <tr>
                    <th class="bg-sofia">Concepto</th>
                    <th class="bg-sofia">Rubro</th>
                    <th class="bg-sofia">Cuenta</th>
                    <th class="bg-sofia">Valor</th>
                    <th class="bg-sofia">Acciones </th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($rubros as $r) {
                    $editar = '<a class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow"  onclick="EditRubroCaja(' . $r['id_caja_rubros'] . ')"><span class="fas fa-pencil-alt"></span></a>';
                    echo '<tr>';
                    echo '<td>' . $r['concepto'] . '</td>';
                    echo '<td>' . $r['cod_pptal'] . ' - ' . $r['nom_rubro'] . '</td>';
                    echo '<td>' . $r['cuenta'] . ' - ' . $r['nombre'] . '</td>';
                    echo '<td class="text-end">' . number_format($r['valor'], 2, ',', '.') . '</td>';
                    echo '<td class="text-center">' . $editar . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <div class="text-end pt-3">
            <a type="button" class="btn btn-success btn-sm" onclick="GuardarRubrosCaja()">Guardar</a>
            <a type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</a>
        </div>
    </div>
</div>
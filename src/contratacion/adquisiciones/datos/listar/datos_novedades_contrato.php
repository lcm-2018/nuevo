<?php

use Src\Common\Php\Clases\Permisos;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../../index.php');
    exit();
}

require_once '../../../../../config/autoloader.php';

$permisos = new Permisos();

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];
$opciones = $permisos->PermisoOpciones($id_user);

$id_ct = isset($_POST['id_csp']) ? $_POST['id_csp'] : exit('Acción no permitida');
$id_ct = $id_ct == '' ? 0 : $id_ct; // ES EL ID DEL CONTRATO
$id_adq = $_POST['id_adq'];
function pesos($valor)
{
    return '$' . number_format($valor, 2, ",", ".");
}
try {
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "SELECT 
                *
            FROM 
                (SELECT 
                    `id_nov_con`,`id_tip_nov`,`id_adq`,`val_adicion` AS `valor1`, '0' AS `valor2`,`fec_adcion` AS `fecha`,`fec_ini_prorroga`AS `inicia`,`fec_fin_prorroga` AS `fin`,`observacion` 
                FROM 
                    `ctt_novedad_adicion_prorroga`
                WHERE `id_adq` = $id_ct
                UNION ALL
                SELECT 
                    `id_cesion`,`id_tipo_nov`,`id_adq`, '0' AS `valor1`, '0' AS `valor2`, `fec_cesion`, '' AS `inicia`, '' AS `fin`, `observacion`
                FROM 
                    `ctt_novedad_cesion`
                WHERE `id_adq` = $id_ct
                UNION ALL
                SELECT 
                    `id_liquidacion`, `id_tipo_nov`, `id_adq`, `val_cte`, `val_cta`, `fec_liq`, '' AS `inicia`, '' AS `fin`, `observacion`
                FROM 
                    `ctt_novedad_liquidacion`
                WHERE `id_adq` = $id_ct
                UNION ALL
                SELECT
                    `ctt_novedad_reinicio`.`id_reinicio`
                    , `ctt_novedad_reinicio`.`id_tipo_nov`
                    , `ctt_novedad_reinicio`.`id_suspension`
                    , '0' AS `valor1`, '0' AS `valor2`
                    , `ctt_novedad_reinicio`.`fec_reinicia`
                    , '' AS `inicia`, '' AS `fin`
                    , `ctt_novedad_reinicio`.`observacion`
                FROM 
                    `ctt_novedad_reinicio`
                INNER JOIN `ctt_novedad_suspension`
                    ON (`ctt_novedad_reinicio`.`id_suspension` = `ctt_novedad_suspension`.`id_suspension`)
                WHERE `ctt_novedad_suspension`.`id_adq` = $id_ct
                UNION ALL
                SELECT
                    `id_suspension`, `id_tipo_nov`, `id_adq`,'0' AS `valor1`, '0' AS `valor2`, `fec_inicia`, `fec_inicia` AS `inicia`, `fec_fin`, `observacion`  
                FROM
                    `ctt_novedad_suspension`
                WHERE `id_adq` = $id_ct
                UNION ALL
                SELECT
                    `id_terminacion`,`id_tipo_nov`,`id_adq`,'0' AS `valor1`, '0' AS `valor2`,'' AS `fec_liq`, '' AS `inicia`, '' AS `fin`, `observacion`
                FROM
                    `ctt_novedad_terminacion`
                WHERE `id_adq` = $id_ct) AS `t` 
            INNER JOIN `ctt_tipo_novedad`
                ON(`t`.`id_tip_nov` = `ctt_tipo_novedad`.`id_novedad`)";
    $rs = $cmd->query($sql);
    $novedades = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $sqsl = "SELECT
                `cfdr`.`id_relacion`
                , `cfdr`.`id_formato`
            FROM
                `ctt_formatos_doc_rel` AS `cfdr`
                INNER JOIN `ctt_adquisiciones` AS `ca` 
                    ON (`cfdr`.`id_tipo_bn_sv` = `ca`.`id_tipo_bn_sv`)
            WHERE (`ca`.`id_adquisicion` = $id_adq)";
    $rs = $cmd->query($sqsl);
    $formatos = $rs->fetchAll(PDO::FETCH_ASSOC);
    $formatos = array_column($formatos, 'id_relacion', 'id_formato');
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
$data = [];
if (!empty($novedades)) {
    foreach ($novedades as $nv) {
        $id_nov = $nv['id_nov_con'] . '|' . $nv['id_tip_nov'];
        if ($permisos->PermisosUsuario($opciones, 5302, 3) || $id_rol == 1) {
            $editar = '<a value="' . $id_nov . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow editar" title="Editar"><span class="fas fa-pencil-alt"></span></a>';
        }
        if ($permisos->PermisosUsuario($opciones, 5302, 4) || $id_rol == 1) {
            $borrar = '<a value="' . $id_nov . '" class="btn btn-outline-danger btn-xs rounded-circle me-1 shadow borrar" title="Eliminar"><span class="fas fa-trash-alt"></span></a>';
        }
        switch ($nv['id_novedad']) {
            case 1:
            case 2:
            case 3:
                $form = isset($formatos[6]) ? $formatos[6] : 0;
                break;
            case 4:
            case 5:
            case 6:
            case 7:
                $form = isset($formatos[$nv['id_novedad'] + 3]) ? $formatos[$nv['id_novedad'] + 3] : 0;
                break;
            case 8:
                $form = isset($formatos[5]) ? $formatos[5] : 0;
                break;
        }
        $word = '<button text="' . $form . '" value="' . $id_nov . '" class="btn btn-outline-primary btn-xs rounded-circle me-1 shadow downloadFormsCtt" title="Word"><span class="fas fa-file-word"></span></button>';
        $data[] = [
            't_novedad' => $nv['descripcion'],
            'fecha' => $nv['fecha'],
            'valor1' => '<div class="text-end">' . pesos($nv['valor1']) . '</div>',
            'valor2' => '<div class="text-end">' . pesos($nv['valor2']) . '</div>',
            'inicia' => $nv['inicia'],
            'fin' => $nv['fin'],
            'observacion' => $nv['observacion'],
            'botones' => '<div class="text-center">' . $editar . $borrar . $word . '</div>',

        ];
    }
}
$datos = ['data' => $data];

echo json_encode($datos);

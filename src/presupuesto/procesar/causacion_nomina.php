<?php

use Src\Common\Php\Clases\Terceros;
use Src\Nomina\Liquidacion\Php\Clases\Nomina;
use Src\Nomina\Liquidado\Php\Clases\Detalles;

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../index.php');
    exit();
}
include '../../../config/autoloader.php';
include '../../financiero/consultas.php';
$vigencia = $_SESSION['vigencia'];
$id_vigencia = $_SESSION['id_vigencia'];
$data = explode('|', file_get_contents("php://input"));
$idNomina = $data[0];
$tipo_nomina = $data[1];
$fec_doc = $data[2];
//validar si hay saldo para los rubros 
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT
                `nom_cdp_empleados`.`rubro`
                , `nom_cdp_empleados`.`valor`
                , `pto_cargue`.`cod_pptal`
            FROM
                `nom_cdp_empleados`
                INNER JOIN `pto_cargue` 
                    ON (`nom_cdp_empleados`.`rubro` = `pto_cargue`.`id_cargue`)
            WHERE (`nom_cdp_empleados`.`id_nomina` = $idNomina AND `nom_cdp_empleados`.`tipo` = 'M')";
    $rs = $cmd->query($sql);
    $valxrubro = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs->closeCursor();
    unset($rs);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}
if (empty($valxrubro)) {
    echo 'No se ha generado una solicitud de CDP para esta nómina';
    exit();
} else {
    $cmd = \Config\Clases\Conexion::getConexion();
    $valida = false;
    $tabla = '<table class="table table-bordered table-striped table-hover table-sm" style="font-size: 12px; width: 100%">
                <thead>
                    <tr>
                        <th>Rubro</th>
                        <th>Valor</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($valxrubro as $vr) {
        $rubro = $vr['rubro'];
        $valor = $vr['valor'];
        $cod_rubro = $vr['cod_pptal'];
        $respuesta = SaldoRubro($cmd, $rubro, $fec_doc, 0);
        $saldo = $respuesta['valor_aprobado'] - $respuesta['debito_cdp'] + $respuesta['credito_cdp'] + $respuesta['debito_mod'] - $respuesta['credito_mod'];
        $estado = $saldo >= $valor ? '<span class="badge rounded-pill text-bg-success">Disponible</span>' : '<span class="badge rounded-pill text-bg-danger">Sin Saldo</span>';
        if ($saldo < $valor) {
            $valida = true;
            $tabla .= '<tr>
                        <td>' . $cod_rubro . '</td>
                        <td class="text-end">$ ' . number_format($valor, 2, ',', '.') . '</td>
                        <td class="text-end">$ ' . number_format($saldo, 2, ',', '.') . '</td>
                        <td>' . $estado . '</td>
                    </tr>';
        }
    }
    $tabla .= '</tbody></table>';
    if ($valida) {
        echo $tabla;
        exit();
    }
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `id_pto` FROM `pto_presupuestos` WHERE `id_tipo` = 2 AND `id_vigencia` = $id_vigencia";
    $rs = $cmd->query($sql);
    $pto = $rs->fetch(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$Detalles = new Detalles();
$Terceros = new Terceros();
$Nomina = new Nomina();

$datos = $Detalles->getRegistrosDT(1, -1, ['id_nomina' => $idNomina], 1, 'ASC');
$nomina = $Nomina->getRegistro($idNomina);
$terceros = $Terceros->getTerceros();
$terceros = array_column($terceros, 'id', 'cedula');

$id_pto = $pto['id_pto'];
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
$fecha = $date->format('Y-m-d');
$objeto = $nomina['descripcion'] . ' DE EMPLEADO(S), NÓMINA No. ' . $idNomina . ' VIGENCIA ' . $vigencia;
$iduser = $_SESSION['id_user'];
$fecha2 = $date->format('Y-m-d H:i:s');
//CDP
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT MAX(`id_manu`) AS `id_manu`  FROM `pto_cdp` WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}

try {
    $cmd = \Config\Clases\Conexion::getConexion();
    $sql = "SELECT `r_admin`, `r_operativo`, `id_tipo` FROM `nom_rel_rubro` WHERE (`id_vigencia` = $id_vigencia)";
    $rs = $cmd->query($sql);
    $rubros = $rs->fetchAll(PDO::FETCH_ASSOC);
    $cmd = null;
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
}

$cerrado = 2;
$cmd = \Config\Clases\Conexion::getConexion();


try {
    $cmd->beginTransaction();
    $sql = "INSERT INTO `pto_cdp` (`id_pto`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_pto, PDO::PARAM_INT);
    $sql->bindParam(2, $id_manu, PDO::PARAM_INT);
    $sql->bindParam(3, $fec_doc, PDO::PARAM_STR);
    $sql->bindParam(4, $objeto, PDO::PARAM_STR);
    $sql->bindParam(5, $iduser, PDO::PARAM_INT);
    $sql->bindParam(6, $fecha2);
    $sql->bindParam(7, $cerrado, PDO::PARAM_INT);
    $sql->execute();
    $id_cdp = $cmd->lastInsertId();
    if (!($id_cdp > 0)) {
        throw new Exception($sql->errorInfo()[2]);
    }

    $sql = "SELECT `id_tercero_api` FROM `tb_terceros` WHERE `nit_tercero` = " . $_SESSION['nit_emp'];
    $rs = $cmd->query($sql);
    $tercero = $rs->fetch();
    $id_ter_api = !empty($tercero) ? $tercero['id_tercero_api'] : null;

    $sql = "SELECT
                MAX(`id_manu`) AS `id_manu` 
            FROM
                `pto_crp`
            WHERE (`id_pto` = $id_pto)";
    $rs = $cmd->query($sql);
    $consecutivo = $rs->fetch();
    $id_manu = !empty($consecutivo) ? $consecutivo['id_manu'] + 1 : 1;
    $sql = "INSERT INTO `pto_crp` (`id_pto`, `id_cdp`, `id_manu`, `fecha`, `objeto`, `id_user_reg`, `fecha_reg`, `estado`, `id_tercero_api`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $id_pto, PDO::PARAM_INT);
    $sql->bindParam(2, $id_cdp, PDO::PARAM_INT);
    $sql->bindParam(3, $id_manu, PDO::PARAM_INT);
    $sql->bindParam(4, $fec_doc, PDO::PARAM_STR);
    $sql->bindParam(5, $objeto, PDO::PARAM_STR);
    $sql->bindParam(6, $iduser, PDO::PARAM_INT);
    $sql->bindParam(7, $fecha2);
    $sql->bindParam(8, $cerrado, PDO::PARAM_INT);
    $sql->bindParam(9, $id_ter_api, PDO::PARAM_INT);
    $sql->execute();
    $id_crp = $cmd->lastInsertId();
    if (!($id_crp > 0)) {
        throw new Exception($sql->errorInfo()[2]);
    }

    $liberado = 0;
    $query = "INSERT INTO `pto_cdp_detalle` 
                (`id_pto_cdp`, `id_rubro`, `valor`, `valor_liberado`) 
            VALUES (?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $id_cdp, PDO::PARAM_INT);
    $query->bindParam(2, $rubro, PDO::PARAM_INT);
    $query->bindParam(3, $valor, PDO::PARAM_STR);
    $query->bindParam(4, $liberado, PDO::PARAM_STR);

    $sqly = "INSERT INTO `pto_crp_detalle` 
                (`id_pto_crp`, `id_pto_cdp_det`, `id_tercero_api`, `valor`, `valor_liberado`) 
            VALUES (?, ?, ?, ?, ?)";
    $sqly = $cmd->prepare($sqly);
    $sqly->bindParam(1, $id_crp, PDO::PARAM_INT);
    $sqly->bindParam(2, $id_detalle_cdp, PDO::PARAM_INT);
    $sqly->bindParam(3, $id_tercero, PDO::PARAM_INT);
    $sqly->bindParam(4, $valor, PDO::PARAM_STR);
    $sqly->bindParam(5, $liberado, PDO::PARAM_STR);

    $contador = 0;
    $tipo_field_map = [
        1  => ['valor_laborado', 'val_compensa'],
        2  => 'horas_ext',
        3  => 'g_representa',
        4  => 'val_bon_recrea',
        5  => 'val_bsp',
        6  => 'aux_tran',
        7  => 'aux_alim',
        9  => 'val_indemniza',
        17 => 'valor_vacacion',
        18 => 'val_cesantias',
        19 => 'val_icesantias',
        20 => 'val_prima_vac',
        21 => 'valor_pv',
        22 => 'valor_ps',
        32 => 'pago_empresa'
    ];
    foreach ($datos as $d) {
        $id_tercero = $terceros[$d['no_documento']];
        foreach ($rubros as $rb) {
            $tipo = $rb['id_tipo'];
            $rubro = $d['tipo_cargo'] == '1' ? $rb['r_admin'] : $rb['r_operativo'];

            $valor = 0;
            if (isset($tipo_field_map[$tipo])) {
                $fields = $tipo_field_map[$tipo];
                if (is_array($fields)) {
                    foreach ($fields as $f) {
                        if (!empty($d[$f])) {
                            $valor += $d[$f];
                        }
                    }
                } else {
                    $valor = !empty($d[$fields]) ? $d[$fields] : 0;
                }
            }

            if ($valor > 0) {
                $query->execute();
                $id_detalle_cdp = $cmd->lastInsertId();
                if ($id_detalle_cdp > 0) {
                    $sqly->execute();
                    $id_detalle_crp = $cmd->lastInsertId();
                    if (!($id_detalle_crp > 0)) {
                        throw new Exception($sqly->errorInfo()[2]);
                    }
                } else {
                    throw new Exception($query->errorInfo()[2]);
                }
            }
        }
        $contador++;
    }
    $cmd = null;

    $estado = 3;
    $cmd = \Config\Clases\Conexion::getConexion();

    $sql = "UPDATE `nom_nominas` SET `estado` = ? WHERE `id_nomina` = ?";
    $sql = $cmd->prepare($sql);
    $sql->bindParam(1, $estado, PDO::PARAM_INT);
    $sql->bindParam(2, $idNomina, PDO::PARAM_INT);
    $sql->execute();

    $query = "INSERT INTO `nom_nomina_pto_ctb_tes` 
                (`id_nomina`, `cdp`, `crp`, `tipo`) 
            VALUES (?, ?, ?, ?)";
    $query = $cmd->prepare($query);
    $query->bindParam(1, $idNomina, PDO::PARAM_INT);
    $query->bindParam(2, $id_cdp, PDO::PARAM_INT);
    $query->bindParam(3, $id_crp, PDO::PARAM_INT);
    $query->bindParam(4, $tipo_nomina, PDO::PARAM_STR);
    $query->execute();
    if (!($cmd->lastInsertId() > 0)) {
        throw new Exception($query->errorInfo()[2]);
    }
    $cmd->commit();
    $cmd = null;
    echo 'ok';
} catch (PDOException $e) {
    $cmd->rollBack();
    throw new Exception($e->getMessage());
}

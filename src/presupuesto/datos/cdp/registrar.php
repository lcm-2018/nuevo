<?php
use Config\Clases\Logs;

if (isset($_POST)) {
    $id_pto_cdp = $_POST['id_pto_cdp'];
    $rubro = $_POST['id_rubroCod'];
    $valorCdp = str_replace(",", "", $_POST['valorCdp']);
    $tipo_mov = 'CDP';
    include '../../../../config/autoloader.php';
    $cmd = \Config\Clases\Conexion::getConexion();
    if (empty($_POST['id_pto_mvto'])) {
        $query = $cmd->prepare("INSERT INTO pto_documento_detalles (id_pto_doc, tipo_mov, rubro, valor,estado) VALUES (:id, :tipo, :rubro, :valor, :estado)");
        $query->bindParam(":id", $id_pto_cdp);
        $query->bindParam(":tipo", $tipo_mov);
        $query->bindParam(":rubro", $rubro);
        $query->bindParam(":valor", $valorCdp);
        $query->bindParam(":estado", 0);
        $query->execute();
        if ($cmd->lastInsertId() > 0)
            Logs::guardaLog("INSERT INTO pto_documento_detalles (id_pto_doc, tipo_mov, rubro, valor,estado) VALUES ($id_pto_cdp, '$tipo_mov', '$rubro', $valorCdp, 0)");
        $cmd = null;
        echo "ok";
    } else {
        $id = $_POST['id_pto_mvto'];
        $query = $cmd->prepare("UPDATE pto_documento_detalles SET id_pto_doc = :id_pto, tipo_mov = :tipo, rubro =:rubro, valor = :valor WHERE id_pto_mvto = :id");
        $query->bindParam(":id_pto", $id_pto_cdp);
        $query->bindParam(":tipo", $tipo_mov);
        $query->bindParam(":rubro", $rubro);
        $query->bindParam(":valor", $valorCdp);
        $query->bindParam("id", $id);
        $query->execute();
        if ($query->rowCount() > 0)
            Logs::guardaLog("UPDATE pto_documento_detalles SET id_pto_doc = $id_pto_cdp, tipo_mov = '$tipo_mov', rubro ='$rubro', valor = $valorCdp WHERE id_pto_mvto = $id");
        $cmd = null;
        echo "modificado";
    }
}

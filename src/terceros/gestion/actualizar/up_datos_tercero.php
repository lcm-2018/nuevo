<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../../../index.php');
    exit();
}
include '../../../../config/autoloader.php';
include 'up_terceros.php';
$idter = isset($_POST['idTercero']) ? $_POST['idTercero'] : exit('Acción no permitida');
$tipotercero = $_POST['slcTipoTercero'];
$fecInicio = date('Y-m-d', strtotime($_POST['datFecInicio']));
$genero = $_POST['slcGenero'];
$fecNacimiento = date('Y-m-d', strtotime($_POST['datFecNacimiento']));
$tipodoc = $_POST['slcTipoDocEmp'];
$cc_nit = $_POST['txtCCempleado'];
$nomb1 = $_POST['txtNomb1Emp'];
$nomb2 = $_POST['txtNomb2Emp'];
$ape1 = $_POST['txtApe1Emp'];
$ape2 = $_POST['txtApe2Emp'];
$razonsoc = $_POST['txtRazonSocial'];
$pais = $_POST['slcPaisEmp'];
$dpto = $_POST['slcDptoEmp'];
$municip = $_POST['slcMunicipioEmp'];
$dir = $_POST['txtDireccion'];
$mail = $_POST['mailEmp'];
$tel = $_POST['txtTelEmp'];
$es_clinic = $_POST['rdo_esasist'];
$iduser = $_SESSION['id_user'];
$tipouser = 'user';
$nit_act = $_SESSION['nit_emp'];
$planilla = $_POST['rdo_planilla'];
$riesgo = isset($_POST['slcRiesgoLab']) ? $_POST['slcRiesgoLab'] : 0;
$riesgo = $planilla == 0 ? NULL : $riesgo;
$date = new DateTime('now', new DateTimeZone('America/Bogota'));
//API URL
$data = [
    "slcGenero" => $genero,
    "datFecNacimiento" => $fecNacimiento,
    "slcTipoDocEmp" => $tipodoc,
    "txtCCempleado" => $cc_nit,
    "txtNomb1Emp" => $nomb1,
    "txtNomb2Emp" => $nomb2,
    "txtApe1Emp" => $ape1,
    "txtApe2Emp" => $ape2,
    "txtRazonSocial" => $razonsoc,
    "slcPaisEmp" => $pais,
    "slcDptoEmp" => $dpto,
    "slcMunicipioEmp" => $municip,
    "txtDireccion" => $dir,
    "mailEmp" => $mail,
    "txtTelEmp" => $tel,
    "id_user" => $iduser,
    "tipuser" => $tipouser,
    "nit_emp" => $nit_act
];
    $api = \Config\Clases\Conexion::Api();
    $url = $api . 'terceros/datos/res/modificar/tercero/' . $idter;
$ch = curl_init($url);
$payload = json_encode($data);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
curl_close($ch);
$res = json_decode($result, true);
if ($res == '1' || $res == '0' || $fecInicio != '') {
    $cmd = \Config\Clases\Conexion::getConexion();
    
    $respuesta = UpTercerosEmpresa($api, [$idter], $cmd, $fecInicio, $es_clinic, $planilla, $riesgo);
    if ($respuesta == 'ok') {
        echo 'ok';
    } else {
        echo $respuesta;
    }
} else {
    echo 'Respuesta: ' . $res;
}

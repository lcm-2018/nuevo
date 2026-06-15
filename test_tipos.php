<?php
require 'c:\wamp64\www\nuevo\config\Clases\Conexion.php';
$c = Config\Clases\Conexion::getConexion();
$s = $c->query('SELECT id_tipo, codigo, descripcion FROM nom_tipo_liquidacion');
foreach($s as $r) {
    echo $r['codigo'] . ' = ' . $r['id_tipo'] . " (" . $r['descripcion'] . ")\n";
}

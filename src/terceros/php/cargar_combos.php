<?php

function sedes($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT tb_sedes.id_sede,tb_sedes.nom_sede FROM tb_sedes";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_sede']  == $id) {
                echo '<option value="' . $obj['id_sede'] . '" selected="selected">' . $obj['nom_sede'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_sede'] . '">' . $obj['nom_sede'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function bodegas_sede($cmd, $titulo = '', $idsede = 0, $id = 0)
{  
    try {
        echo '<option value="">' . $titulo . '</option>';
        if ($idsede != 0) {
            $sql = "SELECT far_bodegas.id_bodega,far_bodegas.nombre FROM far_bodegas
                INNER JOIN tb_sedes_bodega ON (tb_sedes_bodega.id_bodega=far_bodegas.id_bodega)
                WHERE tb_sedes_bodega.id_sede=$idsede";
            $rs = $cmd->query($sql);
            $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
            foreach ($objs as $obj) {
                if ($obj['id_bodega']  == $id) {
                    echo '<option value="' . $obj['id_bodega'] . '" selected="selected">' . $obj['nombre'] . '</option>';
                } else {
                    echo '<option value="' . $obj['id_bodega'] . '">' . $obj['nombre'] . '</option>';
                }
            }
            $cmd = null;
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function bodegas($cmd, $titulo = '', $id = 0)
{  
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT far_bodegas.id_bodega,far_bodegas.nombre FROM far_bodegas";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_bodega']  == $id) {
                echo '<option value="' . $obj['id_bodega'] . '" selected="selected">' . $obj['nombre'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_bodega'] . '">' . $obj['nombre'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}


/* Ordenes de Egreso */
function sedes_usuario($cmd, $titulo = '', $id = 0)
{
    try {
        $idusr = $_SESSION['id_user'];
        $idrol = $_SESSION['rol'];
        echo '<option value="">' . $titulo . '</option>';
        if ($idrol == 1) {
            $sql = "SELECT tb_sedes.id_sede,tb_sedes.nom_sede FROM tb_sedes";
        } else {
            $sql = "SELECT tb_sedes.id_sede,tb_sedes.nom_sede FROM tb_sedes 
                    INNER JOIN seg_sedes_usuario ON (seg_sedes_usuario.id_sede=tb_sedes.id_sede AND seg_sedes_usuario.id_usuario=$idusr)";
        }
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_sede']  == $id || count($objs) == 1) {
                echo '<option value="' . $obj['id_sede'] . '" selected="selected">' . $obj['nom_sede'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_sede'] . '">' . $obj['nom_sede'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function bodegas_usuario($cmd, $titulo = '', $idsede = 0, $id = 0)
{  
    try {

        $idusr = $_SESSION['id_user'];
        $idrol = $_SESSION['rol'];
        echo '<option value="">' . $titulo . '</option>';
        if ($idsede != 0) {
            if ($idrol == 1) {
                $sql = "SELECT far_bodegas.id_bodega,far_bodegas.nombre FROM far_bodegas
                    INNER JOIN tb_sedes_bodega ON (tb_sedes_bodega.id_bodega=far_bodegas.id_bodega)
                    WHERE tb_sedes_bodega.id_sede=$idsede";
            } else {
                $sql = "SELECT far_bodegas.id_bodega,far_bodegas.nombre FROM far_bodegas
                    INNER JOIN tb_sedes_bodega ON (tb_sedes_bodega.id_bodega=far_bodegas.id_bodega)
                    INNER JOIN seg_bodegas_usuario ON (seg_bodegas_usuario.id_bodega=far_bodegas.id_bodega AND seg_bodegas_usuario.id_usuario=$idusr)
                    WHERE tb_sedes_bodega.id_sede=$idsede";
            }
            $rs = $cmd->query($sql);
            $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
            foreach ($objs as $obj) {
                if ($obj['id_bodega']  == $id) {
                    echo '<option value="' . $obj['id_bodega'] . '" selected="selected">' . $obj['nombre'] . '</option>';
                } else {
                    echo '<option value="' . $obj['id_bodega'] . '">' . $obj['nombre'] . '</option>';
                }
            }
            $cmd = null;
        }
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function centros_costo($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_centro,nom_centro FROM tb_centrocostos WHERE id_centro<>0";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_centro']  == $id) {
                echo '<option value="' . $obj['id_centro'] . '" selected="selected">' . $obj['nom_centro'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_centro'] . '">' . $obj['nom_centro'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function centros_costo_usuario($cmd, $titulo = '', $id = 0)
{
    try {
        $idusr = $_SESSION['id_user'];
        $idrol = $_SESSION['rol'];
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT COUNT(*) AS bodegas FROM seg_bodegas_usuario WHERE id_usuario=$idusr";
        $rs = $cmd->query($sql);
        $obj = $rs->fetch();        
        if ($idrol == 1 || $obj['bodegas']>0) {
            $sql = "SELECT id_centro,nom_centro FROM tb_centrocostos WHERE id_centro<>0";
        } else {
            $sql = "SELECT id_centro,nom_centro FROM tb_centrocostos 
                    WHERE id_centro IN (SELECT id_centrocosto FROM seg_usuarios_sistema WHERE id_usuario=$idusr AND id_centrocosto<>0)";
        }
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_centro']  == $id || count($objs) == 1) {
                echo '<option value="' . $obj['id_centro'] . '" selected="selected">' . $obj['nom_centro'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_centro'] . '">' . $obj['nom_centro'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function tipo_egreso($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_tipo_egreso,nom_tipo_egreso,es_int_ext FROM far_orden_egreso_tipo WHERE id_tipo_egreso NOT IN (1,2)";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            $dtad = 'data-intext="' . $obj['es_int_ext'] . '"';
            if ($obj['id_tipo_egreso']  == $id) {
                echo '<option value="' . $obj['id_tipo_egreso'] . '"' . $dtad . ' selected="selected">' . $obj['nom_tipo_egreso'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tipo_egreso'] . '"' . $dtad . '>' . $obj['nom_tipo_egreso'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

/* Ordenes de Ingreso */
function terceros($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_tercero,nom_tercero FROM tb_terceros WHERE id_tercero<>0";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_tercero']  == $id) {
                echo '<option value="' . $obj['id_tercero'] . '" selected="selected">' . $obj['nom_tercero'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tercero'] . '">' . $obj['nom_tercero'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function tipo_ingreso($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_tipo_ingreso,nom_tipo_ingreso,es_int_ext,orden_compra FROM far_orden_ingreso_tipo";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            $dtad = 'data-intext="' . $obj['es_int_ext'] . '"' . 'data-ordcom="' . $obj['orden_compra'] . '"';
            if ($obj['id_tipo_ingreso']  == $id) {
                echo '<option value="' . $obj['id_tipo_ingreso'] . '"' . $dtad . ' selected="selected">' . $obj['nom_tipo_ingreso'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tipo_ingreso'] . '"' . $dtad . '>' . $obj['nom_tipo_ingreso'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function estados_movimientos($titulo = '', $estado = -1)
{
    echo '<option value="">' . $titulo . '</option>';
    $selected = ($estado == 1) ? 'selected="selected"' : '';
    echo '<option value="1"' . $selected . '>PENDIENTE</option>';
    $selected = ($estado == 2) ? 'selected="selected"' : '';
    echo '<option value="2"' . $selected . '>CERRADO</option>';
    $selected = ($estado == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>ANULADO</option>';
}

function estados_pedidos($titulo = '', $estado = -1)
{
    echo '<option value="">' . $titulo . '</option>';
    $selected = ($estado == 1) ? 'selected="selected"' : '';
    echo '<option value="1"' . $selected . '>PENDIENTE</option>';
    $selected = ($estado == 2) ? 'selected="selected"' : '';
    echo '<option value="2"' . $selected . '>CONFIRMADO</option>';
    $selected = ($estado == 3) ? 'selected="selected"' : '';
    echo '<option value="3"' . $selected . '>ACEPTADO</option>';
    $selected = ($estado == 4) ? 'selected="selected"' : '';
    echo '<option value="4"' . $selected . '>CERRADO</option>';
    $selected = ($estado == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>ANULADO</option>';
}

function iva($valor = 0)
{
    $selected = ($valor == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>0</option>';
    $selected = ($valor == 5) ? 'selected="selected"' : '';
    echo '<option value="5"' . $selected . '>5</option>';
    $selected = ($valor == 19) ? 'selected="selected"' : '';
    echo '<option value="19"' . $selected . '>19</option>';
}

/* Articulos */
function cums_articulo($cmd, $id_articulo = 0, $id = 0)
{
    try {
        echo '<option value=""></option>';
        $sql = "SELECT id_cum,CONCAT('[',cum,']',far_presentacion_comercial.nom_presentacion) AS nom_cum 
                FROM far_medicamento_cum
                INNER JOIN far_presentacion_comercial ON (far_presentacion_comercial.id_prescom=far_medicamento_cum.id_prescom)
                WHERE id_med=" . $id_articulo;
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_cum']  == $id) {
                echo '<option value="' . $obj['id_cum'] . '" selected="selected">' . $obj['nom_cum'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_cum'] . '">' . $obj['nom_cum'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function tipo_medicamento_insumo($cmd, $id = 0)
{
    try {
        echo '<option value=""></option>';
        $sql = "SELECT id_tipo_servicio,nom_tipo FROM tb_serv_tipo WHERE id_tipo_servicio IN (12,13,15,16)";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_tipo_servicio']  == $id) {
                echo '<option value="' . $obj['id_tipo_servicio'] . '" selected="selected">' . $obj['nom_tipo'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tipo_servicio'] . '">' . $obj['nom_tipo'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function grupo_articulo($cmd, $titulo = '', $id = 0)
{
    /*Tipo  =0  Cuando se utiliza en formularios como filtro de búsqueda
            !=0 Cuando se utiliza en formularios como dato solicitado */
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_grupo,nom_grupo FROM far_grupos WHERE id_grupo<>0";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_grupo']  == $id) {
                echo '<option value="' . $obj['id_grupo'] . '" selected="selected">' . $obj['nom_grupo'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_grupo'] . '">' . $obj['nom_grupo'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function subgrupo_articulo($cmd, $titulo = '', $id = 0)
{    
    try {        
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_subgrupo,nom_subgrupo FROM far_subgrupos WHERE id_grupo IN (1,2)";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_subgrupo']  == $id) {
                echo '<option value="' . $obj['id_subgrupo'] . '" selected="selected">' . $obj['nom_subgrupo'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_subgrupo'] . '">' . $obj['nom_subgrupo'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function lotes_articulo($cmd, $id_bodega, $id_articulo, $id = 0)
{
    try {        
        echo '<option value=""></option>';
        $sql = "SELECT far_medicamento_lote.id_lote,IF(fec_vencimiento='3000-01-01',lote,CONCAT(lote,'[',fec_vencimiento,']')) AS nom_lote,
                    far_medicamentos.nom_medicamento AS nom_articulo,
                    far_medicamento_lote.id_presentacion,far_presentacion_comercial.nom_presentacion,
                    IFNULL(far_presentacion_comercial.cantidad,1) AS cantidad_umpl
                FROM far_medicamento_lote
                INNER JOIN far_medicamentos ON (far_medicamentos.id_med=far_medicamento_lote.id_med)
                INNER JOIN far_presentacion_comercial ON (far_presentacion_comercial.id_prescom=far_medicamento_lote.id_presentacion)
                WHERE (far_medicamento_lote.id_med=$id_articulo AND far_medicamento_lote.id_bodega=$id_bodega AND 
                        far_medicamento_lote.estado=1 AND far_medicamentos.estado=1 AND
                        far_medicamento_lote.fec_vencimiento>='" . date('Y-m-d') . "') OR far_medicamento_lote.id_lote=$id";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);        
        foreach ($objs as $obj) {
            $dtad = $dtad = 'data-nom_articulo="' . $obj['nom_articulo'] . '"' . 
                    'data-id_presentacion="' . $obj['id_presentacion'] . '"' .
                    'data-nom_presentacion="' . $obj['nom_presentacion'] . '"' .
                    'data-cantidad_umpl="' . $obj['cantidad_umpl'] . '"';
            if ($obj['id_lote']  == $id) {                
                echo '<option value="' . $obj['id_lote'] . '"' . $dtad . ' selected="selected">' . $obj['nom_lote'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_lote'] . '"' . $dtad . '>' . $obj['nom_lote'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}


function estados_registros($titulo = '',$estado = -1)
{
    echo '<option value="">' . $titulo . '</option>';
    $selected = ($estado == 1) ? 'selected="selected"' : '';
    echo '<option value="1"' . $selected . '>ACTIVO</option>';
    $selected = ($estado == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>INACTIVO</option>';
}

function estados_sino($titulo = '',$estado = -1)
{
    echo '<option value="">' . $titulo . '</option>';
    $selected = ($estado == 1) ? 'selected="selected"' : '';
    echo '<option value="1"' . $selected . '>SI</option>';
    $selected = ($estado == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>NO</option>';
}

function modulo_origen($titulo = '',$estado = -1)
{
    echo '<option value="">' . $titulo . '</option>';
    $selected = ($estado == 1) ? 'selected="selected"' : '';
    echo '<option value="1"' . $selected . '>FARMACIA</option>';
    $selected = ($estado == 0) ? 'selected="selected"' : '';
    echo '<option value="0"' . $selected . '>ALMACEN</option>';
}

function tipo_area($cmd, $titulo ='', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT id_tipo,nom_tipo FROM far_area_tipo WHERE id_tipo<>0";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_tipo']  == $id) {
                echo '<option value="' . $obj['id_tipo'] . '" selected="selected">' . $obj['nom_tipo'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tipo'] . '">' . $obj['nom_tipo'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

function areas_centrocosto($cmd, $titulo = '', $idcec = 0, $id = -1)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        if ($idcec != 0) {
            $sql = "SELECT id_area,CONCAT_WS(' - ',nom_area,nom_sede) AS nom_area FROM far_centrocosto_area 
                    INNER JOIN tb_sedes ON (tb_sedes.id_sede=far_centrocosto_area.id_sede)
                    WHERE id_centrocosto=$idcec";
            $rs = $cmd->query($sql);
            $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
            foreach ($objs as $obj) {
                if ($obj['id_area']  == $id) {
                    echo '<option value="' . $obj['id_area'] . '" selected="selected">' . $obj['nom_area'] . '</option>';
                } else {
                    echo '<option value="' . $obj['id_area'] . '">' . $obj['nom_area'] . '</option>';
                }
            }
            $cmd = null;
        }    
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

//tipos de documentos
function tipoDocumento($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT tb_tipo_documento.id_tipo_doc,tb_tipo_documento.tipo_doc FROM tb_tipo_documento";
        $rs = $cmd->query($sql);
        $objs = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach ($objs as $obj) {
            if ($obj['id_tipo_doc']  == $id) {
                echo '<option value="' . $obj['id_tipo_doc'] . '" selected="selected">' . $obj['tipo_doc'] . '</option>';
            } else {
                echo '<option value="' . $obj['id_tipo_doc'] . '">' . $obj['tipo_doc'] . '</option>';
            }
        }
        $cmd = null;
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}

//roles
function roles($cmd, $titulo = '', $id = 0)
{
    try {
        echo '<option value="">' . $titulo . '</option>';
        $sql = "SELECT `id_rol`, `nom_rol` AS `nombre` FROM `seg_rol` ORDER BY `nombre` ASC";
        $rs = $cmd->query($sql);
        $roles = $rs->fetchAll();
    $rs->closeCursor();
    unset($rs);
        foreach($roles as $obj){
            if($obj['id_rol'] == $id){
                echo '<option value="' . $obj['id_rol'] . '" selected="selected">' . $obj['nombre'] . '</option>';
            }
            else{
                echo '<option value="' . $obj['id_rol'] . '">' . $obj['nombre'] . '</option>';
            }
        }
        $cmd = null;  
    } catch (PDOException $e) {
        echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getMessage();
    }
}
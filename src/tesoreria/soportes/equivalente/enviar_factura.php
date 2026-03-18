<?php

/**
 * Envío de Documento Soporte Electrónico – Módulo Tesorería
 *
 * Reutiliza las clases DocumentRepository, TaxxaService y DocumentBuilder
 * de contabilidad. La única diferencia con contabilidad es la query para
 * obtener los datos del documento (sin ctb_factura; el valor viene de
 * tes_caja_mvto y la fecha del ctb_doc).
 */

session_start();

// Validación de sesión
if (!isset($_SESSION['user'])) {
    header("Location: ../../../index.php");
    exit();
}

// Validación de entrada
$id_facno = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id_facno) {
    echo json_encode(['value' => 'Error', 'msg' => 'ID de documento inválido']);
    exit();
}

// Autoloader (carga DocumentRepository, TaxxaService, DocumentBuilder de contabilidad)
include '../../../../config/autoloader.php';

use App\DocumentoElectronico\DocumentRepository;
use App\DocumentoElectronico\TaxxaService;
use App\DocumentoElectronico\DocumentBuilder;
use Config\Clases\Conexion;
use Src\Common\Php\Clases\Valores;

try {
    $conexion = Conexion::getConexion();
    $conexion->beginTransaction();

    // -----------------------------------------------------------------------
    // 1. Datos comunes (mismas clases que contabilidad)
    // -----------------------------------------------------------------------
    $repository = new DocumentRepository($conexion);

    $nonce      = $repository->getAndUpdateNonce();       // ['valor', 'id']
    $empresa    = $repository->getEmpresaData();          // datos de tb_datos_ips
    $resolucion = $repository->getResolucion(1, 2);       // tipo 2 = doc. soporte
    $repository->validarResolucion($resolucion);

    // -----------------------------------------------------------------------
    // 2. Datos del documento — query propia de tesorería
    //    (tesorería no tiene ctb_factura; el valor viene de tes_caja_mvto)
    // -----------------------------------------------------------------------
    $sqlDoc = "SELECT
                    `ctb_doc`.`id_ctb_doc`
                    , `ctb_doc`.`id_tercero`
                    , `ctb_doc`.`fecha`             AS `fecha_fact`
                    , `ctb_doc`.`fecha`             AS `fecha_ven`
                    , COALESCE(`tmv`.`valor`, 0)    AS `valor_pago`
                    , 0                             AS `valor_iva`
                    , COALESCE(`tmv`.`valor`, 0)    AS `valor_base`
                    , `ctb_doc`.`detalle`
                    , `ctb_doc`.`detalle`           AS `nota`
                    , `tb_terceros`.`nit_tercero`
                    , `tb_terceros`.`nom_tercero`
                    , `tb_terceros`.`email`
                    , `tb_terceros`.`tel_tercero`
                    , `tb_municipios`.`codigo_municipio`
                    , `tb_municipios`.`nom_municipio`
                    , `tb_municipios`.`cod_postal`
                    , `tb_departamentos`.`codigo_departamento`
                    , `tb_departamentos`.`nom_departamento`
                    , `tb_terceros`.`dir_tercero`
                FROM `ctb_doc`
                INNER JOIN `tb_terceros`
                    ON (`ctb_doc`.`id_tercero` = `tb_terceros`.`id_tercero_api`)
                INNER JOIN `tb_municipios`
                    ON (`tb_terceros`.`id_municipio` = `tb_municipios`.`id_municipio`)
                INNER JOIN `tb_departamentos`
                    ON (`tb_municipios`.`id_departamento` = `tb_departamentos`.`id_departamento`)
                LEFT JOIN (
                    SELECT `id_ctb_doc`, SUM(`valor`) AS `valor`
                    FROM `tes_caja_mvto`
                    GROUP BY `id_ctb_doc`
                ) AS `tmv`
                    ON (`tmv`.`id_ctb_doc` = `ctb_doc`.`id_ctb_doc`)
                WHERE (`ctb_doc`.`id_ctb_doc` = :id)
                LIMIT 1";

    $stmt = $conexion->prepare($sqlDoc);
    $stmt->execute([':id' => $id_facno]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        throw new Exception("No se encontró el documento con ID: {$id_facno}");
    }

    // -----------------------------------------------------------------------
    // 3. UNSPSC — en tesorería no hay cadena CDP/CRP; se usa el código de config
    // -----------------------------------------------------------------------
    $config = Valores::getOwnerConfig();
    $unspsc = $config['codigo_unsp'] ?? '80161500';

    // -----------------------------------------------------------------------
    // 4. Verificar soporte previo y preparar secuencia
    // -----------------------------------------------------------------------
    $soporteExistente = $repository->getSoporteExistente($id_facno);
    $secuencia        = intval($resolucion['consecutivo']);
    $idSoporte        = null;

    if ($soporteExistente) {
        $partes    = explode('-', $soporteExistente['referencia']);
        $secuencia = intval($partes[1]);
        $idSoporte = $soporteExistente['id_soporte'];
    } else {
        $idSoporte = $repository->crearSoporte(
            $id_facno,
            $resolucion['prefijo'] . '-' . $secuencia,
            date('Y-m-d'),
            $_SESSION['id_user']
        );
    }

    // -----------------------------------------------------------------------
    // 5. Autenticar en Taxxa (mismo TaxxaService de contabilidad)
    // -----------------------------------------------------------------------
    $taxxaService = new TaxxaService(
        $empresa['endpoint'],
        $empresa['user_prov'],
        $empresa['pass_prov'],
        $nonce['valor']
    );
    $taxxaService->authenticate();

    // -----------------------------------------------------------------------
    // 6. Construir el documento soporte (mismo DocumentBuilder de contabilidad)
    // -----------------------------------------------------------------------

    // El tercero (proveedor) es el VENDEDOR en un documento soporte
    $tercero = [
        'tipo_org'        => 1,
        'resp_fiscal'     => 'R-99-PN',
        'reg_fiscal'      => 1,
        'no_doc'          => $documento['nit_tercero'],
        'nit'             => $documento['nit_tercero'],
        'nombre'          => str_replace('-', '', trim($documento['nom_tercero'])),
        'correo'          => $documento['email'],
        'telefono'        => $documento['tel_tercero'],
        'codigo_pais'     => 'CO',
        'codigo_dpto'     => $documento['codigo_departamento'],
        'nom_departamento'=> $documento['nom_departamento'],
        'codigo_municipio'=> $documento['codigo_municipio'],
        'nom_municipio'   => $documento['nom_municipio'],
        'cod_postal'      => $documento['cod_postal'],
        'direccion'       => $documento['dir_tercero'],
        'formato_soporte' => true,   // activa la rama ReverseInvoice en buildPartyInfo
    ];

    $builder = new DocumentBuilder();
    $builder->reset()
        ->setDocumentType('ReverseInvoice')
        ->setBasicInfo([
            'wdocumentsubtype'    => '9',
            'wpaymentmeans'       => '1',
            'wpaymentmethod'      => 'ZZZ',
            'yreversebuyerseller' => 'N',
            'yaiu'                => 'N',
            'wdocumenttypecode'   => '05',
            'idocprecision'       => 2,
            'spaymentid'          => $documento['nota'],
            'yisresident'         => 'Y',
            'sinvoiceperiod'      => '1',
            'rdocumenttemplate'   => $config['rdocumenttemplate'],
        ])
        ->setReference($resolucion['prefijo'], $secuencia)
        ->setDates(
            date('Y-m-d', strtotime($documento['fecha_fact'])),
            date('Y-m-d', strtotime('+1 month', strtotime($documento['fecha_fact'])))
        )
        ->setBuyer($empresa)    // La empresa es la compradora
        ->setSeller($tercero)   // El proveedor es el vendedor
        ->addItem([
            'codigo'      => $unspsc,
            'detalle'     => $documento['nota'],
            'val_unitario'=> floatval($documento['valor_base']),
            'cantidad'    => 1,
            'p_iva'       => 0,
            'val_iva'     => 0,
            'p_dcto'      => 0,
            'val_dcto'    => 0,
        ]);

    $jDocument = $builder->build();

    // -----------------------------------------------------------------------
    // 7. Enviar a Taxxa
    // -----------------------------------------------------------------------
    $response = $taxxaService->sendDocument(
        $jDocument,
        $resolucion['entorno'],
        'classTaxxa.fjDocumentExternalAdd'
    );

    // -----------------------------------------------------------------------
    // 8. Guardar log
    // -----------------------------------------------------------------------
    $taxxaService->saveLog('tes_log_envio_' . $id_facno . '.txt');

    // -----------------------------------------------------------------------
    // 9. Procesar respuesta y actualizar BD
    // -----------------------------------------------------------------------
    $numero = $resolucion['prefijo'] . $secuencia;

    if ($response['error'] === 0) {
        // Éxito
        $hash       = $response['data']['scufe']              ?? '';
        $referencia = $response['data']['sdocumentreference'] ?? $numero;

        $repository->actualizarSoporte($idSoporte, $hash, $referencia, $id_facno, $_SESSION['id_user']);
        $repository->actualizarConsecutivo($resolucion['id_resol'], $secuencia + 1);

        $conexion->commit();

        echo json_encode([
            'value' => 'ok',
            'msg'   => json_encode('Documento enviado correctamente'),
        ]);

    } elseif ($response['error'] === 2) {
        // El documento ya existe en Taxxa — intentar recuperar el hash
        $consulta = $taxxaService->getDocument($numero);

        if ($consulta['error'] === 0) {
            $hash = $consulta['data']['shash'] ?? '';
            $repository->actualizarSoporte($idSoporte, $hash, $numero, $id_facno, $_SESSION['id_user']);
            $conexion->commit();

            echo json_encode([
                'value' => 'ok',
                'msg'   => json_encode('Documento ya estaba enviado, información actualizada'),
            ]);
        } else {
            $conexion->rollBack();
            echo json_encode([
                'value' => 'Error',
                'msg'   => 'No se pudo recuperar el documento existente: ' . $consulta['message'],
            ]);
        }

    } else {
        // Error de Taxxa
        $conexion->rollBack();
        echo json_encode([
            'value' => 'Error',
            'msg'   => $response['message'],
        ]);
    }

} catch (PDOException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $errorMsg = $e->getCode() == 2002
        ? 'Sin Conexión a Mysql (Error: 2002)'
        : 'Error de base de datos: ' . $e->getMessage();

    echo json_encode(['value' => 'Error', 'msg' => $errorMsg]);

} catch (Exception $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    echo json_encode(['value' => 'Error', 'msg' => $e->getMessage()]);

} finally {
    $conexion = null;
}

exit;

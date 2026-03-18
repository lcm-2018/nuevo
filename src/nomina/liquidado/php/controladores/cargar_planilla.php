<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../../../../index.php");
    exit();
}

include_once '../../../../../config/autoloader.php';

$res = ['status' => 'error', 'msg' => ''];

// 1. Validar parámetros
$id_nomina = isset($_POST['id_nomina']) ? intval($_POST['id_nomina']) : 0;
if ($id_nomina === 0) {
    $res['msg'] = 'Parámetro de nómina inválido.';
    echo json_encode($res);
    exit;
}

// 2. Validar que se envió un archivo
if (!isset($_FILES['archivo_planilla']) || $_FILES['archivo_planilla']['error'] !== UPLOAD_ERR_OK) {
    $res['msg'] = 'No se recibió ningún archivo o hubo un error en la carga.';
    echo json_encode($res);
    exit;
}

$file_tmp  = $_FILES['archivo_planilla']['tmp_name'];
$file_name = $_FILES['archivo_planilla']['name'];
$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// 3. Validar extensión
if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
    $res['msg'] = 'El archivo debe ser de tipo Excel (.xlsx) o CSV (.csv).';
    echo json_encode($res);
    exit;
}

// 4. Leer las filas del archivo
$filas = [];

if ($extension === 'csv') {
    // Leer CSV con separador ; (el que genera nuestro formato)
    if (($handle = fopen($file_tmp, 'r')) !== false) {
        // Detectar BOM UTF-8 y descartarlo
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $numFila = 0;
        while (($campos = fgetcsv($handle, 1000, ';')) !== false) {
            $filas[] = $campos;
            $numFila++;
        }
        fclose($handle);
    } else {
        $res['msg'] = 'No se pudo abrir el archivo CSV.';
        echo json_encode($res);
        exit;
    }
} else {
    // Leer XLSX con SimpleXLSX
    include_once '../../../../../vendor/SimpleXLSX/simpleXLSX.php';

    $ruta_temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'planilla_' . $id_nomina . '_' . time() . '.xlsx';
    if (!move_uploaded_file($file_tmp, $ruta_temp)) {
        $res['msg'] = 'No se pudo procesar el archivo subido.';
        echo json_encode($res);
        exit;
    }

    $xlsx = new \SimpleXLSX\SimpleXLSX($ruta_temp);
    if (!$xlsx->success()) {
        @unlink($ruta_temp);
        $res['msg'] = 'No se pudo leer el archivo Excel. Asegúrese de usar el formato descargado (.csv) o un .xlsx válido.';
        echo json_encode($res);
        exit;
    }
    $filas = $xlsx->rows();
    @unlink($ruta_temp);
}

// Quitar encabezado (fila 0)
if (count($filas) <= 1) {
    $res['msg'] = 'El archivo no contiene datos a procesar.';
    echo json_encode($res);
    exit;
}

// 5. Cargar empleados activos de la nómina para cruzar por documento
try {
    $conexion = \Config\Clases\Conexion::getConexion();

    $stmt = $conexion->prepare(
        "SELECT `e`.`id_empleado`, `e`.`no_documento`
         FROM `nom_liq_salario` AS `s`
         INNER JOIN `nom_empleado` AS `e` ON (`s`.`id_empleado` = `e`.`id_empleado`)
         WHERE `s`.`id_nomina` = ? AND `s`.`estado` = 1"
    );
    $stmt->execute([$id_nomina]);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    unset($stmt);

    if (empty($empleados)) {
        $res['msg'] = 'No se encontraron empleados activos en esta nómina.';
        echo json_encode($res);
        exit;
    }

    // Indexar por documento para búsqueda rápida
    $mapa_empleados = [];
    foreach ($empleados as $emp) {
        $mapa_empleados[(string)$emp['no_documento']] = (int)$emp['id_empleado'];
    }

    // 6. Procesar filas dentro de una transacción
    $conexion->beginTransaction();

    $t = 0;
    $errores = [];

    // UPDATE seguridad social — solo si existe el registro con ese id_nomina, id_empleado y estado=1
    $stmtSS = $conexion->prepare(
        "UPDATE `nom_liq_segsocial_empdo`
         SET    `aporte_pension_emp`             = ?,
                `aporte_pension_empresa`         = ?,
                `aporte_solidaridad_pensional`   = ?,
                `aporte_salud_emp`               = ?,
                `aporte_salud_empresa`           = ?,
                `aporte_rieslab`                 = ?
         WHERE  `id_nomina` = ? AND `id_empleado` = ? AND `estado` = 1"
    );

    // UPDATE parafiscales — solo si existe el registro con ese id_nomina, id_empleado y estado=1
    $stmtPF = $conexion->prepare(
        "UPDATE `nom_liq_parafiscales`
         SET    `val_comfam` = ?,
                `val_sena`   = ?,
                `val_icbf`   = ?
         WHERE  `id_nomina` = ? AND `id_empleado` = ? AND `estado` = 1"
    );

    foreach ($filas as $numFila => $campo) {
        // Saltar encabezado (fila 0)
        if ($numFila < 1) {
            continue;
        }

        // Columnas: 0=DOCUMENTO, 1=PENSION_EMP, 2=PENSION_PATRON, 3=PENSION_SOLID,
        //           4=SALUD_EMP, 5=SALUD_PATRON, 6=CAJA, 7=RIESGOS, 8=SENA, 9=ICBF
        $documento      = trim((string)($campo[0] ?? ''));
        $pension_emp    = floatval(str_replace(',', '.', $campo[1] ?? 0));
        $pension_patron = floatval(str_replace(',', '.', $campo[2] ?? 0));
        $pension_solid  = floatval(str_replace(',', '.', $campo[3] ?? 0));
        $salud_emp      = floatval(str_replace(',', '.', $campo[4] ?? 0));
        $salud_patron   = floatval(str_replace(',', '.', $campo[5] ?? 0));
        $caja           = floatval(str_replace(',', '.', $campo[6] ?? 0));
        $riesgos        = floatval(str_replace(',', '.', $campo[7] ?? 0));
        $sena           = floatval(str_replace(',', '.', $campo[8] ?? 0));
        $icbf           = floatval(str_replace(',', '.', $campo[9] ?? 0));

        if ($documento === '') {
            continue; // fila vacía, ignorar
        }

        if (!isset($mapa_empleados[$documento])) {
            $errores[] = "Doc. $documento no encontrado en la nómina.";
            continue;
        }

        $id_empleado = $mapa_empleados[$documento];

        // UPDATE seguridad social
        $stmtSS->execute([
            $pension_emp,
            $pension_patron,
            $pension_solid,
            $salud_emp,
            $salud_patron,
            $riesgos,
            $id_nomina,
            $id_empleado,
        ]);
        $actualizado = $stmtSS->rowCount() > 0;

        // UPDATE parafiscales
        $stmtPF->execute([
            $caja,
            $sena,
            $icbf,
            $id_nomina,
            $id_empleado,
        ]);
        $actualizado = $actualizado || ($stmtPF->rowCount() > 0);

        // Contar una sola vez por empleado
        if ($actualizado) {
            $t++;
        }
    }

    if ($t > 0) {
        $conexion->commit();
        $res['status'] = 'ok';
        $res['msg']    = "Se actualizaron $t registros correctamente.";
        if (!empty($errores)) {
            $res['msg'] .= ' Advertencias: ' . implode(' | ', $errores);
        }
    } else {
        $conexion->rollBack();
        if (!empty($errores)) {
            $res['msg'] = 'No se actualizó ningún registro. ' . implode(' | ', $errores);
        } else {
            $res['msg'] = 'No se realizaron cambios. Verifique que los documentos existan y tengan registros activos en la nómina.';
        }
    }
} catch (PDOException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $res['msg'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($res);

<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
echo "\xEF\xBB\xBF";
if (isset($_POST['html_tabla'])) {
    // Modo HTML: recibe la tabla completa con rowspan/colspan intactos
    $html = $_POST['html_tabla'];
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=reporte_excel.xls");
    echo $html;
} else if (isset($_POST['datos'])) {
    // Modo array: recibe datos como JSON (sin rowspan/colspan)
    $data = json_decode($_POST['datos'], true);
    if (!$data) exit('Acción no permitida');

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=reporte_excel.xls");
    echo "<table border='1'>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    // Modo base64 (txt plano)
    $data = isset($_POST['xls']) ? $_POST['xls'] : exit('Acción no permitida');
    $data = base64_decode($data);
    header('Content-type:application/xls');
    header('Content-Disposition: attachment; filename=reporte_excel.xls');
    echo $data;
}

<?php

function estados_registros(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>ACTIVO</option>';
    $selected = ($estado === 0) ? ' selected="selected"' : '';
    $html .= '<option value="0"' . $selected . '>INACTIVO</option>';
    return $html;
}
<?php

function estados_registros(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>ACTIVO</option>';
    $selected = ($estado === 0) ? ' selected="selected"' : '';
    $html .= '<option value="0"' . $selected . '>INACTIVO</option>';
    return $html;
}

function tipo_analitica(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>Consulta Analítica</option>';
    $selected = ($estado === 2) ? ' selected="selected"' : '';
    $html .= '<option value="2"' . $selected . '>Panel Analítico</option>';
    return $html;
}

function tipo_bdatos(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>Base de Datos Local</option>';
    $selected = ($estado === 2) ? ' selected="selected"' : '';
    $html .= '<option value="2"' . $selected . '>Múltiples Bases de Datos</option>';
    return $html;
}

function tipo_informe(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>Un Informe</option>';
    $selected = ($estado === 2) ? ' selected="selected"' : '';
    $html .= '<option value="2"' . $selected . '>Múltiples Informes</option>';
    return $html;
}

function tipo_consulta(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>Bases de Datos Locales</option>';
    $selected = ($estado === 2) ? ' selected="selected"' : '';
    $html .= '<option value="2"' . $selected . '>Bases de Datos Remotas</option>';
    return $html;
}

function tipo_acceso(string $titulo = '', int $estado = -1): string{
    $html = '<option value="">' . htmlspecialchars($titulo) . '</option>';
    $selected = ($estado === 1) ? ' selected="selected"' : '';
    $html .= '<option value="1"' . $selected . '>Público</option>';
    $selected = ($estado === 2) ? ' selected="selected"' : '';
    $html .= '<option value="2"' . $selected . '>Usuarios Autorizados</option>';
    return $html;
}
# Plan de Actualización del Módulo de Contabilidad

## Objetivo
Actualizar el módulo de contabilidad siguiendo los patrones modernos implementados en el módulo de presupuesto, utilizando la clase `Plantilla` y Bootstrap 5.3.

## Análisis del Estado Actual

### Módulo de Presupuesto (Modernizado)
- ✅ Usa la clase `Plantilla` de `Config\Clases\Plantilla`
- ✅ Implementa Bootstrap 5.3
- ✅ Estructura limpia con heredoc syntax
- ✅ Gestión moderna de CSS y JavaScript
- ✅ Sistema de modales dinámico
- ✅ Clases CSS modernas (bg-sofia, bg-input, bg-wiev)
- ✅ JavaScript con `mostrarOverlay()` y `ocultarOverlay()`

### Módulo de Contabilidad (Antiguo)
- ❌ Usa estructura antigua con includes separados:
  - `head.php`
  - `navsuperior.php`
  - `navlateral.php`
  - `footer.php`
  - `scripts.php`
  - `modales.php`
- ❌ Bootstrap 4.6 (conflictos con Bootstrap 5.3)
- ❌ Estructura HTML fragmentada
- ❌ Gestión manual de recursos

## Archivos a Actualizar

### Archivos PHP principales (24 archivos)
1. `form_documentos_fuente.php`
2. `form_fecha_anulacion.php`
3. `form_plan_cuentas.php`
4. `list_documentos_soporte.php`
5. `lista_causacion_ccostos.php`
6. `lista_causacion_descuentos.php`
7. `lista_causacion_rads.php`
8. `lista_causacion_registros.php`
9. `lista_causacion_registros_rubros.php`
10. `lista_causacion_registros_total.php`
11. `lista_centro_costo_cxp.php`
12. `lista_descuentos_cxp.php`
13. `lista_documentos_det.php` ⭐ (Prioritario - archivo actual del usuario)
14. `lista_documentos_fuente.php`
15. `lista_documentos_invoice.php`
16. `lista_documentos_invoice_detalle.php`
17. `lista_documentos_mov.php`
18. `lista_ejecucion_pto_crp_cxp.php`
19. `lista_facturas_cxp.php`
20. `lista_impuestos.php`
21. `lista_imputacion_cxp.php`
22. `lista_invoices.php`
23. `lista_pgcp_cargue.php`
24. `lista_plan_cuentas.php`

### Archivos JavaScript (2 archivos principales + subcarpetas)
1. `funcioncontabilidad.js` ⭐ (Prioritario)
2. `funciones_retencion.js`
3. Subcarpetas con JS específicos

### Archivos PHP en subcarpetas
- `php/centro_costos/` (14 archivos)
- `php/common/` (7 archivos)
- `php/cuentas_fac/` (5 archivos)
- `php/informes_bancos/` (9 archivos)
- `php/subgrupos/` (11 archivos)
- `php/supersalud/` (5 archivos)
- `php/tipos_orden_egreso/` (8 archivos)
- `php/tipos_orden_ingreso/` (5 archivos)

## Cambios Necesarios

### 1. Estructura de Archivos PHP

#### ANTES (Estructura antigua):
```php
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}
include_once '../conexion.php';
include_once '../permisos.php';
?>
<!DOCTYPE html>
<html lang="es">
<?php include '../head.php'; ?>
<body class="sb-nav-fixed <?php echo $_SESSION['navarlat'] == '1' ? 'sb-sidenav-toggled' : '' ?>">
    <?php include '../navsuperior.php' ?>
    <div id="layoutSidenav">
        <?php include '../navlateral.php' ?>
        <div id="layoutSidenav_content">
            <main>
                <!-- Contenido -->
            </main>
            <?php include '../footer.php' ?>
        </div>
        <?php include '../modales.php' ?>
    </div>
    <?php include '../scripts.php' ?>
</body>
</html>
```

#### DESPUÉS (Estructura moderna):
```php
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit();
}
include '../../config/autoloader.php';

use Config\Clases\Plantilla;
use Src\Common\Php\Clases\Permisos;

$id_rol = $_SESSION['rol'];
$id_user = $_SESSION['id_user'];

$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;
$host = Plantilla::getHost();

// Lógica PHP...

$content = <<<HTML
    <div class="card w-100">
        <div class="card-header bg-sofia text-white">
            <button class="btn btn-sm me-1 p-0" title="Regresar" onclick="window.history.back();"><i class="fas fa-arrow-left fa-lg"></i></button>
            <b>TÍTULO</b>
        </div>
        <div class="card-body p-2 bg-wiev">
            <!-- Contenido -->
        </div>
    </div>
HTML;

$plantilla = new Plantilla($content, 2);
$plantilla->addCssFile("{$host}/assets/css/custom.css?v=" . date("YmdHis"));
$plantilla->addScriptFile("{$host}/src/contabilidad/js/funcioncontabilidad.js?v=" . date("YmdHis"));
$modal = $plantilla->getModal('divModalForms', 'divTamModalForms', 'divForms');
$plantilla->addModal($modal);
echo $plantilla->render();
```

### 2. Actualización de Bootstrap 4.6 a 5.3

#### Cambios de clases comunes:
| Bootstrap 4.6 | Bootstrap 5.3 |
|---------------|---------------|
| `ml-*`, `mr-*` | `ms-*`, `me-*` |
| `pl-*`, `pr-*` | `ps-*`, `pe-*` |
| `form-control-sm` | `form-control form-control-sm bg-input` (para inputs editables) |
| `data-toggle="modal"` | `data-bs-toggle="modal"` |
| `data-target="#modal"` | `data-bs-target="#modal"` |
| `close` class | `btn-close` class |
| `custom-select` | `form-select` |
| `badge badge-*` | `badge bg-*` |

### 3. Actualización de JavaScript

#### Añadir a todas las llamadas AJAX:
```javascript
// ANTES
$.ajax({
    type: "POST",
    url: "ruta.php",
    data: datos,
    success: function(res) {
        // código
    }
});

// DESPUÉS
mostrarOverlay();
$.ajax({
    type: "POST",
    url: "ruta.php",
    data: datos,
    success: function(res) {
        // código
    }
}).always(function() {
    ocultarOverlay();
});
```

### 4. Clases CSS Personalizadas

Asegurar que todos los inputs editables tengan:
- `bg-input` - Para inputs, selects, textareas que NO sean readonly/disabled
- `bg-sofia` - Para headers y elementos destacados
- `bg-wiev` - Para fondos de contenido

### 5. Sistema de Permisos Moderno

```php
// ANTES
if (PermisosUsuario($permisos, 5501, 2) || $id_rol == 1) {
    echo '<input type="hidden" id="peReg" value="1">';
} else {
    echo '<input type="hidden" id="peReg" value="0">';
}

// DESPUÉS
$permisos = new Permisos();
$opciones = $permisos->PermisoOpciones($id_user);
$peReg = $permisos->PermisosUsuario($opciones, 5501, 2) || $id_rol == 1 ? 1 : 0;
// En el HTML:
<input type="hidden" id="peReg" value="{$peReg}">
```

## Estrategia de Implementación

### Fase 1: Archivos Prioritarios (1-3)
1. `lista_documentos_det.php` - Archivo actual del usuario
2. `lista_documentos_mov.php` - Similar y relacionado
3. `lista_documentos_fuente.php` - Base del sistema

### Fase 2: Archivos de Listado (4-12)
4. `lista_causacion_registros.php`
5. `lista_facturas_cxp.php`
6. `lista_invoices.php`
7. `lista_plan_cuentas.php`
8. `lista_impuestos.php`
9. Resto de archivos lista_*

### Fase 3: Formularios (13-15)
10. `form_documentos_fuente.php`
11. `form_fecha_anulacion.php`
12. `form_plan_cuentas.php`

### Fase 4: JavaScript (16-17)
13. `funcioncontabilidad.js` - Añadir overlay a todas las llamadas AJAX
14. Archivos JS en subcarpetas

### Fase 5: Subcarpetas PHP (18-24)
15. Archivos en `php/centro_costos/`
16. Archivos en `php/common/`
17. Archivos en otras subcarpetas

## Verificación de Cambios

Para cada archivo actualizado, verificar:
- ✅ Usa `Plantilla` class
- ✅ Bootstrap 5.3 classes
- ✅ Clase `bg-input` en inputs editables
- ✅ Sistema de permisos moderno
- ✅ JavaScript con overlay
- ✅ Heredoc syntax para HTML
- ✅ Modales gestionados por Plantilla
- ✅ Recursos con versioning (date("YmdHis"))
- ✅ No hay conflictos de estilos
- ✅ Funcionalidad intacta

## Consideraciones Especiales

### Conexión a Base de Datos
- **Antigua**: `include_once '../conexion.php';` + `new PDO(...)`
- **Moderna**: `use Config\Clases\Conexion;` + `Conexion::getConexion()`

### Manejo de Errores PDO
Mantener consistente:
```php
try {
    $cmd = \Config\Clases\Conexion::getConexion();
    // consultas
} catch (PDOException $e) {
    echo $e->getCode() == 2002 ? 'Sin Conexión a Mysql (Error: 2002)' : 'Error: ' . $e->getCode();
}
```

### DataTables
Actualizar configuración para consistencia:
```javascript
var table = $("#tableName").DataTable({
    dom: setdom,
    buttons: [{
        text: '<span class="fa-solid fa-plus "></span>',
        className: 'btn btn-success btn-sm shadow',
        action: function(e, dt, node, config) {
            // acción
        }
    }],
    language: setIdioma, // o dataTable_es según el contexto
    serverSide: true,
    processing: true,
    ajax: {
        url: "ruta.php",
        type: "POST",
        dataType: "json"
    },
    columns: [...]
});
```

## Pruebas Necesarias

Para cada archivo actualizado:
1. Verificar que la página carga correctamente
2. Comprobar que los permisos funcionan
3. Validar que los DataTables se inicializan
4. Probar las funciones CRUD
5. Verificar que los modales se abren/cierran
6. Comprobar que los overlays funcionan
7. Validar que no hay errores de consola
8. Verificar responsive design

## Notas Importantes

1. **No modificar lógica de negocio**: Solo actualizar estructura y estilos
2. **Mantener compatibilidad**: Asegurar que todas las funciones existentes sigan funcionando
3. **Backup**: Antes de modificar cada archivo, tener respaldo
4. **Pruebas incrementales**: Probar cada archivo después de modificarlo
5. **Consistencia**: Seguir exactamente el patrón del módulo de presupuesto

## Archivos de Referencia

### Ejemplos del módulo de presupuesto:
- `src/presupuesto/lista_ejecucion_pto.php` - Estructura moderna
- `src/presupuesto/js/funcionpresupuesto.js` - JavaScript con overlay
- `src/presupuesto/datos/listar/datos_presupuestos.php` - Backend moderno

### Clase Plantilla:
- `config/clases/Plantilla.php` - Clase principal a usar

### Sistema de Permisos:
- `src/common/php/clases/Permisos.php` - Clase de permisos moderna

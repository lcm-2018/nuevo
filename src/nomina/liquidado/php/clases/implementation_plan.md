# Optimización del "Guardar y Reliquidar" para Prestaciones Sociales

## Contexto

Cuando `codigo_nomina` no es `N`, `PS` ni `RA`, solo se muestra el acordeón **Prestaciones Sociales** con su botón `btnGuardarPretaciones` (option=2). Sin embargo, el código actual del controlador (option=2) intenta procesar **todos** los conceptos de prestaciones sin distinguir el tipo de nómina. Esto genera procesamiento innecesario y riesgo de sobreescribir valores que no corresponden.

Además, cuando se "anula y reliquida" (al final de la opción 2), se usa por defecto la clase base `Liquidacion->addRegistro()`, la cual está diseñada para nóminas normales o liquidaciones completas. Dado que cada tipo de nómina tiene su propia clase de liquidación (`Primas`, `Cesantias`, `Vacaciones`), la reliquidación debe instanciar la clase correcta según el tipo de nómina.

## Lo que ya está hecho

1. En el controlador `liquidado.php` (case 2), ya se añadieron los filtros que condicionan el procesamiento de cada bloque (Prima de Servicio, Prima de Navidad, Cesantías, BSP, Vacaciones) según el tipo de nómina actual (`$codigoNomina`).
2. En `Detalles.php`, ya se implementó la lógica para mostrar solo los campos y tablas relevantes según el tipo de nómina.

## Continuación: Propuesta de Optimización para la Reliquidación

> [!IMPORTANT]
> La optimización pendiente se centra en el **controller** ([liquidado.php](file:///c:/wamp64/www/nuevo/src/nomina/liquidado/php/controladores/liquidado.php)) case `edit`, option `2`, en el bloque final donde se reliquida. Se debe usar la clase correspondiente (`Primas`, `Cesantias`, `Vacaciones`, etc.) en lugar de usar la genérica `Liquidacion`.

---

### Componente 1: Controller

#### [MODIFY] [liquidado.php](file:///c:/wamp64/www/nuevo/src/nomina/liquidado/php/controladores/liquidado.php)

**En el `case 2:` (líneas 299-331)**:

Actualmente, tras anular, se llama a:
```php
$Liquidacion = new Liquidacion($conexion);
$array = [ ... ];
$rstd = $Liquidacion->addRegistro($array, 1);
```

**Se modificará por un `switch ($codigoNomina)` para usar las clases específicas:**

1. Consultar también el `id_tipo` (numérico) asociado al `$codigoNomina` (o usar constantes conocidas si ya se tienen, ej. PV=6, PN=7).
2. Construir el `$array` con los parámetros comunes.
3. Llamar al método específico según corresponda, pasando la opción `1` (que indica reliquidación de valores editados en las clases específicas):

```php
// El array base para las reliquidaciones
$array = [
    'chk_liquidacion' => [0  => $id_empleado],
    'id_contrato'     => [$id_empleado => $_POST['id_contrato']],
    'lab'             => [$id_empleado => $_POST['dias_lab']],
    'metodo'          => [$id_empleado => $_POST['metodo_pago']],
    'tipo'            => $id_tipo_nomina, // El ID numérico de la nómina (ej. 6 para PV)
    'mes'             => $_POST['mes'],
];

switch ($codigoNomina) {
    case 'PV':
    case 'PN':
        $Clase = new Primas($conexion);
        $rstd = $Clase->addRegistroPsPn($array, 1);
        break;
    case 'CE':
    case 'IC':
        $Clase = new Cesantias($conexion);
        $rstd = $Clase->addRegistroN($array, 1);
        break;
    case 'VC':
        $Clase = new Vacaciones($conexion);
        $rstd = $Clase->addRegistroNoVc($array, 1);
        break;
    default:
        // Por defecto (N, PS, RA, IN), se mantiene la genérica
        $array['tipo'] = 2; // Mantener el comportamiento actual de Liquidacion
        $Liquidacion = new Liquidacion($conexion);
        $rstd = $Liquidacion->addRegistro($array, 1);
        break;
}
```

---

## User Review Required

> [!WARNING]
> **1. Obtención del `id_tipo`**: Para pasar al método `addRegistro...` de las clases específicas se necesita la clave `tipo` (que es el ID numérico de la tabla `nom_tipo_liquidacion`). Se sugiere modificar la consulta de la nómina actual al principio del case 2 para que obtenga este ID, o mapearlo directamente (`PV` => 6, `PN` => 7, etc.). ¿Prefieres que se consulte a la base de datos o se haga un mapeo en código?

> [!IMPORTANT]
> **2. Validar métodos llamados**: Se utilizarán los siguientes métodos con el parámetro `opcion = 1` para reliquidar:
> - Para PV y PN: `Primas->addRegistroPsPn($array, 1)`
> - Para CE y IC: `Cesantias->addRegistroN($array, 1)`
> - Para VC: `Vacaciones->addRegistroNoVc($array, 1)`
> 
> ¿Es este el comportamiento y mapeo correcto para usar las clases específicas en la reliquidación?

## Verification Plan

### Manual Verification
1. Ingresar a una nómina de tipo Prima de Servicios (PV).
2. Editar el valor de la prima de un empleado y dar clic en "Guardar y Reliquidar".
3. Verificar en la base de datos y la interfaz que la reliquidación no haya alterado conceptos de otras prestaciones y que haya recalculado los totales correctamente usando la clase `Primas`.
4. Repetir la validación para `CE` (Cesantías) y `VC` (Vacaciones).
5. Verificar que para nóminas normales (tipo `N`) el comportamiento genérico siga funcionando intacto.

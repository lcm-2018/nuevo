# Plan de Implementación — Módulo Contratación Pública

> **Referencia de arquitectura**: `src/nomina/configuracion` (ver `analisis_nomina.md`)  
> **Estética base**: Bootstrap + DataTables + clases `bg-sofia`, `bg-wiev`, `bg-head-button`, `bg-input`, `sombra`  
> **Patrón**: Vista (`index.php`) → DataSource (`lista_*.php`) → Controlador (`controladores/*.php`) → Modelo (`clases/*.php`)  
> **Convención de marcado**: `[ ]` pendiente · `[/]` en progreso · `[x]` completado

---

## FASE 1 — Infraestructura de Base de Datos
> Crear las tablas que soportan todo el módulo antes de escribir una línea de PHP.

- [x] **1.1** Crear tabla `ctt_procesos` — Proceso de contratación
  ```sql
  id_proceso, codigo_proceso (UNIQUE), objeto, tipo_proceso (FK), 
  modalidad (FK), area (FK), id_vigencia, estado (1-5), 
  id_user_reg, fec_reg, id_user_act, fec_act
  ```
- [x] **1.2** Crear tabla `ctt_contratos` — Contrato vinculado a proceso
  ```sql
  id_contrato, id_proceso (FK), codigo_contrato (UNIQUE AUTO), 
  objeto_contrato, valor_inicial, valor_final, 
  id_contratista, fecha_inicio, fecha_fin, 
  estado (BORRADOR/REVISION/APROBADO/FINALIZADO/ANULADO),
  id_user_reg, fec_reg, id_user_act, fec_act
  ```
- [x] **1.3** Crear tabla `ctt_minutas` — Versionamiento inmutable de minutas
  ```sql
  id_minuta, id_contrato (FK), version (INT auto-incremental por contrato),
  contenido (LONGTEXT), hash_sha256, 
  es_activa (TINYINT 0/1), 
  id_user_reg, fec_reg
  -- SIN fec_act ni id_user_act: inmutable por diseño
  ```
- [x] **1.4** Crear tabla `ctt_auditoria` — Log de auditoría automático
  ```sql
  id_log, id_contrato (FK), accion (VARCHAR), 
  tabla_afectada, id_registro,
  datos_antes (JSON), datos_despues (JSON),
  ip_usuario, id_user, fec_hora (DATETIME)
  ```
- [x] **1.5** Crear tabla `ctt_bitacora` — Comentarios y observaciones jurídicas
  ```sql
  id_nota, id_contrato (FK), tipo (COMENTARIO/OBSERVACION/ALERTA),
  descripcion, id_user, fec_hora (DATETIME)
  ```
- [x] **1.6** Crear tabla `ctt_aprobaciones` — Flujo multinivel
  ```sql
  id_aprobacion, id_contrato (FK), nivel (INT), 
  id_user_aprobador, estado (PENDIENTE/APROBADO/RECHAZADO),
  comentario, fec_hora
  ```
- [x] **1.7** Crear tabla `ctt_tipo_proceso` — Catálogo de tipos
  ```sql
  id_tipo, nombre, descripcion, activo
  ```
- [x] **1.8** Crear tabla `ctt_modalidad` *(reutilizada — ya existía)* — Catálogo modalidades de contratación
  ```sql
  id_modalidad, nombre (Licitación, Selección abreviada, Contratación directa...), activo
  ```
- [x] **1.9** Crear tabla `ctt_estado_contrato` *(creada como `ctt_estado_proceso`)* — Catálogo de estados
  ```sql
  id_estado, nombre, color_badge, permite_edicion (TINYINT 0/1), orden
  ```
- [x] **1.10** Verificar existencia / adaptar tabla `ctt_estado_adq` *(conservada para adquisiciones, módulo nuevo usa `ctt_estado_proceso`)*

---

## FASE 2 — Configuración Base del Módulo
> Sub-módulo de configuración, espejo exacto de `nomina/configuracion`.

### Estructura de carpetas a crear
```
src/contratacion/
└── configuracion/
    ├── js/
    │   └── funciones.js
    └── php/
        ├── index.php
        ├── lista_tipos_proceso.php
        ├── lista_modalidades.php
        ├── lista_estados.php
        ├── lista_areas.php
        ├── clases/
        │   ├── TiposProceso.php
        │   ├── Modalidades.php
        │   └── Estados.php
        └── controladores/
            ├── tipos_proceso.php
            ├── modalidades.php
            └── estados.php
```

- [x] **2.1** Crear `configuracion/php/index.php` — Vista acordeón con secciones: Tipos de Proceso, Modalidades, Estados (misma estructura que `nomina/configuracion/php/index.php`)
- [x] **2.2** Crear clase `TiposProceso.php` — Métodos: `getTipos()`, `getRegistrosFilter()`, `getRegistrosTotal()`, `getFormulario()`, `getRegistro()`, `addTipo()`, `editTipo()`, `delTipo()`
- [x] **2.3** Crear `lista_tipos_proceso.php` — DataSource JSON server-side con permisos
- [x] **2.4** Crear `controladores/tipos_proceso.php` — Switch `form|add|edit|del`
- [x] **2.5** Crear clase `Modalidades.php` — Mismo patrón que `TiposProceso`
- [x] **2.6** Crear `lista_modalidades.php` + `controladores/modalidades.php`
- [x] **2.7** Crear clase `Estados.php` (gestión de catálogo de estados)
- [x] **2.8** Crear `lista_estados.php` + `controladores/estados.php`
- [x] **2.9** Crear `configuracion/js/funciones.js` — `crearDataTable()` para cada tabla + listeners de modal

---

## FASE 3 — Gestión de Procesos de Contratación
> Vista principal de procesos (equivalente a la vista de lista del módulo existente).

### Estructura de carpetas a crear
```
src/contratacion/
└── procesos/
    ├── js/
    │   └── funciones.js
    └── php/
        ├── index.php
        ├── lista_procesos.php
        ├── clases/
        │   └── Procesos.php
        └── controladores/
            └── procesos.php
```

- [x] **3.1** Crear clase `Procesos.php`
  - `getProcesos($start, $length, $val_busca, $col, $dir)` — con JOIN a tipo, modalidad, área, estado
  - `getRegistrosFilter()` / `getRegistrosTotal()`
  - `getRegistro($id)` — retorna array vacío si `$id == 0`
  - `getFormulario($id)` — genera HTML del formulario con Heredoc
  - `addProceso($array)` — genera `codigo_proceso` automático + `Logs::guardaLog()`
  - `editProceso($array)` — UPDATE + log auditoría
  - `delProceso($id)` — solo si estado = BORRADOR
- [x] **3.2** Crear `lista_procesos.php` — DataSource con columnas: ID, Código, Objeto, Tipo, Modalidad, Área, Estado, Acciones
- [x] **3.3** Crear `controladores/procesos.php` — Switch `form|add|edit|del`
- [x] **3.4** Crear `procesos/php/index.php` — Vista con DataTable + botón "Nuevo proceso" + modal
- [x] **3.5** Crear `procesos/js/funciones.js` — listeners de tabla y modal con validaciones
- [x] **3.6** Implementar **generación automática de código** de proceso (formato: `CTT-YYYY-NNN`)

---

## FASE 4 — Gestión de Contratos
> Vista de contratos asociada a un proceso. Incluye la pantalla de detalle.

### Estructura de carpetas a crear
```
src/contratacion/
└── contratos/
    ├── js/
    │   └── funciones.js
    └── php/
        ├── index.php
        ├── detalle.php
        ├── lista_contratos.php
        ├── clases/
        │   └── Contratos.php
        └── controladores/
            └── contratos.php
```

- [x] **4.1** Crear clase `Contratos.php`
  - `getContratos($start, $length, $val_busca, $col, $dir, $id_proceso)`
  - `getRegistro($id)` con datos vacíos si nuevo
  - `getFormulario($id, $id_proceso)` — formulario con campos: objeto, valor inicial, contratista, fechas, estado
  - `addContrato($array)` — genera `codigo_contrato` automático (`CTT-CON-YYYY-NNN`) + log
  - `editContrato($array)` — bloquea si `estado >= APROBADO` + log
  - `cambiarEstado($id, $nuevo_estado)` — valida transiciones permitidas + log
- [x] **4.2** Crear `lista_contratos.php` — DataSource con badge de estado coloreado
- [x] **4.3** Crear `controladores/contratos.php` — Switch: `form|add|edit|del|estado`
- [x] **4.4** Crear `contratos/php/index.php` — Vista listado con filtro por estado (tabs o badges)
- [x] **4.5** Crear `contratos/php/detalle.php` — Vista de detalle con pestañas:
  - Datos Generales
  - Minuta / Versiones
  - Aprobaciones
  - Auditoría
  - Bitácora
- [x] **4.6** Crear `contratos/js/funciones.js` — DataTable + gestión de tabs + modal

---

## FASE 5 — Versionamiento de Minutas (Inmutable)
> El corazón del sistema. Cada edición genera una nueva versión, nunca sobreescribe.

### Estructura de carpetas a crear
```
src/contratacion/minutas/
├── js/
│   └── funciones.js
└── php/
    ├── editor.php
    ├── lista_versiones.php
    ├── clases/
    │   └── Minutas.php
    └── controladores/
        └── minutas.php
```

- [x] **5.1** Crear clase `Minutas.php`
  - `getVersiones($id_contrato)` — lista todas las versiones con hash visible
  - `getVersionActiva($id_contrato)` — la versión marcada `es_activa = 1`
  - `getVersion($id_minuta)` — para visualizar o comparar
  - `crearVersion($id_contrato, $contenido, $id_user)` — NUNCA UPDATE, siempre INSERT + calcula `hash_sha256` + desactiva versión anterior + `Logs::guardaLog()`
  - `getFormularioEditor($id_contrato)` — genera el HTML del editor con el contenido activo
  - `getComparacion($id_v1, $id_v2)` — retorna diff entre dos versiones
- [x] **5.2** Crear `lista_versiones.php` — DataSource: Versión, Fecha, Usuario, Hash (primeros 8 chars), Acciones (ver / comparar)
- [x] **5.3** Crear `controladores/minutas.php` — Switch: `form|guardar|ver|comparar`
- [x] **5.4** Crear `minutas/php/editor.php` — Editor de texto con número de versión visible + advertencia si estado no permite editar
- [x] **5.5** Implementar lógica de **bloqueo automático**: si `estado_contrato >= APROBADO`, el editor es solo lectura
- [x] **5.6** Mostrar badge visible con `Versión X` en todo momento dentro del editor
- [x] **5.7** Crear `minutas/js/funciones.js` — manejo del editor, guardado AJAX, recarga de tabla de versiones

---

## FASE 6 — Flujo de Aprobación + Auditoría + Bitácora

### Estructura de carpetas a crear
```
src/contratacion/aprobaciones/php/
├── lista_aprobaciones.php
├── clases/Aprobaciones.php
└── controladores/aprobaciones.php

src/contratacion/auditoria/php/
├── lista_auditoria.php
└── clases/Auditoria.php

src/contratacion/bitacora/php/
├── lista_notas.php
├── clases/Bitacora.php
└── controladores/bitacora.php
```

- [x] **6.1** Crear clase `Aprobaciones.php`
  - `getAprobaciones($id_contrato)` — lista niveles con estado
  - `aprobar($id_aprobacion, $comentario, $id_user)` — valida que sea el aprobador del nivel correcto
  - `rechazar($id_aprobacion, $comentario, $id_user)` — regresa estado a BORRADOR + log
  - `getFormulario($id_contrato)` — formulario de aprobación/rechazo con comentario
- [x] **6.2** Crear `lista_aprobaciones.php` + `controladores/aprobaciones.php`
- [x] **6.3** Crear clase `Auditoria.php`
  - `registrar($id_contrato, $accion, $tabla, $id_reg, $antes, $despues)` — método estático llamado desde todas las clases que modifican datos
  - `getLogs($id_contrato, $start, $length, $busca)` — paginado para DataTable
- [x] **6.4** Crear `lista_auditoria.php` — Solo lectura, sin botones de acción. Columnas: Fecha/Hora, Usuario, IP, Acción, Tabla, Datos Antes/Después
- [x] **6.5** Crear clase `Bitacora.php`
  - `getNotas($id_contrato)` — lista de observaciones/comentarios
  - `addNota($array)` — INSERT sin UPDATE posible (inmutable)
  - `getFormulario($id_contrato)` — formulario con tipo (COMENTARIO / OBSERVACION JURÍDICA / ALERTA)
- [x] **6.6** Crear `lista_bitacora.php` + `controladores/bitacora.php`
- [x] **6.7** Integrar llamada a `Auditoria::registrar()` dentro de todos los métodos `add*`, `edit*`, `del*`, `cambiarEstado()` de todas las clases del módulo

---

## FASE 7 — Generación de PDF con Metadatos de Integridad

- [x] **7.1** Evaluar librería disponible en el proyecto (TCPDF, FPDF, mPDF, DomPDF) — verificar en `composer.json` o carpeta `vendor/`
- [x] **7.2** Crear clase `GeneradorPDF.php` en `src/contratacion/pdf/clases/`
  - `generarContrato($id_contrato)` — carga versión activa de minuta + datos del contrato
  - Incluye metadatos en el pie: versión, hash, fecha de generación, usuario, número de contrato
- [x] **7.3** Crear `controladores/pdf.php` — acción `generar` que hace `header('Content-Type: application/pdf')`
- [x] **7.4** Botón "Descargar PDF" en la vista de detalle del contrato (solo visible si estado >= APROBADO)
- [x] **7.5** Registro en auditoría de cada descarga de PDF (quién, cuándo, qué versión)

---

## FASE 8 — Diferenciadores Avanzados
> Funcionalidades que posicionan el módulo como referente en el sector público.

- [ ] **8.1** **Línea de tiempo del contrato** — Vista visual cronológica en `detalle.php` (CSS timeline) con todos los eventos: creación, versiones, cambios de estado, aprobaciones, descargas
- [ ] **8.2** **Comparador visual de versiones** — Modal con dos columnas (versión A vs B) resaltando diferencias (diff por línea)
- [ ] **8.3** **Alertas de cambios críticos** — Notificación en el panel cuando un contrato cambia de estado o es rechazado
- [ ] **8.4** **Panel de indicadores (Dashboard)** — En `index.php` del módulo: total de contratos por estado, contratos próximos a vencer, pendientes de aprobación
- [ ] **8.5** **Indicadores de riesgo contractual** — Badge visual en la lista: contratos sin minuta activa, sin aprobación en X días, próximos a vencer en 30 días
- [ ] **8.6** **Exportación de auditoría** — Botón en la pestaña Auditoría para exportar a Excel/CSV el log completo del contrato
- [ ] **8.7** **Registro estructurado por cláusula** — Campo adicional en la minuta para identificar cláusulas modificadas entre versiones

---

## Resumen de Archivos por Crear

| Sub-módulo | Archivos PHP | JS | Estado |
|------------|-------------|-----|--------|
| `configuracion` | index, 3 lista_*, 3 clases, 3 controladores | funciones.js | `[ ]` |
| `procesos` | index, lista_procesos, Procesos.php, controlador | funciones.js | `[ ]` |
| `contratos` | index, detalle, lista_contratos, Contratos.php, controlador | funciones.js | `[ ]` |
| `minutas` | editor, lista_versiones, Minutas.php, controlador | funciones.js | `[ ]` |
| `aprobaciones` | lista, Aprobaciones.php, controlador | — | `[ ]` |
| `auditoria` | lista, Auditoria.php | — | `[ ]` |
| `bitacora` | lista, Bitacora.php, controlador | — | `[ ]` |
| `pdf` | GeneradorPDF.php, controlador | — | `[ ]` |

---

## Convenciones a Respetar (igual que nómina)

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Tablas HTML | `table` + Recurso + Módulo | `tableProcesosCtt` |
| Cuerpos de tabla | `modifica` + Recurso | `modificaProcesosCtt` |
| Botones guardar | `btnGuarda` + Recurso | `btnGuardaProceso` |
| Formularios | `formGest` + Recurso | `formGestProcesoCtt` |
| Input hidden ID | `id` (siempre) | `<input id="id" name="id">` |
| Namespaces PHP | `Src\Contratacion\[SubMod]\Php\Clases\` | `Src\Contratacion\Configuracion\Php\Clases\TiposProceso` |
| Código auto proceso | `CTT-YYYY-NNN` | `CTT-2026-001` |
| Código auto contrato | `CTT-CON-YYYY-NNN` | `CTT-CON-2026-001` |

---

## Orden de Ejecución Recomendado

```
FASE 1 (BD) → FASE 2 (Config) → FASE 3 (Procesos) → FASE 4 (Contratos)
     → FASE 5 (Minutas) → FASE 6 (Auditoría/Aprobaciones/Bitácora)
          → FASE 7 (PDF) → FASE 8 (Avanzados)
```

> **Principio clave**: Cada fase debe quedar funcional y probada antes de pasar a la siguiente.

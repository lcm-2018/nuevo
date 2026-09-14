# Plan de Implementación: Módulo de Gestión de Contratos (CLM)

Este documento es la guía técnica y arquitectónica para la construcción del módulo CLM (Contract Lifecycle Management) en el sistema. Debe ser utilizado por la IA asistente para mantener el contexto, construir el módulo paso a paso y marcar las tareas a medida que se completen.

## 1. Arquitectura y Patrón de Diseño
Se utilizará el mismo patrón arquitectónico (MVC simplificado basado en endpoints) detectado en el módulo de `nomina`, para mantener consistencia en el proyecto:
- **Controladores (`/src/contrata/php/controladores/`)**: Reciben peticiones POST, instancian clases, ejecutan lógica y devuelven respuestas JSON.
- **Clases/Modelos (`/src/contrata/php/clases/`)**: Contienen la lógica de negocio, consultas SQL usando `PDO` y generación de formularios en HTML embebido.
- **Vistas/Frontend (`/src/contrata/js/`)**: Archivos JavaScript (`funciones.js`, `detalles.js`) para manejar peticiones AJAX, renderizado de DataTables y control del DOM.
- **Seguridad**: Validación de sesión con `$_SESSION['user']` al inicio de cada controlador.

## 2. Requerimientos Clave (Normativa Colombiana - Ley 527 de 1999)
- **Versionamiento Estricto**: Todo cambio en un contrato genera una nueva versión; las versiones anteriores no se sobrescriben.
- **Inmutabilidad de Estados**: Una vez firmado/aprobado, el registro se bloquea criptográficamente (hash) para garantizar que no fue alterado.
- **Control de Cambios (Diff Visual al estilo GitHub)**: Interfaz que muestra qué se agregó o eliminó entre dos versiones de un contrato usando un sistema Diff con vista lado a lado o en línea (ej. librerías como `diff2html` o `jsdiff`).
- **Trazabilidad de Auditoría**: Registro de IP, Timestamp, Usuario, Acción (Creación, Modificación, Firma) y Hash del estado.

## 3. Modelo de Base de Datos Propuesto
Crear las siguientes tablas en la base de datos (usar scripts SQL en el backend para su creación/verificación):

1. **`cnt_contratos`**: Tabla maestra.
   - `id_contrato` (PK), `numero_contrato`, `id_tercero`, `estado` (Borrador, Revisión, Aprobado, Firmado, Anulado), `fecha_inicio`, `fecha_fin`, `valor`.
2. **`cnt_contrato_versiones`**: Almacena inmutablemente el contenido de cada versión.
   - `id_version` (PK), `id_contrato` (FK), `version_num` (1.0, 1.1, 2.0), `contenido_html` o `data_json` (dependiendo de la estructura), `hash_integridad` (SHA-256), `fecha_version`, `id_usuario_crea`.
3. **`cnt_auditoria`**: Trazabilidad legal.
   - `id_auditoria` (PK), `id_contrato` (FK), `accion` (ej: 'FIRMA_ELECTRONICA'), `ip_origen`, `user_agent`, `timestamp`, `id_usuario`, `detalles_json`.

---

## 4. Checklist de Tareas (Roadmap de Desarrollo)
*La IA que asuma este contexto debe cambiar `[ ]` por `[x]` al completar cada hito.*

### Fase 1: Estructura y Base de Datos
- [x] **T1.1**: Crear la estructura de directorios en `src/contrata/` (php/clases, php/controladores, js).
- [x] **T1.2**: Diseñar el script SQL para crear las tablas `cnt_contratos`, `cnt_contrato_versiones` y `cnt_auditoria`.
- [x] **T1.3**: Ejecutar y verificar la creación de las tablas en la BD a través de una clase de instalación o directamente en el gestor.

### Fase 2: Backend (Clases y Controladores)
- [x] **T2.1**: Crear la clase base `Contratos.php` en `/php/clases/` que gestione la conexión y los queries (CRUD).
- [x] **T2.2**: Implementar el método `crearVersion(...)` que aplique SHA-256 al contenido para el hash de integridad (Inmutabilidad).
- [x] **T2.3**: Implementar el método `registrarAuditoria(...)` que capture la IP (`$_SERVER['REMOTE_ADDR']`) y datos del usuario.
- [x] **T2.4**: Crear el controlador `/php/controladores/contratos.php` para manejar las acciones (list, add, edit, diff, sign, annul).

### Fase 3: Frontend y Visual Diff
- [x] **T3.1**: Crear `js/funciones.js` con las llamadas AJAX a `contratos.php`.
- [x] **T3.2**: Configurar DataTables en `js/detalles.js` para listar los contratos (Estado, Versión Actual, Tercero).
- [x] **T3.3**: Integrar un editor de texto enriquecido WYSIWYG (se recomienda **TinyMCE** o **CKEditor 5**) para la redacción y edición del cuerpo de los contratos, garantizando una salida HTML limpia.
- [x] **T3.4**: Implementar la vista del historial de versiones (Timeline).
- [x] **T3.5**: Integrar una librería de Diff (ej. `diff2html` o `diff.js`) para mostrar visualmente las adiciones (verde) y eliminaciones (rojo) entre dos versiones, emulando la interfaz de comparación de GitHub (lado a lado / side-by-side).

### Fase 4: Firma y Cierre (Normativa COP)
- [x] **T4.1**: Implementar el mecanismo de "Firma Electrónica" (aceptación con credenciales y registro de metadata en `cnt_auditoria`).
- [x] **T4.2**: Validación de que un contrato en estado "Firmado" desactiva todos los botones de edición.
- [x] **T4.3**: Generación de documento PDF automático del contrato una vez pase a estado "Aprobado", utilizando librerías como TCPDF, MPDF o Dompdf.
- [x] **T4.4**: Generación de certificado de trazabilidad (PDF o vista detallada) con la cadena de bloques interna (hashes consecutivos de las versiones).

---
## Notas para la IA de Desarrollo
- Mantén siempre la sesión de usuario como fuente de verdad para auditorías (`$_SESSION['user']`, `$_SESSION['id_usuario']`).
- No sobrescribas nunca `cnt_contrato_versiones`. Un "Edit" siempre es un "Insert" de nueva versión.
- Verifica constantemente las reglas de la Ley 527/99 respecto a la equivalencia funcional del mensaje de datos (Integridad y Atribución).

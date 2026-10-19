# Arquitectura backend 0.4.7

PHP con tipos estrictos, PDO preparado y MySQL/MariaDB InnoDB. Las rutas de `server/api` se publican exclusivamente mediante `server/public/router.php` durante desarrollo. El núcleo común carga configuración fuera de Git, tipos/validación, errores JSON, limitación de peticiones, sesión/CSRF y servicios de persistencia. `Controllers` y `Models` permanecen reservados y sin implementación; no representan capas activas.

`AuthService` revalida usuario activo y rol en cada petición autenticada. `AuthorizationService` y `Csrf` protegen operaciones de panel; combustible y mantenimiento tienen el mismo límite que el resto del panel. `ApiKeyValidator` autentica el UID realmente usado por la escritura y evita contradicciones URL/cuerpo. La clave global queda restringida al desarrollo. No se han añadido ACL por dispositivo: el modelo actual es una instalación con roles globales.

`TelemetryValidator` normaliza alias, estados, unidades y salidas antes de `TelemetryRepository`. `BoilerConfigValidator` exporta enteros compatibles con Mega, perfiles de fase y ventana MAN. `CommandRepository` conserva solicitudes e historial atómicos: consultar no significa entrega, y no existe ejecución firmware. `config_ack` conserva versión candidata y alcance parcial como evento, sin confirmar los campos aún no implementados.

`DashboardRepository` distingue ausencia de muestra, simulación y muestra registrada, con antigüedad de recepción. MySQL disponible no significa caldera en comunicación ni medida física válida. El frontend muestra esa calidad y avisa cuando falla la actualización; las secciones y gráficas demo siguen siendo demostración.

Sin MySQL, las sesiones no pueden validar permisos y las escrituras administrativas fallan con 503. Telemetría y ACK pueden responder 202 con `persistence=skipped`; config puede devolver `source=fallback_safe`. El puente rechaza ese origen y conserva la configuración local. Ningún fallback debe considerarse evidencia de control real.

La primera migración versionada está en `server/sql/migrations/0.4.7_auditoria.sql`; ver procedimiento en `server/sql/migrations.md`. El esquema base refleja los mismos campos para instalaciones vacías. Las pruebas destructivas requieren nombre terminado en `_ci`. Los datos demo y sus hashes se conservan solo para desarrollo y pruebas.

El manejo genérico de excepciones evita filtrar SQL y datos de entrada. El limitador falla cerrado al perder almacenamiento. HTTPS, política de retención, monitorización de errores, respaldo/restauración y configuración del servidor de producción siguen pendientes; no se ha desplegado esta revisión.

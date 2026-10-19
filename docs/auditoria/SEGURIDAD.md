# Revisión de seguridad y límites

Revisión directa de código sobre la base 0.4.6 y regresiones de 0.4.7. No es una certificación de instalación ni una prueba de penetración de un despliegue. El flujo automatizado de Codex Security no pudo iniciarse: las referencias internas de su habilidad no fueron accesibles. No se presentan artefactos canónicos de un escáner que no se ejecutó.

Fronteras revisadas: navegador no confiable→PHP; identidad de usuario→rol vigente; clave del dispositivo→su UID; PHP→MySQL; HTTP→parser ESP32; UART→configuración Mega; EEPROM→configuración local. La UART carece de autenticación criptográfica y requiere acceso físico controlado. El firmware conserva exclusivamente simulación.

| ID / gravedad contextual | Defecto verificable en 0.4.6 | Corrección y prueba |
| --- | --- | --- |
| S01 / alta, integridad de datos | `fuel.php` y `maintenance.php` permitían lectura y escritura sin autenticación | Sesión; rol operativo y CSRF en POST; regresiones anónimo/CSRF/solo lectura. |
| S02 / alta, límite entre dispositivos | Validación de API key prefería UID de query; escritura prefería UID de JSON | POST autentica el destino del cuerpo y rechaza conflicto query/cuerpo; claves A/B y comprobación de ausencia de escritura cruzada. |
| S03 / alta, permisos revocados | Sesión conservaba rol y acceso tras cambio de rol o desactivación de usuario | Estado/rol consultados en MySQL por petición, fallo cerrado sin DB, inactividad 30 min y modo estricto; prueba con cookie anterior a la revocación. |
| S04 / alta si se publicaba raíz `server` | Comando recomendado podía servir `.env`, SQL y almacenamiento como estáticos | Raíz `server/public` y router explícito; GET a rutas internas devuelve 404. En Apache/Nginx se debe replicar la lista de rutas, nunca publicar `server`. |
| S05 / media, comandos sin trazabilidad real | GET marcaba cola como enviada aunque ESP32 descartaba respuesta | GET no consume; ESP32 no consulta una cola que no ejecuta; START bloqueado incluso con antigua bandera; creación e historial atómicos. STOP sigue registrándose, sin garantía de ejecución. |
| S06 / media, entradas ambiguas | Arrays como texto, JSON raíz array, fechas inexistentes, secretos recortados y números no finitos | Tipos estrictos, raíz objeto, cuerpo ≤16 KiB/profundidad 32, fechas reales, secretos íntegros ≤72 bytes para bcrypt, finitud y telemetría normalizada; regresiones HTTP. |
| S07 / alta antes de hardware | Mega convertía 306→50 en uint8 antes de validar; parseaba números parciales y sufijos de líneas largas | Parser decimal exacto sobre uint32, rangos antes de convertir, campos obligatorios, sin repetidos/desconocidos, descarte hasta fin de línea; regresiones nativas y sketch real. |
| S08 / alta antes de hardware | Alarmas se autorrearmaban y sobretemperatura apagaba también la bomba simulada | Enclavamiento, detección NaN/Inf, disipación simulada con sonda válida; regresiones del sketch. El procedimiento físico sigue pendiente. |
| S09 / media | Limitador toleraba pérdida del almacenamiento | Fallo 503 al no poder abrir/bloquear/persistir; límite por IP además de identidad. No sustituye control de abuso de un proxy. |
| S10 / media | Clave compartida de desarrollo autorizaba cualquier dispositivo también en producción | `APP_ENV=production` exige hash por dispositivo; placeholders/longitudes inválidas rechazados. La clave compartida sigue disponible en desarrollo para compatibilidad. |
| S11 / media | Excepciones no controladas podían romper JSON o filtrar detalles con display_errors | Manejador de excepciones con respuesta genérica, sin SQL ni entrada; no-store/nosniff. Revisar configuración PHP del despliegue. |

También se corrigió el identificador de inserción de telemetría perdido tras actualizar el dispositivo, y el endpoint de eventos fija origen firmware y valida título/mensaje para impedir atribución de actividad al backend. Los identificadores de edición y campos opcionales de mantenimiento tienen validación explícita.

La revisión conservó consultas PDO preparadas, hash_equals, password_hash/password_verify, regeneración de sesión, cookies HttpOnly/SameSite Strict y escape de HTML con catálogos de color/icono en JavaScript. No se detectó una vía de inyección SQL en las consultas revisadas. Esto no equivale a demostrar ausencia de vulnerabilidades. Los mensajes de restablecimiento siguen sin revelar si existe una cuenta; el envío y consumo de token no están implementados.

Riesgos y trabajos pendientes:

- HTTPS autenticado ESP32↔backend, CA del dispositivo y política de renovación: no configurados ni probados. El ejemplo HTTP es de desarrollo; la compilación de operación está bloqueada.
- Firmware de comandos: no existe protocolo de ejecución, deduplicación, ACK final ni procedimiento seguro de RESET_ALARM. Una orden en MySQL no detiene una caldera. Los comandos no implementados no deben exponerse como controles funcionales.
- El alcance actual es una instalación con roles globales; no existen ACL por usuario y dispositivo. La clave compartida solo es admisible en desarrollo aislado. Rotación de claves y recuperación administrativa requieren diseño posterior.
- El frontend depende de CDN de Bootstrap/Icons/Chart.js con versión fijada pero sin SRI ni copia local; la disponibilidad, CSP y verificación de integridad del despliegue requieren revisión. El workflow usa etiquetas de acciones en vez de SHA inmutables: riesgo de cadena de suministro pendiente.
- Los contadores por fichero no tienen limpieza periódica ni coordinación entre réplicas. La disponibilidad del disco y protección del almacenamiento forman parte del despliegue. Se necesita retención/limpieza y límite de recursos en proxy antes de exposición pública.
- Las cookies Secure dependen de HTTPS reconocido por PHP; configurar terminación TLS del servidor. No confiar ciegamente en X-Forwarded-Proto. Login sin CSRF previo sigue protegido por SameSite Strict y JSON, pero requiere prueba de origen del despliegue real.
- Datos demo y claves ficticias de pruebas son públicos por diseño. Nunca importar seed demo en producción. No se imprimieron secretos locales del despliegue en la auditoría ni se trasladaron a los cambios versionados.
- Watchdog, protección independiente, verificación de sensores y salidas, comportamiento durante caída eléctrica, retorno de llama y bomba de disipación siguen sin validación física. Son bloqueos críticos.

Las correcciones son estructurales y regresables; no se modificó un despliegue, no se enviaron órdenes a dispositivos y no se conectaron cargas.

# Bloque 1: auditoría técnica integral

**Estado: INCOMPLETO / BLOQUEADO por dependencias críticas. No avanzar al bloque 2.**
Revisión: 2026-10-19. Producto propuesto: `0.4.7-auditoria-tecnica`. Base revisada: `e97952921b2f552da1cbff4c8967d1f47a2c2fd9`, documentada como 0.4.6. Rama: `auditoria/bloque-1-base-tecnica`. La entrega es revisable y de simulación, no aprobación para operar la caldera.

## Alcance y estructura revisada

| Área | Contenido encontrado | Resultado de la revisión |
| --- | --- | --- |
| firmware/arduino-mega | Un sketch con sensores/salidas simulados, estados por reloj, EEPROM | Parser y seguridad lógica corregidos; no implementa FSM original completa |
| firmware/esp32 | Un sketch con WiFi/HTTP, UART y extracción textual de configuración | JSON estructurado, límites y entrega/ACK corregidos; red real bloqueada |
| server/api | 18 endpoints PHP y entrada pública | Permisos, tipos, errores, identidad de dispositivo y persistencia revisados |
| server/app | Core y servicios/repositorios; Controllers/Models reservados | Revalidación de sesión y contratos; no se atribuye implementación a carpetas vacías |
| server/sql | Esquema InnoDB, seeds de ejemplo/demo, migraciones antes vacías | Migración 0.4.7 con CHC/REC y perfiles por fase; pruebas de preservación |
| web | Bootstrap 5.3.3, Icons 1.11.3, Chart.js 4.4.1 y JS vanilla | Escape HTML ya existente conservado; calidad y antigüedad de muestra visibles; gran parte del panel sigue siendo demostración |
| tests/tools | Lint, validaciones estáticas, unitarios, smoke e integración | Regresiones ejecutables de parsers/sketch y HTTP, migración y protección del nombre de DB destructiva |
| .github/workflows | CI con PHP/Node/Arduino CLI y MySQL 8.4 | Se amplían pruebas y fijan dependencias de firmware; no despliega ni carga placas |
| docs | Arquitectura, hardware, reglas y planificación histórica | Se corrigen ON=OFF universal y descripción de MAN; nueva evidencia por página y bloqueos |

No se encontraron AGENTS.md ni SECURITY.md de repositorio. Las reglas de seguridad aplicadas están en `docs/seguridad/REGLAS_SEGURIDAD.md`, las decisiones del proyecto y la solicitud del bloque. No se modificaron PDF originales, una base productiva ni un despliegue. No se conectaron ni habilitaron cargas o GPIO.

## Correcciones verificables

- Sesión/roles/CSRF para combustible y mantenimiento; revalidación inmediata de rol/estado de usuario, inactividad, cookie de sesión estricta y rotación de CSRF al iniciar sesión.
- Identidad de dispositivo coherente entre autenticación y persistencia; POST con UID contradictorio se rechaza; clave global limitada a desarrollo.
- Router público que evita servir `.env`, SQL y almacenamiento; errores JSON genéricos y caché deshabilitada; limitador de peticiones con fallo cerrado.
- Validación de tipos, fechas reales, identificadores de edición, secretos íntegros y números finitos. Los eventos de dispositivo no pueden atribuirse al backend ni convertirse silenciosamente en eventos globales de un UID desconocido.
- CHC y REC canónicos con compatibilidad de CHECK; captura correcta de telemetry_id antes del UPDATE de última comunicación.
- Parser de Mega con valores anchos antes de estrechar tipos, rechazo de campos corruptos y descarte de línea completa; contador uint32, rechazo de versiones antiguas/reutilizadas y confirmación idempotente sin desgaste de EEPROM.
- Tiempos ON/OFF independientes por fase; MAN con T04/T05 y CP09/CL09; migración EEPROM v1→v2; alarmas enclavadas y protección NaN/Inf; disipación **simulada** ante sobretemperatura con sonda válida.
- Parser ArduinoJson y respuesta compacta para ESP32, sin aplicar catálogo ni fallback remoto como si fueran datos persistidos; reenvío periódico y ACK parcial con versión candidata separada de activa, conservado hasta persistencia confirmada.
- GET de cola no consume comandos; START siempre bloqueado; los demás solo se registran y no afirman ejecución. Creación e historial son atómicos. La versión de producto se unifica entre PHP y ambos sketches.

Las correcciones de comportamiento legado se justifican por el defecto observado: impedir falsa entrega, evitar alimentación MAN continua, retirar un supuesto universal de fabricante y rechazar datos anteriormente truncados. Se conservan esquema/órdenes/alias históricos y perfiles legados explícitos.

## Dependencias y defectos pendientes

| Bloqueo | Evidencia y trabajo necesario |
| --- | --- |
| B01: fuentes incompletas | UTENTE está en Descargas del ordenador del propietario, fuera del entorno. SY325 no localizado. La reproducción del esquema en p. 27 del UTENTE está indicada por el propietario pero no leída. Incorporar fuente y verificar páginas antes de cerrar auditoría documental. |
| B02: contradicciones originales | A06 duplicado, UA05/UA10, P03/P04, velocidad NORMAL, párrafo de bomba y otros conflictos. Ver [fuentes](FUENTES_ORIGINALES.md). Confirmar centralita/revisión/receta sin escoger arbitrariamente una traducción. |
| B03: control original incompleto | La FSM es demostración temporizada. Faltan guardas de encendido/estabilización/llama, REC, SIC/SPE, antihielo, entradas externas, segundo sinfín y recetas. No basta enumerar once estados. |
| B04: seguridad de operación | Falta verificación física de sondas, termostato, cableado, potencia, bomba, caída eléctrica, watchdog, retorno de llama y actuadores. El inventario es declarado, no comprobado. |
| B05: enlace y comandos de operación | TLS/CA, antigüedad de muestras a nivel local, cola durable e idempotencia, ejecución y ACK de comandos no implementados. En EEPROM falta doble slot y prueba de escritura interrumpida. |
| B06: garantías de calidad restantes | Sonar y pruebas de navegador/despliegue no ejecutados. MariaDB local no acredita MySQL 8.4 ni cadena física; CI remota debe revisarse en la PR. |

La configuración HTTP todavía incluye `mode`, timeout y postventilación no aplicados por Mega. Su ausencia de aplicación se expone en el contrato y el ACK queda limitado a `control_subset`. Los porcentajes de ventilación del simulador no reconstruyen UC/UA por fase. Los datos de panel y gráficas pueden ser demo o muestras antiguas; se indica su calidad.

## Criterios de aceptación

`PASA` acredita el alcance descrito; `FALLA` indica un requisito no satisfecho; `NO COMPROBADO` identifica evidencia o ejecución ausente. Un PASA parcial no cierra otros criterios.

| Criterio | Resultado | Evidencia / límite |
| --- | --- | --- |
| Revisar estructura firmware, backend, MySQL, frontend, pruebas y Actions | PASA | Inventario anterior; código y rutas revisados |
| Contrastar las tres fuentes originales con páginas | NO COMPROBADO | Dos fuentes requeridas inaccesibles; TECNICO adicional no equivale a UTENTE |
| Tabla completa de parámetros de la centralita instalada | NO COMPROBADO | 65 entradas de tablas accesibles más menú de usuario; faltan fuente, receta instalada y códigos ambiguos |
| Matriz de once estados y todas sus transiciones verificadas | FALLA | Matriz documentada; guardas y transiciones críticas no verificables ni implementadas |
| Trabajo/pausa por fase y MAN CL09/CP09/T04/T05 corregidos en simulación | PASA | Modelo y sketch real con E/S simulada; no prueba combustión real |
| Identificar/corregir defectos de seguridad verificables | PASA | Límites de identidad, rol, CSRF y tipos cubiertos por regresiones; revisión directa documentada |
| Resolver todas las contradicciones y riesgos críticos de operación | FALLA | B01–B05 siguen abiertos; simulación obligatoria |
| Contratos del subconjunto implementado coherentes y versionados | PASA | HTTP/UART 1.1, EEPROM 2, SQL 0.4.7, compatibilidad y campos no aplicados explícitos |
| Integración física ESP32↔Mega↔backend↔MySQL completa | NO COMPROBADO | Compilación y simulación de parsers; sin placas ni red de operación |
| Pruebas automatizadas locales ejecutadas y resultados documentados | PASA | [Pruebas](PRUEBAS.md): PHP/JS, 62 comprobaciones nativas, HTTP/MySQL, migración y compilación |
| Sonar y MySQL 8.4 remoto | NO COMPROBADO | No se ejecutó Sonar; estado de Actions se consulta en la PR |
| README, version, CHANGELOG y documentación técnica actualizados | PASA | Revisión 0.4.7 con bloqueos, arquitectura, inventario, decisiones y migración |
| Rama independiente y Pull Request revisable | PASA | Rama de auditoría; enlace de PR en la entrega del chat |
| Sin habilitar salidas físicas y sin avanzar al bloque 2 | PASA | `static_assert(SIMULATION_MODE)` y ausencia de escrituras GPIO |
| Bloque 1 completamente satisfecho | FALLA | Dependencias originales y defectos críticos abiertos |

## Evidencia vinculada

[Fuentes y discrepancias](FUENTES_ORIGINALES.md) · [Parámetros](PARAMETROS_ORIGINALES.md) · [Estados y protecciones](MAQUINA_ESTADOS.md) · [Seguridad](SEGURIDAD.md) · [Pruebas](PRUEBAS.md) · [Contrato](../arquitectura/API_CONTRACT.md) · [Hardware](../arquitectura/HARDWARE.md) · [Migración SQL](../../server/sql/migrations.md).

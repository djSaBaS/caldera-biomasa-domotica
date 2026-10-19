# Bloque 1: auditoría técnica integral

**Estado: INCOMPLETO / BLOQUEADO por dependencias críticas. No avanzar al bloque 2.**
Revisión ampliada con los adjuntos originales y confirmación del propietario: 2026-10-19. Producto propuesto: `0.4.7-auditoria-tecnica`. Base revisada: `e97952921b2f552da1cbff4c8967d1f47a2c2fd9`, documentada como 0.4.6. Rama: `auditoria/bloque-1-base-tecnica`. La entrega es revisable y de simulación, no aprobación para operar la caldera.

## Alcance y estructura revisada

| Área | Contenido encontrado | Resultado de la revisión |
| --- | --- | --- |
| firmware/arduino-mega | Un sketch con sensores/salidas simulados, estados por reloj, EEPROM | Parser, seguridad lógica y diario EEPROM corregidos; no implementa FSM original completa |
| firmware/esp32 | Un sketch con WiFi/HTTP, UART y extracción textual de configuración | JSON estructurado, límites, caducidad de muestra y reintento de ACK corregidos; red real bloqueada |
| server/api | 18 endpoints PHP y entrada pública | Permisos, tipos, errores, identidad de dispositivo y persistencia revisados |
| server/app | Core y servicios/repositorios; Controllers/Models reservados | Revalidación de sesión y contratos; no se atribuye implementación a carpetas vacías |
| server/sql | Esquema InnoDB, seeds de ejemplo/demo, migraciones antes vacías | Migración 0.4.7 con CHC/REC y perfiles por fase; pruebas de preservación |
| web | Bootstrap 5.3.3, Icons 1.11.3, Chart.js 4.4.1 y JS vanilla | Escape HTML ya existente conservado; calidad y antigüedad de muestra visibles; gran parte del panel sigue siendo demostración |
| tests/tools | Lint, validaciones estáticas, unitarios, smoke e integración | Regresiones ejecutables de parsers/sketch y HTTP, migración y protección del nombre de DB destructiva |
| .github/workflows | CI con PHP/Node/Arduino CLI y MySQL 8.4 | Se amplían pruebas y fijan dependencias de firmware; no despliega ni carga placas |
| docs | Arquitectura, hardware, reglas y planificación histórica | Se corrigen ON=OFF universal y descripción de MAN; originales conservados, esquema contrastado y variantes separadas por página |

No se encontraron AGENTS.md ni SECURITY.md de repositorio. Las reglas de seguridad aplicadas están en `docs/seguridad/REGLAS_SEGURIDAD.md`, las decisiones del proyecto y la solicitud del bloque. No se modificaron PDF originales, una base productiva ni un despliegue. No se conectaron ni habilitaron cargas o GPIO.

## Correcciones verificables

- Sesión/roles/CSRF para combustible y mantenimiento; revalidación inmediata de rol/estado de usuario, inactividad, cookie de sesión estricta y rotación de CSRF al iniciar sesión.
- Identidad de dispositivo coherente entre autenticación y persistencia; POST con UID contradictorio se rechaza; clave global limitada a desarrollo.
- Router público que evita servir `.env`, SQL y almacenamiento; errores JSON genéricos y caché deshabilitada; limitador de peticiones con fallo cerrado.
- Validación de tipos, fechas reales, identificadores de edición, secretos íntegros y números finitos. Los eventos de dispositivo no pueden atribuirse al backend ni convertirse silenciosamente en eventos globales de un UID desconocido.
- CHC y REC canónicos con compatibilidad de CHECK; captura correcta de telemetry_id antes del UPDATE de última comunicación.
- Parser de Mega con valores anchos antes de estrechar tipos, rechazo de campos corruptos y descarte de línea completa; contador uint32, rechazo de versiones antiguas/reutilizadas y confirmación idempotente sin desgaste de EEPROM.
- Tiempos ON/OFF independientes por fase; MAN con T04/T05 y CP09/CL09; migración EEPROM v1/v2 legado→diario CBJ1; alarmas enclavadas y protección NaN/Inf; disipación **simulada** ante sobretemperatura con sonda válida.
- Parser ArduinoJson y respuesta compacta para ESP32, sin aplicar catálogo ni fallback remoto como si fueran datos persistidos; reenvío periódico y ACK parcial con versión candidata separada de activa, conservado hasta persistencia confirmada.
- Diario EEPROM con dos ranuras, generación uint32, CRC32, marcador final y lectura de verificación antes de activar/confirmar; recuperación de copia anterior ante corte o corrupción, sin modificar el legado. Regresiones con cortes en todas las fronteras de escritura y corrupción de cada byte.
- ESP32 impide renovar indefinidamente una muestra antigua: caduca a los 15 s desde recepción UART y se consume al confirmar persistencia. Reintentos de ACK espaciados cinco segundos; URL de ejemplo coherente con `/api` público.
- GET de cola no consume comandos; START siempre bloqueado; los demás solo se registran y no afirman ejecución. Creación e historial son atómicos. La versión de producto se unifica entre PHP y ambos sketches.

Las correcciones de comportamiento legado se justifican por el defecto observado: impedir falsa entrega, evitar alimentación MAN continua, retirar un supuesto universal de fabricante y rechazar datos anteriormente truncados. Se conservan esquema/órdenes/alias históricos y perfiles legados explícitos.

## Dependencias y defectos pendientes

| Bloqueo | Evidencia y trabajo necesario |
| --- | --- |
| B01: revisión/ficha no acreditadas | **Resuelto acceso a UTENTE y esquema impreso 27/PDF 28.** Cuatro PDF y contexto conservados con hashes. SCHEDA_SY325.pdf independiente sigue ausente; 4ad709 describe otra variante. Falta referencia/revisión y receta instalada para resolver equivalencias. |
| B02: contradicciones originales | A06 duplicado, UA05/UA10, P03/P04 y ventilación SPE. UTENTE aclara bomba, rango narrativo del ventilador NORMAL, destino MAN por P34 y ramas REC; no elimina los restantes conflictos. Ver [fuentes](FUENTES_ORIGINALES.md). Confirmar centralita/revisión/receta sin escoger arbitrariamente una traducción. |
| B03: control original incompleto | La FSM es demostración temporizada. Faltan guardas de encendido/estabilización/llama, REC, SIC/SPE, antihielo, entradas externas, V3/T51 y recetas. El segundo canal es opcional: el propietario confirma solo un sinfín instalado. No basta enumerar once estados. |
| B04: seguridad de operación | Falta verificación física de sondas, termostato, cableado, potencia, bomba, caída eléctrica, watchdog, retorno de llama y actuadores. El inventario combina manuales y declaración del propietario; no está comprobado físicamente. |
| B05: enlace y comandos de operación | **Resueltos diario EEPROM y caducidad local de telemetría en host.** Pendientes TLS/CA, edad de adquisición de sensor, secuencia/idempotencia, cola durable, ejecución/ACK de comandos y ensayo físico de escritura interrumpida. |
| B06: garantías de calidad restantes | CI #11 verifica la ampliación con MySQL 8.4 y ambos firmwares. Nueve aserciones de navegador básico pasan localmente. Sonar, diseño visual completo, CDN/gráficas y despliegue no comprobados; ninguna prueba de host acredita la cadena física. |

La configuración HTTP todavía incluye `mode`, timeout y postventilación no aplicados por Mega. Su ausencia de aplicación se expone en el contrato y el ACK queda limitado a `control_subset`. Los porcentajes de ventilación del simulador no reconstruyen UC/UA por fase. Los datos de panel y gráficas pueden ser demo o muestras antiguas; se indica su calidad.

## Criterios de aceptación

`PASA` acredita el alcance descrito; `FALLA` indica un requisito no satisfecho; `NO COMPROBADO` identifica evidencia o ejecución ausente. Un PASA parcial no cierra otros criterios.

| Criterio | Resultado | Evidencia / límite |
| --- | --- | --- |
| Revisar estructura firmware, backend, MySQL, frontend, pruebas y Actions | PASA | Inventario anterior; código y rutas revisados |
| Contrastar originales disponibles y esquema CTP con páginas | PASA | ES, UTENTE, TECNICO y variante G; [fuentes](FUENTES_ORIGINALES.md) y [esquema impreso 27/PDF 28](ESQUEMAS_Y_HARDWARE.md) |
| Acreditar SCHEDA independiente y revisión de placa instalada | NO COMPROBADO | Archivo SCHEDA no obtenido; propietario describe variante específica, referencia exacta desconocida |
| Tabla completa de parámetros de la centralita instalada | NO COMPROBADO | 65 entradas CTP y menú, más tabla separada de G; faltan receta instalada, revisión y resolución de códigos ambiguos |
| Matriz de once estados y todas sus transiciones verificadas | FALLA | Matriz documentada; guardas y transiciones críticas no verificables ni implementadas |
| Trabajo/pausa por fase y MAN CL09/CP09/T04/T05 corregidos en simulación | PASA | Modelo y sketch real con E/S simulada; no prueba combustión real |
| Identificar/corregir defectos de seguridad verificables | PASA | Límites de identidad, rol, CSRF y tipos cubiertos por regresiones; revisión directa documentada |
| Resolver todas las contradicciones y riesgos críticos de operación | FALLA | Se cierran acceso UTENTE, esquema, diario y caducidad local; B01–B05 conservan pendientes críticos, simulación obligatoria |
| Contratos del subconjunto implementado coherentes y versionados | PASA | HTTP/UART 1.1, EEPROM lógico 2 / diario CBJ1, SQL 0.4.7, compatibilidad y campos no aplicados explícitos |
| Integración física ESP32↔Mega↔backend↔MySQL completa | NO COMPROBADO | Compilación y simulación de parsers; sin placas ni red de operación |
| Pruebas automatizadas locales ejecutadas y resultados documentados | PASA | [Pruebas](PRUEBAS.md): PHP/JS, 1151 comprobaciones nativas, integridad de cinco fuentes, 43 regresiones HTTP/MySQL, migración y compilación |
| MySQL 8.4 y Actions remotos de esta ampliación | PASA | [CI #11](https://github.com/djSaBaS/caldera-biomasa-domotica/actions/runs/39256861998), success sobre d533eb1; runtime de la ampliación verificado |
| Recorrido básico de navegador | PASA | Nueve aserciones locales en Chromium: login, cookie, CSRF, navegación DOM y dashboard MySQL; sin CDN ni validación visual |
| Sonar, diseño visual/CDN/gráficas y despliegue | NO COMPROBADO | No ejecutados; el recorrido básico no acredita esas áreas |
| README, version, CHANGELOG y documentación técnica actualizados | PASA | Revisión 0.4.7 ampliada con originales conservados, variantes, bloqueos, arquitectura, inventario y contratos |
| Rama independiente y Pull Request revisable | PASA | Rama de auditoría; [PR #2 en borrador](https://github.com/djSaBaS/caldera-biomasa-domotica/pull/2) |
| Sin habilitar salidas físicas y sin avanzar al bloque 2 | PASA | `static_assert(SIMULATION_MODE)` y ausencia de escrituras GPIO |
| Bloque 1 completamente satisfecho | FALLA | Revisión instalada/ficha independiente, FSM y cadena física todavía sin resolver |

## Evidencia vinculada

[Fuentes y discrepancias](FUENTES_ORIGINALES.md) · [Parámetros](PARAMETROS_ORIGINALES.md) · [Estados y protecciones](MAQUINA_ESTADOS.md) · [Seguridad](SEGURIDAD.md) · [Pruebas](PRUEBAS.md) · [Contrato](../arquitectura/API_CONTRACT.md) · [Hardware](../arquitectura/HARDWARE.md) · [Migración SQL](../../server/sql/migrations.md).

## Qué falta para cambiar los criterios restantes

1. Identificar modelo de caldera, referencia/revisión/programa y receta de la centralita concreta. El propietario confirma un sinfín, pero aún no identifica esos datos; no se deducen del manual genérico.
2. Obtener SCHEDA independiente o una fuente técnica inequívoca de esa revisión; resolver A06, UA05, P03/P04 y SPE con evidencia aplicable. El esquema CTP ya está disponible, por lo que no se sigue solicitando el UTENTE.
3. Completar y verificar la FSM local con guardas/temporizadores de llama, entradas, SIC/SPE, antihielo, REC y V3 antes de que pueda sustituir a la centralita. La actual demostración temporizada se conserva claramente identificada y bloqueada para operación; sustituirla por reglas mezcladas de G crearía nuevos defectos.
4. Diseñar y probar TLS, edad real de sensores, watchdog, comandos con ACK e idempotencia y protección física. No hay autorización para conectar cargas; estas verificaciones necesitan un banco y un alcance posterior explícito, sin inferir autorización de la mera lectura de los manuales.
5. Completar Sonar, validación visual responsive/CDN/gráficas y despliegue cuando exista ese entorno. El navegador básico ya está probado; no equivale a esos controles.

La tabla no cambia a PASA por disponer de más documentos de una familia. Se ha completado el trabajo verificable de esta ampliación, sin declarar completado el bloque ni avanzar al bloque 2.

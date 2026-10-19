# Versión del proyecto

**Versión:** `0.4.7-auditoria-tecnica`
**Fecha:** 2026-10-19
**Base auditada:** `0.4.6-dashboard-protegido-mysql-ci`, commit `e97952921b2f552da1cbff4c8967d1f47a2c2fd9`.

Estado: **BLOQUE 1 INCOMPLETO. No avanzar al bloque 2.** Es una revisión de desarrollo y simulación; no valida una instalación ni habilita salidas físicas.

Revisiones independientes: contrato HTTP/UART 1.1; esquema lógico EEPROM 2 con migración de v1/v2 legado y diario CBJ1 de dos ranuras; versión de configuración uint32; migración SQL `0.4.7_auditoria.sql`. Los dos sketches y las entradas PHP publican la misma versión de producto. Los datos demo conservan sus versiones históricas ficticias.

Incluye correcciones de autenticación y permisos, CSRF de combustible/mantenimiento, identidad de dispositivo, raíz pública, validación de datos, contratos de comunicación, parser ESP32 con ArduinoJson 7.4.2, parser estricto Mega, perfiles de sinfín por fase, ventana MAN, enclavamiento de alarmas, migración EEPROM, diario con CRC32/commit final/lectura de verificación, caducidad de telemetría y pruebas de cortes en cada frontera de escritura. El [informe de aceptación](docs/auditoria/BLOQUE_1.md) documenta alcance, evidencia y bloqueos.

UTENTE y su esquema impreso 27/PDF 28 están disponibles y contrastados; los adjuntos se conservan con hashes. El propietario confirma un sinfín instalado y placa capaz de dos: el canal adicional es opcional. Siguen pendientes SCHEDA_SY325.pdf independiente, revisión/receta instalada, guardas originales completas, sensores/cargas físicos, TLS, comandos seguros, watchdog y ensayos físicos de recuperación y protección. Son dependencias críticas, no elementos terminados por disponer de enumeraciones o datos simulados.

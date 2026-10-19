# Versión del proyecto

**Versión:** `0.4.7-auditoria-tecnica`
**Fecha:** 2026-10-19
**Base auditada:** `0.4.6-dashboard-protegido-mysql-ci`, commit `e97952921b2f552da1cbff4c8967d1f47a2c2fd9`.

Estado: **BLOQUE 1 INCOMPLETO. No avanzar al bloque 2.** Es una revisión de desarrollo y simulación; no valida una instalación ni habilita salidas físicas.

Revisiones independientes: contrato HTTP/UART 1.1; esquema EEPROM 2 con migración de v1; versión de configuración uint32; migración SQL `0.4.7_auditoria.sql`. Los dos sketches y las entradas PHP publican la misma versión de producto. Los datos demo conservan sus versiones históricas ficticias.

Incluye correcciones de autenticación y permisos, CSRF de combustible/mantenimiento, identidad de dispositivo, raíz pública, validación de datos, contratos de comunicación, parser ESP32 con ArduinoJson 7.4.2, parser estricto Mega, perfiles de sinfín por fase, ventana MAN, enclavamiento de alarmas, migración EEPROM y pruebas de regresión. El [informe de aceptación](docs/auditoria/BLOQUE_1.md) documenta alcance, evidencia y bloqueos.

Faltan UTENTE y SY325 o su reproducción contrastada, la máquina original con todas sus guardas, identificación real de centralita/sensores/cargas, sinfín 2, TLS, ejecución segura de comandos, watchdog y ensayos físicos de recuperación y protección. Son dependencias críticas, no elementos terminados por disponer de enumeraciones o datos simulados.

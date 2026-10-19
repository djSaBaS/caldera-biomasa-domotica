# Resultados de pruebas del bloque 1

Fecha: 2026-10-19. Código de la rama `auditoria/bloque-1-base-tecnica`, revisión 0.4.7. Solo servicios locales y simulación, sin firmware cargado en placas.

| Comprobación | Resultado | Evidencia y alcance |
| --- | --- | --- |
| Línea base 0.4.6: puerta de calidad existente | PASA | 13 unitarias, lint PHP/JS y validaciones estáticas; no detectaba los defectos de comportamiento encontrados |
| Puerta de calidad ampliada | PASA | Sintaxis PHP/JS/Bash, 16 unitarias, seed demo, contratos/diagramas y regresiones nativas |
| Modelo/parser HTTP y UART reales | PASA | 42 comprobaciones: exportador PHP→ESP32→Mega, versión 70000, campos ausentes/duplicados, overflow, fracciones, destino/origen y ventanas asimétricas/MAN |
| Sketch Mega real con E/S simulada | PASA | 20 comprobaciones: aplicación/rechazo, versión reutilizada, EEPROM sin red/corrupta/v1, línea excesiva, ACK sin candidato, NaN, enclavamiento, bomba y fases MAN |
| Integración PDO con base desechable | PASA | 8 aserciones sobre conexión, login, rol, dashboard, eventos, mantenimiento y combustible; esquema/seed completos |
| Migración SQL real en tablas temporales | PASA | Conserva CHECK histórico y versión 70000; admite CHC/REC y añade doce columnas NULL |
| Smoke HTTP existente | PASA | Claves, configuración, telemetría, endpoints protegidos y solicitud de reset |
| HTTP autenticado existente | PASA | Login/cookie, auth_me, roles, dashboard MySQL, usuarios/dispositivos, STOP registrado y CSRF |
| Regresiones HTTP de auditoría | PASA | 43 peticiones con MySQL: UID cruzado, tipos, límites, CHC/REC, telemetry_id, ACK, origen de eventos, permisos/CSRF, fechas/ids, migración de rol y desactivación de cookie vigente |
| Compilación Mega | PASA | Arduino CLI/AVR fijados; 12874 bytes de flash y 1644 bytes de RAM global (de 8192); sin upload ni salida física |
| Compilación ESP32 | PASA | Core/ArduinoJson fijados; 350692 bytes de flash y 28428 bytes de RAM global; sin WiFi real ni comandos |
| Git diff --check | PASA | Sin errores de whitespace |
| LeakSanitizer | NO COMPROBADO | El host instrumentado usa ptrace; LSan falló por incompatibilidad del entorno y se desactivó en el runner nativo. AddressSanitizer y UndefinedBehaviorSanitizer siguen activos |
| Sonar, navegador funcional, MySQL 8.4 y Actions remotos | NO COMPROBADO | No se equiparan sintaxis/compilación/MariaDB con estas comprobaciones; consultar estado remoto de la PR |
| Hardware, sensores, carga real, corte de energía, watchdog y combustión | NO COMPROBADO | Fuera de la simulación autorizada; siguen siendo bloqueos críticos |

Entorno local: PHP 8.4.26 con PDO MySQL, MariaDB 11.8.6, Node 24.19.0, g++ 14.2.0; Arduino CLI 1.5.1, Arduino AVR 1.8.8, ESP32 3.3.12 y ArduinoJson 7.4.2. CI declara PHP 8.2, Node 22 y MySQL 8.4, por lo que su ejecución debe comprobar compatibilidad adicional.

Las pruebas nativas compilan `ControlModel.h`, `ConfigJson.h` y el sketch Mega de producción con stubs de UART/reloj/EEPROM. No copian algoritmos de control en una implementación de prueba distinta. Se ejecutan con `-Wall -Wextra -Werror -fsanitize=address,undefined`. La migración de EEPROM verifica un layout legado de 20 bytes; no demuestra tolerancia de una escritura física interrumpida.

Los scripts HTTP crean claves y cuentas ficticias exclusivas de pruebas. Los datos de auditoría se retiran en finally. `mysql_integration.php` **recrea** la base y exige nombre terminado en `_ci`; nunca usar un nombre productivo. La prueba de migración usa tablas temporales con nombres propios y verifica las diferencias relevantes del esquema anterior, sin alterar tablas permanentes.

Reproducción con herramientas y configuración de CI preparadas:

```bash
arduino-cli lib install ArduinoJson@7.4.2
tests/backend/run_quality_checks.sh
DB_NAME=caldera_biomasa_ci php tests/backend/mysql_integration.php
DB_NAME=caldera_biomasa_ci php tests/backend/migration_integration.php
tests/firmware/compile_firmware.sh
```

Para compilar sin descargas después de verificar las versiones instaladas: `ARDUINO_SKIP_INSTALL=1 tests/firmware/compile_firmware.sh`. Si ArduinoJson está fuera del directorio estándar, configurar `ARDUINOJSON_INCLUDE` para el runner nativo. Para HTTP, servir la API con las mismas variables DB de la base `_ci` en otra terminal y una clave ficticia de prueba:

```bash
DB_NAME=caldera_biomasa_ci DEVICE_API_KEY=clave-local-pruebas-hardening-no-productiva-123456 php -S 127.0.0.1:8092 -t server/public server/public/router.php
```

```bash
API_BASE_URL=http://127.0.0.1:8092/api tests/backend/smoke_api.sh
API_BASE_URL=http://127.0.0.1:8092/api tests/backend/authenticated_http_api.sh
API_BASE_URL=http://127.0.0.1:8092/api DB_NAME=caldera_biomasa_ci python3 tests/backend/audit_http_regressions.py
```

Durante la auditoría se probó el servidor con la antigua bandera REMOTE_START_ALLOWED=true para confirmar que START continuaba bloqueado. El ID de telemetría a cero fue detectado por una aserción nueva, corregido y verificado en el recorrido final. La integración CLI se corrigió para retener salida durante operaciones de sesión; no se observaron avisos PHP en el recorrido final.

Documentación del parser consultada mediante la habilidad de documentación: [deserializeJson, ArduinoJson 7](https://arduinojson.org/v7/api/json/deserializejson/). No se ejecutó el flujo automatizado Codex Security porque no fueron accesibles sus referencias internas; la revisión de seguridad aquí es directa con pruebas de regresión.

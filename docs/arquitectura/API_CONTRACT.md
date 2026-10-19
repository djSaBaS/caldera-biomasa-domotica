# Contrato HTTP y UART 1.1

Producto: `0.4.7-auditoria-tecnica`. La revisión de protocolo `1.1`, la versión de configuración `config_version` y el esquema EEPROM `2` son identificadores diferentes. No expresan validación para controlar hardware. [Informe y límites](../auditoria/BLOQUE_1.md).

## HTTP: sobre y transporte

```json
{"success":true,"data":{},"error":null,"meta":{"protocol_version":"1.1"}}
```

Los errores contienen `error.code`, `error.message` en español y `error.details`. Métodos no permitidos: 405; falta de identidad: 401; rol/CSRF/START: 403; validación: 422; cuerpo excesivo: 413; límite de peticiones: 429; persistencia/identidad no verificable: 503; excepción interna: 500 genérico. Las respuestas de API llevan `Cache-Control: no-store` y `nosniff`. El router público emite 404 para cualquier archivo ajeno a `/api/[a-z_]+.php` o `/`.

Cuerpo de entrada: objeto JSON, ≤16384 bytes, profundidad ≤32. Campos de texto no aceptan arrays ni truncamiento. Secretos se conservan íntegros, con límite de 72 bytes compatible con bcrypt; API key de dispositivo: 24–72 bytes y sin placeholders públicos. No se deben compartir ni almacenar secretos en Git. HTTP sin TLS solo sirve para desarrollo local. El modo de operación ESP32 permanece bloqueado mientras no se verifique HTTPS y confianza de CA.

| API | Método | Identidad y permisos | Persistencia / comportamiento |
| --- | --- | --- | --- |
| index | GET | Pública | Versión y disponibilidad DB; sin datos de configuración |
| auth_login | POST | `usuario`, `contrasena` | Usuario activo en MySQL; renueva SID y CSRF |
| auth_me | GET | Sesión vigente | Revalida usuario y rol en DB |
| auth_logout | POST | Sesión y CSRF | Destruye sesión/cookie |
| password_reset_request | POST | Pública; `email` válido y límite de peticiones | Mensaje uniforme; prepara token hasheado, sin envío ni consumo implementados |
| csrf_token | GET | Sesión vigente | Token aleatorio de sesión |
| users | GET / POST | Administrador; POST con CSRF | CRUD inicial por servicios; hashes nunca se devuelven |
| devices | GET / POST | GET con sesión; POST administrador y CSRF | API key inicial solo como hash; UID estable |
| dashboard | GET | Sesión; cualquiera de los cuatro roles | Snapshot con `source`, `telemetry_status` y `sample_age_seconds`; demo o muestra registrada no equivale a lectura física en directo |
| fuel | GET / POST | GET con sesión; POST administrador/operador y CSRF | POST requiere DB; tipos pellet/hueso_aceituna/otro y fecha real YYYY-MM-DD |
| maintenance | GET / POST | GET con sesión; POST administrador/operador/mantenimiento y CSRF | POST requiere DB y dispositivo; tipos limpieza/revision/pieza/reparacion |
| command_request | POST | Administrador/operador/mantenimiento y CSRF | Inserción e historial atómicos con expiración de 5 min; START siempre bloqueado |
| telemetry | POST | Clave vinculada al `device_id` del cuerpo | Muestra validada; 202 y `persistence=stored` o `skipped` |
| config | GET | Clave y `device_id` en query | `data.config`, catálogo salvo `view=firmware`; origen database/fallback_safe |
| config_ack | POST | Clave y `device_id` del cuerpo | Evento con versión candidata y alcance; 202 no garantiza persistencia, comprobar `meta.persistence` |
| command | GET | Clave y `device_id` en query | Consulta no consume la cola; `execution_supported=false`; no hay ACK de ejecución |
| events | POST | Siempre clave del dispositivo y UID del cuerpo | Tipos estado/configuracion/orden/comunicacion/sensor/mantenimiento/sistema; severidad info/aviso/error/critico |

Sesiones: cookie HttpOnly, SameSite Strict, Secure si PHP reconoce HTTPS; modo estricto, solo cookies e inactividad máxima 30 min. Permisos globales de instalación, sin ACL por dispositivo. Roles: administrador, operador, solo_lectura y mantenimiento. En `APP_ENV=production` no se acepta la clave global `DEVICE_API_KEY`. En desarrollo sigue admitida por compatibilidad. Las claves de DB requieren dispositivo activo. En POST, un UID de query distinto del cuerpo se rechaza antes de autenticar o escribir.

Límites: login 5 por usuario/IP/300 s más 60/IP/300 s; reset 3 por email/IP/900 s más 30/IP/900 s; dispositivo 120 por clave/IP/60 s más 240/IP/60 s. Pérdida de almacenamiento del limitador: 503.

## Telemetría

```json
{"device_id":"caldera-01","protocol_version":"1.1","firmware_arduino":"0.4.7-auditoria-tecnica","simulation":true,"state":"CHC","water_temp":72.5,"smoke_temp":210.0,"fuel_level":78,"auger":false,"igniter":false,"pump":true,"fan_primary_pct":50,"fan_secondary_pct":50,"config_version":70000,"config_source":"eeprom"}
```

Estados canónicos: OFF, CHC, ACC, STB, REC, NORMAL, MOD, MAN, SIC, SPE, ALT. Entrada histórica CHECK se normaliza a CHC; el ENUM conserva CHECK para no destruir históricos. Sin `protocol_version` se admite telemetría legada; versiones explícitas ajenas a 1.1 se rechazan.

Agua y humos: °C; límites **de transporte del proyecto** −40…125 y −40…600, no consignas originales ni validación de sondas. Nivel/señal: 0…100 %. Ventiladores: enteros 0…100. Salidas: booleanos JSON o 0/1 legado; la cadena `"false"` se rechaza. Modo: manual/automatico/mantenimiento/seguridad. Campos de medida ausentes pueden ser null; no se inventa un cero térmico. Se admiten `temp_water`, `temp_smoke`, `pellet_level` y `outputs` anidado legados; duplicados con valores contradictorios se rechazan. `payload_json` conserva metadatos; no todos tienen columna SQL independiente.

El ESP32 valida objeto y UID de TEL, conserva una sola muestra y descarta definitivamente la pendiente si supera 15000 ms desde su recepción UART (límite del proyecto). Tras POST 2xx con persistencia confirmada no vuelve a publicar esa muestra. Solo hay demo antes de la primera recepción y únicamente en simulación. La edad local limita el reenvío, pero no mide la edad de adquisición del sensor; siguen pendientes secuencia/timestamp de adquisición, alarma de enlace e idempotencia ante respuesta HTTP perdida.

## Configuración: correspondencia exacta

`GET /api/config.php?device_id=caldera-01&view=firmware` devuelve el subconjunto sin catálogo para respetar 4096 bytes de ESP32. El puente exige sobre correcto, protocolo 1.1, origen **database**, destino coincidente y todos los campos numéricos enteros. Rechaza fallback y no reemplaza una EEPROM válida por defaults del servidor. El catálogo completo sigue disponible sin `view=firmware`.

| HTTP/MySQL | UART | Unidad y dominio del simulador |
| --- | --- | --- |
| config_version | version | 1…4294967295; entero uint32 monotónico |
| auger_cycle_seconds | auger | 2…120 s; preferencia simétrica legada |
| fan_primary_pct / fan_secondary_pct | fan1 / fan2 | 0…100 %; no es la escala física original validada |
| pump_on_temp | pump | 35…75 °C enteros |
| target_temp | target | 55…85 °C enteros |
| maintenance_temp | maintenance | 60…88 °C enteros |
| safety_temp | safety | 75…95 °C enteros |
| telemetry_interval_seconds | telemetry | 5…300 s en Mega |
| config_poll_interval_seconds | poll | 10…600 s, consulta de configuración ESP32 |
| notifications_enabled | notifications | 0/1, solo almacenamiento; sin envío de alertas implementado |
| acc_on_seconds / acc_off_seconds | acc_on / acc_off | 0…300 s, CL00/CP00 |
| stb_on_seconds / stb_off_seconds | stb_on / stb_off | 0…300 s, CL04/CP04 |
| normal_on_seconds / normal_off_seconds | normal_on / normal_off | 0…300 s, LED1/CP05 |
| mod_on_seconds / mod_off_seconds | mod_on / mod_off | 0…300 s, CL07/CP07 |
| man_on_seconds / man_off_seconds | man_on / man_off | 0…300 s, CL09/CP09 |
| man_pause_minutes | man_pause | 1…300 **min**, T04 |
| man_work_seconds | man_work | 0…900 **s**, T05; además T05=CP09+CL09 |

El backend y Mega exigen `pump < target ≤ maintenance < safety`. Son límites de coherencia del simulador. La tabla original de fabricante está separada en [parámetros](../auditoria/PARAMETROS_ORIGINALES.md). La igualdad T05=CP09+CL09 y los límites por par reducen a 600 s la ventana representable por esta implementación de un solo ciclo.

Los pares NULL de ACC/STB/NORMAL/MOD se expanden al valor legado `auger_cycle_seconds` en ambos tiempos. Un par parcialmente informado se rechaza. MAN legado queda CL09=CP09=T05=0 y T04=1 min hasta configuración explícita: corrige la alimentación continua errónea. ON=0 implica sin alimentación; OFF=0 con ON>0 expresa alimentación continua durante la fase/ventana simulada. Ninguna de estas opciones habilita hardware.

`mode`, `startup_timeout_seconds` y `post_ventilation_seconds` siguen almacenados/devueltos en HTTP pero **no son aplicados por Mega**. Los ventiladores aún usan porcentajes generales, no UC/UA por fase. Las recetas y sinfín 2 no están implementados. El ACK usa alcance `control_subset`; no confirma la configuración completa de combustión.

## UART y EEPROM

Serial1 Mega ↔ Serial2 ESP32, 115200 baudios. TEL y ACK son JSON prefijado; CFG es clave=entero separado por `;`; cada trama termina en LF. No hay CRC de enlace ni autenticación UART. Línea Mega ≤512 caracteres (cuerpo ≤508); ESP32 ≤700. Exceso de longitud descarta hasta LF, sin reinterpretar un sufijo como trama nueva.

```text
CFG:version=70000;auger=10;fan1=50;fan2=50;pump=60;target=75;maintenance=80;safety=90;telemetry=10;poll=30;notifications=1;acc_on=3;acc_off=7;stb_on=10;stb_off=10;normal_on=10;normal_off=10;mod_on=10;mod_off=10;man_on=4;man_off=6;man_pause=2;man_work=10
```

Se exigen los once campos básicos; se admite el formato legado sin ninguno de los doce campos de fase. Si aparece uno de fase, deben aparecer todos. Se rechazan claves desconocidas/repetidas, ausencia de campo básico, signos, fracciones, sufijos, desbordamientos y separador final. La aplicación es atómica en RAM: un rechazo no modifica la configuración anterior. Las versiones inferiores o iguales con contenido diferente se rechazan. Repetir contenido idéntico comprueba persistencia y, si ya existe copia válida, confirma sin nuevas escrituras; hay reenvío periódico para recuperar pérdidas o reinicio del receptor. Cambiar parámetros requiere aumentar config_version. El contador no se reinicia al perder internet.

EEPROM: esquema lógico 2 con pares por fase y versión uint32; almacenamiento nuevo CBJ1 en dos ranuras de 128 bytes desde dirección 128. Registro de 105 bytes: firma de 4 bytes, generación uint32 LE, 23 campos uint32 LE, CRC32 IEEE de los primeros 100 bytes y marcador final 0xA5. Se escribe ranura inactiva tras invalidarla y se publica el marcador al final; lectura idéntica obligatoria antes de activar y confirmar. Una copia corrupta/interrumpida permite recuperar la otra. La generación usa comparación modular; config_version conserva rechazo de retroceso y no se reinicia con la red. Se mantiene intacto el área legada de dirección cero; se migra v1 AVR de 20 bytes o v2 antiguo con checksum 0xCA1D si valida. MAN legado queda deshabilitado. Sin ninguna copia/legado válido se usan defaults explícitos de simulación. Las regresiones inyectan cortes en todas las fronteras y corrupción de cada byte; no acreditan comportamiento eléctrico de EEPROM real.

```json
{"device_id":"caldera-01","status":"rechazada","config_version":69999,"active_config_version":70000,"scope":"control_subset","protocol_version":"1.1","message":"version_obsoleta_o_reutilizada"}
```

ACK negativo sin candidato numérico válido informa config_version=0. La versión activa se comunica por separado. PHP registra versión candidata y alcance en el evento; no modifica `pending_apply` ni declara aplicada toda la configuración. ESP32 conserva el ACK mientras no haya recepción JSON con `success=true` y `meta.persistence=stored`; los intentos se espacian al menos cinco segundos; solo hay un slot pendiente en RAM, sin idempotencia durable. Es un límite conocido.

## Comandos y recuperación de infraestructura

La cola conserva START/STOP/RESET_ALARM/ENTER_MAINTENANCE/EXIT_MAINTENANCE por compatibilidad de esquema. START devuelve 403 incondicionalmente en esta revisión, aunque exista la antigua bandera REMOTE_START_ALLOWED. Los demás pueden registrarse, pero `execution_supported=false`: no se ejecutan ni confirman en el firmware actual. GET no los consume; los expirados dejan de listarse, sin tarea que actualice automáticamente su estado. Falta diseñar ACK, deduplicación y reglas locales por comando.

La pérdida de DB impide autenticar sesiones y crear órdenes/altas administrativas; config puede ofrecer fallback explícito que ESP32 rechaza. Un 202 de telemetría/ACK con `skipped` confirma validación/recepción, no almacenamiento. El controlador local solo usa su configuración validada; esto no demuestra continuidad segura de una caldera real.

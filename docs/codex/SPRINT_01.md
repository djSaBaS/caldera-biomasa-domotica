# Sprint 01 — Base funcional segura

## Objetivo

Preparar el repositorio para desarrollo real con una base mínima, coherente y segura para backend PHP, base de datos MySQL, panel Bootstrap mobile-first y firmware Arduino/ESP32 en modo simulado.

## Alcance

- Documentación técnica suficiente para futuros agentes y desarrolladores.
- Esquema SQL inicial con tablas principales.
- API PHP inicial sin framework pesado.
- Panel web inicial navegable con datos simulados.
- Firmware Arduino Mega con máquina de estados simulada.
- Firmware ESP32 con conectividad HTTP simulada.
- Sin activación física de cargas de 230V.

## Orden de ejecución recomendado

1. Revisar reglas de seguridad y lógica original de caldera.
2. Crear o actualizar esquema SQL.
3. Crear núcleo PHP común.
4. Crear endpoints API mínimos.
5. Crear interfaz visual Bootstrap.
6. Crear firmware Arduino Mega en simulación.
7. Crear firmware ESP32 en simulación.
8. Actualizar README, versión y CHANGELOG.
9. Ejecutar pruebas estáticas mínimas.

## Tareas

### Documentación

- Actualizar README principal.
- Actualizar contrato API.
- Actualizar arquitectura general.
- Mantener decisiones técnicas registradas.
- Documentar advertencias de seguridad.

### Base de datos

- Crear tablas para usuarios, roles, dispositivos, telemetría, configuración, comandos, eventos, alarmas, combustible, mantenimiento y notificaciones.
- Usar InnoDB y utf8mb4.
- Indexar campos de dispositivo, fechas, estados y severidades.

### Backend PHP

- Crear clase `Database` con PDO.
- Crear helper de respuestas JSON.
- Crear helper de petición HTTP.
- Crear validador de API key.
- Crear endpoints de telemetría, configuración, comandos, ACK, eventos, combustible y mantenimiento.

### Frontend

- Crear panel Bootstrap 5 mobile-first.
- Añadir offcanvas móvil y sidebar escritorio.
- Añadir tarjetas KPI y gráficas Chart.js.
- Crear secciones iniciales: dashboard, estado, usuarios, programación, configuración, logs, combustible, mantenimiento, notificaciones y ajustes.

### Firmware Arduino Mega

- Mantener `SIMULATION_MODE = true`.
- Crear sensores y salidas simuladas.
- Implementar máquina de estados inicial.
- Implementar ciclo de sinfín ON = OFF.
- Evaluar seguridad antes de aplicar salidas.
- Enviar telemetría simulada por serie.

### Firmware ESP32

- Mantener `SIMULATION_MODE = true`.
- Usar placeholders de WiFi y API key.
- Simular envío HTTP y consulta de configuración.
- Actuar solo como puente, no como controlador crítico.

## Criterios de aceptación

- La estructura del repositorio queda ordenada.
- El README describe cómo empezar.
- El SQL puede importarse en MySQL 8 o MariaDB moderno.
- Los endpoints PHP tienen respuestas JSON consistentes.
- El panel web carga sin dependencias locales pesadas.
- El firmware no activa salidas reales por defecto.
- No hay credenciales reales versionadas.
- Las reglas críticas del sinfín quedan documentadas y reflejadas.

## Pruebas mínimas

- Ejecutar `php -l` sobre todos los archivos PHP creados o modificados.
- Revisar visualmente `web/index.html` en móvil y escritorio.
- Revisar que `schema.sql` no contiene secretos.
- Revisar que los firmware mantienen `SIMULATION_MODE = true`.
- Probar endpoints con `curl` cuando haya servidor PHP local.

## Riesgos

- La lógica original de combustión puede requerir ajustes al contrastarla con los manuales.
- Los sensores reales pueden tener ruido, fallos o tiempos de respuesta diferentes a la simulación.
- La activación de cargas de 230V exige instalación eléctrica profesional.
- La autenticación completa necesita endurecimiento adicional antes de exponer el sistema.
- WhatsApp y Telegram requerirán servicios externos y gestión segura de tokens.

## Lo que NO se debe hacer todavía

- No controlar relés reales de 230V.
- No conectar el sistema directamente a internet.
- No sustituir la centralita original sin pruebas de banco prolongadas.
- No implementar PID directo sobre el sinfín.
- No guardar contraseñas en claro.
- No usar credenciales reales en firmware ni backend.
- No permitir encendido remoto si existe alarma o sensor crítico fallando.

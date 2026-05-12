# Arquitectura backend

## Tecnología

- PHP.
- MySQL.
- API REST.
- Panel web separado o integrado.

## Carpetas

```text
server/api/            Endpoints consumidos por ESP32 y panel web.
server/app/Config/     Configuración.
server/app/Core/       Núcleo común.
server/app/Controllers Controladores futuros.
server/app/Models/     Modelos futuros.
server/app/Services/   Servicios de negocio.
server/public/         Entrada pública web/API.
server/sql/            Esquema SQL y migraciones.
server/storage/logs/   Logs locales.
```

## Funcionalidades previstas

- Registrar telemetría.
- Servir configuración.
- Servir comandos.
- Registrar eventos.
- Registrar alarmas.
- Enviar notificaciones.
- Gestionar usuarios y roles.

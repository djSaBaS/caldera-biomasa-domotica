# Arquitectura backend

## Tecnología

- PHP 8+.
- MySQL/MariaDB.
- PDO con consultas preparadas.
- API JSON sin framework pesado.
- Sesiones PHP para autenticación web inicial.

## Carpetas

```text
server/api/            Endpoints consumidos por ESP32 y panel web.
server/app/Config/     Configuración por variables de entorno.
server/app/Core/       Núcleo común: JSON, request, validación, PDO y API key.
server/app/Controllers Controladores futuros si la API crece.
server/app/Models/     Modelos futuros si se adopta mapeo más formal.
server/app/Services/   Servicios y repositorios de negocio.
server/public/         Entrada pública web/API.
server/sql/            Esquema SQL, migraciones futuras y seed de desarrollo.
server/storage/logs/   Logs locales.
```

## Núcleo común

- `JsonResponse`: formato uniforme `success`, `data`, `error`, `meta`.
- `Request`: lectura segura de método, JSON, query string y cabeceras.
- `Validation`: validaciones reutilizables de texto, catálogo, números y fechas.
- `Database`: conexión PDO y modo degradado con `tryConnection()`.
- `ApiKeyValidator`: validación por variable de entorno o `devices.api_key_hash`.

## Servicios y repositorios

- `AuthService`: login, sesión, logout y solicitud de restablecimiento.
- `DeviceRepository`: resolución de dispositivos y última comunicación.
- `TelemetryRepository`: persistencia de telemetría.
- `BoilerConfigRepository`: lectura de configuración activa.
- `CommandRepository`: lectura y marcado de comandos pendientes.
- `EventRepository`: persistencia de eventos.
- `FuelRepository`: compras y resumen de combustible.
- `MaintenanceRepository`: registros de mantenimiento.

## Modo degradado seguro

Si MySQL no está disponible, los endpoints no deben romper el panel ni el firmware durante desarrollo. En ese caso responden con metadatos como:

```json
{
  "meta": {
    "persistence": "skipped"
  }
}
```

Este modo no sustituye a producción. Solo permite continuar pruebas visuales y de firmware simulado.

## Funcionalidades previstas para Sprint 03

- Autorización por rol en cada endpoint.
- CSRF en formularios autenticados.
- Gestión real de usuarios desde panel.
- Migraciones versionadas.
- Tests automatizados con base de datos efímera.
- Auditoría completa de cambios críticos.

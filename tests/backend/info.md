# Pruebas backend

Esta carpeta contiene pruebas de humo y pruebas futuras del backend PHP.

## Prueba disponible

```bash
tests/backend/smoke_api.sh
```

La prueba requiere un servidor PHP levantado, por ejemplo:

```bash
php -S localhost:8081 -t server
```

La variable `API_BASE_URL` permite apuntar a otro puerto durante CI o pruebas locales.

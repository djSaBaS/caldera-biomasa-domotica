# Pruebas backend

Esta carpeta contiene pruebas de humo y pruebas futuras del backend PHP.

## Prueba disponible

```bash
tests/backend/smoke_api.sh
```

La prueba requiere un servidor PHP levantado con una `DEVICE_API_KEY` explícita, larga y distinta de los placeholders documentales:

```bash
DEVICE_API_KEY='clave-local-pruebas-hardening-no-productiva-123456' php -S localhost:8081 -t server
```

En otra terminal se puede ejecutar:

```bash
API_BASE_URL=http://localhost:8081/api DEVICE_API_KEY='clave-local-pruebas-hardening-no-productiva-123456' tests/backend/smoke_api.sh
```

La variable `API_BASE_URL` permite apuntar a otro puerto durante CI o pruebas locales.

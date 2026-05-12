# Reglas de seguridad

## Seguridad eléctrica

- No mezclar baja tensión y 230V en protoboard.
- Usar fusibles adecuados.
- Usar diferencial y magnetotérmico adecuados.
- Usar cajas cerradas.
- Usar bornas, punteras y cableado adecuado.
- Evitar conexiones improvisadas en instalación final.

## Seguridad funcional

- Si falla una sonda crítica, pasar a estado seguro.
- Si se detecta sobretemperatura, parar combustible.
- Si se abre puerta, parar combustible y ventiladores según lógica segura.
- Si falla el encendido, cortar bujía y combustible.
- Si no hay comunicación con servidor, continuar con configuración local segura.
- Si llega una configuración fuera de rango, rechazarla.
- Si hay alarma activa, no aceptar START remoto salvo reset autorizado y condiciones seguras.

## Seguridad remota

- No exponer Arduino ni ESP32 directamente a internet.
- Usar API con clave.
- Usar HTTPS en producción.
- Registrar cambios de configuración.
- Registrar comandos remotos.
- Limitar roles de usuario.

## Modo respaldo

Debe existir la posibilidad de volver al sistema original de la caldera.

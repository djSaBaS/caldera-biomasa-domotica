# Decisiones técnicas

## 0001 — Monorepo

Se decide usar un único repositorio porque firmware, backend y web están fuertemente relacionados.

## 0002 — Arduino Mega como controlador crítico

Arduino Mega será responsable del control local crítico por número de pines y simplicidad.

## 0003 — ESP32 como conectividad

ESP32 actuará como puente WiFi para evitar que el control crítico dependa de la red.

## 0004 — PHP + MySQL

Se usa PHP + MySQL porque es el stack dominado por el desarrollador principal.

## 0005 — Sinfín temporizado

El sinfín no se controla con PID directo. Se respeta ciclo ON/OFF con tiempos iguales.

## 0006 — Autenticación PHP con sesiones en Sprint 02

Se decide usar sesiones PHP nativas para el panel web inicial porque encajan con el stack del proyecto y evitan introducir un framework pesado.

## 0007 — Persistencia con modo degradado seguro

Se decide que los endpoints intenten persistir en MySQL, pero respondan de forma segura si la base no está disponible durante desarrollo. Este comportamiento permite pruebas de firmware y panel sin ocultar en `meta` que la persistencia no se realizó.

## 0008 — API key por entorno o hash de dispositivo

Se decide mantener `DEVICE_API_KEY` para desarrollo rápido y añadir validación contra `devices.api_key_hash` para preparar despliegues con claves por dispositivo sin guardar secretos en claro.

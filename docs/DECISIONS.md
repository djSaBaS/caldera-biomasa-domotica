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

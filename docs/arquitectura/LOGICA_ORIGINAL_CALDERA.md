# Lógica original de la caldera

Este documento resume la lógica funcional que debe respetarse a partir de los manuales de la caldera Pasqualicchio / CS Marina.

## Estados funcionales

### OFF

Caldera apagada.

### CHECK / Chc

Fase inicial de chequeo y limpieza. Puede funcionar el ventilador para limpiar cámara.

### ACC

Fase de encendido. Intervienen encendedor/bujía, ventilación y aporte controlado de combustible según parámetros.

### STB

Fase de estabilización de llama.

### NORMAL

Funcionamiento normal. La caldera trabaja con la potencia configurada para alcanzar temperatura.

### MOD

Modulación. Se reduce aporte de combustible y aire.

### MAN

Automantenimiento. La caldera se mantiene en espera, normalmente sin aporte continuo de combustible.

### SIC

Seguridad. Bloqueo o parada por condición peligrosa.

### SPE

Apagado controlado. Puede mantenerse ventilación y bomba durante el enfriamiento.

### ALT

Alarma. Estado asociado a una causa concreta.

## Regla crítica del sinfín

La carga de combustible se realiza por tiempo.

```text
Sinfín ON  = X segundos
Sinfín OFF = X segundos
```

La pausa debe ser siempre igual al tiempo de carga.

No se debe implementar un PID que active el sinfín directamente por temperatura. La temperatura puede condicionar el estado de la caldera, pero no debe convertir el sinfín en una salida proporcional directa.

## Parámetros configurables

- Tiempo de ciclo del sinfín.
- Porcentaje ventilador primario.
- Porcentaje ventilador secundario.
- Temperatura de activación de bomba.
- Temperatura objetivo / mantenimiento.
- Límites de seguridad.
- Timeouts de encendido.
- Tiempo de post-ventilación.

## Alarmas conocidas

- Fallo de encendido.
- Apagado accidental.
- Sobretemperatura.
- Sondas fuera de rango.
- Falta de combustible.
- Puerta abierta.
- Termostato de seguridad activado.

## Criterio de diseño

Si hay conflicto entre comodidad y seguridad, gana seguridad.

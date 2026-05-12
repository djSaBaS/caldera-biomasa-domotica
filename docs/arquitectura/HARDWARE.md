# Hardware

## Hardware disponible

- Elegoo Mega 2560 R3.
- Pantalla LCD1602.
- Módulo de 4 relés JQC-3FF-S-Z.
- 2 módulos Robotdyn AC Light Dimmer con zero-cross.
- ESP32.
- ESP32-CAM.
- Relé con módulo ESP32.
- Servos.
- Motor paso a paso con driver.
- Sensores varios pendientes de inventario final.

## Hardware recomendado adicional

- Segundo módulo de relés o relés/contactores adecuados para cargas de 230V.
- Termopar tipo K con módulo MAX31855 o MAX6675 para humos.
- DS18B20 encapsulado metálico para agua.
- Sensor de nivel de combustible, preferiblemente robusto para polvo.
- Fuente de alimentación 5V estable para lógica.
- Fuente separada si hay actuadores de 12V.
- Caja eléctrica con carril DIN, bornas, fusibles y prensaestopas.
- Selector físico ORIGINAL / DOMÓTICO.
- Setas de emergencia o corte manual si el montaje lo requiere.

## Actuadores previstos

- Motor del sinfín.
- Bujía incandescente.
- Ventilador primario.
- Ventilador secundario.
- Bomba de circulación.
- Servo de trampilla en fase futura.

## Sensores previstos

- Temperatura de agua.
- Temperatura de humos.
- Estado de puerta.
- Termostato de seguridad.
- Nivel de combustible.
- Temperatura ambiente opcional.

## Consideraciones eléctricas

La parte de 230V debe montarse con criterio eléctrico real. No se debe dejar en protoboard ni con conexiones Dupont.

Los módulos de relé de bajo coste pueden servir para pruebas, pero para instalación final conviene valorar contactores, SSR adecuados o relés industriales según carga real.

Los dimmers AC con zero-cross deben probarse primero con carga controlada, nunca directamente en la caldera sin banco de pruebas.

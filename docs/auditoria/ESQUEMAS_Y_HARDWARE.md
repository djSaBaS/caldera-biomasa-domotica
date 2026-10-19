# Esquemas originales y hardware declarado

La página **impresa 27 del UTENTE es PDF 28**; PDF 27 es la introducción al esquema. Se ha inspeccionado visualmente el diagrama completo y sus bornes. No se identifica explícitamente en él la revisión instalada ni se demuestra identidad con el archivo ausente SCHEDA_SY325.pdf.

El propietario confirma **una sola carga de sinfín instalada** y placa con capacidad para dos. Los bornes siguientes son referencias documentales de CTP, **no pines Arduino ni un plano verificado de conexión**. No se conecta ni habilita ninguna salida.

| Bornes CTP | Función dibujada | Alcance / diferencia frente a SY325 Pellet G |
| --- | --- | --- |
| 1–2 | Alimentación 230 V / 50 Hz | No es alimentación de lógica de Mega/ESP32 |
| 3–4 | V1, ventilador primario | Coincide el grupo funcional, no se acredita interfaz eléctrica |
| 5–6 | V2, ventilador secundario opcional | G lo describe como aspiración; no asumir idéntica función |
| 7–8 | THM, termostato de máxima | NC, rearme manual; no sustituible por un flag de software |
| 9–10 | M1, motor sinfín | G emplea salida hacia inverter; no equivale al mismo mando |
| 11–12 | M2 auxiliar | Leyenda CTP identifica agitador para CSB; **G usa encendedor aquí** |
| 13–14 | Contactor de resistencia del encendedor | **G usa bomba aquí** |
| 15–16 | Bomba de circulación | G representa conexión auxiliar al inverter; no trasladar bornes |
| 17–18 | Contactor de V3, ventilador del encendedor | V3 es distinto de V1/V2 y conserva enfriamiento tras parar resistencia |
| 19–20, 23–26 | Sin conexión descrita en este dibujo | No se atribuye disponibilidad ni función para esta placa |
| 21–22 | SF, sonda de humos | Termopar K; no imponer polaridad de otra revisión |
| 27–28 | SC, sonda de agua | NTC 10 kΩ; no es DS18B20 |
| 29–30 | Crono | Su semántica depende de P03 en CTP |
| 31–32 | Puerta | CTP detiene ventiladores; G permite aspiración máxima mediante P15 |
| 33–34 | Termostato ambiente | Su semántica depende de P04 en CTP |
| 39–40–41 | Sensor de nivel pellet | Dibujo muestra tres conductores; **G usa 42–43–44**. No inferir +12 V/GND/señal de G para estos bornes |
| 42–44 | Sin uso descrito en el esquema CTP | Su uso por G no acredita uso en CTP |

Fuente: [UT PDF 28 / impresa 27](../manuales/CTP_Manuale_Uso_CS_v1_2008_UTENTE.pdf#page=28). Comparación visual: [G PDF 12 / impresa 11](../manuales/4ad709.pdf#page=12).

| Elemento | Dato de la leyenda CTP | Estado de la evidencia |
| --- | --- | --- |
| SC | NTC 10 kΩ, 0–110 °C | Tecnología/rango documental, sin calibración ni identificación instalada |
| SF | Termopar K, 0–500 °C | No confundir rango del sensor con HI=901 de otro programa |
| THM | Normalmente cerrado, rearme manual, Tmax=100 °C | Valor de leyenda; protección física no ensayada |
| M | 370 W, 220 V, 50 Hz, 1370 rpm, cos φ 0,97, 3 A, 16 µF | Placa de características dibujada, no rating medido del motor del propietario |
| V1 | 73 W, 820 mA, 2550 rpm, 220 m³/h, 360 Pa | Leyenda documental, sin confirmar modelo instalado |
| V2/M2 | Secundario opcional hasta CS130 / agitador en CSB | No acredita segundo sinfín instalado |
| Encendedor por aire | Resistencia 230 V, 4,5 A, 1400 W; V3 120 W | Se transcriben datos impresos aunque W y A no resulten consistentes entre sí; verificar placa real |

UT 29/PDF 30 describe resistencia que cesa por criterio de humos y V3 que continúa unos tres minutos; IT 35 enumera T51 sin rango. UT 30/PDF 31 menciona válvula térmica opcional en conducto de combustible sobre 95 °C, distinta de THM y de la seguridad de agua A04. No se acredita su existencia instalada.

Pendientes antes de diseñar potencia: modelo de caldera, referencia/revisión y programa de centralita, esquema real de la variante, ratings de cargas, aislamiento y adaptación 5 V/3,3 V de UART, sondas/termostatos, hidráulica y protecciones independientes. Las propuestas de DS18B20/MAX6675/MAX31855 del inventario son alternativas de diseño que requieren validación; no sustituciones directas del circuito original.

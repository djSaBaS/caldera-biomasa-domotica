# Variante genérica SY325 Pellet Rev.1.3

Fuente **G**: [4ad709.pdf](../manuales/4ad709.pdf), aportada por el propietario. Referencias por página impresa; índice PDF = impresa + 1. **No acredita el programa instalado ni sustituye a CTP.** El cuerpo describe una sola salida de sinfín con inverter, extractor de cenizas y fotoresistencia opcional. La caldera del propietario tiene un solo sinfín, pero eso no demuestra que ejecute este programa.

## Extracción de parámetros del cuerpo del manual

Todas las filas siguientes están **documentadas para G**, con rangos y valores de defecto impresos. Ningún valor se ha comprobado en la instalación ni se envía al firmware. No aparecen CP, CL09, UC09, UA09, P03/P04/P34 o T08 equivalentes a las tablas CTP.

| Código | Función | Rango y unidad | Defecto impreso, no instalado | Estado o ámbito | Página |
| --- | --- | --- | --- | --- | --- |
| L00 | Umbral de llama apagada, foto | 0–100, unidad relativa | 5 | OFF/SPE | G 6 / PDF 7 |
| L01 | Umbral de llama encendida, foto | 0–100, unidad relativa | 15 | ACC/STB | G 6 / PDF 7 |
| F16 | Umbral humos apagada | 30–90 °C | 50 °C | OFF/SPE/pérdida de llama | G 6 / PDF 7 |
| F18 | Umbral humos encendida | 30–90 °C | 60 °C | ACC/STB | G 6 / PDF 7 |
| F22 | Umbral humos modulación | 50 °C–HI | HI | NORMAL/MOD | G 6 / PDF 7 |
| F24 | Umbral humos mantenimiento | 50 °C–HI | 350 °C | MAN | G 6 / PDF 7 |
| IF22 | Histéresis F22 | 1–20 °C | 10 °C | MOD | G 6 / PDF 7 |
| IF24 | Histéresis F24 | 1–20 °C | 10 °C | MAN | G 6 / PDF 7 |
| A01 | Activación bomba | 30–80 °C | 40 °C | Todos; excepciones de protección | G 6 / PDF 7 |
| A04 | Seguridad de agua | 85–97 °C | 90 °C | SIC | G 6 / PDF 7 |
| A05 | Diferencial de modulación | 0–15 °C | 5 °C | NORMAL/MOD | G 6 / PDF 7 |
| A12 | Mínimo de consigna | 50–70 °C | 65 °C | Menú | G 6 / PDF 7 |
| A13 | Máximo de consigna | 75–95 °C | 80 °C | Menú | G 6 / PDF 7 |
| UC00 | Velocidad de combustión | UC20–99 % | 99 % | ACC | G 6 / PDF 7 |
| UC04 | Velocidad de combustión | UC20–99 % | 99 % | STB | G 6 / PDF 7 |
| UC05 | Velocidad de combustión | UC20–99 % | 99 % | NORMAL | G 6 / PDF 7 |
| UC07 | Velocidad de combustión | UC20–99 % | 99 % | MOD | G 6 / PDF 7 |
| UC10 | Velocidad de combustión | UC20–99 % | 99 % | SPE | G 6 / PDF 7 |
| UC20 | Velocidad de combustión | 0–99 % | 99 % | Mínimo de su ventilador | G 6 / PDF 7 |
| UA00 | Velocidad de aspiración | UA20–99 % | 99 % | ACC | G 6 / PDF 7 |
| UA04 | Velocidad de aspiración | UA20–99 % | 99 % | STB | G 6 / PDF 7 |
| UA05 | Velocidad de aspiración | UA20–99 % | 99 % | NORMAL | G 7 / PDF 8 |
| UA07 | Velocidad de aspiración | UA20–99 % | 99 % | MOD | G 7 / PDF 8 |
| UA10 | Velocidad de aspiración | UA20–99 % | 99 % | SPE | G 7 / PDF 8 |
| UA20 | Velocidad de aspiración | 0–99 % | 99 % | Mínimo de su ventilador | G 7 / PDF 8 |
| CL00 | Trabajo sinfín | 0–C40 s | 99 s | ACC | G 7 / PDF 8 |
| CL04 | Trabajo sinfín | 0–C40 s | 30 s | STB | G 7 / PDF 8 |
| CL05 | Trabajo sinfín | 0–C40 s | 99 s | NORMAL | G 7 / PDF 8 |
| CL07 | Trabajo sinfín | 0–C40 s | 80 s | MOD | G 7 / PDF 8 |
| C40 | Periodo total trabajo + pausa | 5–99 s | 99 s | ACC/STB/NORMAL/MOD | G 7 / PDF 8 |
| T01 | Primera fase de encendido | 0–10 min | 5 min | ACC fija | G 7 / PDF 8 |
| T02 | Segunda fase de encendido | 1–99 min | 10 min | ACC variable | G 7 / PDF 8 |
| T03 | Estabilización | 0–30 min | 5 min | STB | G 7 / PDF 8 |
| T04 | Pausa de mantenimiento | 1–99 min | 90 min | MAN espera | G 7 / PDF 8 |
| T05 | Trabajo de mantenimiento | 0–99 s | 90 s | MAN activa | G 7 / PDF 8 |
| T06 | Espera antes del apagado por pérdida de llama | 1–300 s | 120 s | NORMAL/MOD→SPE | G 7 / PDF 8 |
| T09 | Limpieza final | 0–900 s | 10 s | SPE | G 7 / PDF 8 |
| T14 | Retardo parada aspiración en MAN | 0–900 s | 0 s | MAN | G 7 / PDF 8 |
| T16 | Apagado por falta de pellet | 0–900 s | 10 s | Protección nivel | G 7 / PDF 8 |
| T24 | Tiempo extractor cenizas OFF | 1–300 min | 60 min | Cenizas | G 7 / PDF 8 |
| T25 | Tiempo extractor cenizas ON | 0–900 s | 120 s | Cenizas | G 7 / PDF 8 |
| P02 | Intentos de encendido | 1–5 | 2 | ACC/reintentos | G 7 / PDF 8 |
| P08 | Habilitar combustión residual en apagado | 0–1 | 0 | SPE | G 7 / PDF 8 |
| P15 | Aspiración máxima con puerta abierta | 0–1 | 1 | Puerta | G 7 / PDF 8 |
| P27 | Seleccionar fotorresistencia | 0–1 | 0 | Detección de llama | G 7 / PDF 8 |
| P31 | Habilitar ventilador aspiración | 0–1 | 1 | Todas sus salidas | G 8 / PDF 9 |
| P40 | Lógica entrada de pellet | 0–1 | 0 | Nivel | G 8 / PDF 9 |

G 5/PDF 6 añade consigna de agua TH-CALDAIA entre A12 y A13 (defecto 80 °C), selector Auto/MAnu y lectura FuMi o LuMi. No se les inventan códigos de tabla.

G 8/PDF 9 precisa: pausa = C40 − CL; CL=C40 mantiene sinfín ON, CL=0 lo mantiene OFF y el menú avanza en **0,5 s**. El contrato actual CTP usa segundos enteros y CP independiente: **no representa ese ajuste de medio segundo ni importa C40**. HI=901 °C desactiva el umbral, siendo 900 el último valor; es una codificación del programa, no una temperatura admisible de instalación. UC20 eleva los valores menores salvo cero. A05=0 desactiva modulación por agua. El texto de P02=1 dice que no se intentará encender: el cómputo exacto de intentos no se extrapola a CTP.

G 9/PDF 10, valores no programables: antihielo 5 °C, alarma agua 97 °C; histéresis F16/F18, MAN por agua, alarma y bomba 2 °C; seguridad/antihielo/foto 0 °C. Estos números no se convierten en valores CTP.

## Estados, guardas y salidas de esta variante

| Estado / evento | Condición y destino descritos | Salidas / tiempo | Página G / PDF |
| --- | --- | --- | --- |
| OFF | Después de SPE; si humos suben sobre F16, vuelve a SPE | Combustión parada; bomba según A01 y protecciones | 12 / 13 |
| CHC | No figura fase equivalente parametrizada en G | No importar una limpieza CHC desde este manual | 12–22 / 13–23 |
| ACC fija | Orden ON desde OFF/SPE si no hay alarmas/puerta abierta | T01, sinfín cíclico CL00/C40, encendedor ON, ventilación de ACC | 13–14 / 14–15 |
| ACC variable | Tras fija; éxito de llama según F18 o L01; primera puesta→STB, salida de MAN→NORMAL | T02, **sinfín OFF**, encendedor ON; agotamiento/reintentos P02 y ALT+Acc | 13–14 / 14–15 |
| STB | Refuerzo T03; T03=0 permite omitir; cumplimiento de encendido→NORMAL, pérdida→reintento según condiciones impresas | CL04/C40; encendedor OFF; ventilación STB | 15 / 16 |
| REC | Vuelta de alimentación, análisis de unos 5 s, retorno a encendido | No ofrece las ramas P34/estado previo de CTP | 16 / 17 |
| NORMAL | Agua sobre consigna−A05 o humos sobre F22→MOD; agua sobre consigna o humos sobre F24→MAN; agua sobre A04→SIC | CL05/C40, UC05/UA05; pérdida de llama sostenida T06→SPE+SPAc | 17 / 18 |
| MOD | Temperatura de agua o humos bajando de umbral puede retornar NORMAL; agua/umbral humos de mantenimiento→MAN; A04→SIC | CL07/C40, UC07/UA07; pérdida de llama T06→SPE+SPAc | 18 / 19 |
| MAN pausa | Agua/humos pueden solicitar mantenimiento; salida por humos inferiores a F24→MOD, por agua inferior a consigna→ACC | T04: sinfín y primario OFF, aspiración con retardo T14; bomba según reglas | 19 / 20 |
| MAN trabajo | Al terminar T04, ventana T05; **si agua supera consigna se omite T05**; T05=0 deshabilita trabajo | Durante T05, **sinfín continuo ON**, ventiladores en valores NORMAL, encendedor OFF | 19 / 20 |
| SIC | Agua sobre A04; bajo A04→MAN; alarma agua a 97 °C | Combustión OFF, bomba ON | 20 / 21 |
| SPE P08=1 | Combustión residual hasta F16, después limpieza T09 y OFF | Sinfín/encendedor OFF; UC10/UA10 y limpieza máxima. El texto introduce inhibición de ventilación por agua sobre consigna | 21–22 / 22–23 |
| SPE P08=0 | Solo limpieza T09 y después OFF | Primario OFF, aspiración de SPE | 21–22 / 22–23 |
| ALT | Indicación alternada con tSic/Sic/Acc/SPAc/PELL | No confundir borrado de visualización con rearme físico de termostato | 4 y 23 / 5 y 24 |
| Termostato máximo | Entrada 7–8 abierta→SPE, tSic; impide encendido | Inhibe combustión; rearme del dispositivo independiente | 23 / 24 |
| Nivel pellet | Falta en OFF inhibe arranque; en marcha, T16→SPE+PELL | 42=+12 V, 43=GND, 44=señal, interpretación P40 | 23 / 24 |
| Puerta | Apertura inhibe primario, sinfín, encendedor y cenizas | Aspiración máxima si P15=1, OFF si P15=0; **difiere de CTP** | 23 / 24 |
| Crono / ambiente | Crono abierto→MAN, cerrado→ACC; ambiente abierto inhibe bomba salvo seguridad/antihielo | Entradas 29–30 y 33–34; no aplicar P03/P04 ausentes | 23–24 / 24–25 |
| Manual / bomba | Manual ignición directa y control por agua; bomba por A01, seguridad y antihielo | La enumeración de estados manuales en G es menos amplia que CTP; no acreditar SIC ausente por omisión | 24 / 25 |

Las prioridades entre guardas simultáneas, algunas desigualdades/histéresis y el número exacto de reintentos requieren conservar las tablas completas. Esta síntesis no es un programa de control certificado ni resuelve los conflictos de CTP. Los añadidos de PDF 26–28 no se utilizan para corregir silenciosamente el cuerpo del manual.

# Máquina original: reconstrucción parcial y matriz de verificación

Estado: **reconstrucción CTP ampliada, todavía incompleta para programar la centralita instalada**. ES 13–17, UT 12–16 y 28–30 e IT técnico 33–38 describen estados, contactos y parámetros. UT se cita por página impresa (PDF = impresa + 1); IT PDF 1 = impresa 33. El UTENTE ya está disponible y su esquema está comprobado. Falta la ficha SCHEDA independiente y la revisión instalada; los conflictos de CTP no se completan usando la [máquina de otra variante Pellet](VARIANTE_SY325_PELLET.md).

Se confirman un sinfín instalado y capacidad declarada de un segundo canal. La reconstrucción mantiene los parámetros opcionales de fabricante sin exigir un segundo motor.

MAN = automantenimiento. **Manu/manual es un modo**, distinto del estado MAN: ES p. 17 describe encendido manual directo y funcionamiento limitado a NORMAL, MAN y SIC basado en temperatura de agua. No está reconstruida su interacción completa con OFF/ALT.

| Estado canónico | Función original y entradas verificadas | Salidas/tipos de control documentados | Tiempos/salidas pendientes | Fuente |
| --- | --- | --- | --- | --- |
| OFF | Apagado; orden ON o cierre de entrada configurada puede iniciar chequeo | ES permite carga manual LOAD mientras se mantiene SET, aun en OFF; no equivale a ciclo automático | Reglas de bomba/antihielo y carga manual con protecciones | ES 14–15; IT 36/38 |
| CHC | Chequeo y limpieza inicial; alias histórico CHECK/Chc | Ventilación para limpiar cámara | T08; UT 28 confirma limpieza con ventilación y después ACC, duración ajustable aproximadamente 1 min; valor instalado desconocido | ES 15/21; IT 35; UT 28/PDF 29 |
| ACC | Inicio de llama; incluye etapas y posible encendedor | CL00/CP00, UC00/UA00; opcional sinfín 2 CL20/CP20; habilitación P34 | T00/T01/T02, F18/F21, P02; orden y guardas exactas de reintento/agotamiento | ES 15/20–21; IT 34–35 |
| STB | Refuerzo/estabilización de llama | CL04/CP04, UC04/UA04; CL24/CP24 | T03, éxito o pérdida de llama y límites de salida | ES 15/20–21; IT 34–35 |
| REC | Recuperación tras interrupción/restauración de alimentación | No hay política completa de actuadores accesible | UT 30: unos 5 s; P34=1→ACC, P34=0→último estado registrado; faltan umbrales, persistencia y prioridades | ES 15; UT 30/PDF 31 |
| NORMAL | Potencia configurada; el display muestra temperatura sin sigla | LED1 y CP05; LED2 para primario; CL25/CP25 para segundo sinfín | Velocidad secundaria (UA05 dudoso); fronteras completas de MOD/MAN y pérdidas de llama | ES 16–21; IT 33–34 |
| MOD | Reducción de combustible/aire; condicionada por agua y humos | CL07/CP07, UC07/UA07; CL27/CP27 | A05/F22; histéresis y prioridad entre umbrales | ES 13/15/20–21; IT 34 |
| MAN | Entrada al alcanzar consigna LED4; también contactos según P03/P04 | T04 espera: sinfín y ventilador parados, bomba según protección; T05: ventilación, CP09 pausa y CL09 carga | T04 min, T05 s; T05=CP09+CL09; salida por LED4−IA06; UT 30: P34=1→ACC o P34=0→NORMAL; permisos y canal opcional pendientes | IT 35–36; ES 13/15/20–21; UT 30/PDF 31 |
| SIC | Seguridad/bloqueo del sistema | A04 seguridad agua y A06 salida SIC; cortar combustible es protección del proyecto | Guardas exactas y prioridad de bomba/ventilación; A06 duplicado impide asignación inequívoca | ES 15–16/21; IT 34–35/38 |
| SPE | Apagado controlado, puede mantener bomba y ventilación | UC10/UA10 (también UA05 dice SPE); P08 habilita fase | UT 29 confirma SPE→OFF por humos bajo umbral (ejemplo 50 °C); T06/T09/P08 y ventilación tienen descripción contradictoria | ES 15/20–21; IT 34–35; UT 14 y 29 |
| ALT | Señalización de apagado con alarma y causa | Diagnósticos tSic/Sic/Acc/SPAc/Sond; IT añade PELL | Rearme, enfriamiento y autorización local; no asumir que reset remoto equivale a rearme físico | ES 15–16/24; IT 38–39 |

Matriz de transiciones: `D` = relación descrita cualitativamente; `C` = alguna entrada tiene conflicto entre fuentes; `P` = secuencia plausible por nombres, **NO COMPROBADA**; `?` = sin evidencia suficiente, no significa prohibida; `—` = permanencia no caracterizada. `R` = regreso condicional al último estado registrado si P34=0, no transición garantizada a cada destino. La matriz no es una especificación ejecutable.

| Desde / hacia | OFF | CHC | ACC | STB | REC | NORMAL | MOD | MAN | SIC | SPE | ALT |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OFF | — | D | ? | ? | ? | D | ? | ? | ? | ? | ? |
| CHC | ? | — | D | ? | ? | ? | ? | ? | ? | D | ? |
| ACC | ? | ? | — | P | ? | D | ? | ? | ? | D | ? |
| STB | ? | ? | ? | — | ? | P | ? | ? | ? | D | ? |
| REC | ? | ? | D | ? | — | R | R | R | ? | ? | ? |
| NORMAL | ? | ? | ? | ? | ? | — | D | D/C | ? | D/C | ? |
| MOD | ? | ? | ? | ? | ? | P | — | D/C | ? | D/C | ? |
| MAN | ? | ? | D | ? | ? | D | ? | — | ? | D | ? |
| SIC | ? | ? | ? | ? | ? | ? | ? | ? | — | ? | ? |
| SPE | D | ? | ? | ? | ? | ? | ? | ? | ? | — | P |
| ALT | ? | ? | ? | ? | ? | ? | ? | ? | ? | ? | — |

Las relaciones hacia SPE se apoyan en P04=2 y en STOP descrito por UT 29, sin acreditar prioridad sobre todas las protecciones. UT 28 confirma CHC→ACC y resume ACC→NORMAL por umbral de humos, mientras UT 13/IT 34–35 describen STB: esa omisión no autoriza a eliminar STB ni a copiar su criterio desde G. OFF→NORMAL se limita al modo Manu.

MAN→ACC/NORMAL por temperatura está documentada en UT 30 según P34, con histéresis IA06 en IT 35; los contactos pueden tener otras transiciones según P03/P04. REC dura aproximadamente 5 s según UT 30 y depende de P34/estado anterior. El firmware conserva únicamente configuración: no afirma haber recuperado el estado de combustión.

| Transición o secuencia CTP aclarada | Fuente | Límite pendiente |
| --- | --- | --- |
| OFF→CHC→ACC con orden local | UT 28/PDF 29, T08 IT 35 | ON aparece como 3 s en UT 28 y 5 s en UT 12; receta y permisos exactos |
| ACC→NORMAL por humos | UT 28/PDF 29 | Relación exacta con STB/F18/F21/T00–T03 omitida en el resumen |
| NORMAL→MOD al acercarse a consigna | UT 29/PDF 30, A05 IT 34 | Ejemplo 5 °C/30 % no es receta universal; prioridad con F22/F24 |
| NORMAL/MOD→MAN a consigna | UT 29, LED4 IT 33 y gráfico IT 36 | Prioridades, contactos y permisivos |
| MAN: T04 espera, T05=CP09+CL09 con alimentación | IT 36/PDF 4 | No imponer reglas de T05/sinfín continuo de G |
| MAN→ACC si P34=1; →NORMAL si P34=0 | UT 30/PDF 31; IA06 IT 35 | Ajuste instalado y protecciones simultáneas |
| Restauración→REC→ACC o estado previo | UT 30/PDF 31 | Persistencia del estado, duración de corte y condiciones de rearranque |
| Orden OFF→SPE→OFF por umbral de humos | UT 29/PDF 30 | Ventilación contradictoria entre UT 14 y 29; secuencia exacta P08/T09 |
| Resistencia OFF y V3 enfría unos 3 min | UT 29/PDF 30; T51 IT 35 | V3 no se modela todavía; rango T51 ausente |


| Evento o protección | Evidencia original | Estado en firmware 0.4.7 de auditoría | Veredicto funcional |
| --- | --- | --- | --- |
| Puerta inferior abierta | ES 22 / IT 36–37: Port; ambos sinfines, ambos ventiladores y encendedor OFF | Una puerta simulada corta combustión y enclava ALT; bomba con sonda válida según umbral | Parcial; semántica Port y rearme original pendientes |
| Termostato de seguridad | tSic y rearme manual ES 16/24, IT 38 | Entrada digital simulada enclava alarma; START siempre bloqueado | Sin cadena física verificada |
| Sobretemperatura agua | A04/A06; bomba no debe inhibirse por termostato ambiente en seguridad agua (IT 37) | Corte de combustible y encendedor; disipación simulada con agua válida | Política del proyecto, no equivalencia completa del fabricante |
| Sonda inválida | Sond ES 16 | Flag crítico o NaN/Inf enclavan alarma | Faltan detección real de circuito abierto/corto y plausibilidad/antigüedad de sonda |
| Encendido fallido/pérdida de llama | Acc/SPAc; F16/F18/F21 y P02, T00–T03 | Sin guardas reales: solo demostración temporizada | **FALLA** para control original |
| Nivel de combustible | PELL/T16/T65/P54 IT 35/38 | UT 13 añade reserva de 3 min/ALT+PELL; firmware mantiene nivel fijo, sin apagado por falta de combustible | **FALLA** para control original |
| Postventilación/apagado | SPE, P08, T06/T09, UC10/UA10 | No se ejecuta una secuencia de enfriamiento original | **FALLA** para control original |
| Antihielo / bomba / ACS | ES 13 menciona función; IT 37–38 excepción bomba y prioridad de ACS sobre termostato | Umbral de bomba simulado; sin antihielo ni flujostato | **NO COMPROBADO** y no implementado |
| Apagón/recuperación | REC ES 15 y UT 30 | Arranque OFF; cache de configuración, no del estado de combustión | **FALLA** para recuperación original |
| Watchdog / salidas pegadas | Sin especificación completa aplicable a la revisión instalada | Sin watchdog de aplicación ni retorno de actuadores | Pendiente crítico antes de hardware |

El firmware mantiene `static_assert(SIMULATION_MODE)` en ambos sketches y no escribe GPIO. La secuencia de panel sigue OFF→CHC→ACC→STB→NORMAL cada 15 s de simulación. MOD/MAN/REC/SIC/SPE/ALT existen en el catálogo, pero esto no acredita su alcanzabilidad ni la máquina original. MAN se prueba mediante el sketch real con E/S simulada; eso verifica su ventana, no la combustión real. La alarma queda enclavada sin reset remoto implementado; no se habilita una nueva ruta de rearme.

La lectura de un diagrama y las regresiones lógicas no acreditan protecciones de potencia. No se ha implementado la política genérica G de aspiración máxima con puerta abierta, un segundo motor ni recuperación automática tras apagón: necesitan identificación de variante y prioridades de seguridad. El bloqueo de simulación evita habilitarlos accidentalmente.

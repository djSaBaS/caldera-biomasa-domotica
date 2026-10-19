# Máquina original: reconstrucción parcial y matriz de verificación

Estado: **INCOMPLETA**. ES pp. 13–17 identifica fases y modos; tablas ES 20–21 e IT técnico 34–36 describen parámetros. Ninguna fuente accesible proporciona todas las guardas, prioridades y transiciones. Faltan UTENTE y la ficha SY325. No se deben rellenar estos huecos con la secuencia de demostración del firmware.

MAN = automantenimiento. **Manu/manual es un modo**, distinto del estado MAN: ES p. 17 describe encendido manual directo y funcionamiento limitado a NORMAL, MAN y SIC basado en temperatura de agua. No está reconstruida su interacción completa con OFF/ALT.

| Estado canónico | Función original y entradas verificadas | Salidas/tipos de control documentados | Tiempos/salidas pendientes | Fuente |
| --- | --- | --- | --- | --- |
| OFF | Apagado; orden ON o cierre de entrada configurada puede iniciar chequeo | ES permite carga manual LOAD mientras se mantiene SET, aun en OFF; no equivale a ciclo automático | Reglas de bomba/antihielo y carga manual con protecciones | ES 14–15; IT 36/38 |
| CHC | Chequeo y limpieza inicial; alias histórico CHECK/Chc | Ventilación para limpiar cámara | T08; valor/ventilador exactos y criterio de salida hacia ACC | ES 15/21; IT 35 |
| ACC | Inicio de llama; incluye etapas y posible encendedor | CL00/CP00, UC00/UA00; opcional sinfín 2 CL20/CP20; habilitación P34 | T00/T01/T02, F18/F21, P02; orden y guardas exactas de reintento/agotamiento | ES 15/20–21; IT 34–35 |
| STB | Refuerzo/estabilización de llama | CL04/CP04, UC04/UA04; CL24/CP24 | T03, éxito o pérdida de llama y límites de salida | ES 15/20–21; IT 34–35 |
| REC | Recuperación tras interrupción/restauración de alimentación | No hay política completa de actuadores accesible | Persistencia de estado, duración del corte, umbrales y destino | ES 15; faltan UTENTE/SY325 |
| NORMAL | Potencia configurada; el display muestra temperatura sin sigla | LED1 y CP05; LED2 para primario; CL25/CP25 para segundo sinfín | Velocidad secundaria (UA05 dudoso); fronteras completas de MOD/MAN y pérdidas de llama | ES 16–21; IT 33–34 |
| MOD | Reducción de combustible/aire; condicionada por agua y humos | CL07/CP07, UC07/UA07; CL27/CP27 | A05/F22; histéresis y prioridad entre umbrales | ES 13/15/20–21; IT 34 |
| MAN | Entrada al alcanzar consigna LED4; también contactos según P03/P04 | T04 espera: sinfín y ventilador parados, bomba según protección; T05: ventilación, CP09 pausa y CL09 carga | T04 min, T05 s; T05=CP09+CL09; salida por LED4−IA06; segundo sinfín/permisos completos pendientes | IT 35–36; ES 13/15/20–21 |
| SIC | Seguridad/bloqueo del sistema | A04 seguridad agua y A06 salida SIC; cortar combustible es protección del proyecto | Guardas exactas y prioridad de bomba/ventilación; A06 duplicado impide asignación inequívoca | ES 15–16/21; IT 34–35/38 |
| SPE | Apagado controlado, puede mantener bomba y ventilación | UC10/UA10 (también UA05 dice SPE); P08 habilita fase | T06 preapagado y T09 limpieza, F16, destino OFF/ALT y orden exacto | ES 15/20–21; IT 34–35 |
| ALT | Señalización de apagado con alarma y causa | Diagnósticos tSic/Sic/Acc/SPAc/Sond; IT añade PELL | Rearme, enfriamiento y autorización local; no asumir que reset remoto equivale a rearme físico | ES 15–16/24; IT 38–39 |

Matriz de transiciones: `D` = relación descrita cualitativamente; `C` = alguna entrada tiene conflicto entre fuentes; `P` = secuencia plausible por nombres, **NO COMPROBADA**; `?` = sin evidencia suficiente, no significa prohibida; `—` = permanencia no caracterizada. La matriz no es una especificación ejecutable.

| Desde / hacia | OFF | CHC | ACC | STB | REC | NORMAL | MOD | MAN | SIC | SPE | ALT |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| OFF | — | D | ? | ? | ? | D | ? | ? | ? | ? | ? |
| CHC | ? | — | P | ? | ? | ? | ? | ? | ? | D | ? |
| ACC | ? | ? | — | P | ? | ? | ? | ? | ? | D | ? |
| STB | ? | ? | ? | — | ? | P | ? | ? | ? | D | ? |
| REC | ? | ? | ? | ? | — | ? | ? | ? | ? | ? | ? |
| NORMAL | ? | ? | ? | ? | ? | — | D | D/C | ? | D/C | ? |
| MOD | ? | ? | ? | ? | ? | P | — | D/C | ? | D/C | ? |
| MAN | ? | ? | D | ? | ? | P | ? | — | ? | D | ? |
| SIC | ? | ? | ? | ? | ? | ? | ? | ? | — | ? | ? |
| SPE | P | ? | ? | ? | ? | ? | ? | ? | ? | — | P |
| ALT | ? | ? | ? | ? | ? | ? | ? | ? | ? | ? | — |

Las relaciones hacia SPE marcadas D se apoyan en P04=2 al abrir el contacto durante funcionamiento (ES 23 / IT 38); no acreditan transiciones por STOP ni su prioridad. OFF→CHC aparece con P03=0 al cerrar crono y P04=2 al cerrar desde apagado. OFF→NORMAL se limita al modo Manu descrito en ES 17. MAN→ACC está descrita al cerrar crono P03=1 o termostato P04=1 en IT 36–37. NORMAL/MOD→MAN por temperatura LED4 y por P04=1 en IT; los textos ES sobre contactos discrepan. La restauración eléctrica lleva a REC según ES 15, pero se desconocen sus condiciones por estado previo, por eso no se inventan aristas de REC en cada fila.

| Evento o protección | Evidencia original | Estado en firmware 0.4.7 de auditoría | Veredicto funcional |
| --- | --- | --- | --- |
| Puerta inferior abierta | ES 22 / IT 36–37: Port; ambos sinfines, ambos ventiladores y encendedor OFF | Una puerta simulada corta combustión y enclava ALT; bomba con sonda válida según umbral | Parcial; semántica Port y rearme original pendientes |
| Termostato de seguridad | tSic y rearme manual ES 16/24, IT 38 | Entrada digital simulada enclava alarma; START siempre bloqueado | Sin cadena física verificada |
| Sobretemperatura agua | A04/A06; bomba no debe inhibirse por termostato ambiente en seguridad agua (IT 37) | Corte de combustible y encendedor; disipación simulada con agua válida | Política del proyecto, no equivalencia completa del fabricante |
| Sonda inválida | Sond ES 16 | Flag crítico o NaN/Inf enclavan alarma | Faltan detección real de circuito abierto/corto y plausibilidad/antigüedad de sonda |
| Encendido fallido/pérdida de llama | Acc/SPAc; F16/F18/F21 y P02, T00–T03 | Sin guardas reales: solo demostración temporizada | **FALLA** para control original |
| Nivel de combustible | PELL/T16/T65/P54 IT 35/38 | Nivel fijo simulado, sin apagado por falta de combustible | **FALLA** para control original |
| Postventilación/apagado | SPE, P08, T06/T09, UC10/UA10 | No se ejecuta una secuencia de enfriamiento original | **FALLA** para control original |
| Antihielo / bomba / ACS | ES 13 menciona función; IT 37–38 excepción bomba y prioridad de ACS sobre termostato | Umbral de bomba simulado; sin antihielo ni flujostato | **NO COMPROBADO** y no implementado |
| Apagón/recuperación | REC ES 15 | Arranque OFF; cache de configuración, no del estado de combustión | **FALLA** para recuperación original |
| Watchdog / salidas pegadas | Sin especificación suficiente en fuentes accesibles | Sin watchdog de aplicación ni retorno de actuadores | Pendiente crítico antes de hardware |

El firmware mantiene `static_assert(SIMULATION_MODE)` en ambos sketches y no escribe GPIO. La secuencia de panel sigue OFF→CHC→ACC→STB→NORMAL cada 15 s de simulación. MOD/MAN/REC/SIC/SPE/ALT existen en el catálogo, pero esto no acredita su alcanzabilidad ni la máquina original. MAN se prueba mediante el sketch real con E/S simulada; eso verifica su ventana, no la combustión real. La alarma queda enclavada sin reset remoto implementado; no se habilita una nueva ruta de rearme.

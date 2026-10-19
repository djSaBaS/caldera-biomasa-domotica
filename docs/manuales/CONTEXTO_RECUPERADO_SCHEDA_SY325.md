# Recuperación documental — placa SY325 y proyecto Caldera

**Estado:** dossier de contexto recuperado; **NO** es el archivo original `SCHEDA_SY325.pdf`, **NO** sustituye un esquema eléctrico certificado y **NO** debe utilizarse directamente para cablear 230 V.
**Fecha de reconstrucción:** 2026-10-19.

## 1. Procedencia y fiabilidad

- **HISTORIAL DEL PROYECTO (no copia íntegra):** el usuario aportó en una conversación anterior `SCHEDA_SY325.pdf`, `CTP_Manuale_Uso_CS_v1_2008_UTENTE.pdf` y `MANUAL CS PASQUALICCHIO.pdf`. Se documentó que el primero era el esquema de la placa electrónica SY325, también visible en el manual italiano (se anotó página 27 de aquel archivo). Los PDF originales no están accesibles actualmente para comparar visualmente.
- **CORROBORADO EN DOCUMENTACIÓN PÚBLICA DE OTRAS VARIANTES SY325:** esquema general de entradas/salidas, sensores NTC y tipo K, estados y parámetros por fase. No garantiza que el cableado coincida exactamente con la unidad del usuario.
- **SIN VERIFICAR:** número exacto de revisión de la placa instalada, potencia y configuración de la caldera, todas las posiciones de los bornes de potencia, presencia efectiva de dos sinfines, dos ventiladores o encendedor eléctrico, la foto real del cableado y las calibraciones de funcionamiento.

Fuentes públicas de apoyo (versiones diferentes, por tanto solo referencia):
1. [Manual SY325 Combi v3.0 — Scribd, italiano](https://it.scribd.com/document/737371808/Manuale-SY325-GET-35-Combi-V4). Figura de instalación aproximadamente pp. 16-17; especificaciones aprox. p. 44 del contenido.
2. [TiEmme SY325 Pellet v1.3 — ManualsLib, español](https://www.manualslib.es/manual/253882/Tiemme-Sy325.html).
3. [Sime SY325 LM02 v2.2 — ManualsLib, español](https://www.manualslib.es/manual/22645/Sime-Sy325.html). Variante diferente y no intercambiable sin comprobación.
4. [Manual Pasqualicchio CS Marina (PDF público)](https://www.climatizzatori.it/files/archivio_pronto_Files/DocFiles/000/000/131/manuali_CSmarina.pdf). **ATENCIÓN:** el esquema que contiene se identifica como **SY400** y los terminales **NO** deben extrapolarse a SY325.

## 2. Placa y filosofía de funcionamiento

Centralita comercial SY325 basada en placa de potencia/electrónica y panel frontal con display de cuatro dígitos, LEDs y teclas; unida al panel mediante cable interno. Gestiona encendido y apagado, alimentación de biomasa, ventilación, bomba, sensores y seguridad, usando estados y recetas/tiempos programables. Alimentación de la familia documentada: 220–230 V CA a 50 Hz; fusible retardado de 6,3 A en referencia SY325 Combi. Estos valores son **referencias** y requieren confirmación sobre la placa física.

### Sensores previstos

- Humos: termopar tipo K; intervalo citado 0–500 °C en SY325 Combi.
- Agua: termistor NTC 10 kΩ; intervalo citado 0–110 °C.
- Termostato de máxima, de rearme manual e independiente del control lógico (contacto NC en versión descrita).
- Contacto de puerta (habitualmente NC).
- Contactos opcionales: crono/horario externo, termostato ambiente, final de carrera de válvula de humos.
- Sensor de nivel de combustible presente en algunas versiones de caldera, por verificar en el equipo real.

**Nota:** los DS18B20 no equivalen directamente a la NTC original ni sirven para medición directa de humos de alta temperatura. No se deben sustituir sondas ni alterar su papel de seguridad sin validación.

### Actuadores/cargas posibles

- 1 o 2 tornillos sinfín (`Coclea 1/2`), salidas ON/OFF a relé en SY325 Combi.
- Ventilador de combustión 1 y segundo ventilador opcional. En SY325 Combi las salidas de ventilador son reguladas mediante TRIAC (especificación de referencia hasta 1,3 A por salida); en otras variantes puede haber un segundo ventilador no utilizado.
- Encendedor (`Accenditore`) ON/OFF, opcional según configuración.
- Bomba del circuito de agua.
- Válvula/actuador de aspiración o protección de circuito, según variante.

El equipamiento concreto de la caldera se ha de inspeccionar físicamente. No trasladar las cifras de potencia nominal de manuales de distintas máquinas a un dimensionamiento eléctrico real.

## 3. Referencias de bornes corroboradas para **SY325 COMBI** (no usar sin verificar la revisión)

| Bornes | Referencia documental SY325 Combi | Observaciones |
|---|---|---|
| 7–8 | Termostato de máxima con rearme manual | Cadena de seguridad; **nunca puentear para anular protecciones**. |
| 21–22 | Termopar K de humos | La edición Combi indica polaridad, pero colores de cables no son universales; confirmar pieza y placa. |
| 25–26 | Final de carrera de válvula de humos, cuando existe | Entrada opcional en variante Combi. |
| 29–30 | Crono/reloj externo | Semántica dependiente del parámetro `P03`. |
| 31–32 | Contacto de puerta | Contacto normalmente cerrado, en referencia Combi. |
| 33–34 | Termostato de ambiente | Semántica dependiente del parámetro `P04`. |

**No se dispone de una transcripción verificada del pinout completo** del `SCHEDA_SY325.pdf` original, especialmente de todas las salidas de potencia de motores, encendedor, bombas y ventiladores. Los diagramas SY400 no sirven para completarlo: por ejemplo, la documentación SY400 que se encuentra online asigna **29–30 a un motor de limpieza**, mientras las referencias SY325 Combi asignan **29–30 al crono externo**.

### P03 — Entrada crono

En la versión SY325 Combi consultada:
- `P03=0`: apertura de crono solicita apagado; cierre puede iniciar `CHECK UP` desde apagado (contacto NA según esa receta).
- `P03=1`: apertura desde NORMAL/MOD lleva a AUTOMANTENIMIENTO; cierre puede solicitar ENCENDIDO (contacto NC según esa receta).

Esto sugiere que el contacto de crono podría ser una vía para control limitado de demanda desde Caldera manteniendo la lógica original, pero requiere validar el modelo, el modo operativo y los enclavamientos eléctricos. **No conectar una salida GPIO ni aplicar tensión a una entrada de contacto sin conocer la interfaz.**

### P04 — Entrada termostato ambiente

- `P04=0`: controla demanda de bomba según lógica interna.
- `P04=1`: puede llevar desde NORMAL/MOD a AUTOMANTENIMIENTO y reactivar proceso al volver demanda.
- `P04=2`: apertura lleva al apagado y cierre a CHECK UP según el manual SY325 Combi; tipo de contacto configurado diferente.

### Contacto de puerta — diferencias entre variantes

En SY325 Combi v3.0, la apertura de puerta interrumpe ambos sinfines y el encendedor; **el ventilador 1 pasa al 99 %**, el segundo se detiene y la válvula de aspiración puede activarse. En otras variantes SY325 el esquema de ventilación es distinto. Por tanto, la orden simplificada «puerta abierta → apagar todos los ventiladores» **NO** replica universalmente a SY325. No usarla sin reproducir la lógica de la versión concreta y sin revisión de seguridad.

## 4. Estados de la máquina documentados en conversaciones previas

| Estado | Significado funcional |
|---|---|
| `OFF` | Apagado/inactivo, con supervisiones de seguridad que procedan. |
| `Chc` / CHECK | Comprobación y limpieza previa al encendido. |
| `Acc` | Encendido en fases, con temporizaciones y control de llama. |
| `Stb` | Estabilización de combustión. |
| `rEc` | Recuperación de encendido tras interrupción o situación documentada según variante. |
| NORMAL | Potencia normal y alimentación temporizada del combustible. |
| `Mod` | Modulación de potencia. |
| `MAn` | Automantenimiento que combina **fase de pausa** y, si procede, **fase de mantenimiento** (NO equivale a sinfín constantemente activo). |
| `Sic` | Seguridad al alcanzar condición de temperatura/control. |
| `SPE` | Secuencia controlada de apagado, distinta de cortar todas las cargas instantáneamente. |
| `Alt` | Alarma/fallo mostrado en display. |

La transición y el comportamiento de cada salida dependen de termostatos del agua/humos, temporizadores, receta, opciones habilitadas y estado de seguridad. No improvisar una FSM reducida para conectarla a 230 V.

## 5. Parámetros de fabricante recopilados

### Sinfín 1: trabajo y pausa INDEPENDIENTES

| Fase | Trabajo ON | Pausa OFF |
|---|---|---|
| Encendido | `CL00` | `CP00` |
| Estabilización | `CL04` | `CP04` |
| Normal | Tiempo ON de menú de usuario (normalmente no `CL05` en la tabla Combi consultada) | `CP05` |
| Modulación | `CL07` | `CP07` |
| Automantenimiento | `CL09` | `CP09` |

### Sinfín 2 (si la configuración lo habilita)

| Fase | Trabajo ON | Pausa OFF |
|---|---|---|
| Encendido | `CL20` | `CP20` |
| Estabilización | `CL24` | `CP24` |
| Normal | `CL25` | `CP25` |
| Modulación | `CL27` | `CP27` |
| Automantenimiento | `CL29` | `CP29` |

Variantes concretas contemplan otros códigos (`CL06/26/30` y `CP06/26/30`) para transiciones o salidas especiales. **No inventar su existencia en el equipo instalado.**

### Ventilación

- Ventilador principal: `UC00` (encendido), `UC04` (estabilización), `UC05` (normal, según menú), `UC07` (modulación), `UC09` (mantenimiento), `UC10` (apagado), `UC20` (mínimo configurable, variante Combi).
- Ventilador secundario: familia `UA00`, `UA04`, `UA05`, `UA07`, `UA09`, `UA10`, `UA20` cuando está equipado.
- Porcentajes de receta y habilitación física deben comprobarse con el ventilador real; un dimmer Robotdyn genérico no es necesariamente compatible con motor de inducción/condensador ni apto para instalaciones definitivas.

### Umbrales y temporizadores

- `F16` humos: criterio de apagado; `F18` humos: criterio de encendido; `F21` humos: transición rápida de encendido; `F22` humos: modulación; `F24` humos: mantenimiento.
- `A04`: termostato lógico de seguridad del agua, documentado en una tabla de conversación previa con rango 86–95 °C. No equivale al termostato físico independiente. Otros `A` y `IA06` definen umbrales/histéresis según receta.
- `T00`: precalentamiento de encendedor.
- `T01`, `T02`: fases de encendido (los manuales/variantes difieren en la denominación exacta y las unidades).
- `T03`: estabilización.
- `T04`: **pausa** de automantenimiento (en referencia Combi).
- `T05`: **trabajo** de mantenimiento (puede aportar mínimo combustible, no omitir).
- `T06`: preapagado; `T08` limpieza inicial; `T09` limpieza final según variante.
- `P02`: número de intentos de encendido (ref. previa 1–5).
- `P03`: comportamiento de entrada crono.
- `P04`: comportamiento del termostato ambiente.
- `P08`: habilita procedimientos de apagado.
- `P30`/`P31`: habilitación de ventiladores; `P32`/`P33`: sinfines; `P34`: encendedor en variante Combi.

Estos códigos son **información para mapear**, no valores de instalación recomendados. Para las tablas completas se necesita fijar la revisión exacta del controlador instalado.

## 6. Advertencia sobre la hipótesis del sinfín ON = OFF

El usuario solicitó que en su configuración habitual el sinfín trabaje `X segundos ON + X segundos OFF`. Se conserva como **preferencia concreta del proyecto**; **NO** se atribuye al fabricante como norma universal. La tabla SY325 define `CLxx` y `CPxx` independientes por estado y a menudo con valores muy diferentes. El software debe poder representar la receta original y, solo donde sea admisible y validado, habilitar una política local 1:1. No usar una única variable universal para todas las fases de ignición, estabilización, normal, modulación y mantenimiento.

## 7. Automantenimiento: detalle crítico

En la documentación pública de SY325 Combi, el automantenimiento distingue:
1. **Pausa (`T04`)**: reducción de la combustión; sinfines desconectados.
2. **Mantenimiento (`T05`)**: aporte mínimo de combustible y ventilación según receta; puede haber sinfines temporizados `CL09/CP09` (y variantes del segundo sinfín).

Por tanto son incorrectas tanto la premisa «en MAN siempre está apagado el sinfín» como la premisa «en MAN hay alimentación normal continua». Es necesario implementar las subfases de mantenimiento con los parámetros exactos de la placa.

## 8. Riesgos y arquitectura sugerida del proyecto

- Conservar SY325 como centralita original y **fallback** físicamente separado/enclavado: impedir que dos controladores alimenten simultáneamente un motor.
- Preferencia inicial de seguridad: monitorizar y, si la revisión lo permite, solicitar demanda usando el contacto documentado de crono/termostato mediante aislamiento apropiado, sin intervenir en la cadena de seguridad.
- Nunca puentear, anular ni sustituir el termostato de máxima de rearme manual, interruptor de puerta ni protecciones independientes.
- El Arduino no puede convertirse en único control de seguridad de 230 V. Separar cadena física de máxima temperatura, protección de motores, bomba y disipación térmica en pérdida de suministro.
- La bomba podría necesitar seguir operando durante apagado, alta temperatura u otras contingencias; no es seguro establecer `error -> todas las salidas OFF`.
- El control real de motores 230 V y la conmutación del modo ORIGINAL/DOMÓTICO requieren proyecto eléctrico e instalador competente.
- Las referencias de hardware en poder del usuario son: Elegoo Mega 2560 R3, LCD1602, placa de 4 relés JQC-3FF-S-Z, 2 Robotdyn AC Light Dimmer, ESP32/ESP32-CAM, servos, motor paso a paso y sensores por inventariar. Ninguno acredita por sí solo idoneidad para controlar una caldera real.

## 9. Qué falta recuperar para reproducir `SCHEDA_SY325.pdf`

1. Una copia o fotografía legible de la **etiqueta de la placa**, referencia completa y revisión.
2. Fotos generales de la placa, bornes, cables, panel y etiquetas de conexiones con instalación **desenergizada**.
3. Foto de placa de características de la caldera y de sus motores/ventiladores/bomba/encendedor.
4. El PDF/imagen original o la página correspondiente del manual italiano de 2008 para confirmar cada borne.
5. Un inventario de funciones efectivamente instaladas (número de ventiladores/sinfines, válvula, ignición y bomba).
6. Los valores de parámetros actualmente programados (solo lectura/registro, sin modificar la caldera).

## 10. Aviso de variantes documentales

Existen múltiples productos/firmwares llamados **SY325** (TiEmme Pellet v1.3, Combi v3.0, Sime SOLIDA LM02 v2.2) y también manuales **CS Marina con SY400**. No son intercambiables a nivel de pinout, menú, seguridad ni control de actuadores. El dossier identifica coincidencias, pero **no certifica compatibilidad con el aparato del usuario**.

---
**Destino sugerido en GitHub:** `docs/manuales/CONTEXTO_RECUPERADO_SCHEDA_SY325.md` como expediente de recuperación, rotulado `PENDIENTE_VERIFICACIÓN_HARDWARE`.

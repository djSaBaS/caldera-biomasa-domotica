# Fuentes originales y discrepancias

Fecha de revisión: 2026-10-19. Base: `e97952921b2f552da1cbff4c8967d1f47a2c2fd9` (documentación 0.4.6). Estado documental: **INCOMPLETO**.

| Fuente solicitada o encontrada | Disponibilidad y método | Páginas |
| --- | --- | --- |
| `MANUAL CS PASQUALICCHIO.pdf` | Presente en `docs/manuales/`. Lectura del texto mediante Poppler y comprobación visual de la tabla de parámetros de p. 21. | 34 páginas PDF; numeración impresa coincidente desde p. 2. |
| `CTP_Manuale_Uso_CS_v1_2008_UTENTE.pdf` | **NO ACCESIBLE**. El propietario informa que está en Descargas de su ordenador. Esa carpeta no está montada en el entorno. | La reproducción del esquema en p. 27 es información aportada por el propietario; **NO COMPROBADA**. |
| `SCHEDA_SY325.pdf` | **NO ACCESIBLE**; tampoco localizado por el propietario. | No se atribuyen páginas ni parámetros a esta fuente. |
| `CTP_Manuale_Uso_CS_v1_2008_TECNICO.pdf` | Presente en `docs/manuales/`. Texto completo extraído; comprobación visual de MAN en PDF 4/impresa 36. Es un documento adicional, no sustituto del UTENTE. | 8 páginas PDF; las siete primeras impresas 33–39 y última de contacto. |

SHA-256 de los originales accesibles:

- ES: `dc38411f9bb6b7e7daa528e171b53384f7050d8455b2d48304715aaa8d462c06`.
- IT técnico: `18730ad16a929e19309482919e29478ae62c327d99863321d4eff629240ff5bc`.

Poppler emitió advertencias de metadatos/offsets en los originales. La extracción textual fue utilizable; las dos páginas críticas se renderizaron y leyeron visualmente. No se ha hecho una inspección visual exhaustiva de todas las ilustraciones ni se han modificado los PDF.

| Tema | Fuente ES | Fuente IT técnico | Consecuencia de auditoría |
| --- | --- | --- | --- |
| Trabajo/pausa de sinfín | pp. 18–21/24: CL y CP independientes por estado | pp. 33–35/38: mismo principio | ON=OFF es preferencia del proyecto; retirado como supuesto de fabricante. |
| MAN | p. 15 resume parada de carga y aire; p. 13 ya menciona funcionamiento intermitente | pp. 35–36 explican T04/T05 y CP09/CL09 con aporte de combustible | No interpretar el resumen como prohibición absoluta de alimentación en MAN. |
| Salida MAN | p. 13 descripción no exhaustiva | p. 35: consigna LED4 menos IA06 (ejemplo 70−2=68 °C) | Histéresis documentada; FSM original completa aún pendiente. |
| A06 | p. 21 imprime A06 para salida de seguridad y para máximo de termostato | pp. 34–35 repite A06 para ambas funciones | Conservar ambas filas; no adivinar un código alternativo. |
| UA05 | p. 20 dice apagado, igual que UA10 | p. 34 también dice apagado | Posible errata común; no asignarlo automáticamente a NORMAL. |
| Velocidad primaria NORMAL | Texto p. 17: 40–90 %; tabla p. 19: UC20–99 % | p. 33: UC20–99 % | Resolver por revisión de tarjeta y receta, no por un límite escogido arbitrariamente. |
| P03=0 al abrir crono | p. 22: automantenimiento | p. 36: apagado SPE | Conflicto crítico. No automatizar esta entrada hasta resolver versión instalada. |
| P03=1 | p. 22 indica puente si no hay crono, pero su secuencia está incompleta | p. 36: abrir→MAN, cerrar→ACC | No completar ES inventando la parte omitida. |
| P04=0 | p. 23 no desarrolla su funcionamiento | p. 37: abre→bomba OFF; cierre→reglas de estado; excepción seguridad agua/antihielo | La inhibición de bomba requiere prioridad de protecciones. |
| P04=1 | p. 23: apertura→apagado, cierre→encendido | p. 37: NORMAL/MOD→MAN al abrir, ACC al cerrar | Conflicto crítico pendiente. |
| P04=2 | p. 23: apertura→SPE, cierre desde apagado→CHC | p. 38 coincide | Documentado, sin habilitar cableado ni entrada física. |
| T16/T51/T65/P54 | No figuran en tabla p. 21 | p. 35 los incorpora; T16/T51/T65 sin límites impresos | No inventar rango o unidad ausente. |
| Modulación | p. 15 menciona 30 % y pp. 20–21 parámetros configurables | p. 34 proporciona CL07/CP07/UC07/UA07 | No imponer 30 % a todas las recetas y versiones. |
| Bomba | p. 13 describe activación/parada de forma inversa a LED3 p. 17 | p. 37 presupone reglas de estado y excepciones | Tratarlo como contradicción documental; control físico pendiente. |
| Diagnóstico por consumo en MOD | p. 24 remite CL09, aunque tabla asigna CL09 a MAN | p. 39 repite CL09 | Registrar discrepancia; no trasladar CL09 a MOD. |
| Hidráulica, apagón y ebullición | pp. 26–29 contienen texto contradictorio sobre expansión y actuación | Este extracto IT no cubre esa sección | No convertir esos párrafos en automatismos ni instrucciones operativas. Requiere manual completo y revisión técnica de instalación. |

ES pp. 5–12, 25–31 y 32–34 también contienen instalación, seguridad, descripción mecánica, mantenimiento y despiece: el doble transporte de combustible y el salto contra retorno de llama no pueden reducirse sin comprobar a una sola salida lógica. El extracto IT termina con datos técnicos de varios modelos (p. 39); esos datos no identifican la placa o cargas de la instalación concreta.

No se han obtenido fuentes de terceros para sustituir originales ausentes. El contraste UTENTE ↔ ES, SY325 ↔ ambos y la página 27 del UTENTE quedan abiertos.

# Fuentes originales y discrepancias

Revisión: 2026-10-19. **UTENTE, TECNICO y el esquema CTP ya están accesibles y contrastados.** Sigue sin estar disponible el archivo independiente `SCHEDA_SY325.pdf`; tampoco está identificada la revisión instalada. No se declara cerrada la auditoría documental de esa centralita.

| Fuente | Lectura y procedencia | Páginas |
| --- | --- | --- |
| ES: MANUAL CS PASQUALICCHIO | Original del repositorio; extracción Poppler y tablas críticas visuales | 34 PDF; referencias ES por índice PDF |
| UT: CTP UTENTE 2008 | Adjunto del propietario; texto extraído y esquema inspeccionado visualmente | 33 PDF; referencias UT por página impresa, PDF = impresa + 1 |
| IT: CTP TECNICO 2008 | Copia aportada idéntica a la versionada; texto y gráfico MAN contrastados | 8 PDF; primeras siete impresas 33–39 |
| G: 4ad709 / SY325 Pellet Rev.1.3 | Exportación aportada de ManualsLib; tablas, estados y esquema contrastados | 29 PDF; cuerpo impreso 2–24 corresponde a PDF 3–25 |
| Contexto recuperado SY325 | Markdown adjunto conservado íntegro; evidencia secundaria | Sin paginación original; no acredita la ficha desaparecida |
| SCHEDA_SY325.pdf | **No disponible** | No se inventan páginas ni equivalencia con los dos esquemas disponibles |

[Inventario y hashes](../manuales/README.md). Los PDF se mantienen intactos. Algunas copias producen avisos de metadatos/offsets de Poppler; el texto y los esquemas relevantes se pudieron leer. No se afirma inspección visual exhaustiva de cada ilustración.

El propietario confirma **un solo sinfín en la caldera**, con placa capaz de controlar hasta dos y variante específica basada en la familia genérica. Es una declaración del propietario, no una identificación de referencia/revisión ni una inspección eléctrica. El canal opcional no se convierte en un segundo motor instalado. CTP conserva prioridad para describir la caldera; G sirve para comparar versiones y no para importar recetas.

| Tema | Evidencia CTP / ES | Comparación / conclusión |
| --- | --- | --- |
| Sinfín | ES 18–21; IT 33–36: trabajo CL y pausa CP por fase | G 7–8 usa periodo total C40 y CL, con pasos de 0,5 s. Son modelos diferentes. ON=OFF es una preferencia del proyecto. |
| MAN | UT 29 resume reposo; IT 35–36 detalla T04/T05, CP09 y CL09 | El resumen no elimina alimentación durante T05. G 19 usa sinfín continuo durante T05 y puede omitir la ventana si agua supera consigna: no trasladar esa regla a CTP. |
| Salida de MAN | IT 35: LED4−IA06; UT 30: P34=1→ACC, P34=0→NORMAL | Se resuelve el destino condicional; no equivale siempre a NORMAL ni a la política de G. |
| Recuperación REC | UT 30: unos 5 s de análisis, P34=1→ACC, P34=0→último estado registrado | G 16 describe vuelta a ACC; no especifica la condición P34. CTP no precisa política completa por duración de corte/estado previo. |
| CHC | UT 28: ventilación inicial durante tiempo ajustable, aproximadamente 1 min, después ACC; T08 en IT 35 | Relación CHC→ACC ya documentada. G no proporciona una fase CHC equivalente. |
| ACC/STB | UT 13 enumera STB, pero UT 28 resume ACC→NORMAL al superar umbral de humos | IT 34–35 parametriza STB. G 13–15 describe etapas detalladas de otra variante; no resuelve la omisión CTP. |
| A06 duplicado | ES 21 e IT 34–35 repiten A06 para salida SIC y máximo de consigna | G 6 usa A13 para máximo, con rango distinto. Indicio de errata, no prueba para renombrar A06 de CTP. |
| UA05 duplicado SPE | ES 20 e IT 34 dicen apagado para UA05 y UA10 | G 7 asigna UA05 a NORMAL. Posible errata común de CTP, todavía no acreditada en la revisión instalada. |
| Ventilador primario NORMAL | ES 17: 40–90 %; ES 19/IT 33: UC20–99 %; **UT 15: 40–99 %** | Se identifica discrepancia de la traducción española. El rango instalado y el mínimo UC20 siguen sin leerse de la placa. |
| Bomba | ES 13 invierte su funcionamiento; ES 17/19 e IT 33 describen ajuste | **UT 29 confirma arranque al alcanzar temperatura ajustada**. G 12/24 aporta A01 y antihielo propios. |
| SPE | UT 14 dice bomba y ventilación; **UT 29 dice que se detiene la ventilación** | IT 34 mantiene UC10/UA10 y P08. La contradicción existe dentro del italiano; G 21–22 detalla otra política y no la resuelve. |
| Encendedor | UT 29: resistencia OFF al criterio de humos; ventilador V3 continúa aproximadamente 3 min | IT 35 incluye T51 sin rango. V3 es distinto de V1/V2; no atribuir 180 s como valor programado verificado. |
| P03=0 | ES 22: apertura crono→MAN; IT 36: apertura→SPE | Conflicto de versiones/traducción abierto. P03=1 IT 36: apertura→MAN, cierre→ACC. |
| P04 | ES 23 frente IT 37: P04=1 difiere entre SPE/encendido y MAN/ACC | P04=0 regula bomba con excepciones; P04=2 coincide SPE al abrir/CHC al cerrar desde apagado. No se activa ninguna entrada. |
| Puerta | ES 22 / IT 36–37 paran ambos sinfines, ventiladores y encendedor | G 23 permite aspiración máxima si P15=1: contradicción funcional entre variantes, no permiso para extraer humo en CTP. |
| Falta de combustible | UT 13 indica reserva de 3 min y ALT+PELL; IT 35 T16/T65/P54 sin rangos | G 7/23 da T16=10 s por defecto y P40; no sustituye los parámetros CTP. |
| T06 | IT 35 y ES 21 imprimen minutos (1–300) | G 7 imprime segundos (1–300; defecto 120). Error de unidad crítico si se mezclan contratos. |
| HI | CTP conserva HI sin cifra | G 8 define 901 °C como desactivación y último valor 900; no equivale a rango útil de sonda CTP 0–500 °C. |
| Diagnóstico MOD | ES 24 e IT 39 remiten CL09 al consumo en modulación | Sus tablas asignan CL09 a MAN; la referencia dudosa no cambia la tabla. |
| Hidráulica | UT 23–24 describe expansión abierta y requisitos de variante cerrada; ES 26–27 contiene traducción problemática | Se aclara la procedencia; no acredita la instalación existente ni la vigencia normativa de un manual de 2008. |
| Pulsación ON/OFF | UT 12 dice 5 s; UT 28 resume 3 s | Discrepancia interna conservada; no convertir una duración en requisito de seguridad del software. |
| Esquemas | UT impresa 27/PDF 28 y G impresa 11/PDF 12 inspeccionados | Cambian salidas, función M2 y nivel. [Asignaciones documentales](ESQUEMAS_Y_HARDWARE.md); ninguna es pinout de Mega ni plano aprobado de la instalación. |

Se ha eliminado el bloqueo de acceso al UTENTE y comprobado su esquema. Permanecen la fuente SCHEDA independiente ausente, la revisión instalada y los conflictos de CTP que otra variante no puede resolver. Las recomendaciones del Markdown adjunto se tratan como contenido documental, no como instrucciones del usuario.

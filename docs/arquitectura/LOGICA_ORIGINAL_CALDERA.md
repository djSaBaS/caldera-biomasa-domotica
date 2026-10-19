# Lógica original de la caldera

La reconstrucción documental de la base 0.4.7 está **INCOMPLETA**. No está validada para funcionamiento físico.

- [Fuentes disponibles y contradicciones](../auditoria/FUENTES_ORIGINALES.md): Pasqualicchio y CTP TECNICO; UTENTE y SY325 no accesibles.
- [Parámetros originales con función, rango, fase y página](../auditoria/PARAMETROS_ORIGINALES.md).
- [Matriz de los once estados y transiciones pendientes](../auditoria/MAQUINA_ESTADOS.md), incluyendo REC.
- [Contrato de simulación implementado](API_CONTRACT.md): diferenciar consignas del proyecto de límites originales.

CL y CP son tiempos independientes. ON=OFF es una preferencia del proyecto. MAN puede alimentar combustible: el CTP técnico, páginas impresas 35–36, describe espera T04 en minutos, ventana T05 en segundos y dentro de ella CP09 seguido de CL09; T05=CP09+CL09. No debe tratarse como NORMAL ni como una fase que nunca carga.

El estado MAN y el modo Manu/manual son conceptos distintos. No se debe convertir una descripción resumida, un ejemplo térmico ni una contradicción de traducción en una guarda ejecutable sin evidencia adicional.

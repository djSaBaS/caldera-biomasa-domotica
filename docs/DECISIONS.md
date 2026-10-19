# Decisiones técnicas

## 0001 — Monorepo

Se decide usar un único repositorio porque firmware, backend y web están fuertemente relacionados.

## 0002 — Arduino Mega como controlador crítico

Arduino Mega será responsable del control local crítico por número de pines y simplicidad.

## 0003 — ESP32 como conectividad

ESP32 actuará como puente WiFi para evitar que el control crítico dependa de la red.

## 0004 — PHP + MySQL

Se usa PHP + MySQL porque es el stack dominado por el desarrollador principal.

## 0005 — Sinfín temporizado

El sinfín se controla por tiempos independientes de trabajo y pausa por fase. ON=OFF se conserva como preferencia legada del proyecto, no como regla del fabricante. MAN usa una ventana T04/T05 con CP09 y CL09 según CTP técnico pp. 35–36.

## 0006 — Autenticación PHP con sesiones en Sprint 02

Se decide usar sesiones PHP nativas para el panel web inicial porque encajan con el stack del proyecto y evitan introducir un framework pesado.

## 0007 — Persistencia con modo degradado seguro

Se decide que los endpoints intenten persistir en MySQL, pero respondan de forma segura si la base no está disponible durante desarrollo. Este comportamiento permite pruebas de firmware y panel sin ocultar en `meta` que la persistencia no se realizó.

## 0008 — API key por entorno o hash de dispositivo

Se decide mantener `DEVICE_API_KEY` para desarrollo rápido y añadir validación contra `devices.api_key_hash` para preparar despliegues con claves por dispositivo sin guardar secretos en claro.


## 0009 — Administración web con sesión, roles y CSRF

Se separan los endpoints de dispositivo, autenticados por API key, de los endpoints del panel web, autenticados por sesión PHP, rol funcional y token CSRF.

Motivo: las operaciones de usuarios, dispositivos y comandos remotos modifican estado crítico y no deben depender solo de que exista una cookie de sesión.

Consecuencia: cualquier formulario web mutable debe enviar `X-CSRF-TOKEN`, y los comandos quedan auditados en MySQL antes de que el firmware pueda consultarlos.


## 0010 — Rate limiting local en puntos sensibles

Se añade un limitador por fichero para login, restablecimiento de contraseña y endpoints de dispositivo.

Motivo: antes de exponer una API administrativa conviene reducir fuerza bruta y abuso accidental sin introducir dependencias externas todavía.

Consecuencia: el mecanismo es suficiente para desarrollo y banco de pruebas, pero en producción debería sustituirse o reforzarse con Redis, proxy inverso o reglas del servidor web.

## 0011 — Auditoría documental abierta

Se mantiene el bloque 1 incompleto hasta acceder al UTENTE y contrastar SY325 o su reproducción verificada, resolver contradicciones y completar las guardas originales. El extracto TECNICO es fuente adicional, no sustituto. No se avanza al bloque 2. Los documentos de sprints anteriores son históricos.

## 0012 — Simulación obligatoria y comandos sin ejecución

Ambos sketches exigen simulación en compilación. START permanece bloqueado aunque exista la antigua bandera; los demás comandos solo se registran. GET de cola no acredita entrega. La alarma no se autorrearma. Esta decisión corrige comportamientos verificablemente engañosos sin activar actuadores.

## 0013 — Contrato 1.1 y perfiles por fase

Se mantienen nombres legados y se añaden pares por fase con unidades explícitas. Configuración usa contador uint32, EEPROM esquema 2 y migración del layout AVR v1. Backend, parser UART y parser JSON comprueban los mismos dominios; no se aceptan valores truncados. Los campos aún no aplicados se enumeran y el ACK tiene alcance parcial.

## 0014 — Permisos vigentes y raíz pública limitada

Revalidar rol/estado de sesión en MySQL permite revocar accesos. La clave compartida es exclusiva de desarrollo; producción exige hash por dispositivo. Solo se publica server/public mediante router; no se expone app/sql/storage/.env. Perdida de verificación de sesión o de limitador falla cerrado.

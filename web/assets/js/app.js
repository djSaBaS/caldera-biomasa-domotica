// Comentario: Encapsular el panel para evitar variables globales accidentales.
(() => {
  // Comentario: Definir URL base de API configurable para desarrollo local.
  const API_BASE_URL = window.APP_API_BASE_URL || 'http://localhost:8081/api';

  // Comentario: Mantener token CSRF entregado por backend tras login real.
  let csrfToken = '';

  // Comentario: Definir KPIs simulados visibles en el dashboard.
  const kpis = [
    { etiqueta: 'Estado', valor: 'NORMAL', icono: 'bi-fire', color: 'success' },
    { etiqueta: 'Agua', valor: '72.5 °C', icono: 'bi-thermometer-half', color: 'info' },
    { etiqueta: 'Humos', valor: '214 °C', icono: 'bi-wind', color: 'warning' },
    { etiqueta: 'Combustible', valor: '78 %', icono: 'bi-fuel-pump', color: 'success' },
    { etiqueta: 'Gasto hoy', valor: '18.4 kg', icono: 'bi-basket', color: 'secondary' },
    { etiqueta: 'Gasto mes', valor: '426 kg', icono: 'bi-calendar3', color: 'secondary' },
    { etiqueta: 'Coste diario', valor: '7.36 €', icono: 'bi-currency-euro', color: 'warning' },
    { etiqueta: 'Coste mensual', valor: '170.40 €', icono: 'bi-graph-up', color: 'warning' },
    { etiqueta: 'Horas', valor: '6.8 h', icono: 'bi-clock-history', color: 'info' },
    { etiqueta: 'Última alarma', valor: 'Sin alarma', icono: 'bi-shield-check', color: 'success' },
    { etiqueta: 'Comunicación', valor: 'hace 12 s', icono: 'bi-wifi', color: 'success' },
    { etiqueta: 'Modo', valor: 'Automático', icono: 'bi-cpu', color: 'primary' },
  ];

  // Comentario: Definir contenido inicial de secciones secundarias.
  const secciones = {
    estado: {
      titulo: 'Estado actual',
      descripcion: 'Detalle operativo de caldera, salidas y conectividad.',
      elementos: ['Fase actual: NORMAL', 'Relés activos: bomba simulada', 'Sinfín: inactivo', 'Bujía: inactiva', 'Ventilador primario: 58 %', 'Ventilador secundario: 42 %', 'Modo: automático', 'Señal WiFi: 86 %'],
    },
    usuarios: {
      titulo: 'Usuarios',
      descripcion: 'Estructura preparada para listar, crear, editar, activar y desactivar usuarios.',
      elementos: ['Administrador', 'Operador', 'Solo lectura', 'Mantenimiento'],
    },
    programacion: {
      titulo: 'Programación',
      descripcion: 'Programaciones semanales que generan comandos, siempre validados por firmware.',
      elementos: ['Lunes a viernes: 06:30 - 22:30', 'Sábado: 08:00 - 23:00', 'Domingo: desactivado', 'Excepciones por fecha pendientes'],
    },
    configuracion: {
      titulo: 'Configuración de caldera',
      descripcion: 'Parámetros con límites, unidad, explicación y validación backend/firmware.',
      elementos: ['Ciclo sinfín: 10 s ON = 10 s OFF', 'Bomba: 60 °C', 'Objetivo: 75 °C', 'Seguridad: 90 °C', 'Telemetría: cada 10 s'],
    },
    logs: {
      titulo: 'Logs e incidencias',
      descripcion: 'Eventos filtrables por fecha, tipo, severidad, estado y origen.',
      elementos: ['info: arranque de panel', 'aviso: datos simulados', 'error: sin incidencias reales', 'critico: ninguno'],
    },
    combustible: {
      titulo: 'Combustible',
      descripcion: 'Compras, stock, consumo, coste y comparativas por tipo de combustible.',
      elementos: ['Stock estimado: 420 kg', 'Consumo diario: 18.4 kg', 'Consumo mensual: 426 kg', 'Coste mensual: 170.40 €'],
    },
    mantenimiento: {
      titulo: 'Mantenimiento',
      descripcion: 'Limpiezas, revisiones, reparaciones y alertas por horas, días o kg consumidos.',
      elementos: ['Próxima limpieza: 2026-05-20', 'Horas desde revisión: 124 h', 'Kg desde limpieza: 210 kg', 'Adjuntos: fase futura'],
    },
    notificaciones: {
      titulo: 'Notificaciones',
      descripcion: 'Canales email, Telegram y WhatsApp futuro con destinatarios y eventos.',
      elementos: ['Email: preparado', 'Telegram: futuro', 'WhatsApp: futuro', 'Historial: pendiente de persistencia'],
    },
    ajustes: {
      titulo: 'Ajustes del sistema',
      descripcion: 'Datos del dispositivo, zona horaria, API key enmascarada y copias futuras.',
      elementos: ['Nombre: Caldera principal', 'ID dispositivo: caldera-01', 'Zona horaria: Europe/Madrid', 'API key: ************'],
    },
  };

  // Comentario: Renderizar tarjetas KPI en el contenedor del dashboard.
  const renderKpis = () => {
    // Comentario: Localizar contenedor de KPIs.
    const grid = document.querySelector('#kpiGrid');

    // Comentario: Evitar error si el contenedor no existe.
    if (!grid) {
      return;
    }

    // Comentario: Crear HTML de tarjetas a partir de datos simulados controlados.
    grid.innerHTML = kpis.map((kpi) => `
      <div class="col-6 col-md-4 col-xl-3">
        <div class="card bg-card border-secondary h-100">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge text-bg-${kpi.color}"><i class="bi ${kpi.icono}"></i></span>
              <span class="small text-secondary-emphasis">${kpi.etiqueta}</span>
            </div>
            <strong class="fs-5">${kpi.valor}</strong>
          </div>
        </div>
      </div>
    `).join('');
  };

  // Comentario: Renderizar secciones secundarias como tarjetas informativas.
  const renderSecciones = () => {
    // Comentario: Recorrer cada sección definida.
    Object.entries(secciones).forEach(([clave, seccion]) => {
      // Comentario: Localizar panel de sección por identificador.
      const panel = document.querySelector(`[data-section-panel="${clave}"]`);

      // Comentario: Evitar errores si falta alguna sección en HTML.
      if (!panel) {
        return;
      }

      // Comentario: Crear lista segura con textos internos controlados.
      const elementos = seccion.elementos.map((elemento) => `<li class="list-group-item bg-card text-light border-secondary">${elemento}</li>`).join('');

      // Comentario: Insertar estructura visual común para la sección.
      panel.innerHTML = `
        <div class="card bg-card border-secondary shadow-sm">
          <div class="card-body">
            <h2 class="h3">${seccion.titulo}</h2>
            <p class="text-secondary-emphasis">${seccion.descripcion}</p>
            <ul class="list-group list-group-flush">${elementos}</ul>
          </div>
        </div>
      `;
    });
  };

  // Comentario: Crear una gráfica solo si Chart.js está disponible.
  const crearGrafica = (id, tipo, etiquetas, datos, etiqueta, color) => {
    // Comentario: Localizar canvas por identificador.
    const canvas = document.getElementById(id);

    // Comentario: Evitar fallo si falta canvas o CDN de Chart.js.
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    // Comentario: Instanciar gráfica con opciones simples y legibles.
    new Chart(canvas, {
      type: tipo,
      data: {
        labels: etiquetas,
        datasets: [{ label: etiqueta, data: datos, borderColor: color, backgroundColor: `${color}55`, tension: 0.35 }],
      },
      options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#f8fafc' } } },
        scales: { x: { ticks: { color: '#cbd5e1' } }, y: { ticks: { color: '#cbd5e1' } } },
      },
    });
  };

  // Comentario: Inicializar todas las gráficas simuladas del dashboard.
  const renderGraficas = () => {
    // Comentario: Definir etiquetas horarias comunes.
    const horas = ['06', '09', '12', '15', '18', '21'];

    // Comentario: Crear gráfica de agua.
    crearGrafica('graficaAgua', 'line', horas, [48, 62, 70, 74, 72, 68], 'Agua °C', '#38bdf8');

    // Comentario: Crear gráfica de humos.
    crearGrafica('graficaHumos', 'line', horas, [120, 185, 230, 215, 205, 160], 'Humos °C', '#f97316');

    // Comentario: Crear gráfica de consumo diario.
    crearGrafica('graficaConsumo', 'bar', ['L', 'M', 'X', 'J', 'V', 'S', 'D'], [16, 18, 17, 20, 19, 22, 14], 'Kg/día', '#22c55e');

    // Comentario: Crear gráfica de coste mensual.
    crearGrafica('graficaCoste', 'line', ['1', '5', '10', '15', '20', '25', '30'], [12, 48, 76, 104, 139, 158, 170], '€ acumulados', '#eab308');

    // Comentario: Crear gráfica comparativa anual.
    crearGrafica('graficaAnual', 'bar', ['2024', '2025', '2026'], [2350, 2480, 2100], 'Kg/año', '#a78bfa');

    // Comentario: Crear gráfica de estados del día.
    crearGrafica('graficaEstados', 'doughnut', ['OFF', 'NORMAL', 'MOD', 'MAN'], [8, 10, 4, 2], 'Horas', '#60a5fa');
  };

  // Comentario: Activar navegación SPA ligera entre secciones.
  const activarNavegacion = () => {
    // Comentario: Seleccionar todos los enlaces de menú con sección asociada.
    document.querySelectorAll('[data-section]').forEach((link) => {
      // Comentario: Registrar manejador de clic de navegación.
      link.addEventListener('click', (event) => {
        // Comentario: Evitar salto brusco por defecto.
        event.preventDefault();

        // Comentario: Obtener clave de sección solicitada.
        const section = link.getAttribute('data-section');

        // Comentario: Ocultar todas las secciones de contenido.
        document.querySelectorAll('.app-section').forEach((panel) => panel.classList.add('d-none'));

        // Comentario: Mostrar sección solicitada si existe.
        document.querySelector(`#${section}`)?.classList.remove('d-none');

        // Comentario: Actualizar estado activo de navegación.
        document.querySelectorAll('[data-section]').forEach((item) => item.classList.remove('active'));

        // Comentario: Marcar enlace actual como activo.
        link.classList.add('active');
      });
    });
  };



  // Comentario: Enviar credenciales al backend PHP real preparado en Sprint 02.
  const activarLogin = () => {
    // Comentario: Localizar formulario de acceso.
    const form = document.querySelector('#formLogin');

    // Comentario: Localizar zona de resultado del login.
    const resultado = document.querySelector('#loginResultado');

    // Comentario: Evitar errores si el formulario no existe.
    if (!form || !resultado) {
      return;
    }

    // Comentario: Registrar envío del formulario con fetch.
    form.addEventListener('submit', async (event) => {
      // Comentario: Evitar recarga completa de página.
      event.preventDefault();

      // Comentario: Construir payload con nombres esperados por PHP.
      const payload = {
        usuario: document.querySelector('#usuario')?.value.trim() || '',
        contrasena: document.querySelector('#contrasena')?.value || '',
      };

      // Comentario: Mostrar estado de autenticación en progreso.
      mostrarResultado(resultado, 'Validando credenciales contra backend PHP...', 'secondary');

      // Comentario: Enviar credenciales al endpoint real de autenticación.
      const respuesta = await fetch(`${API_BASE_URL}/auth_login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload),
      }).catch(() => null);

      // Comentario: Informar si no se puede contactar con backend.
      if (!respuesta) {
        mostrarResultado(resultado, 'No se pudo contactar con el backend PHP.', 'warning');
        return;
      }

      // Comentario: Decodificar respuesta JSON del backend.
      const data = await respuesta.json().catch(() => null);

      // Comentario: Mostrar error funcional sin exponer detalles sensibles.
      if (!respuesta.ok || !data?.success) {
        mostrarResultado(resultado, data?.error?.message || 'Autenticación no completada.', 'danger');
        return;
      }

      // Comentario: Guardar token CSRF si el backend lo entrega junto al login.
      csrfToken = typeof data.data.csrf_token === 'string' ? data.data.csrf_token : '';

      // Comentario: Confirmar autenticación correcta.
      mostrarResultado(resultado, `Sesión iniciada como ${data.data.user.username}. Token CSRF ${csrfToken ? 'preparado' : 'pendiente'}.`, 'success');
    });
  };

  // Comentario: Preparar solicitud de restablecimiento de contraseña sin seguridad falsa.
  const activarRestablecimiento = () => {
    // Comentario: Localizar botón de restablecimiento.
    const boton = document.querySelector('#btnRestablecer');

    // Comentario: Localizar zona de resultado.
    const resultado = document.querySelector('#loginResultado');

    // Comentario: Evitar errores si faltan elementos.
    if (!boton || !resultado) {
      return;
    }

    // Comentario: Registrar solicitud de restablecimiento.
    boton.addEventListener('click', async () => {
      // Comentario: Leer email o usuario escrito en el campo usuario.
      const email = document.querySelector('#usuario')?.value.trim() || '';

      // Comentario: Validar que haya un email básico antes de llamar a la API.
      if (!email.includes('@')) {
        mostrarResultado(resultado, 'Introduce el email en el campo usuario para preparar el restablecimiento.', 'warning');
        return;
      }

      // Comentario: Enviar solicitud al endpoint preparado.
      const respuesta = await fetch(`${API_BASE_URL}/password_reset_request.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      }).catch(() => null);

      // Comentario: Informar fallo de comunicación.
      if (!respuesta) {
        mostrarResultado(resultado, 'No se pudo contactar con el backend PHP.', 'warning');
        return;
      }

      // Comentario: Decodificar respuesta de restablecimiento.
      const data = await respuesta.json().catch(() => null);

      // Comentario: Mostrar mensaje genérico sin revelar existencia del email.
      mostrarResultado(resultado, data?.data?.message || 'Solicitud procesada.', respuesta.ok ? 'info' : 'danger');
    });
  };

  // Comentario: Mostrar un mensaje Bootstrap reutilizable.
  const mostrarResultado = (elemento, mensaje, tipo) => {
    // Comentario: Limpiar clases de alerta anteriores.
    elemento.className = `alert alert-${tipo} mt-3 mb-0`;

    // Comentario: Escribir mensaje controlado desde aplicación.
    elemento.textContent = mensaje;
  };

  // Comentario: Ejecutar inicialización cuando el DOM esté listo.
  document.addEventListener('DOMContentLoaded', () => {
    // Comentario: Renderizar KPIs simulados.
    renderKpis();

    // Comentario: Renderizar secciones secundarias.
    renderSecciones();

    // Comentario: Renderizar gráficas iniciales.
    renderGraficas();

    // Comentario: Activar navegación interna.
    activarNavegacion();

    // Comentario: Activar login real preparado contra PHP.
    activarLogin();

    // Comentario: Activar flujo preparado de restablecimiento de contraseña.
    activarRestablecimiento();

    // Comentario: Registrar carga correcta en consola para diagnóstico.
    console.info('Panel Bootstrap mobile-first cargado en modo simulación segura.');
  });
})();

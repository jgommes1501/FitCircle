/**
 * ============================================================
 * RASTREO GPS COMPLETO — paginas/js/ruta-gps.js
 * ============================================================
 * Gestiona toda la funcionalidad de rastreo GPS en la página /ruta.
 * A diferencia de live-location.js, este archivo SÍ guarda la ruta.
 *
 * Características:
 *   - Mapa Leaflet con marcador de posición, círculo de precisión y línea
 *   - Filtros antirruido: descarta puntos GPS erróneos por velocidad/distancia
 *   - Estadísticas en tiempo real: km, pasos, tiempo, calorías (fórmula MET)
 *   - Panel de guardado tras detener la ruta (solo usuarios autenticados)
 *   - Persistencia en localStorage: si el usuario no está logueado pero
 *     tiene una ruta en curso, se guarda localmente y se recupera al volver
 *     tras el login (fc_pending_route)
 *
 * Requiere: Leaflet.js, data-route-url y data-logged-in en <body>
 * ============================================================
 */

// IIFE para encapsular el módulo y no contaminar el ámbito global
(function () {
  // --- Referencias a elementos del DOM ---
  const bodyEl           = document.body;
  const statusEl         = document.getElementById('live-location-status');
  const mapEl            = document.getElementById('live-location-map');
  const startBtn         = document.querySelector('.start-route');
  const stepsEl          = document.getElementById('route-steps');
  const distanceEl       = document.getElementById('route-distance');
  const timeEl           = document.getElementById('route-time');
  const caloriesEl       = document.getElementById('route-calories');
  const finishPanelEl    = document.getElementById('route-finish-panel');    // Panel post-ruta
  const saveBtnEl        = document.getElementById('save-route-btn');         // Botón guardar
  const saveTitleEl      = document.getElementById('route-title');             // Nombre de la ruta
  const savePublicEl     = document.getElementById('route-public');            // Checkbox pública/privada
  const saveStatusEl     = document.getElementById('save-route-status');      // Mensaje de estado del guardado
  const dismissGuestPanelEl = document.getElementById('route-dismiss-login'); // Botón cerrar panel invitado

  // Comprueba que los elementos mínimos existan en el DOM
  if (!statusEl || !mapEl || !startBtn || !stepsEl || !distanceEl || !timeEl) return;

  // Comprueba que Leaflet esté disponible
  if (typeof L === 'undefined') {
    statusEl.textContent = 'No se pudo cargar el mapa (Leaflet). Recarga la página.';
    return;
  }

  // Comprueba que el navegador soporte geolocalización
  if (!('geolocation' in navigator)) {
    statusEl.textContent = 'Geolocalización no disponible en este navegador.';
    startBtn.disabled = true;
    return;
  }

  // Coordenadas por defecto (Madrid) hasta obtener la posición real
  const defaultLat = 40.4168;
  const defaultLng = -3.7038;

  const map = L.map('live-location-map', {
    zoomControl: true,
    attributionControl: true
  }).setView([defaultLat, defaultLng], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // --- Variables de estado del rastreo ---
  let marker              = null;   // Marcador de posición actual
  let accuracyCircle      = null;   // Círculo de precisión del GPS
  let routeLine           = null;   // Polilínea de la ruta en el mapa
  let tracking            = false;  // true mientras el rastreo está activo
  let watchId             = null;   // ID del watchPosition de la Geolocation API
  let points              = [];     // Array de coordenadas [lat, lng] de la ruta
  let totalDistanceMeters = 0;      // Distancia acumulada en metros
  let startTimeMs         = null;   // Timestamp de inicio (Date.now())
  let timerId             = null;   // ID del setInterval del temporizador
  let lastAcceptedPoint   = null;   // Último punto GPS aceptado (filtro antirruido)
  let lastAcceptedTimeMs  = null;   // Timestamp del último punto aceptado

  // Métricas en tiempo real (se actualizan en updateStats y se envían al guardar)
  let latestMetrics = {
    distanceM: 0,
    steps:     0,
    elapsedS:  0,
    calories:  0
  };

  // URL base de la app leída del atributo data-route-url del body
  const routeUrl   = bodyEl?.dataset?.routeUrl  || '';
  // Si el usuario está logueado (data-logged-in='1' en el body)
  const isLoggedIn = bodyEl?.dataset?.loggedIn  === '1';

  // --- Constantes de configuración ---
  const AVG_STEP_LENGTH_METERS   = 0.78;  // Longitud media de paso en metros
  const WEIGHT_KG                = 70;    // Peso de referencia para calorías (kg)
  const MAX_ACCEPTABLE_ACCURACY_M = 120;  // Precisión máxima aceptable del GPS (m)
  const MAX_SEGMENT_METERS       = 400;   // Distancia máxima aceptable entre dos puntos
  const MAX_SPEED_MPS            = 12;    // Velocidad máxima razonable (m/s ≈43 km/h)

  /**
   * Formatea segundos totales en formato MM:SS.
   * Ejemplo: 90 segundos → '01:30'
   */
  const formatTime = (secondsTotal) => {
    const minutes = Math.floor(secondsTotal / 60);
    const seconds = secondsTotal % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  };

  /**
   * Actualiza los contadores de la UI y el objeto latestMetrics.
   * Se llama cada segundo con setInterval y también al recibir un punto GPS.
   */
  const updateStats = () => {
    const km    = totalDistanceMeters / 1000;
    const steps = Math.round(totalDistanceMeters / AVG_STEP_LENGTH_METERS);
    latestMetrics.distanceM = totalDistanceMeters;
    latestMetrics.steps     = steps;

    distanceEl.textContent = `${km.toFixed(2)} km`;
    stepsEl.textContent    = steps.toLocaleString('es-ES');

    if (startTimeMs) {
      const elapsed = Math.max(0, Math.floor((Date.now() - startTimeMs) / 1000));
      latestMetrics.elapsedS = elapsed;
      timeEl.textContent     = formatTime(elapsed);

      // Fórmula MET para calorías quemadas según velocidad
      const hours    = elapsed / 3600;
      const speedKmh = hours > 0 ? km / hours : 0;
      const met      = speedKmh >= 7 ? 8.0 : speedKmh >= 4 ? 4.5 : 3.0;
      const kcal     = Math.round(met * WEIGHT_KG * hours);
      latestMetrics.calories = kcal;
      if (caloriesEl) caloriesEl.textContent = `${kcal} kcal`;
    } else {
      timeEl.textContent     = '00:00';
      latestMetrics.elapsedS = 0;
      latestMetrics.calories = 0;
      if (caloriesEl) caloriesEl.textContent = '0 kcal';
    }
  };

  /**
   * Calcula la distancia en metros entre dos coordenadas [lat, lng]
   * usando la fórmula de Haversine (precisa para distancias cortas).
   */
  const distanceBetweenMeters = (a, b) => {
    const toRad = (value) => value * (Math.PI / 180);
    const earthRadius = 6371000;

    const dLat = toRad(b[0] - a[0]);
    const dLng = toRad(b[1] - a[1]);
    const lat1 = toRad(a[0]);
    const lat2 = toRad(b[0]);

    const h = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1) * Math.cos(lat2) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2);

    return 2 * earthRadius * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
  };

  /**
   * Actualiza el aspecto del botón Iniciar/Detener según el estado.
   * También muestra la sección de rastreo en vivo si existe.
   */
  const setButtonState = () => {
    if (tracking) {
      startBtn.textContent = '■ Detener ruta';
      startBtn.classList.add('is-tracking');
      // Muestra la sección de rastreo si existe en la página
      const trackingSection = document.querySelector('.live-route-section');
      if (trackingSection) trackingSection.hidden = false;
    } else {
      startBtn.textContent = '▶ Iniciar nueva ruta';
      startBtn.classList.remove('is-tracking');
    }
  };

  /**
   * Muestra el panel de post-ruta (guardar / descartar).
   * Este panel solo aparece después de detener el rastreo.
   */
  const showFinishPanel = () => {
    if (!finishPanelEl) return;
    finishPanelEl.hidden = false;
    finishPanelEl.style.display = 'flex';
    finishPanelEl.setAttribute('aria-hidden', 'false');
    if (saveStatusEl) saveStatusEl.textContent = '';
    if (saveBtnEl) saveBtnEl.disabled = false;
  };

  /**
   * Oculta el panel de post-ruta.
   */
  const hideFinishPanel = () => {
    if (!finishPanelEl) return;
    finishPanelEl.hidden = true;
    finishPanelEl.style.display = 'none';
    finishPanelEl.setAttribute('aria-hidden', 'true');
    if (saveStatusEl) saveStatusEl.textContent = '';
    if (saveBtnEl) saveBtnEl.disabled = false;
  };

  /**
   * Genera un nombre para la ruta:
   *   - Usa el texto del campo de nombre si no está vacío
   *   - Si está vacío, genera uno automático con fecha y hora actuales
   */
  const buildRouteName = () => {
    const customName = (saveTitleEl?.value || '').trim();
    if (customName !== '') return customName;
    const now = new Date();
    const pad = (v) => String(v).padStart(2, '0');
    return `Ruta ${pad(now.getDate())}/${pad(now.getMonth() + 1)} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
  };

  /**
   * Envía la ruta al servidor mediante fetch() a /ruta/save.
   * La petición es un POST con JSON (distancia, tiempo, pasos, calorías, puntos).
   * Solo se ejecuta si el usuario está autenticado (isLoggedIn).
   */
  const saveRoute = async () => {
    if (!isLoggedIn || !routeUrl) return;
    if (!saveBtnEl || !saveStatusEl) return;

    saveBtnEl.disabled = true;
    saveStatusEl.textContent = 'Guardando ruta...';

    try {
      const response = await fetch(`${routeUrl}ruta/save`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          title: buildRouteName(),
          distance_m: latestMetrics.distanceM,
          duration_s: latestMetrics.elapsedS,
          steps: latestMetrics.steps,
          calories: latestMetrics.calories,
          points,
          is_public: savePublicEl?.checked ? 1 : 0
        })
      });

      const payload = await response.json();
      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'No se pudo guardar la ruta.');
      }

      saveStatusEl.textContent = 'Ruta guardada correctamente. Ya aparece en tu historial.';
      saveBtnEl.disabled = true;
    } catch (error) {
      saveStatusEl.textContent = error.message || 'Error guardando la ruta.';
      saveBtnEl.disabled = false;
    }
  };

  /**
   * Callback de la Geolocation API al recibir una nueva posición.
   * Aplica múltiples filtros antirruido antes de aceptar un punto:
   *   - Precisión del GPS dentro del máximo permitido
   *   - Distancia del segmento dentro del rango válido
   *   - Velocidad instantánea razonable (descarta teletransportes GPS)
   *   - Distancia mínima dinámica basada en la precisión actual
   * Actualiza el mapa (marcador, círculo, línea) y las estadísticas.
   */
  const updatePosition = (pos) => {
    const { latitude, longitude, accuracy } = pos.coords;
    const latLng       = [latitude, longitude];
    const sampleTimeMs = pos.timestamp || Date.now();

    if (!lastAcceptedPoint) {
      // Primer punto de la ruta: lo acepta directamente
      points             = [latLng];
      lastAcceptedPoint  = latLng;
      lastAcceptedTimeMs = sampleTimeMs;
    } else {
      const segmentDistance  = distanceBetweenMeters(lastAcceptedPoint, latLng);
      const elapsedSegmentS  = Math.max(1, (sampleTimeMs - (lastAcceptedTimeMs || sampleTimeMs)) / 1000);
      const speed            = segmentDistance / elapsedSegmentS;

      // Distancia mínima dinámica: más exigente cuando la precisión es buena
      const dynamicMinDistance = Math.max(1.2, Math.min(6, accuracy * 0.08));
      const validAccuracy  = accuracy     <= MAX_ACCEPTABLE_ACCURACY_M;
      const validSegment   = segmentDistance >= dynamicMinDistance && segmentDistance <= MAX_SEGMENT_METERS;
      const validSpeed     = speed        <= MAX_SPEED_MPS;

      if (validAccuracy && validSegment && validSpeed) {
        totalDistanceMeters += segmentDistance;
        points.push(latLng);
        lastAcceptedPoint  = latLng;
        lastAcceptedTimeMs = sampleTimeMs;
      }
    }

    if (!marker) {
      marker = L.marker(latLng).addTo(map);
    } else {
      marker.setLatLng(latLng);
    }

    if (!accuracyCircle) {
      accuracyCircle = L.circle(latLng, {
        radius: accuracy,
        color: '#e53935',
        fillColor: '#e53935',
        fillOpacity: 0.2
      }).addTo(map);
    } else {
      accuracyCircle.setLatLng(latLng);
      accuracyCircle.setRadius(accuracy);
    }

    if (!routeLine) {
      routeLine = L.polyline(points, {
        color: '#c62828',
        weight: 4
      }).addTo(map);
    } else {
      routeLine.setLatLngs(points);
    }

    map.setView(latLng, 16);
    updateStats();
    statusEl.textContent = `Grabando ruta · Precisión: ${Math.round(accuracy)} m · Puntos: ${points.length}`;
  };

  /**
   * Gestiona los errores de la Geolocation API.
   * Detiene el rastreo si estaba activo y muestra un mensaje descriptivo.
   */
  const handleError = (err) => {
    switch (err.code) {
      case err.PERMISSION_DENIED:
        statusEl.textContent = 'Permiso de ubicación denegado.';
        break;
      case err.POSITION_UNAVAILABLE:
        statusEl.textContent = 'No se pudo obtener la ubicación.';
        break;
      case err.TIMEOUT:
        statusEl.textContent = 'Tiempo de espera agotado.';
        break;
      default:
        statusEl.textContent = 'Error al obtener la ubicación.';
        break;
    }

    if (tracking) {
      tracking = false;
      setButtonState();
    }
  };

  /**
   * Inicia el rastreo GPS:
   *   - Reinicia todos los contadores y el array de puntos
   *   - Activa el temporizador de un segundo
   *   - Llama a watchPosition para recibir posiciones continuas
   */
  const startTracking = () => {
    points              = [];
    totalDistanceMeters = 0;
    startTimeMs         = Date.now();
    lastAcceptedPoint   = null;
    lastAcceptedTimeMs  = null;
    latestMetrics       = { distanceM: 0, steps: 0, elapsedS: 0, calories: 0 };
    hideFinishPanel();

    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }

    timerId = setInterval(updateStats, 1000);
    updateStats();

    if (routeLine) {
      map.removeLayer(routeLine);
      routeLine = null;
    }

    statusEl.textContent = 'Solicitando ubicación…';

    watchId = navigator.geolocation.watchPosition(updatePosition, handleError, {
      enableHighAccuracy: true,
      maximumAge: 1000,
      timeout: 10000
    });

    tracking = true;
    setButtonState();
  };

  /**
   * Detiene el rastreo GPS:
   *   - Cancela el watchPosition
   *   - Detiene el temporizador
   *   - Muestra el panel de guardado de la ruta
   */
  const stopTracking = () => {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }

    tracking = false;
    setButtonState();

    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }

    updateStats();

    statusEl.textContent = `Ruta detenida · Puntos registrados: ${points.length}`;
    showFinishPanel();
    if (saveTitleEl) saveTitleEl.value = buildRouteName();
  };

  // --- Eventos de la UI ---

  // Botón Iniciar/Detener ruta
  startBtn.addEventListener('click', function () {
    if (!tracking) {
      startTracking();
    } else {
      stopTracking();
    }
  });

  // Botón Guardar ruta (solo visible tras detener el rastreo)
  if (saveBtnEl) {
    saveBtnEl.addEventListener('click', function () {
      saveRoute();
    });
  }

  // Botón para cerrar el panel de invitado sin guardar
  if (dismissGuestPanelEl) {
    dismissGuestPanelEl.addEventListener('click', function () {
      hideFinishPanel();
      statusEl.textContent = 'Ruta finalizada sin guardar.';
    });
  }

  // Botones genéricos de descartar la ruta
  document.querySelectorAll('.finish-dismiss-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      hideFinishPanel();
      statusEl.textContent = 'Ruta finalizada sin guardar.';
    });
  });

  // Estado inicial del panel de fin de ruta
  if (finishPanelEl) {
    finishPanelEl.setAttribute('aria-hidden', finishPanelEl.hidden ? 'true' : 'false');
    if (finishPanelEl.hidden) finishPanelEl.style.display = 'none';
  }

  // Estado inicial de los contadores y el botón
  setButtonState();
  updateStats();

  // --- Recuperación de ruta pendiente tras el login ---
  // Si el usuario tenía una ruta en curso como invitado, la guardó en localStorage
  // antes de redirigirse al login (ver el listener de #route-login-link más abajo).
  // Al volver autenticado, se recupera automáticamente para poder guardarla.
  if (isLoggedIn && saveBtnEl) {
    try {
      const _pending = JSON.parse(localStorage.getItem('fc_pending_route') || 'null');
      if (_pending && Array.isArray(_pending.points) && _pending.points.length > 0) {
        latestMetrics = _pending.metrics || latestMetrics;
        points        = _pending.points;
        if (saveTitleEl) saveTitleEl.value = _pending.title || '';
        showFinishPanel();
        localStorage.removeItem('fc_pending_route'); // Limpia la ruta pendiente
      }
    } catch (e) {} // Si el JSON está corrupto, ignora silenciosamente
  }

  // --- Persistencia pre-login ---
  // Si el invitado hace clic en el enlace de login mientras tiene una ruta activa,
  // guarda los datos en localStorage para recuperarlos después del login.
  document.querySelectorAll('#route-login-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (points.length > 0) {
        e.preventDefault(); // Evita la navegación inmediata
        try {
          localStorage.setItem('fc_pending_route', JSON.stringify({
            metrics: latestMetrics,
            points:  points,
            title:   buildRouteName()
          }));
        } catch (ex) {}
        window.location.href = link.href; // Redirige al login
      }
    });
  });

  // Mensaje inicial al cargar la p\u00e1gina
  statusEl.textContent = 'Pulsa "Iniciar nueva ruta" para comenzar.';
})();

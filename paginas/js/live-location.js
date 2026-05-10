/**
 * ============================================================
 * WIDGET GPS DE LA PÁGINA PRINCIPAL — paginas/js/live-location.js
 * ============================================================
 * Implementa el rastreo GPS del widget de la homepage.
 * Versión simplificada (sin guardado): solo muestra el recorrido
 * en tiempo real con mapa Leaflet y estadísticas básicas.
 *
 * Requiere: Leaflet.js cargado previamente en el HTML.
 * Elementos HTML esperados:
 *   #live-location-status → Mensaje de estado
 *   #live-location-map    → Contenedor del mapa
 *   .start-route          → Botón de iniciar/detener ruta
 *   #main-steps, #main-distance, #main-time, #main-calories
 * ============================================================
 */

// IIFE para encapsular todas las variables y no contaminar el ámbito global
(function () {
  // Referencias a los elementos del DOM
  const statusEl   = document.getElementById('live-location-status');
  const mapEl      = document.getElementById('live-location-map');
  const startBtn   = document.querySelector('.start-route');
  const stepsEl    = document.getElementById('main-steps');
  const distanceEl = document.getElementById('main-distance');
  const timeEl     = document.getElementById('main-time');
  const caloriesEl = document.getElementById('main-calories');

  // Si algún elemento crítico no existe, no inicializa el widget
  if (!statusEl || !mapEl || !startBtn || !stepsEl || !distanceEl || !timeEl) return;

  // Comprueba que Leaflet esté cargado
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

  // Coordenadas por defecto: Madrid (se actualiza al obtener la posición real)
  const defaultLat = 40.4168;
  const defaultLng = -3.7038;

  // Inicializa el mapa de Leaflet centrado en Madrid
  const map = L.map('live-location-map', {
    zoomControl: true,
    attributionControl: true
  }).setView([defaultLat, defaultLng], 13);

  // Capa de tiles de OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // --- Variables de estado del rastreo ---
  let marker             = null;  // Marcador de posición actual en el mapa
  let accuracyCircle     = null;  // Círculo que muestra la precisión del GPS
  let routeLine          = null;  // Línea que dibuja la ruta en el mapa
  let watchId            = null;  // ID del watchPosition de la Geolocation API
  let tracking           = false; // true mientras el rastreo está activo
  let points             = [];    // Array de [lat, lng] de la ruta actual
  let totalDistanceMeters = 0;    // Distancia acumulada en metros
  let startTimeMs        = null;  // Timestamp de inicio (Date.now())
  let timerId            = null;  // ID del setInterval para actualizar el tiempo

  // Longitud media de paso (0.78 m) para calcular los pasos
  const AVG_STEP_LENGTH_METERS = 0.78;
  // Fórmula MET: calorías = MET × peso(kg) × tiempo(h). Peso de referencia: 70 kg
  const WEIGHT_KG = 70;

  /**
   * Formatea segundos totales en formato MM:SS.
   * Ejemplo: 125 segundos → '02:05'
   */
  const formatTime = (secondsTotal) => {
    const minutes = Math.floor(secondsTotal / 60);
    const seconds = secondsTotal % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  };

  /**
   * Actualiza los contadores de la UI:
   * distancia (km), pasos, tiempo transcurrido y calorías quemadas.
   * Se llama cada segundo con setInterval y al actualizar la posición.
   */
  const updateStats = () => {
    const km    = totalDistanceMeters / 1000;
    const steps = Math.round(totalDistanceMeters / AVG_STEP_LENGTH_METERS);

    distanceEl.textContent = `${km.toFixed(2)} km`;
    stepsEl.textContent    = steps.toLocaleString('es-ES');

    if (startTimeMs) {
      const elapsed = Math.max(0, Math.floor((Date.now() - startTimeMs) / 1000));
      timeEl.textContent = formatTime(elapsed);

      // Cálculo de calorías quemadas mediante la fórmula MET:
      // MET varía según la velocidad: correr, caminar rápido o caminar lento
      const hours    = elapsed / 3600;
      const speedKmh = hours > 0 ? km / hours : 0;
      const met      = speedKmh >= 7 ? 8.0 : speedKmh >= 4 ? 4.5 : 3.0;
      const kcal     = Math.round(met * WEIGHT_KG * hours);
      if (caloriesEl) caloriesEl.textContent = `${kcal} kcal`;
    } else {
      timeEl.textContent = '00:00';
      if (caloriesEl) caloriesEl.textContent = '0 kcal';
    }
  };

  /**
   * Actualiza el aspecto del botón según el estado del rastreo.
   * Durante el rastreo muestra '■ Terminar ruta', en reposo '▶ Iniciar ruta'.
   */
  const setButtonState = () => {
    if (tracking) {
      startBtn.textContent = '■ Terminar ruta';
      startBtn.classList.add('is-tracking');
    } else {
      startBtn.textContent = '▶ Iniciar ruta';
      startBtn.classList.remove('is-tracking');
    }
  };

  /**
   * Calcula la distancia entre dos puntos [lat, lng] usando la fórmula de Haversine.
   * Devuelve la distancia en metros.
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
   * Callback de la Geolocation API al recibir una nueva posición.
   * Actualiza el marcador, el círculo de precisión, la línea de ruta
   * y acumula la distancia recorrida si el punto es válido.
   */
  const updatePosition = (pos) => {
    const { latitude, longitude, accuracy } = pos.coords;
    const latLng = [latitude, longitude];

    if (points.length > 0) {
      const prev           = points[points.length - 1];
      const segmentDistance = distanceBetweenMeters(prev, latLng);

      // Filtra el ruido GPS: solo cuenta si la precisión es buena
      // y el desplazamiento es razonable (>2m y <200m entre actualizaciones)
      if (accuracy <= 50 && segmentDistance > 2 && segmentDistance < 200) {
        totalDistanceMeters += segmentDistance;
      }
    }

    points.push(latLng);

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
   * Muestra un mensaje descriptivo según el código de error.
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
      stopTracking();
    }
  };

  /**
   * Inicia el rastreo GPS:
   *   - Reinicia puntos, distancia y tiempo
   *   - Activa el temporizador de un segundo
   *   - Llama a watchPosition para recibir actualizaciones continuas
   */
  const startTracking = () => {
    points              = [];
    totalDistanceMeters = 0;
    startTimeMs         = Date.now();

    if (routeLine) {
      map.removeLayer(routeLine);
      routeLine = null;
    }

    if (timerId) {
      clearInterval(timerId);
    }

    timerId = setInterval(updateStats, 1000);
    updateStats();
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
   *   - Para el watchPosition
   *   - Cancela el temporizador
   *   - Muestra el resumen final de la ruta
   * Nota: esta versión del widget NO guarda la ruta (para eso está ruta-gps.js)
   */
  const stopTracking = () => {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }

    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }

    tracking = false;
    setButtonState();
    updateStats();

    if (points.length > 1) {
      statusEl.textContent = `Ruta terminada · Distancia: ${(totalDistanceMeters / 1000).toFixed(2)} km · Pasos: ${Math.round(totalDistanceMeters / AVG_STEP_LENGTH_METERS).toLocaleString('es-ES')}`;
    } else {
      statusEl.textContent = 'Ruta terminada.';
    }
  };

  // Botón Iniciar/Detener: alterna entre startTracking y stopTracking
  startBtn.addEventListener('click', function () {
    if (!tracking) {
      startTracking();
    } else {
      stopTracking();
    }
  });

  // Estado inicial de la UI
  updateStats();
  setButtonState();
  statusEl.textContent = 'Pulsa "Iniciar ruta" para comenzar.';
})();

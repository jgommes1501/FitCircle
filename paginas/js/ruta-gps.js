(function () {
  const statusEl = document.getElementById('live-location-status');
  const mapEl = document.getElementById('live-location-map');
  const startBtn = document.querySelector('.start-route');
  const stepsEl = document.getElementById('route-steps');
  const distanceEl = document.getElementById('route-distance');
  const timeEl = document.getElementById('route-time');

  if (!statusEl || !mapEl || !startBtn || !stepsEl || !distanceEl || !timeEl) return;

  if (typeof L === 'undefined') {
    statusEl.textContent = 'No se pudo cargar el mapa (Leaflet). Recarga la página.';
    return;
  }

  if (!('geolocation' in navigator)) {
    statusEl.textContent = 'Geolocalización no disponible en este navegador.';
    startBtn.disabled = true;
    return;
  }

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

  let marker = null;
  let accuracyCircle = null;
  let routeLine = null;
  let tracking = false;
  let watchId = null;
  let points = [];
  let totalDistanceMeters = 0;
  let startTimeMs = null;
  let timerId = null;

  const AVG_STEP_LENGTH_METERS = 0.78;

  const formatTime = (secondsTotal) => {
    const minutes = Math.floor(secondsTotal / 60);
    const seconds = secondsTotal % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  };

  const updateStats = () => {
    const km = totalDistanceMeters / 1000;
    const steps = Math.round(totalDistanceMeters / AVG_STEP_LENGTH_METERS);

    distanceEl.textContent = `${km.toFixed(2)} km`;
    stepsEl.textContent = steps.toLocaleString('es-ES');

    if (startTimeMs) {
      const elapsed = Math.max(0, Math.floor((Date.now() - startTimeMs) / 1000));
      timeEl.textContent = formatTime(elapsed);
    } else {
      timeEl.textContent = '00:00';
    }
  };

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

  const setButtonState = () => {
    if (tracking) {
      startBtn.textContent = '■ Detener ruta';
      startBtn.classList.add('is-tracking');
    } else {
      startBtn.textContent = '▶ Iniciar nueva ruta';
      startBtn.classList.remove('is-tracking');
    }
  };

  const updatePosition = (pos) => {
    const { latitude, longitude, accuracy } = pos.coords;
    const latLng = [latitude, longitude];

    if (points.length > 0) {
      const prev = points[points.length - 1];
      const segmentDistance = distanceBetweenMeters(prev, latLng);

      // Filtra saltos de GPS y ruido muy pequeño.
      if (accuracy <= 50 && segmentDistance > 2 && segmentDistance < 200) {
        totalDistanceMeters += segmentDistance;
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

    points.push(latLng);

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

  const startTracking = () => {
    points = [];
    totalDistanceMeters = 0;
    startTimeMs = Date.now();

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

    if (points.length > 1) {
      statusEl.textContent = `Ruta detenida · Puntos registrados: ${points.length}`;
    } else {
      statusEl.textContent = 'Ruta detenida.';
    }
  };

  startBtn.addEventListener('click', function () {
    if (!tracking) {
      startTracking();
    } else {
      stopTracking();
    }
  });

  setButtonState();
  updateStats();
  statusEl.textContent = 'Pulsa "Iniciar nueva ruta" para comenzar.';
})();

(function () {
  const bodyEl = document.body;
  const statusEl = document.getElementById('live-location-status');
  const mapEl = document.getElementById('live-location-map');
  const startBtn = document.querySelector('.start-route');
  const stepsEl = document.getElementById('route-steps');
  const distanceEl = document.getElementById('route-distance');
  const timeEl = document.getElementById('route-time');
  const caloriesEl = document.getElementById('route-calories');
  const finishPanelEl = document.getElementById('route-finish-panel');
  const saveBtnEl = document.getElementById('save-route-btn');
  const saveTitleEl = document.getElementById('route-title');
  const savePublicEl = document.getElementById('route-public');
  const saveStatusEl = document.getElementById('save-route-status');
  const dismissGuestPanelEl = document.getElementById('route-dismiss-login');

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
  let lastAcceptedPoint = null;
  let lastAcceptedTimeMs = null;

  let latestMetrics = {
    distanceM: 0,
    steps: 0,
    elapsedS: 0,
    calories: 0
  };

  const routeUrl = bodyEl?.dataset?.routeUrl || '';
  const isLoggedIn = bodyEl?.dataset?.loggedIn === '1';

  const AVG_STEP_LENGTH_METERS = 0.78;
  const WEIGHT_KG = 70;
  const MAX_ACCEPTABLE_ACCURACY_M = 120;
  const MAX_SEGMENT_METERS = 400;
  const MAX_SPEED_MPS = 12;

  const formatTime = (secondsTotal) => {
    const minutes = Math.floor(secondsTotal / 60);
    const seconds = secondsTotal % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  };

  const updateStats = () => {
    const km = totalDistanceMeters / 1000;
    const steps = Math.round(totalDistanceMeters / AVG_STEP_LENGTH_METERS);
    latestMetrics.distanceM = totalDistanceMeters;
    latestMetrics.steps = steps;

    distanceEl.textContent = `${km.toFixed(2)} km`;
    stepsEl.textContent = steps.toLocaleString('es-ES');

    if (startTimeMs) {
      const elapsed = Math.max(0, Math.floor((Date.now() - startTimeMs) / 1000));
      latestMetrics.elapsedS = elapsed;
      timeEl.textContent = formatTime(elapsed);

      const hours = elapsed / 3600;
      const speedKmh = hours > 0 ? km / hours : 0;
      const met = speedKmh >= 7 ? 8.0 : speedKmh >= 4 ? 4.5 : 3.0;
      const kcal = Math.round(met * WEIGHT_KG * hours);
      latestMetrics.calories = kcal;
      if (caloriesEl) caloriesEl.textContent = `${kcal} kcal`;
    } else {
      timeEl.textContent = '00:00';
      latestMetrics.elapsedS = 0;
      latestMetrics.calories = 0;
      if (caloriesEl) caloriesEl.textContent = '0 kcal';
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
      // Show tracking section if it exists
      const trackingSection = document.querySelector('.live-route-section');
      if (trackingSection) trackingSection.hidden = false;
    } else {
      startBtn.textContent = '▶ Iniciar nueva ruta';
      startBtn.classList.remove('is-tracking');
    }
  };

  const showFinishPanel = () => {
    if (!finishPanelEl) return;
    finishPanelEl.hidden = false;
    finishPanelEl.style.display = 'flex';
    finishPanelEl.setAttribute('aria-hidden', 'false');
    if (saveStatusEl) saveStatusEl.textContent = '';
    if (saveBtnEl) saveBtnEl.disabled = false;
  };

  const hideFinishPanel = () => {
    if (!finishPanelEl) return;
    finishPanelEl.hidden = true;
    finishPanelEl.style.display = 'none';
    finishPanelEl.setAttribute('aria-hidden', 'true');
    if (saveStatusEl) saveStatusEl.textContent = '';
    if (saveBtnEl) saveBtnEl.disabled = false;
  };

  const buildRouteName = () => {
    const customName = (saveTitleEl?.value || '').trim();
    if (customName !== '') return customName;
    const now = new Date();
    const pad = (v) => String(v).padStart(2, '0');
    return `Ruta ${pad(now.getDate())}/${pad(now.getMonth() + 1)} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
  };

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

  const updatePosition = (pos) => {
    const { latitude, longitude, accuracy } = pos.coords;
    const latLng = [latitude, longitude];
    const sampleTimeMs = pos.timestamp || Date.now();

    if (!lastAcceptedPoint) {
      points = [latLng];
      lastAcceptedPoint = latLng;
      lastAcceptedTimeMs = sampleTimeMs;
    } else {
      const segmentDistance = distanceBetweenMeters(lastAcceptedPoint, latLng);
      const elapsedSegmentS = Math.max(1, (sampleTimeMs - (lastAcceptedTimeMs || sampleTimeMs)) / 1000);
      const speed = segmentDistance / elapsedSegmentS;

      const dynamicMinDistance = Math.max(1.2, Math.min(6, accuracy * 0.08));
      const validAccuracy = accuracy <= MAX_ACCEPTABLE_ACCURACY_M;
      const validSegment = segmentDistance >= dynamicMinDistance && segmentDistance <= MAX_SEGMENT_METERS;
      const validSpeed = speed <= MAX_SPEED_MPS;

      if (validAccuracy && validSegment && validSpeed) {
        totalDistanceMeters += segmentDistance;
        points.push(latLng);
        lastAcceptedPoint = latLng;
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
    lastAcceptedPoint = null;
    lastAcceptedTimeMs = null;
    latestMetrics = {
      distanceM: 0,
      steps: 0,
      elapsedS: 0,
      calories: 0
    };
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

  startBtn.addEventListener('click', function () {
    if (!tracking) {
      startTracking();
    } else {
      stopTracking();
    }
  });

  if (saveBtnEl) {
    saveBtnEl.addEventListener('click', function () {
      saveRoute();
    });
  }

  if (dismissGuestPanelEl) {
    dismissGuestPanelEl.addEventListener('click', function () {
      hideFinishPanel();
      statusEl.textContent = 'Ruta finalizada sin guardar.';
    });
  }

  document.querySelectorAll('.finish-dismiss-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      hideFinishPanel();
      statusEl.textContent = 'Ruta finalizada sin guardar.';
    });
  });

  if (finishPanelEl) {
    finishPanelEl.setAttribute('aria-hidden', finishPanelEl.hidden ? 'true' : 'false');
    if (finishPanelEl.hidden) finishPanelEl.style.display = 'none';
  }

  setButtonState();
  updateStats();
  // Restore a route tracked as guest and saved to localStorage before logging in
  if (isLoggedIn && saveBtnEl) {
    try {
      const _pending = JSON.parse(localStorage.getItem('fc_pending_route') || 'null');
      if (_pending && Array.isArray(_pending.points) && _pending.points.length > 0) {
        latestMetrics = _pending.metrics || latestMetrics;
        points = _pending.points;
        if (saveTitleEl) saveTitleEl.value = _pending.title || '';
        showFinishPanel();
        localStorage.removeItem('fc_pending_route');
      }
    } catch (e) {}
  }

  // Before login redirect: save route so it survives the redirect
  document.querySelectorAll('#route-login-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (points.length > 0) {
        e.preventDefault();
        try {
          localStorage.setItem('fc_pending_route', JSON.stringify({
            metrics: latestMetrics,
            points: points,
            title: buildRouteName()
          }));
        } catch (ex) {}
        window.location.href = link.href;
      }
    });
  });

  statusEl.textContent = 'Pulsa "Iniciar nueva ruta" para comenzar.';
})();

(function () {
  const statusEl = document.getElementById('live-location-status');
  const mapEl = document.getElementById('live-location-map');

  if (!statusEl || !mapEl) return;

  if (!('geolocation' in navigator)) {
    statusEl.textContent = 'Geolocalización no disponible en este navegador.';
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

  const updatePosition = (pos) => {
    const { latitude, longitude, accuracy } = pos.coords;

    if (!marker) {
      marker = L.marker([latitude, longitude]).addTo(map);
    } else {
      marker.setLatLng([latitude, longitude]);
    }

    if (!accuracyCircle) {
      accuracyCircle = L.circle([latitude, longitude], {
        radius: accuracy,
        color: '#e53935',
        fillColor: '#e53935',
        fillOpacity: 0.2
      }).addTo(map);
    } else {
      accuracyCircle.setLatLng([latitude, longitude]);
      accuracyCircle.setRadius(accuracy);
    }

    map.setView([latitude, longitude], 16);
    statusEl.textContent = `Ubicación actualizada · Precisión: ${Math.round(accuracy)} m`;
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
  };

  statusEl.textContent = 'Solicitando ubicación…';

  navigator.geolocation.watchPosition(updatePosition, handleError, {
    enableHighAccuracy: true,
    maximumAge: 1000,
    timeout: 10000
  });
})();

/**
 * Map Handler - Leaflet Integration
 * Display booking locations on interactive map
 */

console.log('[Map Handler] Script loaded');

const offlineMode = typeof window !== 'undefined' && !!window.APP_OFFLINE_MODE;

// Map instance global
let mapInstance = null;
let markerLayer = null;

function renderOfflineMap(container, title, lines) {
  if (!container) {
    return;
  }

  const detailHtml = Array.isArray(lines)
    ? lines.filter(Boolean).map((line) => `<div style="margin-top:6px; font-size:13px; color:#546e7a;">${escapeHtml(String(line))}</div>`).join('')
    : '';

  container.innerHTML = `
    <div class="map-placeholder" style="min-height: 320px; display:flex; align-items:center; justify-content:center; padding:24px; background: linear-gradient(135deg, #f7fdf9 0%, #ffffff 100%); border: 1px dashed #dcefe6; border-radius: 18px;">
      <div style="text-align:center; max-width: 360px;">
        <div style="font-size: 28px; margin-bottom: 10px;">🗺️</div>
        <div style="font-weight: 800; color: #1f2d3d; margin-bottom: 6px;">${escapeHtml(title)}</div>
        <div style="color: #546e7a; font-size: 14px; line-height: 1.6;">Chế độ test nội bộ không tải dịch vụ bản đồ ngoài.</div>
        ${detailHtml}
      </div>
    </div>
  `;
}

/**
 * Initialize map for booking location
 * @param {string} containerId - HTML element ID for map
 * @param {string|null} locationAddress - Address string to geocode
 * @param {number} latitude - Fallback latitude (default: Hanoi)
 * @param {number} longitude - Fallback longitude (default: Hanoi)
 */
function initializeBookingMap(containerId, locationAddress = null, latitude = 21.0285, longitude = 105.8542) {
  const container = document.getElementById(containerId);
  if (!container) {
    console.warn(`[Map] Container #${containerId} not found`);
    return null;
  }

  if (offlineMode || typeof L === 'undefined') {
    renderOfflineMap(container, 'Bản đồ test', [
      locationAddress ? `Địa chỉ: ${locationAddress}` : 'Chưa có địa chỉ cụ thể',
      `Tọa độ tham chiếu: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`,
    ]);
    return null;
  }

  // Check if Leaflet is loaded
  if (typeof L === 'undefined') {
    console.error('[Map] Leaflet library not loaded. Make sure Leaflet CDN is included.');
    renderOfflineMap(container, 'Bản đồ không thể tải', ['Vui lòng kiểm tra kết nối internet hoặc tính năng chặn quảng cáo.']);
    return null;
  }

  console.log(`[Map] Initializing map in container #${containerId} at (${latitude}, ${longitude})`);

  // Destroy existing map if present
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }

  // Initialize map
  mapInstance = L.map(containerId, {
    scrollWheelZoom: true,
    zoomControl: true,
    touchZoom: true
  }).setView([latitude, longitude], 15);

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapInstance);

  // Create marker layer group
  markerLayer = L.layerGroup().addTo(mapInstance);

  // Add marker for service location
  const marker = L.circleMarker([latitude, longitude], {
    radius: 8,
    fillColor: '#b5d8b8',
    color: '#a8b8a8',
    weight: 2,
    opacity: 0.8,
    fillOpacity: 0.6,
  }).bindPopup(`
    <div style="font-family: Manrope, sans-serif; font-size: 13px;">
      <strong style="color: #9ba3b8;">Địa điểm phục vụ</strong><br/>
      ${locationAddress ? `<small>${escapeHtml(locationAddress)}</small>` : '<small>Chưa xác định</small>'}
    </div>
  `).addTo(markerLayer);

  marker.openPopup();

  // Add geolocation control (optional)
  if (navigator.geolocation) {
    L.Control.extend({
      options: {
        position: 'topright',
      },
      onAdd: function (map) {
        const btn = L.DomUtil.create('button', 'leaflet-control leaflet-bar');
        btn.innerHTML = '📍';
        btn.title = 'Vị trí hiện tại';
        btn.style.width = '36px';
        btn.style.height = '36px';
        btn.style.lineHeight = '36px';
        btn.style.textAlign = 'center';
        btn.style.cursor = 'pointer';
        btn.style.fontSize = '18px';
        btn.style.backgroundColor = 'rgba(232, 220, 208, 0.8)';
        btn.style.border = '1px solid rgba(181, 216, 184, 0.4)';
        btn.style.borderRadius = '4px';

        btn.addEventListener('click', function () {
          navigator.geolocation.getCurrentPosition(function (position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            mapInstance.setView([lat, lng], 16);
            L.circleMarker([lat, lng], {
              radius: 6,
              fillColor: '#9ba3b8',
              color: '#a8a8a8',
              weight: 2,
              opacity: 0.9,
              fillOpacity: 0.5,
            }).addPopup('Vị trí hiện tại').addTo(markerLayer);
          });
        });

        L.DomEvent.disableClickPropagation(btn);
        return btn;
      },
    });
  }

  return mapInstance;
}

/**
 * Initialize worker route map with two locations (worker + customer)
 * @param {string} containerId - HTML element ID for map
 * @param {string} customerAddress - Customer/job location address
 * @param {string} workerAddress - Worker current address
 */
function initializeWorkerRouteMap(containerId, customerAddress, workerAddress) {
  const container = document.getElementById(containerId);
  if (!container) {
    console.warn(`[Map] Container #${containerId} not found`);
    return null;
  }

  if (offlineMode || typeof L === 'undefined') {
    renderOfflineMap(container, 'Bản đồ test tuyến đường', [
      customerAddress ? `Khách hàng: ${customerAddress}` : 'Chưa có địa chỉ khách hàng',
      workerAddress ? `Worker: ${workerAddress}` : 'Chưa có địa chỉ worker',
    ]);
    return null;
  }

  if (typeof L === 'undefined') {
    console.error('[Map] Leaflet library not loaded');
    renderOfflineMap(container, 'Bản đồ không thể tải', ['Vui lòng kiểm tra kết nối internet.']);
    return null;
  }

  console.log('[Map] Initializing worker route map');
  console.log('[Map] Customer address:', customerAddress);
  console.log('[Map] Worker address:', workerAddress);

  // Destroy existing map if present
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }

  // Initialize map with default center (Hanoi)
  mapInstance = L.map(containerId, {
    scrollWheelZoom: true,
    zoomControl: true,
    touchZoom: true
  }).setView([21.0285, 105.8542], 13);

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(mapInstance);

  // Create marker layer group
  markerLayer = L.layerGroup().addTo(mapInstance);

  // Geocode both addresses and add markers
  const distanceEl = document.getElementById('distanceResult');
  const etaEl = document.getElementById('etaResult');

  if (!customerAddress) {
    if (distanceEl) distanceEl.textContent = 'Chưa có địa chỉ khách hàng';
    if (etaEl) etaEl.textContent = 'Chưa có địa chỉ khách hàng';
    return mapInstance;
  }

  // Geocode customer location
  geocodeAddress(customerAddress, function (customerCoords) {
    if (!customerCoords) {
      console.warn('[Map] Could not geocode customer address:', customerAddress);
      if (distanceEl) distanceEl.textContent = 'Không định vị được địa chỉ khách';
      if (etaEl) etaEl.textContent = 'Không định vị được địa chỉ khách';
      return;
    }

    console.log('[Map] Customer coords:', customerCoords);

    // Add customer marker (primary)
    const customerMarker = L.circleMarker([customerCoords.latitude, customerCoords.longitude], {
      radius: 10,
      fillColor: '#b5d8b8',
      color: '#a8b8a8',
      weight: 2.5,
      opacity: 0.9,
      fillOpacity: 0.7,
    })
      .bindPopup(`<div style="font-family: Manrope, sans-serif; font-size: 12px;"><strong style="color: #9ba3b8;">📍 Vị trí khách hàng</strong><br/><small>${escapeHtml(customerAddress)}</small></div>`)
      .addTo(markerLayer);

    customerMarker.openPopup();

    // Try to geocode worker address
    if (workerAddress) {
      geocodeAddress(workerAddress, function (workerCoords) {
        if (workerCoords) {
          console.log('[Map] Worker coords:', workerCoords);

          // Add worker marker (secondary)
          L.circleMarker([workerCoords.latitude, workerCoords.longitude], {
            radius: 8,
            fillColor: '#9ba3b8',
            color: '#a8a8a8',
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.6,
          })
            .bindPopup(`<div style="font-family: Manrope, sans-serif; font-size: 12px;"><strong style="color: #9ba3b8;">👤 Vị trí của bạn</strong><br/><small>${escapeHtml(workerAddress)}</small></div>`)
            .addTo(markerLayer);

          // Calculate distance and ETA
          const distance = calculateHaversineDistance(workerCoords.latitude, workerCoords.longitude, customerCoords.latitude, customerCoords.longitude);
          const averageSpeedKmPerHour = 30;
          const etaMinutes = Math.max(1, Math.round((distance / averageSpeedKmPerHour) * 60));

          console.log('[Map] Distance:', distance.toFixed(2), 'km');
          console.log('[Map] ETA:', etaMinutes, 'minutes');

          if (distanceEl) distanceEl.textContent = distance.toFixed(2) + ' km';
          if (etaEl) etaEl.textContent = etaMinutes + ' phút';

          // Draw route line
          const latlngs = [
            [workerCoords.latitude, workerCoords.longitude],
            [customerCoords.latitude, customerCoords.longitude],
          ];
          L.polyline(latlngs, {
            color: '#b5d8b8',
            weight: 3,
            opacity: 0.6,
            dashArray: '5, 5',
          }).addTo(markerLayer);

          // Fit bounds to show both markers
          const group = new L.featureGroup([customerMarker]);
          mapInstance.fitBounds(group.getBounds().pad(0.1), { maxZoom: 15 });
        } else {
          console.warn('[Map] Could not geocode worker address:', workerAddress);
          // Just center on customer
          mapInstance.setView([customerCoords.latitude, customerCoords.longitude], 15);
        }
      });
    } else {
      // Just center on customer
      mapInstance.setView([customerCoords.latitude, customerCoords.longitude], 15);
    }
  });

  return mapInstance;
}

/**
 * Calculate Haversine distance between two coordinates in km
 */
function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
  const toRad = (deg) => deg * (Math.PI / 180);
  const R = 6371; // Earth radius in km
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

/**
 * Update map with new location
 * @param {number} latitude
 * @param {number} longitude
 * @param {string} label
 */
function updateMapLocation(latitude, longitude, label = '') {
  if (!mapInstance) return;

  markerLayer.clearLayers();

  const marker = L.circleMarker([latitude, longitude], {
    radius: 8,
    fillColor: '#b5d8b8',
    color: '#a8b8a8',
    weight: 2,
    opacity: 0.8,
    fillOpacity: 0.6,
  }).addTo(markerLayer);

  if (label) {
    marker.bindPopup(`<strong style="color: #9ba3b8;">${escapeHtml(label)}</strong>`).openPopup();
  }

  mapInstance.setView([latitude, longitude], 15);
}

/**
 * Geocode address using Nominatim (OpenStreetMap)
 * @param {string} address
 * @param {function} callback - Called with {latitude, longitude} or null
 */
function geocodeAddress(address, callback) {
  if (!address) {
    callback(null);
    return;
  }

  if (offlineMode) {
    callback(null);
    return;
  }

  const encodedAddress = encodeURIComponent(address);
  const url = `https://nominatim.openstreetmap.org/search?q=${encodedAddress}&format=json&limit=1`;

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      if (data && data.length > 0) {
        callback({
          latitude: parseFloat(data[0].lat),
          longitude: parseFloat(data[0].lon),
        });
      } else {
        callback(null);
      }
    })
    .catch((err) => {
      console.warn('Geocoding error:', err);
      callback(null);
    });
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * Initialize map with address geocoding
 * @param {string} containerId
 * @param {string} address
 */
function initializeMapWithAddress(containerId, address) {
  if (!address) {
    initializeBookingMap(containerId);
    return;
  }

  geocodeAddress(address, (coords) => {
    if (coords) {
      initializeBookingMap(containerId, address, coords.latitude, coords.longitude);
    } else {
      console.warn(`Could not geocode: ${address}`);
      initializeBookingMap(containerId, address);
    }
  });
}

// Auto-init on DOM ready
document.addEventListener('DOMContentLoaded', function () {
  console.log('[Map] DOM ready, checking for map containers...');
  
  // Small delay to ensure Leaflet is loaded
  setTimeout(function() {
    initMapsWhenReady();
  }, 500);
});

function initMapsWhenReady() {
  if (offlineMode) {
    const bookingMapContainer = document.getElementById('booking-map');
    if (bookingMapContainer) {
      const address = bookingMapContainer.getAttribute('data-address') || null;
      const lat = parseFloat(bookingMapContainer.getAttribute('data-lat') || '21.0285');
      const lng = parseFloat(bookingMapContainer.getAttribute('data-lng') || '105.8542');
      renderOfflineMap(bookingMapContainer, 'Bản đồ test', [
        address ? `Địa chỉ: ${address}` : 'Chưa có địa chỉ',
        `Tọa độ tham chiếu: ${lat.toFixed(6)}, ${lng.toFixed(6)}`,
      ]);
    }

    const workerMapContainer = document.getElementById('worker-map');
    if (workerMapContainer && workerMapContainer.classList && !workerMapContainer.classList.contains('fallback-map')) {
      const customerAddress = workerMapContainer.getAttribute('data-address') || null;
      const workerAddressEl = document.getElementById('workerAddress');
      const workerAddress = workerAddressEl ? workerAddressEl.textContent.trim() : null;
      renderOfflineMap(workerMapContainer, 'Bản đồ test tuyến đường', [
        customerAddress ? `Khách hàng: ${customerAddress}` : 'Chưa có địa chỉ khách hàng',
        workerAddress ? `Worker: ${workerAddress}` : 'Chưa có địa chỉ worker',
      ]);
    }

    return;
  }

  // Check if Leaflet is available
  if (typeof L === 'undefined') {
    console.warn('[Map] Leaflet not available, switching to fallback maps...');
    return;
  }

  console.log('[Map] Leaflet available, initializing maps...');

  // Check for booking map (admin/booking-detail)
  const bookingMapContainer = document.getElementById('booking-map');
  if (bookingMapContainer) {
    console.log('[Map] Found booking-map container');
    const address = bookingMapContainer.getAttribute('data-address') || null;
    const lat = parseFloat(bookingMapContainer.getAttribute('data-lat') || '21.0285');
    const lng = parseFloat(bookingMapContainer.getAttribute('data-lng') || '105.8542');

    if (address) {
      console.log('[Map] Initializing booking map with address:', address);
      initializeMapWithAddress('booking-map', address);
    } else {
      console.log('[Map] Initializing booking map with coordinates:', lat, lng);
      initializeBookingMap('booking-map', null, lat, lng);
    }
  }

  // Check for worker route map (worker/job-detail)
  const workerMapContainer = document.getElementById('worker-map');
  if (workerMapContainer && workerMapContainer.classList && !workerMapContainer.classList.contains('fallback-map')) {
    console.log('[Map] Found worker-map container');
    const customerAddress = workerMapContainer.getAttribute('data-address') || null;
    const workerAddressEl = document.getElementById('workerAddress');
    const workerAddress = workerAddressEl ? workerAddressEl.textContent.trim() : null;

    console.log('[Map] Customer address:', customerAddress);
    console.log('[Map] Worker address:', workerAddress);

    if (customerAddress) {
      initializeWorkerRouteMap('worker-map', customerAddress, workerAddress);
    } else {
      console.warn('[Map] No customer address found for worker map');
    }
  }
}

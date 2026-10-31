(() => {
  'use strict';
  const provinces = Array.from(document.querySelectorAll('[data-location-province]'));
  if (!provinces.length) return;
  let cache = null;
  const load = async () => {
    if (cache) return cache;
    if (window.TAKIMBUL_LOCATION_DATA && typeof window.TAKIMBUL_LOCATION_DATA === 'object') { cache = window.TAKIMBUL_LOCATION_DATA; return cache; }
    const response = await fetch('/assets/data/turkey-locations.json', {credentials: 'same-origin', cache: 'force-cache'});
    if (!response.ok) throw new Error('location_data_unavailable');
    cache = await response.json();
    return cache;
  };
  const fillDistrict = (district, districts, current, emptyLabel) => {
    district.innerHTML = '';
    const first = document.createElement('option');
    first.value = '';
    first.textContent = emptyLabel || 'İlçe seç';
    district.appendChild(first);
    (districts || []).forEach(name => {
      const option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      if (name === current) option.selected = true;
      district.appendChild(option);
    });
    district.disabled = !districts || districts.length === 0;
  };
  provinces.forEach(province => {
    const group = province.closest('[data-location-group]');
    const scope = group || province.closest('form') || document;
    const district = scope.querySelector('[data-location-district]');
    if (!district) return;
    province.addEventListener('change', async () => {
      try {
        const data = await load();
        fillDistrict(district, data[province.value] || [], '', district.dataset.emptyLabel);
      } catch (_) {
        district.disabled = false;
      }
    });
  });
})();

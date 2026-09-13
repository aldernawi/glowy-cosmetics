

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const mapElement = document.querySelector('#delivery-map');
if (mapElement?.dataset.key) {
    window.glowyInitDeliveryMap = () => {
        const center = { lat: 32.8872, lng: 13.1913 };
        const map = new google.maps.Map(mapElement, { center, zoom: 12, clickableIcons: false });
        const marker = new google.maps.Marker({ map, position: center, draggable: true });
        const latitude = document.querySelector('#latitude'); const longitude = document.querySelector('#longitude'); const status = document.querySelector('#map-status');
        const setLocation = (position) => { latitude.value = position.lat().toFixed(7); longitude.value = position.lng().toFixed(7); marker.setPosition(position); status.textContent = 'تم تحديد موقع التسليم. احسبي رسوم التوصيل للمتابعة.'; };
        map.addListener('click', ({ latLng }) => setLocation(latLng));
        marker.addListener('dragend', () => setLocation(marker.getPosition()));
        const search = document.querySelector('#places-search'); const autocomplete = new google.maps.places.Autocomplete(search, { fields: ['geometry', 'place_id', 'formatted_address'] });
        autocomplete.addListener('place_changed', () => { const place = autocomplete.getPlace(); if (!place.geometry) return; map.panTo(place.geometry.location); map.setZoom(15); setLocation(place.geometry.location); document.querySelector('#place_id').value = place.place_id || ''; document.querySelector('[name="address"]').value = place.formatted_address || ''; });
        document.querySelector('#use-location').addEventListener('click', () => navigator.geolocation?.getCurrentPosition((position) => { const location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude); map.panTo(location); setLocation(location); }, () => { status.textContent = 'تعذر استخدام موقعك. اختاريه يدويًا من الخريطة.'; }));
    };
    const script = document.createElement('script'); script.src = `https://maps.googleapis.com/maps/api/js?libraries=places&key=${encodeURIComponent(mapElement.dataset.key)}&callback=glowyInitDeliveryMap`; script.async = true; script.onerror = () => { document.querySelector('#map-status').textContent = 'تعذر تحميل الخريطة. أعيدي المحاولة لاحقًا.'; }; document.head.append(script);
}

document.querySelector('#quote-button')?.addEventListener('click', async () => { const latitude = document.querySelector('#latitude').value; const longitude = document.querySelector('#longitude').value; const card = document.querySelector('#delivery-quote'); if (!latitude || !longitude) { card.textContent = 'حددي موقع التسليم أولًا.'; card.classList.remove('hidden'); return; } const response = await fetch('/checkout/quote', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: JSON.stringify({ latitude, longitude }) }); const data = await response.json(); card.textContent = response.ok ? `المسافة ${data.distance_km} كم — التوصيل ${data.delivery_fee} د.ل — حوالي ${data.duration_minutes} دقيقة` : data.message; card.classList.remove('hidden'); document.querySelector('#confirm-button').disabled = !response.ok; });



import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealElements = [...document.querySelectorAll('[data-reveal]')];

document.documentElement.classList.add('motion-ready');

if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    revealElements.forEach((element) => element.classList.add('is-visible'));
} else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    revealElements.forEach((element) => revealObserver.observe(element));
}

if (!prefersReducedMotion && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('[data-parallax-scene]').forEach((scene) => {
        const layers = [...scene.querySelectorAll('[data-parallax]')];
        let animationFrame;

        const render = (event) => {
            const bounds = scene.getBoundingClientRect();
            const x = (event.clientX - bounds.left) / bounds.width - 0.5;
            const y = (event.clientY - bounds.top) / bounds.height - 0.5;

            layers.forEach((layer) => {
                const depth = Number(layer.dataset.parallaxDepth || 1);
                layer.style.setProperty('--parallax-x', `${x * 16 * depth}px`);
                layer.style.setProperty('--parallax-y', `${y * 12 * depth}px`);
            });
        };

        scene.addEventListener('pointermove', (event) => {
            cancelAnimationFrame(animationFrame);
            animationFrame = requestAnimationFrame(() => render(event));
        }, { passive: true });

        scene.addEventListener('pointerleave', () => {
            layers.forEach((layer) => {
                layer.style.setProperty('--parallax-x', '0px');
                layer.style.setProperty('--parallax-y', '0px');
            });
        });
    });
}

document.querySelectorAll('[data-marquee]').forEach((marquee) => {
    const toggle = marquee.querySelector('[data-marquee-toggle]');
    if (!toggle) return;

    const setPaused = (paused) => {
        marquee.classList.toggle('is-paused', paused);
        toggle.setAttribute('aria-pressed', String(paused));
        toggle.textContent = paused ? 'تشغيل الحركة' : 'إيقاف الحركة';
    };

    if (prefersReducedMotion) {
        setPaused(true);
        toggle.textContent = 'الحركة متوقفة';
        toggle.disabled = true;
    } else {
        toggle.addEventListener('click', () => setPaused(!marquee.classList.contains('is-paused')));
    }
});

const siteHeader = document.querySelector('[data-site-header]');
if (siteHeader) {
    const updateHeader = () => siteHeader.classList.toggle('is-scrolled', window.scrollY > 24);

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}

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

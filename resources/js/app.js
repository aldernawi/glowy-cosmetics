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
        scene.addEventListener('pointerleave', () => layers.forEach((layer) => {
            layer.style.setProperty('--parallax-x', '0px');
            layer.style.setProperty('--parallax-y', '0px');
        }));
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

const initializeMaps = async () => {
    const hasMap = document.querySelector('#delivery-map, #store-origin-map');
    if (!hasMap) return;

    const maplibregl = await import('maplibre-gl');
    await import('maplibre-gl/dist/maplibre-gl.css');

    const createMap = (element, initialLatitude, initialLongitude, initialZoom = 13) => {
    const hasInitialLocation = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude);
    const map = new maplibregl.Map({
        container: element,
        style: 'https://tiles.openfreemap.org/styles/liberty',
        center: hasInitialLocation ? [initialLongitude, initialLatitude] : [0, 20],
        zoom: hasInitialLocation ? initialZoom : 1.6,
        attributionControl: true,
    });
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-left');
    map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-right');

    return map;
    };

    const createGlowyMarker = (map, latitude, longitude) => {
    const element = document.createElement('span');
    element.className = 'glowy-map-marker';
    element.setAttribute('aria-hidden', 'true');
    element.innerHTML = '<i>✦</i>';

    return new maplibregl.Marker({ element, draggable: true, anchor: 'bottom' })
        .setLngLat([longitude, latitude])
        .addTo(map);
    };

    const moveMapTo = (map, latitude, longitude, zoom = 15) => map.flyTo({
    center: [Number(longitude), Number(latitude)],
    zoom,
    essential: true,
    });

    const formatMapCoordinates = (latitude, longitude) => `${Number(latitude).toFixed(5)}، ${Number(longitude).toFixed(5)}`;

    const bindLocationSearch = (input, button, resultsElement, onSelect, status) => {
    if (!input || !button || !resultsElement) return;
    const search = async () => {
        const query = input.value.trim();
        if (query.length < 3) {
            status.textContent = 'اكتبي ثلاثة أحرف على الأقل للبحث عن الموقع.';
            return;
        }
        button.disabled = true;
        button.textContent = 'جارٍ البحث…';
        try {
            const response = await fetch(`/locations/search?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'تعذر البحث عن الموقع.');
            resultsElement.replaceChildren(...data.results.map((result) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'block w-full rounded-xl px-3 py-2 text-right text-sm transition hover:bg-glowy-blush';
                const label = document.createElement('span');
                label.className = 'block font-bold text-glowy-plum';
                label.textContent = result.label || result.address;
                item.append(label);
                if (result.secondary_label) {
                    const secondaryLabel = document.createElement('span');
                    secondaryLabel.className = 'mt-0.5 block text-xs text-glowy-muted';
                    secondaryLabel.textContent = result.secondary_label;
                    item.append(secondaryLabel);
                }
                item.addEventListener('click', () => {
                    resultsElement.replaceChildren();
                    onSelect(result);
                });
                return item;
            }));
            if (!data.results.length) status.textContent = 'لم نجد نتيجة واضحة. اختاري الموقع يدويًا من الخريطة.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
            button.textContent = 'بحث';
        }
    };
    button.addEventListener('click', search);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    });
    };

    const mapElement = document.querySelector('#delivery-map');
if (mapElement?.dataset.originLatitude && mapElement.dataset.originLongitude) {
    const originLatitude = Number(mapElement.dataset.originLatitude);
    const originLongitude = Number(mapElement.dataset.originLongitude);
    const map = createMap(mapElement, originLatitude, originLongitude, 12);
    const latitude = document.querySelector('#latitude');
    const longitude = document.querySelector('#longitude');
    const placeId = document.querySelector('#place_id');
    const address = document.querySelector('[name="address"]');
    const status = document.querySelector('#map-status');
    const confirmLocation = document.querySelector('#confirm-location');
    const locationConfirmed = document.querySelector('#location-confirmed');
    const quoteButton = document.querySelector('#quote-button');
    const confirmOrder = document.querySelector('#confirm-button');
    let marker;

    const invalidateConfirmation = () => {
        locationConfirmed.value = '0';
        confirmLocation.disabled = true;
        quoteButton.disabled = true;
        confirmOrder.disabled = true;
        document.querySelector('#delivery-quote').classList.add('hidden');
        document.querySelector('#checkout-delivery-fee').textContent = 'تُحسب بعد الموقع';
        document.querySelector('#checkout-total').textContent = `${document.querySelector('#checkout-subtotal').textContent} + التوصيل`;
    };
    const setLocation = (lat, lng, details = {}) => {
        latitude.value = Number(lat).toFixed(7);
        longitude.value = Number(lng).toFixed(7);
        if (marker) marker.setLngLat([lng, lat]);
        else {
            marker = createGlowyMarker(map, lat, lng);
            marker.on('dragend', () => {
                const position = marker.getLngLat();
                setLocation(position.lat, position.lng);
            });
        }
        if (details.placeId !== undefined) placeId.value = details.placeId || '';
        if (details.address && address && !address.value) address.value = details.address;
        invalidateConfirmation();
        confirmLocation.disabled = false;
        status.textContent = 'تم اختيار الموقع. اضغطي «تأكيد موقع التسليم» قبل حساب الرسوم.';
    };

    map.on('click', (event) => setLocation(event.lngLat.lat, event.lngLat.lng));
    map.on('error', () => { status.textContent = 'تعذر تحميل الخريطة الآن. تحققي من اتصال الإنترنت ثم أعيدي المحاولة.'; });
    bindLocationSearch(document.querySelector('#places-search'), document.querySelector('#places-search-button'), document.querySelector('#places-results'), (result) => {
        moveMapTo(map, result.latitude, result.longitude);
        setLocation(result.latitude, result.longitude, { placeId: result.place_id, address: result.address });
    }, status);
    document.querySelector('#use-location')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            status.textContent = 'متصفحك لا يدعم تحديد الموقع. اختاريه يدويًا من الخريطة.';
            return;
        }
        navigator.geolocation.getCurrentPosition((position) => {
            moveMapTo(map, position.coords.latitude, position.coords.longitude);
            setLocation(position.coords.latitude, position.coords.longitude);
        }, () => {
            status.textContent = 'تعذر استخدام موقعك. اختاريه يدويًا من الخريطة.';
        });
    });
    confirmLocation.addEventListener('click', () => {
        if (!latitude.value || !longitude.value) return;
        locationConfirmed.value = '1';
        quoteButton.disabled = false;
        status.textContent = 'تم تأكيد موقع التسليم. يمكنك الآن حساب رسوم التوصيل.';
    });
}

    const originMap = document.querySelector('#store-origin-map');
if (originMap) {
    const initialLatitude = Number(originMap.dataset.latitude);
    const initialLongitude = Number(originMap.dataset.longitude);
    const hasInitialLocation = Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude) && originMap.dataset.latitude !== '' && originMap.dataset.longitude !== '';
    const map = createMap(originMap, hasInitialLocation ? initialLatitude : Number.NaN, hasInitialLocation ? initialLongitude : Number.NaN);
    const latitude = document.querySelector('#store-latitude');
    const longitude = document.querySelector('#store-longitude');
    const address = document.querySelector('#store-address');
    const placeId = document.querySelector('#store-place-id');
    const status = document.querySelector('#origin-map-status');
    const locationSummary = document.querySelector('#origin-location-summary');
    const useCurrentLocation = document.querySelector('#origin-use-location');
    const refreshAddress = document.querySelector('#origin-refresh-address');
    let marker = hasInitialLocation ? createGlowyMarker(map, initialLatitude, initialLongitude) : null;

    [latitude, longitude].forEach((input) => input?.addEventListener('invalid', () => {
        input.closest('details')?.setAttribute('open', 'open');
    }));
    const setOrigin = (lat, lng, details = {}) => {
        latitude.value = Number(lat).toFixed(7);
        longitude.value = Number(lng).toFixed(7);
        if (marker) marker.setLngLat([lng, lat]);
        else {
            marker = createGlowyMarker(map, lat, lng);
            marker.on('dragend', () => {
                const position = marker.getLngLat();
                setOrigin(position.lat, position.lng, { clearAddress: true });
            });
        }
        if (details.placeId !== undefined) placeId.value = details.placeId || '';
        else if (details.clearAddress) placeId.value = '';
        if (details.address !== undefined) address.value = details.address || '';
        else if (details.clearAddress) address.value = '';
        if (locationSummary) locationSummary.textContent = address.value || `إحداثيات مختارة: ${formatMapCoordinates(lat, lng)}`;
        status.textContent = details.address
            ? 'تم جلب عنوان نقطة الانطلاق. احفظي الإعدادات لتفعيلها في حساب التوصيل.'
            : 'تم اختيار نقطة الانطلاق. يمكنك جلب اسم العنوان أو حفظ الإعدادات مباشرة.';
    };
    if (marker) marker.on('dragend', () => {
        const position = marker.getLngLat();
        setOrigin(position.lat, position.lng, { clearAddress: true });
    });
    const reverseOrigin = async () => {
        if (!latitude.value || !longitude.value) {
            status.textContent = 'اختاري نقطة على الخريطة أولًا حتى نتمكن من جلب اسم العنوان.';
            return;
        }
        if (refreshAddress) refreshAddress.disabled = true;
        status.textContent = 'جارٍ جلب اسم العنوان…';
        try {
            const response = await fetch(`/locations/reverse?latitude=${encodeURIComponent(latitude.value)}&longitude=${encodeURIComponent(longitude.value)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'تعذر جلب اسم العنوان.');
            setOrigin(data.result.latitude, data.result.longitude, { placeId: data.result.place_id, address: data.result.address });
        } catch (error) {
            status.textContent = error.message;
        } finally {
            if (refreshAddress) refreshAddress.disabled = false;
        }
    };

    map.on('click', (event) => setOrigin(event.lngLat.lat, event.lngLat.lng, { clearAddress: true }));
    map.on('error', () => { status.textContent = 'تعذر تحميل الخريطة الآن. تحققي من اتصال الإنترنت ثم أعيدي المحاولة.'; });
    bindLocationSearch(document.querySelector('#origin-places-search'), document.querySelector('#origin-places-search-button'), document.querySelector('#origin-places-results'), (result) => {
        moveMapTo(map, result.latitude, result.longitude);
        setOrigin(result.latitude, result.longitude, { placeId: result.place_id, address: result.address });
    }, status);
    refreshAddress?.addEventListener('click', reverseOrigin);
    useCurrentLocation?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            status.textContent = 'متصفحك لا يدعم تحديد الموقع. اختاريه يدويًا من الخريطة.';
            return;
        }
        useCurrentLocation.disabled = true;
        status.textContent = 'ننتظر إذنك لتحديد موقعك الحالي…';
        navigator.geolocation.getCurrentPosition(async (position) => {
            moveMapTo(map, position.coords.latitude, position.coords.longitude, 16);
            setOrigin(position.coords.latitude, position.coords.longitude, { clearAddress: true });
            await reverseOrigin();
            useCurrentLocation.disabled = false;
        }, () => {
            status.textContent = 'تعذر استخدام موقعك. تأكدي من إذن المتصفح أو اختاريه يدويًا.';
            useCurrentLocation.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });
    }
};

void initializeMaps();

const cartFeedback = document.querySelector('#cart-feedback');
const showCartFeedback = (message, isError = false) => {
    if (!cartFeedback) return;
    cartFeedback.textContent = message;
    cartFeedback.className = `pointer-events-none fixed inset-x-4 bottom-5 z-[60] mx-auto max-w-sm rounded-2xl px-5 py-4 text-center text-sm font-bold shadow-lg sm:inset-x-auto sm:start-5 ${isError ? 'bg-red-700 text-white' : 'bg-glowy-plum text-white'}`;
    window.setTimeout(() => cartFeedback.classList.add('hidden'), 3500);
};

document.querySelectorAll('[data-ajax-cart-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const label = button.textContent;
        button.disabled = true;
        button.textContent = 'جارٍ الإضافة…';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(form),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'تعذر إضافة المنتج إلى السلة.');

            document.querySelectorAll('[data-cart-count]').forEach((count) => { count.textContent = data.cart_count; });
            showCartFeedback(data.message);
        } catch (error) {
            showCartFeedback(error.message || 'تعذر إضافة المنتج إلى السلة.', true);
        } finally {
            button.disabled = false;
            button.textContent = label;
        }
    });
});

document.querySelector('#quote-button')?.addEventListener('click', async () => {
    const latitude = document.querySelector('#latitude').value;
    const longitude = document.querySelector('#longitude').value;
    const locationConfirmed = document.querySelector('#location-confirmed').value === '1';
    const card = document.querySelector('#delivery-quote');
    const confirmOrder = document.querySelector('#confirm-button');
    if (!locationConfirmed || !latitude || !longitude) {
        card.textContent = 'حددي موقع التسليم وأكديه أولًا.';
        card.classList.remove('hidden');
        return;
    }
    const button = document.querySelector('#quote-button');
    button.disabled = true;
    button.textContent = 'جارٍ الحساب…';
    try {
        const response = await fetch('/checkout/quote', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: JSON.stringify({ latitude, longitude }) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'تعذر حساب رسوم التوصيل لهذا الموقع.');
        card.textContent = `تم تأكيد الموقع: ${data.distance_km} كم · حوالي ${data.duration_minutes} دقيقة · رسوم التوصيل ${data.delivery_fee} د.ل`;
        document.querySelector('#checkout-delivery-fee').textContent = `${data.delivery_fee} د.ل`;
        const subtotal = Number(document.querySelector('#checkout-subtotal').textContent.replace(/[^0-9.]/g, ''));
        document.querySelector('#checkout-total').textContent = `${(subtotal + Number(data.delivery_fee)).toFixed(3)} د.ل`;
        confirmOrder.disabled = false;
    } catch (error) {
        card.textContent = error.message;
        confirmOrder.disabled = true;
    } finally {
        card.classList.remove('hidden');
        button.disabled = false;
        button.textContent = 'إعادة حساب رسوم التوصيل';
    }
});

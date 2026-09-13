

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const mapElement = document.querySelector('#delivery-map');
if (mapElement?.dataset.key) {
    window.glowyInitDeliveryMap = () => {
        const center = { lat: 32.8872, lng: 13.1913 };
        const map = new google.maps.Map(mapElement, { center, zoom: 12, clickableIcons: false });
        const marker = new google.maps.Marker({ map, position: center, draggable: true });
        const setLocation = (position) => { document.querySelector('#latitude').value = position.lat().toFixed(7); document.querySelector('#longitude').value = position.lng().toFixed(7); marker.setPosition(position); };
        map.addListener('click', ({ latLng }) => setLocation(latLng));
        marker.addListener('dragend', () => setLocation(marker.getPosition()));
    };
    const script = document.createElement('script'); script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapElement.dataset.key)}&callback=glowyInitDeliveryMap`; script.async = true; document.head.append(script);
}

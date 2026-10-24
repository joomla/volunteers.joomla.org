/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
if (!Joomla) {
    throw new Error('Joomla API is not properly initialized');
}

if (Joomla.getOptions('com_volunteers_maps')) {
    let options = Joomla.getOptions('com_volunteers_maps');
    let joomlers = JSON.parse(options.markers);

    // Centre low enough that the top of the world fills the map without a grey band
    let map = L.map('map-canvas', { minZoom: 1 }).setView([20, 0], 1);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);


    let myIcon = L.icon({
        iconUrl: 'media/com_volunteers/images/joomla.png',
        iconSize: [50, 50],
        iconAnchor: [0, 0],
        popupAnchor: [0, 0]
    });

    let markers = L.markerClusterGroup();

    for (let i = 0; i < joomlers.length; i++) {
        let a = JSON.parse(joomlers[i]);
        let marker = L.marker(new L.LatLng(a.lat, a.lng), { icon: myIcon});
        marker.bindPopup('<div class="map-popup"><img class="map-popup-image float-start" width="40" height="40" alt="" src="' + a.image + '"><a href="' + a.url + '">' + a.title + '</a></div>');
        markers.addLayer(marker);
    }

    map.addLayer(markers);
}


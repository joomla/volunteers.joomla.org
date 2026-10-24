/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Latitude/longitude location picker backed by Leaflet + OpenStreetMap.
 * Renders on every ".gllpLatlonPicker" container and keeps the
 * ".gllpLatitude" / ".gllpLongitude" / ".gllpZoom" hidden inputs in sync.
 *
 * Geocoding uses Komoot's Photon API (https://photon.komoot.io), an
 * OpenStreetMap-data-based geocoder with CORS enabled for browser use.
 */
(() => {
    const imagePath = `${Joomla.getOptions('system.paths').root}/media/com_volunteers/images/`;

    const markerIcon = L.icon({
        iconUrl: `${imagePath}marker-icon.png`,
        iconRetinaUrl: `${imagePath}marker-icon-2x.png`,
        shadowUrl: `${imagePath}marker-shadow.png`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    const showNotFound = () => {
        Joomla.renderMessages({ warning: [Joomla.Text._('COM_VOLUNTEERS_LOCATION_NOT_FOUND')] });
    };

    /*
     * The picker can live in a tab that isn't active at page load.
     * Leaflet maps built in a hidden container get a broken pixel origin
     * that invalidateSize() doesn't fully repair, so the map is only
     * built once its container is visible.
     */
    function LocationPicker(container) {
        const latField = container.querySelector('.gllpLatitude');
        const lngField = container.querySelector('.gllpLongitude');
        const zoomField = container.querySelector('.gllpZoom');
        const mapContainer = container.querySelector('.gllpMap');

        const lat = parseFloat(latField.value) || 0;
        const lng = parseFloat(lngField.value) || 0;
        const zoom = parseInt(zoomField.value, 10) || 1;

        let map;
        let marker;

        function initMap() {
            if (map) {
                return;
            }

            map = L.map(mapContainer, {
                doubleClickZoom: false,
            }).setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(map);

            marker = L.marker([lat, lng], {
                icon: markerIcon,
                draggable: true,
            }).addTo(map);

            map.on('dblclick', (event) => {
                setPosition(event.latlng);
            });

            marker.on('dragend', () => {
                setPosition(marker.getLatLng());
            });

            map.on('zoomend', () => {
                zoomField.value = map.getZoom();
            });
        }

        function setPosition(latlng, zoomLevel) {
            initMap();

            marker.setLatLng(latlng);
            map.setView(latlng, zoomLevel !== undefined ? zoomLevel : map.getZoom());

            zoomField.value = map.getZoom();
            latField.value = latlng.lat;
            lngField.value = latlng.lng;
        }

        const performSearch = (query) => {
            if (!query) {
                return;
            }

            const url = new URL('https://photon.komoot.io/api/');
            url.searchParams.set('q', query);
            url.searchParams.set('limit', '1');

            fetch(url)
                .then((response) => response.json())
                .then((data) => {
                    const features = data.features || [];

                    if (!features.length) {
                        showNotFound();
                        return;
                    }

                    const [resultLng, resultLat] = features[0].geometry.coordinates;

                    // Zoom and pan in a single setView() call, two animated view
                    // changes back to back let the zoom swallow the pan
                    setPosition(L.latLng(resultLat, resultLng), 11);
                })
                .catch(showNotFound);
        };

        if (mapContainer.offsetParent !== null) {
            initMap();
        } else {
            document.addEventListener('joomla.tab.shown', function onShown() {
                if (mapContainer.offsetParent !== null) {
                    document.removeEventListener('joomla.tab.shown', onShown);
                    initMap();
                }
            });
        }

        container.querySelector('.gllpSearchButton').addEventListener('click', () => {
            performSearch(container.querySelector('.gllpSearchField').value);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.gllpLatlonPicker').forEach((container) => {
            new LocationPicker(container);
        });

        // Move the marker to the city and country when either one changes
        document.querySelectorAll('.location').forEach((field) => {
            field.addEventListener('change', () => {
                const city = document.querySelector('.location-city')?.value || '';
                const country = document.querySelector('.location-country')?.value || '';
                const query = [city, country].filter(Boolean).join(', ');

                document.querySelectorAll('.gllpSearchField').forEach((searchField) => {
                    searchField.value = query;
                });
                document.querySelectorAll('.gllpSearchButton').forEach((searchButton) => {
                    searchButton.click();
                });
            });
        });
    });
})();

/**
 * Draws a Google map in every element carrying data-osmium-map, a JSON object:
 *   { pins: [{ lat, lng, title?, text?, url?, inactive? }], height?, fullscreen? }
 * The same JSON as the Leaflet Maps service. Pin text is inserted as text, never HTML.
 *
 * Google's script is loaded only after cookie consent (osmium_cookie_consent cookie
 * reading 'accepted', or the osmium:consent event). Settings come from
 * window.OsmiumGoogleMaps = { apiKey, mapId, consentRequired }.
 */
(function () {
    'use strict';

    var settings = window.OsmiumGoogleMaps || {};
    var googleLoading = null;

    function consentGiven() {
        if (!settings.consentRequired) return true; // dev/staging: no banner exists
        var name = (window.OsmiumCookieConsent && window.OsmiumCookieConsent.cookieName) || 'osmium_cookie_consent';
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return !!match && decodeURIComponent(match[1]) === 'accepted';
    }

    function loadGoogle() {
        if (googleLoading) return googleLoading;

        googleLoading = new Promise(function (resolve, reject) {
            window.__osmiumGoogleMapsReady = resolve;
            window.gm_authFailure = function () { reject(new Error('Google rejected the API key')); };

            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(settings.apiKey)
                + '&loading=async&v=weekly&callback=__osmiumGoogleMapsReady';
            script.onerror = function () { reject(new Error('Google Maps could not be loaded')); };
            document.head.appendChild(script);
        });

        return googleLoading;
    }

    function notice(element, message) {
        element.textContent = '';
        var box = document.createElement('div');
        box.className = 'osmium-gmap-notice';
        box.textContent = message;
        element.appendChild(box);
    }

    function popupFor(pin) {
        var box = document.createElement('div');

        if (pin.title) {
            var heading = document.createElement('strong');
            heading.textContent = pin.title;
            box.appendChild(heading);
        }
        if (pin.text) {
            var paragraph = document.createElement('div');
            paragraph.textContent = pin.text;
            box.appendChild(paragraph);
        }
        if (pin.url && /^https?:\/\//i.test(pin.url)) {
            var link = document.createElement('a');
            link.href = pin.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'More information';
            box.appendChild(link);
        }

        return box.childNodes.length ? box : null;
    }

    async function draw(element, options) {
        var pins = (options.pins || []).filter(function (pin) {
            return typeof pin.lat === 'number' && typeof pin.lng === 'number';
        });

        var libraries = await Promise.all([
            google.maps.importLibrary('maps'),
            google.maps.importLibrary('marker'),
            google.maps.importLibrary('core')
        ]);
        var Map = libraries[0].Map;
        var InfoWindow = libraries[0].InfoWindow;
        var AdvancedMarkerElement = libraries[1].AdvancedMarkerElement;
        var PinElement = libraries[1].PinElement;
        var LatLngBounds = libraries[2].LatLngBounds;

        element.textContent = '';
        var map = new Map(element, {
            center: { lat: 54.5, lng: -3 }, // No pins: show the UK
            zoom: 5,
            mapId: settings.mapId,
            fullscreenControl: options.fullscreen !== false
        });

        var infoWindow = new InfoWindow();
        var bounds = new LatLngBounds();
        var markers = pins.map(function (pin) {
            var marker = new AdvancedMarkerElement({
                position: { lat: pin.lat, lng: pin.lng },
                title: pin.title || '',
                gmpClickable: true
            });
            marker.append(new PinElement(pin.inactive
                ? { background: '#9e9e9e', borderColor: '#757575', glyphColor: '#ffffff' }
                : {}));

            var popup = popupFor(pin);
            if (popup) {
                marker.addEventListener('gmp-click', function () {
                    infoWindow.close();
                    infoWindow.setContent(popup);
                    infoWindow.open(marker.map, marker);
                });
            }

            bounds.extend({ lat: pin.lat, lng: pin.lng });
            return marker;
        });

        new markerClusterer.MarkerClusterer({ markers: markers, map: map });

        if (pins.length > 1) map.fitBounds(bounds, 30);
        else if (pins.length === 1) { map.setCenter({ lat: pins[0].lat, lng: pins[0].lng }); map.setZoom(13); }
    }

    function start(element) {
        var options;
        try {
            options = JSON.parse(element.getAttribute('data-osmium-map'));
        } catch (e) {
            return;
        }

        element.classList.add('osmium-gmap');
        if (options.height) element.style.height = options.height;

        var drawn = false;
        function show() {
            if (drawn) return;
            drawn = true;
            loadGoogle().then(function () { return draw(element, options); }).catch(function () {
                notice(element, 'The map could not be loaded.');
            });
        }

        if (consentGiven()) {
            show();
            return;
        }

        notice(element, 'This map is provided by Google. Accept cookies to view it.');
        document.addEventListener('osmium:consent', function (e) {
            if (e.detail && e.detail.value === 'accepted') show();
        });
    }

    function startAll() {
        document.querySelectorAll('[data-osmium-map]').forEach(start);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startAll);
    else startAll();
})();

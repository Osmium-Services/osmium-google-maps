# Google Maps Service

Repo: `Osmium-Services/osmium-google-maps`, service id `google-maps`, category developer. Currently v1.0.2<!--v:google-maps--> (published 2026-10-07 as v1.0.0; v1.0.1 gave the map container a full width; v1.0.2 added the CSP nonce to the inline theme-block scripts). **Untested with a real Google key** (no paid account).

## What it does

The Google counterpart of [[Leaflet Maps Service]] (which has the same consent gate as an optional `"consent": true` per map; Google's is always on). It takes the same `data-osmium-map` pin JSON (`pins` with `lat`, `lng`, `title`, `text`, `url`, `inactive`, plus `height`, `fullscreen`), so a theme can build the pin list once and use whichever service is installed. Install only one of the two on a site.

- Admin page `admin/settings/google-maps`: Enabled (off by default), Maps JavaScript API key, optional Map ID. Enabling without a key is refused.
- A `google-maps-assets` theme block, called once per page that shows a map. It renders nothing until the service is enabled with a valid-looking key.
- Needs the site's own API key and a Google billing account. The key is public in the page source by design: restrict it by HTTP referrer and to the Maps JavaScript API in Google Cloud.
- Pins are advanced markers, so Google needs a Map ID. Blank falls back to `DEMO_MAP_ID`, which Google says is not for production.

## Consent

Requires `cookie-consent`. Nothing is fetched from Google until the visitor has accepted: the script reads the `osmium_cookie_consent` cookie (or `window.OsmiumCookieConsent.cookieName`) and listens for `osmium:consent`. Before that the map area shows "This map is provided by Google. Accept cookies to view it." On dev and staging there is no banner, so `consentRequired` is false and the map loads at once.

## CSP

Core only extends the directives it already lists, and `worker-src` is not one. The manifest declares `script-src` (`https://maps.googleapis.com`, `blob:`), `style-src`, `font-src`, `connect-src` and `frame-src` from Google's CSP page. Google's page also lists `worker-src blob:`; `blob:` under `script-src` is the nearest a service can get, because workers fall back to it.

## Bundled library

`public/markerclusterer.min.js` is `@googlemaps/markerclusterer` 2.6.2 (Apache-2.0, licence beside it), global `markerClusterer`. Bundled so no script loads from unpkg.

## Tested and not tested

- Tested (local install, real browser, fake key): settings page renders and validates; theme block gets its config through core's injection; no request to Google before consent, Google's script loaded after the Accept event; dev mode loads at once; Google's real script ran our callback and created a map, then refused the fake key (`InvalidKeyMapError`).
- **Not verified, needs a valid key:** that pins, clustering, popups, grey inactive pins and fullscreen render; `marker.append(PinElement)` and the `gmp-click` event (Google's pages show both, not on one page); whether `blob:` under `script-src` is enough for Google's workers; the real API-key format (validation is loose, 20-100 letters, digits, `_`, `-`).

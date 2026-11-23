---
title: Show a map of pins with Google Maps
tags: google maps, map, pins, api key, map id, cookie consent
---

**Google Maps** draws a map of pins, with clustering and a fullscreen button, wherever your site's design places it.

## Set it up

1. In Google Cloud, create a **Maps JavaScript API key**. A billing account is required.
2. Restrict the key to this site's address and to the Maps JavaScript API only, because the key is visible in the page source.
3. Under Map Management in Google Cloud, create a **Map ID**. Google requires one for pins to appear. Without it the testing ID is used, which Google says is not for production.
4. Open **Google Maps Settings**, enter the key and Map ID and tick **Enabled**.
5. Press **Save Settings**.

Maps load Google only after a visitor accepts cookies, so **Cookie Consent** must be enabled too. For a free map with no key and no cookies, use Leaflet Maps instead.

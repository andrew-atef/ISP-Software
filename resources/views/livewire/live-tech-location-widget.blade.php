<div>
    {{-- Leaflet.js CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    {{-- Leaflet.js Script --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    @php
        $lat = $record->current_lat ?: 30.0444;
        $lng = $record->current_lng ?: 31.2357;
    @endphp

    <div
        x-data="liveTechMap({{ $lat }}, {{ $lng }})"
        x-init="init()"
        wire:ignore
        style="height: 400px; width: 100%; border-radius: 12px; overflow: hidden; position: relative; border: 2px solid #e5e7eb; z-index: 0;"
    >
        {{-- Map Container --}}
        <div x-ref="mapContainer" style="height: 100%; width: 100%; z-index: 1;"></div>
        
        {{-- Live Indicator Badge (Top-Right) --}}
        <div class="live-indicator-badge" style="position: absolute; top: 12px; right: 12px; z-index: 1000; padding: 6px 14px; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;">
            <span class="pulse-dot"></span>
            LIVE TRACKING
        </div>

        {{-- Address Badge (Bottom-Center) --}}
        <div x-show="address" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="address-badge" 
             style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; padding: 8px 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); font-size: 13px; font-weight: 600; white-space: nowrap; max-width: 90%; text-overflow: ellipsis; overflow: hidden;">
            <svg style="display: inline-block; width: 14px; height: 14px; margin-right: 4px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span x-text="address"></span>
        </div>
    </div>

    <style>
        .live-indicator-badge, .address-badge {
            background-color: white;
            color: #111827;
            border: 1px solid #e5e7eb;
        }

        .dark .live-indicator-badge, .dark .address-badge {
            background-color: #1f2937;
            color: white;
            border: 1px solid #374151;
        }

        .pulse-dot {
            height: 10px;
            width: 10px;
            background-color: #22c55e;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        /* Leaflet Layout Fixes */
        .leaflet-container { font-family: inherit; }
        .leaflet-control-zoom { border: none !important; box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important; }
        .leaflet-bar a { background-color: white !important; color: #374151 !important; border-bottom: 1px solid #f3f4f6 !important; }
        .dark .leaflet-bar a { background-color: #1f2937 !important; color: white !important; border-bottom: 1px solid #374151 !important; }
    </style>

    <script>
        function liveTechMap(initialLat, initialLng) {
            return {
                map: null,
                marker: null,
                lat: initialLat,
                lng: initialLng,
                address: '',
                
                init() {
                    this.$nextTick(() => {
                        // 1. Initialize Leaflet Map
                        this.map = L.map(this.$refs.mapContainer, {
                            zoomControl: true,
                            attributionControl: false
                        }).setView([this.lat, this.lng], 15);

                        // 2. Add OpenStreetMap Tile Layer
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19
                        }).addTo(this.map);

                        // 3. Add Custom Marker
                        const techIcon = L.icon({
                            iconUrl: 'https://cdn-icons-png.flaticon.com/512/6342/6342703.png',
                            iconSize: [38, 38],
                            iconAnchor: [19, 38],
                            popupAnchor: [0, -38]
                        });

                        this.marker = L.marker([this.lat, this.lng], { icon: techIcon })
                            .addTo(this.map)
                            .bindPopup('Technician');

                        // 4. Initial Geocoding
                        this.fetchAddress(this.lat, this.lng);

                        // 5. Start Polling (Rate Limited to 10s for Nominatim compliance)
                        setInterval(() => {
                            this.$wire.getCoordinates().then(coords => {
                                this.updateLocation(coords);
                            });
                        }, 10000);
                    });
                },

                async fetchAddress(lat, lng) {
                    try {
                        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                            headers: { 'Accept-Language': 'en' }
                        });
                        const data = await response.json();
                        
                        if (data.address) {
                            const area = data.address.suburb || data.address.neighbourhood || data.address.district || data.address.city_district || '';
                            const city = data.address.city || data.address.town || data.address.state || '';
                            
                            this.address = area && city ? `${area} - ${city}` : (area || city || 'Location Identified');
                        }
                    } catch (error) {
                        console.error('Geocoding error:', error);
                    }
                },

                updateLocation(coords) {
                    if (!coords.lat || !coords.lng) return;
                    
                    const newPos = [coords.lat, coords.lng];
                    const hasMoved = coords.lat !== this.lat || coords.lng !== this.lng;

                    // Only update and geocode if position changed
                    if (hasMoved) {
                        this.lat = coords.lat;
                        this.lng = coords.lng;
                        
                        this.marker.setLatLng(newPos);
                        this.map.panTo(newPos);
                        
                        // Update Address
                        this.fetchAddress(coords.lat, coords.lng);
                    }
                }
            };
        }
    </script>
</div>

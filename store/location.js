/* ── Store Data ── */
        const stores = {
            baliwag: {
                shops: [
                    {
                        label: "BoyCold Cafe Baliwag",
                        address: "40 Calle Rizal, Baliwag, 3006 Bulacan",
                        hours: "14:00 – 1:00",
                        phone: "0923-421-6448",
                        lat: 14.93564,
                        lng: 120.88853
                    }
                ]
            },
            bustos: {
                shops: [
                    {
                        label: "BoyCold Cafe Bustos",
                        address: "Petron C.L. Hilario St., Tanawan, Bustos, Bulacan",
                        hours: "13:00 – 24:00",
                        phone: "0923-421-6448",
                        lat: 14.95409,
                        lng: 120.92060
                    }
                ]
            }
        };

        const allBranches = [
            { label: "Baliwag Branch", lat: 14.93564, lng: 120.88853 },
            { label: "Bustos Branch",  lat: 14.95409, lng: 120.92060 }
        ];

        let map, leafletMarkers = [];

        /* ── Map Init ── */
        function initMap() {
            map = L.map('mapContainer', {
                center: [14.946, 120.90626],  // midpoint between Baliwag & Bustos
                zoom: 13,
                zoomControl: true,
                attributeControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            const pinIcon = L.divIcon({
                className: '',
                html: `<div class="map-pin-marker"><img src="../picture/LOGO.png" alt="BoyCold logo"></div>`,
                iconSize: [38, 38],
                iconAnchor: [19, 38],
                popupAnchor: [0, -40]
            });

            allBranches.forEach(b => {
                const m = L.marker([b.lat, b.lng], { icon: pinIcon })
                    .addTo(map)
                    .bindPopup(`<span class="map-popup-label">${b.label}</span>`);
                leafletMarkers.push({ marker: m, lat: b.lat, lng: b.lng });
            });
            
        }

        /* ── Dropdown Logic ── */
        function onCityChange() {
            const city = document.getElementById('citySelect').value;
            const shopSel = document.getElementById('shopSelect');
            shopSel.innerHTML = '';
            stores[city].shops.forEach((s, i) => {
                const o = document.createElement('option');
                o.value = i;
                o.textContent = s.label;
                shopSel.appendChild(o);
            });
            onShopChange();
        }

        function onShopChange() {
            const city = document.getElementById('citySelect').value;
            const idx = parseInt(document.getElementById('shopSelect').value) || 0;
            const shop = stores[city].shops[idx];
            document.getElementById('shopAddress').textContent = shop.address;
            document.getElementById('storeHours').textContent = shop.hours;
            document.getElementById('phoneNum').textContent = shop.phone;

            if (map) {
                map.setView([shop.lat, shop.lng], 14, { animate: true });
                leafletMarkers.forEach(m => {
                    if (Math.abs(m.lat - shop.lat) < 0.001) m.marker.openPopup();
                    else m.marker.closePopup();
                });
            }
        }

        let userMarker = null;

        function determineLocation() {
            if (!navigator.geolocation) return alert('Geolocation not supported.');
            navigator.geolocation.getCurrentPosition(pos => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                if (map) {
                    map.setView([lat, lng], 15, { animate: true });

                    // Remove old user marker if exists
                    if (userMarker) map.removeLayer(userMarker);

                    const youIcon = L.divIcon({
                        className: '',
                        html: `<div class="map-you-marker"><i class="fa-solid fa-circle-dot"></i></div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                        popupAnchor: [0, -16]
                    });

                    userMarker = L.marker([lat, lng], { icon: youIcon })
                        .addTo(map)
                        .bindPopup('<span class="map-popup-label">📍 You\'re here</span>')
                        .openPopup();
                }
            }, () => alert('Could not get your location. Please allow location access.'));
        }

        function chooseStore() {
            const shopName = document.getElementById('shopSelect').options[document.getElementById('shopSelect').selectedIndex].text;
            alert('Selected: ' + shopName);
            // Wire to your ordering flow here
        }

        /* ── Nav Sidebar ── */
        const nav = document.getElementById('mainNav');

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar.classList.toggle('open');
            overlay.classList.toggle('open', isOpen);
            nav.classList.toggle('sidebar-open', isOpen);
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('open');
            nav.classList.remove('sidebar-open');
        }

        function toggleSearch() {
            const search = document.getElementById('navSearch');
            const btn = document.getElementById('searchIconBtn');
            const isOpen = search.classList.toggle('open');
            btn.classList.toggle('active', isOpen);
            if (isOpen) setTimeout(() => search.querySelector('input').focus(), 420);
            else search.querySelector('input').value = '';
        }

        function toggleAvatarDropdown() {
            document.getElementById('avatarDropdown').classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const search = document.getElementById('navSearch');
            const btn = document.getElementById('searchIconBtn');
            if (search && btn && !search.contains(e.target) && !btn.contains(e.target)) {
                search.classList.remove('open');
                btn.classList.remove('active');
                search.querySelector('input').value = '';
            }

            const wrap = document.querySelector('.avatar-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const dd = document.getElementById('avatarDropdown');
                if (dd) dd.classList.remove('open');
            }
        });

        /* ── Init on load ── */
        window.addEventListener('load', () => {
            initMap();
            onShopChange();
        });
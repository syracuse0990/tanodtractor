<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&libraries=geometry&callback=initMap" async defer>
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-marker-clusterer/1.0.0/markerclusterer.js"></script>
<script src="/assets/js/markerckuster@2.5.3.min.js"></script>
<script>
    var maps = {};
    var markers = {};
    var contents = {};
    var clusterMarkers = [];
    var newCordinates = [];
    var markerCluster;
    var is_refresh = true;
    var is_hide = false;
    var is_paused = false;
    var centerImei = null;
    var old_imei = null;
    var deviceDataStore = {}; // IMEI => device value object for sidebar
    var currentSidebarImei = null; // IMEI of the device currently shown in sidebar
    var currentSidebarDeviceId = null; // DB device ID for the sidebar device
    var liveFollowInterval = null; // interval for Live follow mode
    var radiusCircle = null;
    var trackPath = null;
    var playback_marker = null;
    var playback_polyline = null;
    var trackPath = null;
    var animationTimer = null;
    var animationInterval = null;
    var historyCordinates = null;
    var gpsTime = null;
    var gpsSpeed = null;
    var direction = null;
    var locationPath = null;
    var latitude = null;
    var longitude = null;
    var is_marker = null;
    var step = 0;
    var progress = 0;
    const intervalDuration = 16;
    const pixelsToMovePerInterval = 0.5;

    const liveviewRefreshIntervalMs = Number(window.liveviewRefreshIntervalMs || 20000);
    const liveviewAutoRefreshEnabled = window.liveviewAutoRefreshEnabled !== false;
    const liveviewRefreshSeconds = Math.max(1, Math.ceil(liveviewRefreshIntervalMs / 1000));

    // Allow dashboard to override these URLs with cached endpoints
    const markersDataUrl = window.liveviewMarkersDataUrl || '{{ route('liveview.markersData') }}';
    const devicesCountUrl = window.liveviewDevicesCountUrl || '{{ route('liveview.getDevicesCount') }}';
    const appendGroupDevicesUrl = window.liveviewAppendGroupDevicesUrl || '{{ route('liveview.appendGroupDevices') }}';
    const currentDeviceUrl = window.liveviewCurrentDeviceUrl || '{{ route('liveview.currentDevice') }}';
    const searchUrl = window.liveviewSearchUrl || '{{ route('liveview.search') }}';
    const getDeviceWithStateUrl = window.liveviewGetDeviceWithStateUrl || '{{ route('liveview.getDeviceWithState') }}';
    const getFilteredDevicesUrl = window.liveviewGetFilteredDevicesUrl || '{{ route('liveview.getFilteredDevices') }}';

    let timeLeft = liveviewRefreshSeconds; // Seconds
    const greenIcon = '{{ asset('assets/img/green_tractor.png') }}';
    const redIcon = '{{ asset('assets/img/red_tractor.png') }}';
    const yellowIcon = '{{ asset('assets/img/yellow_tractor.png') }}';

    // Map type preference: dashboard can pre-set this via window.liveviewDefaultMapType
    const defaultMapType = window.liveviewDefaultMapType || 'roadmap';

    // ─── Tractor Detail Sidebar ───
    function ensureSidebarExists() {
        if (document.getElementById('tractorDetailSidebar')) return;

        // Overlay
        var overlay = document.createElement('div');
        overlay.id = 'tractorSidebarOverlay';
        overlay.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.25);z-index:9999;';
        overlay.addEventListener('click', closeTractorSidebar);
        document.body.appendChild(overlay);

        // Sidebar
        var sb = document.createElement('div');
        sb.id = 'tractorDetailSidebar';
        sb.style.cssText = 'position:fixed;top:0;right:-420px;width:400px;max-width:95vw;height:100vh;background:#fff;box-shadow:-4px 0 20px rgba(0,0,0,.15);z-index:10000;transition:right .3s ease;overflow-y:auto;font-family:Segoe UI,Roboto,Arial,sans-serif;';
        sb.innerHTML =
            '<div class="tds-header">' +
                '<h4 class="tds-device-name" id="tdsDeviceName"></h4>' +
                '<p class="tds-imei" id="tdsImei"></p>' +
                '<button class="tds-close" onclick="closeTractorSidebar()">&times;</button>' +
            '</div>' +
            '<div class="tds-body">' +
                '<div class="tds-section">' +
                    '<div class="tds-status-row">' +
                        '<span class="tds-status-badge" id="tdsStatusBadge"></span>' +
                        '<span class="tds-status-time" id="tdsStatusTime"></span>' +
                    '</div>' +
                '</div>' +
                '<div class="tds-section">' +
                    '<div class="tds-section-title">Address</div>' +
                    '<div class="tds-address" id="tdsAddress">Loading...</div>' +
                    '<div class="tds-coords" id="tdsCoords"></div>' +
                '</div>' +
                '<div class="tds-section">' +
                    '<div class="tds-section-title">Device</div>' +
                    '<div class="tds-row"><span class="tds-row-label">GNSS</span><span class="tds-row-value" id="tdsGnss"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">Visible satellites</span><span class="tds-row-value" id="tdsSatellites"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">Last online</span><span class="tds-row-value" id="tdsLastOnline"></span></div>' +
                '</div>' +
                '<div class="tds-section">' +
                    '<div class="tds-section-title">Today\'s Activity</div>' +
                    '<div class="tds-row"><span class="tds-row-label">Device accumulated mileage</span><span class="tds-row-value" id="tdsMileage"></span></div>' +
                '</div>' +
                '<div class="tds-section">' +
                    '<div class="tds-section-title">Vehicle</div>' +
                    '<div class="tds-row"><span class="tds-row-label">Name</span><span class="tds-row-value" id="tdsVehicleName"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">User</span><span class="tds-row-value" id="tdsUser"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">Phone</span><span class="tds-row-value" id="tdsPhone"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">Group</span><span class="tds-row-value" id="tdsGroup"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">ID</span><span class="tds-row-value" id="tdsId"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">Model</span><span class="tds-row-value" id="tdsModel"></span></div>' +
                    '<div class="tds-row"><span class="tds-row-label">License plate</span><span class="tds-row-value" id="tdsPlate"></span></div>' +
                '</div>' +
                '<div class="tds-action-bar">' +
                    '<button class="tds-action-btn" id="tdsLiveBtn" onclick="startLiveFollow()" title="Live"><i class="fa-solid fa-satellite-dish"></i><span>Live</span></button>' +
                    '<button class="tds-action-btn" id="tdsTracksBtn" onclick="openTracksModal()" title="Tracks"><i class="fa-solid fa-route"></i><span>Tracks</span></button>' +
                    '<button class="tds-action-btn" id="tdsShareBtn" onclick="openShareModal()" title="Share"><i class="fa-solid fa-share-nodes"></i><span>Share</span></button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(sb);
    }

    function openTractorSidebar(value) {
        try {
        ensureSidebarExists();

        var api = value.apiData || {};
        var tractor = value.tractor || {};
        var user = value.user || {};
        var group = value.group || {};

        // Store current sidebar context
        currentSidebarImei = value.imei_no || api.imei || null;
        currentSidebarDeviceId = value.id || null;

        // Header
        document.getElementById('tdsDeviceName').textContent = value.device_name || api.deviceName || 'Unknown';
        document.getElementById('tdsImei').textContent = value.imei_no || api.imei || '';

        // Status
        var statusBadge = document.getElementById('tdsStatusBadge');
        var statusTime = document.getElementById('tdsStatusTime');
        var acc = (api.accStatus == 1) ? 'ON' : 'OFF';
        var minutes = value.minutes || 0;

        statusBadge.className = 'tds-status-badge';
        if (minutes > 8) {
            statusBadge.classList.add('offline');
            statusBadge.innerHTML = '<i class="fa-solid fa-ban"></i> Offline (ACC: ' + acc + ')';
            statusTime.textContent = value.diff || '';
        } else if (api.status == 1 && api.accStatus == 1 && api.speed && api.speed != 0) {
            statusBadge.classList.add('moving');
            statusBadge.innerHTML = '<i class="fa-solid fa-tractor"></i> Moving (ACC: ' + acc + ')';
            statusTime.textContent = api.speed + ' km/h';
        } else if (api.status == 1) {
            statusBadge.classList.add('idling');
            statusBadge.innerHTML = '<i class="fa-solid fa-stopwatch"></i> Idling (ACC: ' + acc + ')';
            statusTime.textContent = value.diff || '';
        } else {
            statusBadge.classList.add('offline');
            statusBadge.innerHTML = '<i class="fa-solid fa-ban"></i> Offline (ACC: ' + acc + ')';
            statusTime.textContent = value.diff || '';
        }

        // Address — reverse geocode
        var lat = parseFloat(api.lat) || 0;
        var lng = parseFloat(api.lng) || 0;
        document.getElementById('tdsCoords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        document.getElementById('tdsAddress').textContent = 'Loading address...';
        reverseGeocode(lat, lng, function(addr) {
            var el = document.getElementById('tdsAddress');
            if (el) el.textContent = addr;
        });

        // Device
        var hbDate = new Date(api.hbTime || '');
        var localTime = isNaN(hbDate.getTime()) ? (api.hbTime || 'N/A') : hbDate.toLocaleString('en-US', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
        });
        document.getElementById('tdsGnss').textContent = api.posType || 'N/A';
        document.getElementById('tdsSatellites').textContent = (api.gpsNum !== undefined) ? api.gpsNum : 'N/A';
        document.getElementById('tdsLastOnline').textContent = localTime;

        // Mileage
        var mileage = api.currentMileage ? (parseFloat(api.currentMileage).toFixed(2) + ' km') : 'N/A';
        document.getElementById('tdsMileage').textContent = mileage;

        // Vehicle
        document.getElementById('tdsVehicleName').textContent = tractor.id_no ? (tractor.id_no + (tractor.model ? ' (' + tractor.model + ')' : '')) : '-';
        document.getElementById('tdsUser').textContent = user.name || user.email || '-';
        document.getElementById('tdsPhone').textContent = user.phone || '-';
        document.getElementById('tdsGroup').textContent = group.name || '-';
        document.getElementById('tdsId').textContent = tractor.id_no || '-';
        document.getElementById('tdsModel').textContent = tractor.model || '-';
        document.getElementById('tdsPlate').textContent = tractor.no_plate || '-';

        // Open
        var oEl = document.getElementById('tractorSidebarOverlay');
        var sEl = document.getElementById('tractorDetailSidebar');
        oEl.classList.add('open');
        oEl.style.display = 'block';
        sEl.classList.add('open');
        sEl.style.right = '0';
        } catch(e) {
            console.error('openTractorSidebar error:', e);
        }
    }

    function closeTractorSidebar() {
        var sb = document.getElementById('tractorDetailSidebar');
        var ov = document.getElementById('tractorSidebarOverlay');
        if (sb) { sb.classList.remove('open'); sb.style.right = '-420px'; }
        if (ov) { ov.classList.remove('open'); ov.style.display = 'none'; }
        stopLiveFollow();
    }

    // ─── Live Follow ───
    function startLiveFollow() {
        var btn = document.getElementById('tdsLiveBtn');
        if (liveFollowInterval) {
            // Already following — stop
            stopLiveFollow();
            return;
        }
        if (!currentSidebarImei) return;

        // Visual feedback
        if (btn) { btn.classList.add('active'); btn.querySelector('span').textContent = 'Following'; }

        // Immediately center
        var m = markers['marker_' + currentSidebarImei];
        if (m) {
            map.setZoom(16);
            map.panTo(m.getPosition());
        }

        // Re-center every 3 seconds
        liveFollowInterval = setInterval(function() {
            var mk = markers['marker_' + currentSidebarImei];
            if (mk) {
                map.panTo(mk.getPosition());
            }
        }, 3000);
    }

    function stopLiveFollow() {
        if (liveFollowInterval) {
            clearInterval(liveFollowInterval);
            liveFollowInterval = null;
        }
        var btn = document.getElementById('tdsLiveBtn');
        if (btn) { btn.classList.remove('active'); btn.querySelector('span').textContent = 'Live'; }
    }

    // ─── Tracks Modal ───
    function ensureTracksModalExists() {
        if (document.getElementById('tracksModalOverlay')) return;

        var overlay = document.createElement('div');
        overlay.id = 'tracksModalOverlay';
        overlay.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.5);z-index:10010;align-items:center;justify-content:center;';
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeTracksModal(); });

        var modal = document.createElement('div');
        modal.id = 'tracksModal';
        modal.style.cssText = 'background:#fff;border-radius:12px;width:820px;max-width:95vw;max-height:92vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,.25);font-family:Segoe UI,Roboto,Arial,sans-serif;';
        modal.innerHTML =
            '<div style="padding:14px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">' +
                '<h5 style="margin:0;font-size:17px;font-weight:600;"><i class="fa-solid fa-route" style="color:#1a73e8;margin-right:6px;"></i>Track History</h5>' +
                '<button onclick="closeTracksModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;padding:0 4px;">&times;</button>' +
            '</div>' +
            '<div style="padding:16px 20px;">' +
                '<div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">' +
                    '<div style="flex:1;min-width:140px;">' +
                        '<label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:3px;">Period</label>' +
                        '<select id="tracksModalPeriod" style="width:100%;padding:7px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;">' +
                            '<option value="1">Today</option>' +
                            '<option value="3" selected>Last 3 days</option>' +
                            '<option value="4">This week</option>' +
                            '<option value="6">This month</option>' +
                            '<option value="7">Last month</option>' +
                            '<option value="8">Custom</option>' +
                        '</select>' +
                    '</div>' +
                    '<div id="tracksModalDateRow" style="flex:1;min-width:180px;display:none;">' +
                        '<label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:3px;">Date Range</label>' +
                        '<input type="text" id="tracksModalDateRange" style="width:100%;padding:7px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;" placeholder="Select date range" readonly />' +
                    '</div>' +
                    '<button id="tracksModalSearchBtn" onclick="searchTracksModal()" style="padding:7px 20px;background:#1a73e8;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;height:36px;">Search</button>' +
                '</div>' +
                '<div id="tracksModalLoader" style="display:none;text-align:center;padding:30px;">' +
                    '<span class="spinner-border text-primary"></span> Loading track data...' +
                '</div>' +
                '<div id="tracksModalError" style="display:none;margin-top:12px;padding:10px;background:#fff3cd;color:#856404;border-radius:6px;font-size:13px;"></div>' +
                '<div id="tracksModalMapContainer" style="display:none;margin-top:14px;border-radius:8px;overflow:hidden;border:1px solid #eee;">' +
                    '<div id="tracksModalMap" style="width:100%;height:420px;"></div>' +
                    '<div id="tmPlaybackPanel" style="background:#fff;padding:10px 16px 8px;">' +
                        '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">' +
                            '<span style="font-size:12px;color:#555;">Speed: <b id="tmSpeedVal">0</b> km/h</span>' +
                            '<span id="tmGpsTime" style="font-size:12px;color:#888;"></span>' +
                        '</div>' +
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                            '<button id="tmPlayBtn" onclick="tmTogglePlay()" style="width:32px;height:32px;border:none;background:#1a73e8;color:#fff;border-radius:50%;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-play"></i></button>' +
                            '<div id="tmProgressBarWrap" style="flex:1;height:6px;background:#e0e0e0;border-radius:3px;cursor:pointer;position:relative;" onclick="tmSeek(event)">' +
                                '<div id="tmProgressBar" style="height:100%;width:0%;background:#1a73e8;border-radius:3px;transition:width .1s;"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">' +
                            '<button onclick="tmReplay()" style="border:none;background:none;cursor:pointer;font-size:12px;color:#1a73e8;display:flex;align-items:center;gap:4px;"><i class="fa-solid fa-arrow-rotate-right"></i> Replay</button>' +
                            '<div style="display:flex;align-items:center;gap:4px;">' +
                                '<span style="font-size:11px;color:#888;">Speed:</span>' +
                                '<select id="tmSpeedMultiplier" onchange="tmUpdateSpeed()" style="border:1px solid #ddd;border-radius:4px;font-size:11px;padding:1px 4px;">' +
                                    '<option value="1" selected>1x</option>' +
                                    '<option value="2">2x</option>' +
                                    '<option value="4">4x</option>' +
                                    '<option value="8">8x</option>' +
                                '</select>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    }

    var tracksModalMap = null;
    var tracksModalPolyline = null;
    // Tracks modal playback state
    var tmTrackPath = null;      // full path polyline (light)
    var tmAnimPolyline = null;   // animated progressive polyline
    var tmAnimMarker = null;     // tractor marker
    var tmAnimInterval = null;
    var tmCoords = [];
    var tmGpsTime = [];
    var tmGpsSpeed = [];
    var tmDirection = [];
    var tmStep = 0;
    var tmPlaying = false;
    var tmSpeedMult = 1;
    var tmAnimDrawn = [];        // coords drawn so far
    const tmIntervalMs = 16;
    const tmPixelsPerTick = 0.5;
    const tmTractorSvg = "M15 30C18.9782 30 22.7936 28.4196 25.6066 25.6066C28.4196 22.7936 30 18.9782 30 15C30 11.0218 28.4196 7.20644 25.6066 4.3934C22.7936 1.58035 18.9782 0 15 0C11.0218 0 7.20644 1.58035 4.3934 4.3934C1.58035 7.20644 0 11.0218 0 15C0 18.9782 1.58035 22.7936 4.3934 25.6066C7.20644 28.4196 11.0218 30 15 30ZM22.0898 15.8789C22.6406 16.4297 22.6406 17.3203 22.0898 17.8652C21.5391 18.4102 20.6484 18.416 20.1035 17.8652L15.0059 12.7676L9.9082 17.8652C9.35742 18.416 8.4668 18.416 7.92187 17.8652C7.37695 17.3145 7.37109 16.4238 7.92187 15.8789L14.0039 9.78516C14.5547 9.23438 15.4453 9.23438 15.9902 9.78516L22.0898 15.8789Z";

    function openTracksModal() {
        if (!currentSidebarImei) return;
        ensureTracksModalExists();
        tmStopAnimation();

        var overlay = document.getElementById('tracksModalOverlay');
        overlay.style.display = 'flex';
        document.getElementById('tracksModalMapContainer').style.display = 'none';
        document.getElementById('tracksModalError').style.display = 'none';
        document.getElementById('tracksModalLoader').style.display = 'none';

        var periodEl = document.getElementById('tracksModalPeriod');
        periodEl.value = '3';
        document.getElementById('tracksModalDateRow').style.display = 'none';

        periodEl.onchange = function() {
            document.getElementById('tracksModalDateRow').style.display = (this.value == '8') ? 'block' : 'none';
        };

        if ($.fn.daterangepicker && !$('#tracksModalDateRange').data('daterangepicker')) {
            $('#tracksModalDateRange').daterangepicker({
                autoUpdateInput: false,
                maxDate: new Date(),
                locale: { format: 'YYYY/MM/DD', cancelLabel: 'Clear' }
            });
            $('#tracksModalDateRange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format('YYYY/MM/DD'));
            });
            $('#tracksModalDateRange').on('cancel.daterangepicker', function() {
                $(this).val('');
            });
        }
    }

    function closeTracksModal() {
        var overlay = document.getElementById('tracksModalOverlay');
        if (overlay) overlay.style.display = 'none';
        tmStopAnimation();
        if (tmTrackPath) { tmTrackPath.setMap(null); tmTrackPath = null; }
        if (tmAnimPolyline) { tmAnimPolyline.setMap(null); tmAnimPolyline = null; }
        if (tmAnimMarker) { tmAnimMarker.setMap(null); tmAnimMarker = null; }
        if (tracksModalPolyline) { tracksModalPolyline.setMap(null); tracksModalPolyline = null; }
    }

    function searchTracksModal() {
        if (!currentSidebarDeviceId && !currentSidebarImei) return;

        var period = document.getElementById('tracksModalPeriod').value;
        var dateRange = document.getElementById('tracksModalDateRange') ? document.getElementById('tracksModalDateRange').value : '';
        var errEl = document.getElementById('tracksModalError');
        var loaderEl = document.getElementById('tracksModalLoader');
        var mapContainer = document.getElementById('tracksModalMapContainer');

        errEl.style.display = 'none';
        loaderEl.style.display = 'block';
        mapContainer.style.display = 'none';
        tmStopAnimation();

        $.ajax({
            url: '{{ route("liveview.getTrackData") }}',
            type: 'GET',
            data: {
                device_imei: currentSidebarDeviceId || '',
                period: period,
                date_range: dateRange
            },
            success: function(response) {
                loaderEl.style.display = 'none';
                if (response.error) {
                    errEl.textContent = response.error;
                    errEl.style.display = 'block';
                    return;
                }
                if (!response.latlng || response.latlng.length === 0) {
                    errEl.textContent = 'No track data found for this period.';
                    errEl.style.display = 'block';
                    return;
                }

                // Store track data
                tmCoords = response.latlng;
                tmGpsTime = response.gpsTime || [];
                tmGpsSpeed = response.gpsSpeed || [];
                tmDirection = response.direction || [];
                tmStep = 0;
                tmPlaying = false;
                tmAnimDrawn = [];

                // Show map
                mapContainer.style.display = 'block';
                if (!tracksModalMap) {
                    tracksModalMap = new google.maps.Map(document.getElementById('tracksModalMap'), {
                        center: tmCoords[0],
                        zoom: 14,
                        mapTypeId: 'roadmap',
                        mapTypeControl: false,
                        streetViewControl: false,
                    });
                } else {
                    tracksModalMap.setCenter(tmCoords[0]);
                    tracksModalMap.setZoom(14);
                }

                // Clear old overlays
                if (tmTrackPath) tmTrackPath.setMap(null);
                if (tmAnimPolyline) tmAnimPolyline.setMap(null);
                if (tmAnimMarker) { tmAnimMarker.setMap(null); tmAnimMarker = null; }
                if (tracksModalPolyline) { tracksModalPolyline.setMap(null); tracksModalPolyline = null; }

                // Light full-path polyline (shows entire route faintly)
                tmTrackPath = new google.maps.Polyline({
                    path: tmCoords,
                    geodesic: true,
                    strokeColor: '#1a73e8',
                    strokeOpacity: 0.25,
                    strokeWeight: 3,
                    map: tracksModalMap,
                });

                // Animated progressive polyline (draws behind the tractor)
                tmAnimPolyline = new google.maps.Polyline({
                    geodesic: true,
                    strokeColor: '#1a73e8',
                    strokeOpacity: 1.0,
                    strokeWeight: 3,
                    zIndex: 1000,
                    map: tracksModalMap,
                });

                // Fit bounds
                var bounds = new google.maps.LatLngBounds();
                tmCoords.forEach(function(p) { bounds.extend(p); });
                tracksModalMap.fitBounds(bounds);
                google.maps.event.trigger(tracksModalMap, 'resize');

                // Reset playback UI
                document.getElementById('tmProgressBar').style.width = '0%';
                document.getElementById('tmSpeedVal').textContent = tmGpsSpeed[0] || '0';
                tmUpdateTimeDisplay(0);
                var playBtn = document.getElementById('tmPlayBtn');
                playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
            },
            error: function() {
                loaderEl.style.display = 'none';
                errEl.textContent = 'Failed to fetch track data. Please try again.';
                errEl.style.display = 'block';
            }
        });
    }

    // ─── Tracks Modal Playback Engine ───

    function tmTogglePlay() {
        if (tmCoords.length === 0) return;
        if (tmPlaying) {
            tmPause();
        } else {
            tmPlay();
        }
    }

    function tmPlay() {
        if (tmCoords.length === 0) return;
        tmPlaying = true;
        var playBtn = document.getElementById('tmPlayBtn');
        playBtn.innerHTML = '<i class="fa-solid fa-pause"></i>';

        // If finished, restart
        if (tmStep >= tmCoords.length) {
            tmStep = 0;
            tmAnimDrawn = [];
            if (tmAnimPolyline) tmAnimPolyline.setPath([]);
        }

        // Create tractor marker if needed
        if (!tmAnimMarker) {
            tmAnimMarker = new google.maps.Marker({
                map: tracksModalMap,
                position: tmCoords[tmStep],
                icon: {
                    path: tmTractorSvg,
                    anchor: new google.maps.Point(15, 15),
                    rotation: tmDirection[tmStep] || 0,
                    fillColor: '#fff',
                    fillOpacity: 1,
                    strokeColor: '#1a73e8',
                    strokeWeight: 2,
                    scale: 1,
                },
                zIndex: 2000,
            });
        } else {
            tmAnimMarker.setMap(tracksModalMap);
            tmAnimMarker.setPosition(tmCoords[tmStep]);
        }

        tmAnimDrawn.push(tmCoords[tmStep]);

        tmSpeedMult = parseInt(document.getElementById('tmSpeedMultiplier').value) || 1;

        tmAnimInterval = setInterval(function() {
            if (tmStep >= tmCoords.length) {
                tmStopAnimation();
                var playBtn2 = document.getElementById('tmPlayBtn');
                if (playBtn2) playBtn2.innerHTML = '<i class="fa-solid fa-play"></i>';
                return;
            }

            var target = tmCoords[tmStep];
            var curPos = tmAnimMarker.getPosition();
            var heading = google.maps.geometry.spherical.computeHeading(curPos, target);
            var dist = google.maps.geometry.spherical.computeDistanceBetween(curPos, target);
            var moveBy = tmPixelsPerTick * tmSpeedMult;

            if (dist > moveBy) {
                var newPos = google.maps.geometry.spherical.computeOffset(curPos, moveBy, heading);
                tmAnimMarker.setPosition(newPos);
                tmAnimMarker.setIcon({
                    path: tmTractorSvg,
                    anchor: new google.maps.Point(15, 15),
                    rotation: tmDirection[tmStep] || 0,
                    fillColor: '#fff',
                    fillOpacity: 1,
                    strokeColor: '#1a73e8',
                    strokeWeight: 2,
                    scale: 1,
                });
                tmAnimDrawn.push({lat: newPos.lat(), lng: newPos.lng()});
            } else {
                tmAnimMarker.setPosition(target);
                tmAnimMarker.setIcon({
                    path: tmTractorSvg,
                    anchor: new google.maps.Point(15, 15),
                    rotation: tmDirection[tmStep] || 0,
                    fillColor: '#fff',
                    fillOpacity: 1,
                    strokeColor: '#1a73e8',
                    strokeWeight: 2,
                    scale: 1,
                });
                tmAnimDrawn.push(target);
                tmStep++;
            }

            // Update progressive polyline
            tmAnimPolyline.setPath(tmAnimDrawn);

            // Update UI
            var pct = (tmStep / tmCoords.length) * 100;
            document.getElementById('tmProgressBar').style.width = pct + '%';
            var idx = Math.min(tmStep, tmGpsSpeed.length - 1);
            document.getElementById('tmSpeedVal').textContent = tmGpsSpeed[idx] || '0';
            tmUpdateTimeDisplay(idx);
        }, tmIntervalMs);
    }

    function tmPause() {
        tmPlaying = false;
        if (tmAnimInterval) { clearInterval(tmAnimInterval); tmAnimInterval = null; }
        var playBtn = document.getElementById('tmPlayBtn');
        if (playBtn) playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
    }

    function tmStopAnimation() {
        tmPlaying = false;
        if (tmAnimInterval) { clearInterval(tmAnimInterval); tmAnimInterval = null; }
    }

    function tmReplay() {
        tmStopAnimation();
        tmStep = 0;
        tmAnimDrawn = [];
        if (tmAnimPolyline) tmAnimPolyline.setPath([]);
        if (tmAnimMarker) tmAnimMarker.setMap(null);
        tmAnimMarker = null;
        document.getElementById('tmProgressBar').style.width = '0%';
        document.getElementById('tmSpeedVal').textContent = tmGpsSpeed[0] || '0';
        tmUpdateTimeDisplay(0);
        var playBtn = document.getElementById('tmPlayBtn');
        if (playBtn) playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
        tmPlay();
    }

    function tmUpdateSpeed() {
        tmSpeedMult = parseInt(document.getElementById('tmSpeedMultiplier').value) || 1;
    }

    function tmSeek(event) {
        if (tmCoords.length === 0) return;
        var bar = document.getElementById('tmProgressBarWrap');
        var rect = bar.getBoundingClientRect();
        var pct = (event.clientX - rect.left) / rect.width;
        pct = Math.max(0, Math.min(1, pct));
        var newStep = Math.floor(pct * tmCoords.length);

        var wasPlaying = tmPlaying;
        tmStopAnimation();

        tmStep = newStep;
        // Rebuild drawn path up to this step
        tmAnimDrawn = tmCoords.slice(0, tmStep + 1);
        if (tmAnimPolyline) tmAnimPolyline.setPath(tmAnimDrawn);

        if (tmAnimMarker) {
            tmAnimMarker.setPosition(tmCoords[tmStep]);
        }

        document.getElementById('tmProgressBar').style.width = (pct * 100) + '%';
        var idx = Math.min(tmStep, tmGpsSpeed.length - 1);
        document.getElementById('tmSpeedVal').textContent = tmGpsSpeed[idx] || '0';
        tmUpdateTimeDisplay(idx);

        if (wasPlaying) tmPlay();
    }

    function tmUpdateTimeDisplay(idx) {
        var el = document.getElementById('tmGpsTime');
        if (!el) return;
        var raw = tmGpsTime[idx];
        if (!raw) { el.textContent = ''; return; }
        var d = new Date(raw);
        if (isNaN(d.getTime())) { el.textContent = raw; return; }
        el.textContent = d.toLocaleString('en-US', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
        });
    }

    // ─── Share Modal ───
    function ensureShareModalExists() {
        if (document.getElementById('shareModalOverlay')) return;

        var overlay = document.createElement('div');
        overlay.id = 'shareModalOverlay';
        overlay.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.5);z-index:10010;align-items:center;justify-content:center;';
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeShareModal(); });

        var modal = document.createElement('div');
        modal.id = 'shareModal';
        modal.style.cssText = 'background:#fff;border-radius:12px;width:420px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.25);font-family:Segoe UI,Roboto,Arial,sans-serif;';
        modal.innerHTML =
            '<div style="padding:16px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;">' +
                '<h5 style="margin:0;font-size:17px;font-weight:600;">Share Location</h5>' +
                '<button onclick="closeShareModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;padding:0 4px;">&times;</button>' +
            '</div>' +
            '<div style="padding:20px;">' +
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding:12px;background:#f8f9fa;border-radius:8px;">' +
                    '<i class="fa-solid fa-clock" style="font-size:20px;color:#1a73e8;"></i>' +
                    '<div>' +
                        '<div style="font-size:15px;font-weight:600;color:#333;">Valid for 1 Hour</div>' +
                        '<div style="font-size:12px;color:#888;">Anyone with the link can view the live location</div>' +
                    '</div>' +
                '</div>' +
                '<div id="shareModalResult" style="display:none;margin-bottom:14px;">' +
                    '<label style="font-size:13px;font-weight:500;color:#555;display:block;margin-bottom:4px;">Share Link</label>' +
                    '<div style="display:flex;gap:8px;">' +
                        '<input type="text" id="shareModalUrl" readonly style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:13px;background:#f8f9fa;" />' +
                        '<button onclick="copyShareLink()" style="padding:8px 14px;background:#1a73e8;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;white-space:nowrap;"><i class="fa-solid fa-copy"></i> Copy</button>' +
                    '</div>' +
                    '<div id="shareModalCopied" style="display:none;font-size:12px;color:#28a745;margin-top:4px;">Link copied to clipboard!</div>' +
                '</div>' +
                '<div id="shareModalError" style="display:none;margin-bottom:14px;padding:10px;background:#f8d7da;color:#842029;border-radius:6px;font-size:13px;"></div>' +
                '<button id="shareModalGetLinkBtn" onclick="getShareLink()" style="width:100%;padding:10px;background:#1a73e8;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer;">' +
                    '<i class="fa-solid fa-link"></i> Get a link' +
                '</button>' +
                '<div id="shareModalLoader" style="display:none;text-align:center;padding:12px;">' +
                    '<span class="spinner-border spinner-border-sm text-primary"></span> Generating link...' +
                '</div>' +
            '</div>';

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    }

    function openShareModal() {
        if (!currentSidebarImei) return;
        ensureShareModalExists();

        var overlay = document.getElementById('shareModalOverlay');
        overlay.style.display = 'flex';
        document.getElementById('shareModalResult').style.display = 'none';
        document.getElementById('shareModalError').style.display = 'none';
        document.getElementById('shareModalLoader').style.display = 'none';
        document.getElementById('shareModalGetLinkBtn').style.display = 'block';
    }

    function closeShareModal() {
        var overlay = document.getElementById('shareModalOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    function getShareLink() {
        if (!currentSidebarImei) return;

        var btnEl = document.getElementById('shareModalGetLinkBtn');
        var loaderEl = document.getElementById('shareModalLoader');
        var resultEl = document.getElementById('shareModalResult');
        var errEl = document.getElementById('shareModalError');

        btnEl.style.display = 'none';
        loaderEl.style.display = 'block';
        resultEl.style.display = 'none';
        errEl.style.display = 'none';

        $.ajax({
            url: '{{ route("liveview.createShareLink") }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { imei: currentSidebarImei },
            success: function(response) {
                loaderEl.style.display = 'none';
                if (response.success) {
                    document.getElementById('shareModalUrl').value = response.url;
                    resultEl.style.display = 'block';
                    document.getElementById('shareModalCopied').style.display = 'none';
                } else {
                    errEl.textContent = response.error || 'Failed to generate link.';
                    errEl.style.display = 'block';
                    btnEl.style.display = 'block';
                }
            },
            error: function(xhr) {
                loaderEl.style.display = 'none';
                var msg = 'Failed to generate share link.';
                if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                errEl.textContent = msg;
                errEl.style.display = 'block';
                btnEl.style.display = 'block';
            }
        });
    }

    function copyShareLink() {
        var urlInput = document.getElementById('shareModalUrl');
        if (!urlInput) return;
        urlInput.select();
        document.execCommand('copy');
        var copiedEl = document.getElementById('shareModalCopied');
        if (copiedEl) {
            copiedEl.style.display = 'block';
            setTimeout(function() { copiedEl.style.display = 'none'; }, 2000);
        }
    }

    function reverseGeocode(lat, lng, callback) {
        if (!window.google || !google.maps || !google.maps.Geocoder) {
            callback(lat + ', ' + lng);
            return;
        }
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat: parseFloat(lat), lng: parseFloat(lng) } }, function(results, status) {
            if (status === 'OK' && results && results[0]) {
                callback(results[0].formatted_address);
            } else {
                callback(lat + ', ' + lng);
            }
        });
    }

    //Initialize Map 
    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            center: {
                lat: 14.17092,
                lng: 121.291831,
            },
            zoom: 5,
            mapTypeId: defaultMapType,
            mapTypeControl: false, // We use our own custom control
            streetViewControl: true,
        });

        // --- Custom Map Type Switcher Control ---
        const mapTypeControlDiv = document.createElement('div');
        mapTypeControlDiv.id = 'customMapTypeControl';
        mapTypeControlDiv.style.cssText = 'display:flex;gap:2px;margin:10px;background:#fff;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.3);overflow:hidden;font-family:Roboto,Arial,sans-serif;font-size:12px;';

        const types = [
            { id: 'roadmap',   label: 'Map' },
            { id: 'satellite', label: 'Satellite' },
            { id: 'terrain',   label: 'Terrain' },
        ];

        types.forEach(function(t) {
            const btn = document.createElement('button');
            btn.textContent = t.label;
            btn.dataset.maptype = t.id;
            btn.style.cssText = 'border:none;padding:6px 12px;cursor:pointer;font-size:12px;font-weight:500;transition:background .2s,color .2s;';
            if (t.id === defaultMapType) {
                btn.style.background = '#1a73e8';
                btn.style.color = '#fff';
            } else {
                btn.style.background = '#fff';
                btn.style.color = '#333';
            }
            btn.addEventListener('click', function() {
                map.setMapTypeId(t.id);
                mapTypeControlDiv.querySelectorAll('button').forEach(function(b) {
                    b.style.background = '#fff';
                    b.style.color = '#333';
                });
                btn.style.background = '#1a73e8';
                btn.style.color = '#fff';
            });
            mapTypeControlDiv.appendChild(btn);
        });

        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(mapTypeControlDiv);

        createMarkersFunction();

        maps['map'] = map;

        map.addListener("click", function(event) {
            // mapClicked(event);
        });

        radiusCircle = new google.maps.Circle({
            clickable: false,
            strokeColor: "#c3fc49",
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: "#c3fc49",
            fillOpacity: 0.35,
        });

        playback_polyline = new google.maps.Polyline({
            geodesic: true,
            strokeColor: "#008000",
            strokeOpacity: 2.0,
            strokeWeight: 2,
            zIndex: 1000
        });

        playback_marker = new google.maps.Marker();
    }

    //Function to create markers on initialization
    function createMarkersFunction() {
        const source = new EventSource(markersDataUrl);

        source.onmessage = function(event) {
            const response = JSON.parse(event.data);

            if (response.end) {
                source.close();
                initializeMarkerClusterer(clusterMarkers); // Finalize clustering
                return;
            }

            const value = response.device; // Extract individual device data
            // Create marker for this device
            let icon = redIcon;
            let dateTimeString = value.apiData.hbTime;
            let parsedDate = new Date(dateTimeString);
            let localTime = parsedDate.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            if (value.minutes > 8) {
                icon = redIcon;
            } else {
                if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed != 0) {
                    icon = greenIcon;
                } else if (value.apiData.status == 1 && (value.apiData.speed == 0 || value.apiData.speed == null)) {
                    icon = yellowIcon;
                }
            }

            let acc = 'OFF';
            if (value.apiData.accStatus == 1) { // Fixed typo: = to ==
                acc = 'ON';
            }

            let lat = value.apiData.lat;
            let lng = value.apiData.lng;

            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(lat, lng),
                icon: icon,
                map: map,
            });

            var contentString =
                '<div id="content">' +
                '<div id="siteNotice" class="tractor-details">' +
                '<h3 id="firstHeading" class="tractor-heading">' + value.device_name + '</h3>' +
                '<h4 id="firstHeading" class="tractor-id">' + value.imei_no + '</h4>' +
                '<div class="card mb-3">' +
                '<div class="card-body">' +
                '<div class="d-flex align-items-center justify-content-between">' +
                '<div class="status">';


            if (value.minutes > 8) {
                contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' +
                    acc + ')';
            } else {
                if (value.apiData.status == 1 && (value.apiData.speed == 0 || value.apiData.speed == null)) {
                    contentString +=
                        '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' + acc +
                        ')';
                } else if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed != 0) {
                    contentString +=
                        '<span><i class="fa-solid fa-tractor text-success me-2"></i></span>Moving (ACC: ' + acc +
                        ')';
                } else {
                    contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' +
                        acc + ')';
                }
            }



            contentString += '</div>';
            contentString += '<div>';

            if (value.minutes > 8) {
                contentString += value.diff;
            } else {
                if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed != 0) {
                    contentString += value.apiData.speed + ' km/h';
                } else {
                    contentString += value.diff;
                }
            }


            contentString += '</div></div></div></div>' +
                '<div class="card  mb-3"> <div class="card-body">' +
                '<h5 class="tractor-heading">Address</h5>' +
                '<h4 class="tractor-id" id="device_address' + value.imei_no + '">' + lat + ', ' + lng + '</h4>' +
                '</div></div>' +
                '<div class="card  mb-3"><div class="card-body">' +
                '<h3 class="tractor-id">Device</h3>' +
                '<div class="d-flex flex-wrap justify-content-between">' +
                '<div>GNSS</div>' +
                '<div>' + value.apiData.posType + '</div>' +
                '</div>' +
                '<div class="d-flex flex-wrap justify-content-between">' +
                '<div>Visible Satelites</div>' +
                '<div>' + value.apiData.gpsNum + '</div>' +
                '</div>' +
                '<div class="d-flex flex-wrap justify-content-between gap-2">' +
                '<div>Last online</div>' +
                '<div>' + localTime + '</div>' +
                '</div></div></div>';

                contentString += '<div class="card mb-3">' +
                    '<div class="card-body">' +
                    '<h3 class="tractor-id">Vehicle</h3>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Name : </div>' +
                    '<div>' + (value.tractor?.id_no ? value.tractor.id_no + ' (' + value.tractor.model + ')' : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>User : </div>' +
                    '<div>' + ((value.user?.name || value.user?.email) ? (value.user.name || value.user.email) : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Group : </div>' +
                    '<div>' + (value.group?.name ? value.group.name : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Phone : </div>' +
                    '<div>' + (value.user?.phone ? value.user.phone : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Id : </div>' +
                    '<div>' + (value.tractor?.id_no ? value.tractor.id_no : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Model : </div>' +
                    '<div>' + (value.tractor?.model ? value.tractor.model : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>License plate : </div>' +
                    '<div>' + (value.tractor?.no_plate ? value.tractor.no_plate : 'N/A') + '</div>' +
                    '</div></div></div></div>';

            var infowindow = new google.maps.InfoWindow({
                content: contentString,
                ariaLabel: "Uluru",
            });

            marker.addListener("click", () => {
                // Zoom to tractor location
                map.setZoom(16);
                map.panTo(marker.getPosition());

                // Open the detail sidebar with stored device data
                var storedData = deviceDataStore[value.imei_no] || value;
                openTractorSidebar(storedData);
            });

            // Store device data for sidebar use
            deviceDataStore[value.imei_no] = value;

            markers['marker_' + value.imei_no] = marker;
            contents['infowindow_' + value.imei_no] = infowindow;
            clusterMarkers.push(marker);
        };

        source.onerror = function() {
            console.error('Error occurred in streaming');
            source.close();
        };
    }

    //function to create cluster of markers
    function initializeMarkerClusterer(markers) {
        markerCluster = new MarkerClusterer(map, markers, {
            imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
        });
    }

    function playPauseDevice(event) {
        let id = $(event).data('id');
        let action = $(event).data('action');

        if (action == 'play') {
            $('#locate_button').removeClass('d-none');
            $('#playButton').addClass('d-none');
            $('#pauseButton').removeClass('d-none');
            playReplay()
        } else if (action == 'replay') {
            if (playback_marker) {
                playback_marker.setMap(null);
            }
            if (playback_polyline) {
                playback_polyline.setMap(null);
            }
            clearInterval(animationInterval);
            $('#locate_button').removeClass('d-none');
            $('#playButton').addClass('d-none');
            $('#pauseButton').removeClass('d-none');
            is_paused = false;
            playReplay()
        } else if (action == 'pause') {
            $('#playButton').removeClass('d-none');
            $('#pauseButton').addClass('d-none');
            is_paused = true;
            clearInterval(animationInterval);
            animationInterval = null;
        }
    }

    function playReplay() {
        if (is_paused) {
            playback_marker.setMap(null);
            step = step;
        } else {
            step = 0;
            if (playback_marker) {
                playback_marker.setMap(null);
            }
            if (playback_polyline) {
                playback_polyline.setMap(null);
            }
        }
        newCordinates = [];
        let path = historyCordinates;
        const totalSteps = path.length;
        const lineSymbol = {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 7,
            strokeColor: "#393",
        };
        const tractorPath =
            "M15 30C18.9782 30 22.7936 28.4196 25.6066 25.6066C28.4196 22.7936 30 18.9782 30 15C30 11.0218 28.4196 7.20644 25.6066 4.3934C22.7936 1.58035 18.9782 0 15 0C11.0218 0 7.20644 1.58035 4.3934 4.3934C1.58035 7.20644 0 11.0218 0 15C0 18.9782 1.58035 22.7936 4.3934 25.6066C7.20644 28.4196 11.0218 30 15 30ZM22.0898 15.8789C22.6406 16.4297 22.6406 17.3203 22.0898 17.8652C21.5391 18.4102 20.6484 18.416 20.1035 17.8652L15.0059 12.7676L9.9082 17.8652C9.35742 18.416 8.4668 18.416 7.92187 17.8652C7.37695 17.3145 7.37109 16.4238 7.92187 15.8789L14.0039 9.78516C14.5547 9.23438 15.4453 9.23438 15.9902 9.78516L22.0898 15.8789Z";

        if (is_paused) {
            playback_marker.setMap(map);
            playback_marker.setPosition(path[step]);
            playback_marker.setIcon({
                path: tractorPath,
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(16, 16),
                rotation: direction[step],
                fillColor: 'white',
                fillOpacity: 1,
                strokeColor: 'green',
                strokeWeight: 2,

            });
        } else {
            playback_marker.setMap(map);
            playback_marker.setPosition(path[0]);
            playback_marker.setIcon({
                path: tractorPath,
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(16, 16),
                rotation: direction[step],
                fillColor: 'white',
                fillOpacity: 1,
                strokeColor: 'green',
                strokeWeight: 2,

            });
        }
        if (is_paused) {
            newCordinates.push(path[step]); // Initialize with the step position
        } else {
            newCordinates.push(path[0]); // Initialize with the first position
        }

        animationInterval = setInterval(function() {
            if (step >= path.length) {
                clearInterval(animationInterval);
                playback_marker.setMap(null);
                playback_polyline.setMap(null);
                $('#locate_button').addClass('d-none');
                return;
            }

            const newPosition = path[step];
            const currentRotation = direction[step - 1];
            const currentMarkerPosition = playback_marker.getPosition();
            const heading = google.maps.geometry.spherical.computeHeading(
                currentMarkerPosition,
                newPosition
            );
            const distance = google.maps.geometry.spherical.computeDistanceBetween(
                currentMarkerPosition,
                newPosition
            );

            if (distance > pixelsToMovePerInterval) {
                const newLatLng = google.maps.geometry.spherical.computeOffset(
                    currentMarkerPosition,
                    pixelsToMovePerInterval,
                    heading,
                );
                playback_marker.setIcon({
                    path: tractorPath,
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(16, 16),
                    rotation: direction[step],
                    fillColor: 'white',
                    fillOpacity: 1,
                    strokeColor: 'green',
                    strokeWeight: 2,

                });
                playback_marker.setPosition(newLatLng);
                // map.panTo(newLatLng);
            } else {
                playback_marker.setPosition(newPosition);
                playback_marker.setIcon({
                    path: tractorPath,
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(16, 16),
                    rotation: direction[step],
                    fillColor: 'white',
                    fillOpacity: 1,
                    strokeColor: 'green',
                    strokeWeight: 2,

                });
                // map.panTo(newPosition);
                step++;
            }
            progress = (step / totalSteps) * 100; // Update progress based on steps
            updateProgressBar();
            var dateTimeString = gpsTime[step];
            if (dateTimeString === undefined) {
                var dateTimeString = gpsTime[step - 1];
            }
            var parsedDate = new Date(dateTimeString);
            var localTime = parsedDate.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            $('#gpsSpeedId').text(gpsSpeed[step]);
            $('#gpsTimeId').text(localTime);
            locationPath = path[step];
            newCordinates.push(newPosition); // Push each new position into the array
            playback_polyline.setMap(map);
            playback_polyline.setPath(newCordinates);
        }, intervalDuration);
    }

    function updateProgressBar() {
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = progress + '%';
    }

    function locateDevice() {
        maps['map'].setCenter({
            lat: locationPath.lat,
            lng: locationPath.lng
        });
    }

    $(document).ready(function() {
        let state = "{{ $state }}";

        // Load initial data
        appendGroupDevices();
        getDevicesCount();

        function appendGroupDevices() {
            const source = new EventSource(appendGroupDevicesUrl);

            source.onmessage = function(event) {
                const data = JSON.parse(event.data);

                // Handle end signal
                if (data.end) {
                    source.close(); // Close the connection
                    return;
                }

                // Append HTML for each group
                const groupId = data.group_id;
                const html = data.html;
                let div = $('#groupDevices' + groupId);
                div.html(html);

                // Update device count badge if provided
                if (data.device_count !== undefined) {
                    let badge = div.closest('.accordion-item').find('.accordion-button .badge');
                    if (badge.length) {
                        badge.text(data.device_count);
                    }
                }
            };

            source.onerror = function() {
                console.error('Error occurred in streaming');
                source.close();
            };
        }

        function getDevicesCount() {
            $.ajax({
                url: devicesCountUrl,
                type: 'GET',
                success: function(response) {
                    $('#onlineCount').html('(' + response.data.onlineCount + ')');
                    $('#offlineCount').html('(' + response.data.offlineCount + ')');
                    $('#inactiveCount').html('(' + response.data.inactiveCount + ')');
                    $('#movingDevices').html('Moving (' + response.data.movingCount + ')');
                    $('#idleDevices').html('Idle (' + response.data.idleCount + ')');
                }
            });
        }

        $('#home-tab').click(function() {
            is_refresh = true;
            if (trackPath != null) {
                trackPath.setMap(null);
            }
            if (playback_marker != null) {
                playback_marker.setMap(null);
            }
            if (playback_polyline) {
                playback_polyline.setMap(null);
            }
            clearInterval(animationInterval);
            $.each(markers, function(index, value) {
                markers[index].setMap(map);
            });
            markerCluster = new MarkerClusterer(maps['map'], clusterMarkers, {
                imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
            });
            $('#clock').removeClass('d-none');
            $('#locate_button').addClass('d-none');
            $('#playbackControl').html('');
            getDevicesCount();
        });

        $('#contact-tab').click(function() {
            is_refresh = false;
            $.each(markers, function(index, value) {
                markers[index].setMap(null);
            });
            markerCluster.clearMarkers();
            radiusCircle.setMap(null);
            $('#clock').addClass('d-none');

        });

        if (liveviewAutoRefreshEnabled) {
            setInterval(function getData() {
                if (is_refresh) {
                    const source = new EventSource(markersDataUrl);

                    source.onmessage = function(event) {
                        const response = JSON.parse(event.data);

                        if (response.end) {
                            source.close();
                            return;
                        }

                        const value = response.device; // Extract individual device data
                        // update marker
                        updateMarkerData(value);
                    };

                    source.onerror = function() {
                        console.error('Error occurred in streaming');
                        source.close();
                    };
                }
            }, liveviewRefreshIntervalMs);
        } else {
            $('#clock').addClass('d-none');
        }

        //function to update marker data
        function updateMarkerData(value) {
            let icon = redIcon;
            let lat = value.apiData.lat;
            let lng = value.apiData.lng;
            let dateTimeString = value.apiData.hbTime;
            let parsedDate = new Date(dateTimeString);
            let localTime = parsedDate.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            if (is_hide == true && value.imei_no == centerImei && markers['marker_' +
                    centerImei]) {
                maps['map'].setCenter({
                    lat: lat,
                    lng: lng
                })
            }

            markers['marker_' + value.imei_no].setPosition(new google.maps.LatLng(lat, lng));

            if (value.minutes > 8) {
                icon = redIcon;
            } else {
                if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed != 0) {
                    icon = greenIcon;
                } else if (value.apiData.status == 1 && (value.apiData.speed == 0 || value.apiData.speed ==
                        null)) {
                    icon = yellowIcon;
                }
            }

            markers['marker_' + value.imei_no].setIcon(icon);

            let acc = 'OFF';
            if (value.apiData.accStatus == 1) {
                acc = 'ON';
            }

            var contentString =
                '<div id="content">' +
                '<div id="siteNotice" class="tractor-details">' +
                '<h3 id="firstHeading" class="tractor-heading">' + value.device_name +
                '</h3>' +
                '<h4 id="firstHeading" class="tractor-id">' + value.imei_no + '</h4>' +
                '<div class="card mb-3">' +
                '<div class="card-body">' +
                '<div class="d-flex align-items-center justify-content-between">' +
                '<div class="status">';

            if (value.minutes > 8) {
                contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' +
                    acc + ')';
            } else {
                if (value.apiData.status == 1 && (value.apiData.speed == 0 || value.apiData.speed == null)) {
                    contentString +=
                        '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' +
                        acc + ')';
                } else if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed !=
                    0) {
                    contentString +=
                        '<span><i class="fa-solid fa-tractor text-success me-2"></i></span>Moving (ACC: ' +
                        acc + ')';
                } else {
                    contentString +=
                        '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc +
                        ')';
                }
            }



            contentString += '</div>';
            contentString += '<div>';

            if (value.minutes > 8) {
                contentString += value.diff;
            } else {
                if (value.apiData.status == 1 && value.apiData.accStatus == 1 && value.apiData.speed != 0) {
                    contentString += value.apiData.speed + ' km/h';
                } else {
                    contentString += value.diff;
                }
            }

            contentString += '</div></div></div></div>' +
                '<div class="card  mb-3"> <div class="card-body">' +
                '<h5 class="tractor-heading">Address</h5>' +
                '<h4 class="tractor-id" id="device_address' + value.imei_no + '">' + lat +
                ', ' + lng + '</h4>' +
                '</div></div>' +
                '<div class="card  mb-3"><div class="card-body">' +
                '<h3 class="tractor-id">Device</h3>' +
                '<div class="d-flex flex-wrap justify-content-between">' +
                '<div>GNSS</div>' +
                '<div>' + value.apiData.posType + '</div>' +
                '</div>' +
                '<div class="d-flex flex-wrap justify-content-between">' +
                '<div>Visible Satelites</div>' +
                '<div>' + value.apiData.gpsNum + '</div>' +
                '</div>' +
                '<div class="d-flex flex-wrap justify-content-between gap-2">' +
                '<div>Last online</div>' +
                '<div>' + localTime + '</div>' +
                '</div></div></div>';

                contentString += '<div class="card mb-3">' +
                    '<div class="card-body">' +
                    '<h3 class="tractor-id">Vehicle</h3>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Name : </div>' +
                    '<div>' + (value.tractor?.id_no ? value.tractor.id_no + ' (' + value.tractor.model + ')' : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>User : </div>' +
                    '<div>' + ((value.user?.name || value.user?.email) ? (value.user.name || value.user.email) : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Group : </div>' +
                    '<div>' + (value.group?.name ? value.group.name : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Phone : </div>' +
                    '<div>' + (value.user?.phone ? value.user.phone : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Id : </div>' +
                    '<div>' + (value.tractor?.id_no ? value.tractor.id_no : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>Model : </div>' +
                    '<div>' + (value.tractor?.model ? value.tractor.model : 'N/A') + '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap justify-content-between">' +
                    '<div>License plate : </div>' +
                    '<div>' + (value.tractor?.no_plate ? value.tractor.no_plate : 'N/A') + '</div>' +
                    '</div></div></div></div>';

            contents['infowindow_' + value.imei_no].setContent(contentString);

            // Update stored device data for sidebar
            deviceDataStore[value.imei_no] = value;

            // If sidebar is open for this device, refresh it
            var sb = document.getElementById('tractorDetailSidebar');
            if (sb && sb.classList.contains('open')) {
                var currentImei = document.getElementById('tdsImei');
                if (currentImei && currentImei.textContent === value.imei_no) {
                    openTractorSidebar(value);
                }
            }
        }

        //function to show countdown for 20 seconds on map
        function countdown() {
            timeLeft--;
            document.getElementById("seconds").innerHTML = String(timeLeft + 's');
            if (timeLeft > 0) {
                setTimeout(countdown, 1000);
            } else {
                timeLeft = liveviewRefreshSeconds;
                setTimeout(countdown, 1000);
            }
        }
        setTimeout(countdown, 1000);

        function hideMarkers(event) {
            let imei = $(event).data('imei');
            setMapOnAll(imei);
        }


        // Removes the markers from the map, but keeps them in the array.
        function setMapOnAll(imei) {
            centerImei = imei;
            if (old_imei != imei) {
                is_hide = false;
            }
            if (is_hide) {
                let markersCluster = [];
                is_hide = false;
                maps['map'].setZoom(
                    5
                );
                $.each(markers, function(index, value) {

                    markers[index].setMap(map);
                    markersCluster.push(markers[index]);
                })
                radiusCircle.setVisible(false);
                initializeMarkerClusterer(markersCluster);
            } else {
                is_hide = true;
                old_imei = imei;
                maps['map'].setZoom(
                    15
                );
                $.each(markers, function(index, value) {
                    if (index != 'marker_' + imei) {
                        markerCluster.clearMarkers();
                        markers[index].setMap(null)
                        if (markers['marker_' + imei]) {
                            markers['marker_' + imei].setMap(map);
                        } else {
                            markers[index].setMap(null);
                            radiusCircle.setVisible(false);
                        }
                    }
                });
            }
        }

        //function to show only selected device on map
        $(document).on('click', '.current-device', function() {
            let imei = $(this).data('imei');
            $('#ajax-loader').remove();
            let loader = $('<div id="ajax-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); display: flex; align-items: center; justify-content: center; z-index: 9999;">' +
                   '<div style="display: flex; flex-direction: column; align-items: center; text-align: center; color: white; font-size: 18px; font-weight: bold;">' +
                   '<div class="spinner"></div>' +
                   '<p style="margin-top: 10px;">Please wait...</p>' +
                   '</div>' +
                   '</div>');
            // Append loader to body
            $('body').append(loader);

            $.ajax({
                url: currentDeviceUrl,
                type: 'GET',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'imei': imei
                },
                success: function(response) {
                    $('#ajax-loader').remove();
                    if (response.status == 'OK') {
                        setMapOnAll(imei);
                        const value = response.device;

                        let fence = response.fence;
                        is_marker = 'marker_' + value.imei_no;
                        latitude = value.apiData.lat;
                        longitude = value.apiData.lng;
                        maps['map'].setCenter({
                            lat: latitude,
                            lng: longitude
                        });

                        // update marker
                        updateMarkerData(value);

                        // Open detail sidebar for this device
                        openTractorSidebar(value);

                        if (fence) {
                            let latlng = new google.maps.LatLng(fence.latitude, fence
                                .longitude);
                            let radius = fence.radius * 100;
                            if (is_hide == true) {
                                radiusCircle.setMap(map);
                                radiusCircle.setCenter(latlng);
                                radiusCircle.setRadius(radius);
                                radiusCircle.setVisible(true);
                            }
                        }
                    } else {
                        Swal.fire({
                            title: "Opps!",
                            text: "No Data Found!",
                            icon: "error"
                        });
                    }
                },
                error: function() {
                    // Remove loader on error
                    $('#ajax-loader').remove();
                    Swal.fire({
                        title: "Error!",
                        text: "Something went wrong!",
                        icon: "error"
                    });
                }
            });
        });

        $('#selectDevice').select2({
            placeholder: "Select Device",
        });

        $('#period').select2({
            placeholder: 'Select Period',
        });

        $('#period').on('change', function() {
            let value = $(this).val();
            if (value != 8) {
                $('input[name="date_range"]').val('');
                $('input[name="date_range"]').prop('disabled', true);
            } else {
                $('input[name="date_range"]').prop('disabled', false);
            }
        });

        let value = $('#period').val();
        if (value != 8) {
            $('input[name="date_range"]').val('');
            $('input[name="date_range"]').prop('disabled', true);
        } else {
            $('input[name="date_range"]').prop('disabled', false);
        }

        $('input[name="date_range"]').daterangepicker({
            autoUpdateInput: false,
            maxDate: "{{ date('Y/m/d') }}",
            locale: {
                format: 'YYYY/MM/DD',
                cancelLabel: 'Clear'
            }
        });

        $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format(
                'YYYY/MM/DD'));
        });

        $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $('#searchDevice').click(function() {
            $("#overlay").fadeIn(300);
            $('#playbackControl').html('');
            let formData = $('#trackForm').serialize();
            if (trackPath) {
                trackPath.setMap(null);
            }
            $.ajax({
                url: '{{ route('liveview.getTrackData') }}',
                type: 'GET',
                data: formData,
                success: function(response) {
                    $("#overlay").fadeOut(300);
                    if (response.error) {
                        Swal.fire({
                            title: "Opps!",
                            text: response.error,
                            icon: "error"
                        });
                    } else {
                        $('#playbackControl').html('');
                        if (playback_marker) {
                            playback_marker.setMap(null);
                        }
                        if (playback_polyline) {
                            playback_polyline.setMap(null);
                        }
                        clearInterval(animationInterval);

                        if (response.latlng.length === 0) {
                            Swal.fire({
                                title: "Opps!",
                                text: "No Data Found!",
                                icon: "error"
                            });
                        } else {
                            is_refresh = false;
                            $('#playbackControl').append(response.playbackControl);
                            $('#locate_button').addClass('d-none');

                            if (playback_marker) {
                                playback_marker.setMap(null);
                            }
                            if (playback_polyline) {
                                playback_polyline.setMap(null);
                            }
                            clearInterval(animationInterval);
                            if (animationInterval) {
                                animationInterval = null;
                            }
                            if (trackPath) {
                                trackPath.setMap(null);
                            }
                            if (step != 0) {
                                step = 0;
                            }
                            latitude = response.latlng[0]['lat'];
                            longitude = response.latlng[0]['lng'];
                            maps['map'].setCenter({
                                lat: latitude,
                                lng: longitude
                            });
                            maps['map'].setZoom(
                                18
                            );
                            historyCordinates = response.latlng;
                            gpsTime = response.gpsTime;
                            gpsSpeed = response.gpsSpeed;
                            direction = response.direction;
                            trackPath = new google.maps.Polyline({
                                path: historyCordinates,
                                geodesic: true,
                                strokeColor: "#FF0000",
                                strokeOpacity: 1.0,
                                strokeWeight: 2,
                                map: map
                            });
                        }

                    }
                },
            });
        });

        $('#resetDevice').click(function() {
            $('#trackForm')[0].reset();
            $('#selectDevice').val(null).trigger('change');
            $('#period').val(3).trigger('change');
            $('#playbackControl').html('');
            if (playback_marker) {
                playback_marker.setMap(null);
            }
            if (playback_polyline) {
                playback_polyline.setMap(null);
            }
            clearInterval(animationInterval);
            if (animationInterval) {
                animationInterval = null;
            }
            if (trackPath) {
                trackPath.setMap(null);
            }
            $('#locate_button').addClass('d-none');
        });

        $("#search-box").bind('input', function() {
            $("#suggesstion-box").show();
            $("#suggesstion-box").html('');
            $.ajax({
                url: searchUrl,
                type: "GET",
                data: {
                    'search': $(this).val()
                },
                success: function(response) {
                    var list = [];
                    if (response.device) {
                        if (response.device.length == 0) {
                            $("#suggesstion-box").html('');
                            $("#suggesstion-box").addClass('d-none');
                            $('.input-group #clear-btn').remove();
                        } else {
                            $.each(response.device, function(index, value) {
                                var row =
                                    '<a href="javascript:void(0);" class = "text-secondary current-device search-result" data-imei = "' +
                                    value.imei_no + '">' +
                                    '<div><h5 class = "device-name">' + value
                                    .device_name +
                                    '<span class="d_imei d-block">' +
                                    value
                                    .imei_no + '</span></h5>' +
                                    '</div></a>';
                                $("#suggesstion-box").append(row);
                                $("#suggesstion-box").removeClass('d-none');
                            })
                        }
                    } else {
                        $("#suggesstion-box").html('');
                        $("#suggesstion-box").addClass('d-none');
                        $('.input-group #clear-btn').remove();
                    }
                }
            });
        });

        $(document).on('click', '.search-result', function() {
            let object = $(this)[0];
            let imei = $(object).attr('data-imei');
            $('#suggesstion-box').hide();
            $('#search-box').val(imei);
            $('.input-group #clear-btn').remove();
            $('.input-group').append(
                '<button type="button" id="clear-btn" class="btn btn-outline-secondary current-device" data-imei="' +
                imei + '">&times;</button>')
        });

        $(document).on('click', '#clear-btn', function() {
            $('#search-box').val('');
            $('#clear-btn').remove();
        });

        $(document).on('click', '.state-tabs .nav-link', function() {
            let state = $(this).data('state');
            $('.state-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            if (state == 1) {
                $('.listSections').addClass('d-none');
                $('#grouplistSection').removeClass('d-none');
                appendGroupDevices();
            } else if (state == 2) {
                $('.listSections').addClass('d-none');
                $('#onlinelistSection').removeClass('d-none');
                selectStateData(state, $('#appendDevices'));
            } else if (state == 3) {
                $('.listSections').addClass('d-none');
                $('#offlinelistSection').removeClass('d-none');
                selectStateData(state, $('#offlinelistSection'));
            } else if (state == 4) {
                $('.listSections').addClass('d-none');
                $('#inactivelistSection').removeClass('d-none');
                selectStateData(state, $('#inactivelistSection'));
            }
        });


        function selectStateData(state, htmlSection) {
            htmlSection.html(
                '<div class="loading-div text-center my-3"><span class="spinner-border text-primary"></span> Loading...</div>'
            );

            const source = new EventSource(getDeviceWithStateUrl + "?state=" + state);

            source.onmessage = function(event) {
                const data = JSON.parse(event.data);
                // Handle end signal
                if (data.end) {
                    source.close(); // Close the connection
                    let count = htmlSection.children(':not(.loading-div)').length;
                    if (!count) {
                        htmlSection.html('');
                        htmlSection.html('<div class="text-center my-3 text-danger">No data found</div>');
                    }
                    $('.loading-div').remove();
                    getDevicesCount();

                    return;
                }
                htmlSection.append(data.html);
            };

            source.onerror = function() {
                console.error('Error occurred in state change');
                source.close();
            };
        }

        $(document).on('click', '.filterDevices', function() {
            let type = $(this).data('type');
            let htmlSection = $('#appendDevices');
            htmlSection.html(
                '<div class="loading-div text-center my-3"><span class="spinner-border text-primary"></span> Loading...</div>'
            );

            $('.filterDevice .checkmark').addClass('d-none');

            // Show checkmark for the selected item
            $(this).closest('.filterDevice').find('.checkmark').removeClass('d-none');

            let source;
            if (type == 3) {
                source = new EventSource(getDeviceWithStateUrl + "?state=" + 2);
            } else {
                source = new EventSource(getFilteredDevicesUrl + "?type=" + type);
            }

            source.onmessage = function(event) {
                const data = JSON.parse(event.data);
                // Handle end signal
                if (data.end) {
                    source.close(); // Close the connection
                    let count = htmlSection.children(':not(.loading-div)').length;
                    if (!count) {
                        htmlSection.html('');
                        htmlSection.html(
                            '<div class="text-center my-3 text-danger">No data found</div>');
                    }
                    $('.loading-div').remove();
                    return;
                }
                htmlSection.append(data.html);
            };

            source.onerror = function() {
                console.error('Error occurred in filtering');
                source.close();
            };
        });

        var groups = {!! json_encode($groupNameArray) !!};

        $(document).on('click', '.btn-link', function(e) {
            e.stopPropagation(); // Prevent event bubbling

            var button = $(this); // The clicked button
            var imei = $(this).data('imei');
            var old_group_id = $(this).data('old-group');
            var dropdownMenu = $('#dynamicDropdownMenu');

            // Remove any existing dropdowns to avoid duplication
            dropdownMenu.remove();
            $('#dynamicSubmenu').remove();

            // Create a new dropdown menu dynamically
            var dropdownHtml = `
                <div id="dynamicDropdownMenu" class="dropdown-menu show"
                    style="position: absolute; top: ${button.offset().top + button.outerHeight()}px; left: ${button.offset().left}px; min-width: 200px; overflow: auto; max-height: 300px; z-index: 1050;">
                    <li class="dropdown-submenu">
                        <a class="dropdown-item moveToGroup position-relative" type="button" data-imei="${imei}" data-group-id="${old_group_id}">
                            <i class="fa-solid fa-folder-minus"></i> Move to group
                        </a>
                    </li>
                </div>
            `;

            // Append to body for absolute positioning
            $('body').append(dropdownHtml);
        });

        // Handle submenu positioning and hover behavior
        $(document).on('mouseenter', '.moveToGroup', function() {
            var imei = $(this).data('imei');
            var old_group_id = $(this).data('group-id');
            let submenu = $('#dynamicSubmenu');
            if (submenu.length) submenu.remove(); // Remove existing submenu

            let parentOffset = $(this).offset(); // Get position of "Move to group"

            // Create a new submenu dynamically
            let submenuHtml = `
                <ul id="dynamicSubmenu" class="dropdown-menu show"
                    style="position: absolute; top: ${parentOffset.top}px; left: ${parentOffset.left + 200}px; min-width: 250px; max-height: 400px; overflow-y: auto; z-index: 1051;">
                </ul>
            `;

            $('body').append(submenuHtml);

            // Populate submenu with group names
            $.each(groups, function(index, group) {
                $('#dynamicSubmenu').append(
                    `<li><a class="dropdown-item movedGroup" type="button" data-old-group="${old_group_id}" data-new-group="${index}" data-imei="${imei}">${group}</a></li>`
                );
            });
        });

        // Keep submenu open when hovering over it
        $(document).on('mouseenter', '#dynamicSubmenu', function() {
            $(this).addClass('show');
        });

        // Remove menus when hovering outside
        $(document).on('mouseleave', '.dropdown-submenu, #dynamicSubmenu', function(e) {
            let $submenu = $('#dynamicSubmenu');
            let $mainMenu = $('#dynamicDropdownMenu');

            if (!$submenu.is(':hover') && !$(this).is('.moveToGroup:hover')) {
                $submenu.remove();
            }
        });

        // Close menu when clicking "Move to group"
        $(document).on('click', '.moveToGroup', function(e) {
            e.preventDefault();
        });

        // Close all menus when clicking outside
        $(document).on('click', function() {
            $('#dynamicDropdownMenu').remove();
            $('#dynamicSubmenu').remove();
        });

        $(document).on('click', '.movedGroup', function() {
            let imei = $(this).data('imei');
            let old_group_id = $(this).data('old-group');
            let new_group_id = $(this).data('new-group');

            $.ajax({
                url: "{{ route('liveview.updateGroup') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Add CSRF token in headers
                },
                data: {
                    'imei': imei,
                    'old_group_id': old_group_id,
                    'new_group_id': new_group_id,
                },
                success: function(response) {
                    if (response.success) {
                        appendGroupDevices();
                    }
                }
            });
        });
    });
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $share->device_name ?? 'Tractor' }} — Live Location</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: #f0f2f5; }
        #map { width: 100%; height: 100vh; }

        .share-info-card {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
            padding: 16px 24px;
            z-index: 100;
            min-width: 320px;
            max-width: 95vw;
        }
        .share-info-card .device-name {
            font-size: 17px;
            font-weight: 600;
            color: #222;
        }
        .share-info-card .device-imei {
            font-size: 12px;
            color: #888;
            margin-bottom: 8px;
        }
        .share-info-card .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 3px 0;
        }
        .share-info-card .info-label { color: #666; }
        .share-info-card .info-value { color: #222; font-weight: 500; }
        .share-info-card .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        .status-badge.moving { color: #28a745; }
        .status-badge.idling { color: #e8a317; }
        .status-badge.offline { color: #999; }
        .share-info-card .expires {
            font-size: 11px;
            color: #999;
            margin-top: 8px;
            text-align: right;
        }
        .share-info-card .address {
            font-size: 13px;
            color: #444;
            margin-top: 4px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <div class="share-info-card">
        <div class="device-name" id="deviceName">{{ $share->device_name }}</div>
        <div class="device-imei" id="deviceImei">{{ $share->imei }}</div>
        <div id="statusRow">
            <span class="status-badge" id="statusBadge"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</span>
        </div>
        <div class="address" id="deviceAddress">Loading address...</div>
        <div style="margin-top:8px;">
            <div class="info-row">
                <span class="info-label">Speed</span>
                <span class="info-value" id="deviceSpeed">—</span>
            </div>
            <div class="info-row">
                <span class="info-label">Last update</span>
                <span class="info-value" id="deviceTime">—</span>
            </div>
        </div>
        <div class="expires" id="expiresText">Expires: {{ $share->expires_at->format('M d, Y g:i A') }}</div>
    </div>

    <script>
        var shareToken = '{{ $share->token }}';
        var shareMarker = null;
        var shareMap = null;
        var firstLoad = true;

        var greenIcon = '{{ asset('assets/img/green_tractor.png') }}';
        var redIcon = '{{ asset('assets/img/red_tractor.png') }}';
        var yellowIcon = '{{ asset('assets/img/yellow_tractor.png') }}';

        function initMap() {
            shareMap = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 14.17, lng: 121.29 },
                zoom: 6,
                mapTypeId: 'roadmap',
                streetViewControl: false,
            });
            fetchShareData();
            // Auto-refresh every 20 seconds
            setInterval(fetchShareData, 20000);
        }

        function fetchShareData() {
            fetch('/share/' + shareToken + '/data')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) {
                        document.getElementById('statusBadge').innerHTML = '<i class="fa-solid fa-ban"></i> ' + data.error;
                        document.getElementById('statusBadge').className = 'status-badge offline';
                        return;
                    }
                    var lat = parseFloat(data.lat);
                    var lng = parseFloat(data.lng);
                    var pos = { lat: lat, lng: lng };

                    // Determine tractor icon based on status
                    var tractorIcon = redIcon;
                    var mins = data.minutes || 0;
                    if (mins > 8) {
                        tractorIcon = redIcon;
                    } else if (data.status == 1 && data.accStatus == 1 && data.speed && data.speed != 0) {
                        tractorIcon = greenIcon;
                    } else if (data.status == 1) {
                        tractorIcon = yellowIcon;
                    }

                    // Update marker
                    if (!shareMarker) {
                        shareMarker = new google.maps.Marker({
                            position: pos,
                            map: shareMap,
                            title: data.device_name,
                            icon: tractorIcon,
                        });
                    } else {
                        shareMarker.setPosition(pos);
                        shareMarker.setIcon(tractorIcon);
                    }

                    if (firstLoad) {
                        shareMap.setCenter(pos);
                        shareMap.setZoom(15);
                        firstLoad = false;
                    }

                    // Status
                    var badge = document.getElementById('statusBadge');
                    var minutes = data.minutes || 0;
                    var acc = (data.accStatus == 1) ? 'ON' : 'OFF';

                    if (minutes > 8) {
                        badge.className = 'status-badge offline';
                        badge.innerHTML = '<i class="fa-solid fa-ban"></i> Offline (ACC: ' + acc + ')';
                    } else if (data.status == 1 && data.accStatus == 1 && data.speed && data.speed != 0) {
                        badge.className = 'status-badge moving';
                        badge.innerHTML = '<i class="fa-solid fa-tractor"></i> Moving (ACC: ' + acc + ')';
                    } else if (data.status == 1) {
                        badge.className = 'status-badge idling';
                        badge.innerHTML = '<i class="fa-solid fa-stopwatch"></i> Idling (ACC: ' + acc + ')';
                    } else {
                        badge.className = 'status-badge offline';
                        badge.innerHTML = '<i class="fa-solid fa-ban"></i> Offline (ACC: ' + acc + ')';
                    }

                    // Speed
                    document.getElementById('deviceSpeed').textContent = (data.speed || 0) + ' km/h';

                    // Time
                    if (data.hbTime) {
                        var d = new Date(data.hbTime);
                        document.getElementById('deviceTime').textContent = isNaN(d.getTime()) ? data.hbTime : d.toLocaleString('en-US', {
                            month: '2-digit', day: '2-digit', year: 'numeric',
                            hour: 'numeric', minute: '2-digit', hour12: true
                        });
                    }

                    // Reverse geocode
                    if (window.google && google.maps && google.maps.Geocoder) {
                        var geocoder = new google.maps.Geocoder();
                        geocoder.geocode({ location: pos }, function(results, status) {
                            var el = document.getElementById('deviceAddress');
                            if (status === 'OK' && results && results[0]) {
                                el.textContent = results[0].formatted_address;
                            } else {
                                el.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                            }
                        });
                    }
                })
                .catch(function(err) {
                    console.error('Share data fetch error:', err);
                });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapKey }}&callback=initMap" async defer></script>
</body>
</html>

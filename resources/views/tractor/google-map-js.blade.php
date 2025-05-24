@php
$date = date('Y-m-d H:i:s');
$gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
@endphp

@push('js')
{{-- Google Map --}}
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&callback=initMap" async defer>
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-marker-clusterer/1.0.0/markerclusterer.js"></script>

<script src="/assets/js/markerckuster@2.5.3.min.js"></script>
<script>
    let map, activeInfoWindow, markers = {};
    let clusterMarkers = [];
    let markerCluster;
    let contents = maps = markersArray = {};
    var radiusCircle = playback_marker = playback_polyline =  null;
    const greenIcon = '{{ asset('assets/img/green_tractor.png') }}';
    const redIcon = '{{ asset('assets/img/red_tractor.png') }}';
    const yellowIcon = '{{ asset('assets/img/yellow_tractor.png') }}';

    var trackPath = null;
    var animationTimer = null;
    var animationInterval = null;
    var newCordinates = []; // Initialize as an empty array

    var historyCordinates = null;
    var gpsTime = null;
    var gpsSpeed = null;
    var direction = null;
    var step = 0;

    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            center: {
                lat: 14.17092,
                lng: 121.291831,
            },
            zoom: 5,
            // streetViewControl: false,
            // mapTypeId: "satellite",
        }); 

        createMarkersFunction();

        maps['map'] = map;
        

        map.addListener("click", function(event) {
            mapClicked(event);
        });

        radiusCircle = new google.maps.Circle({
            clickable : false,
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

    function initializeMarkerClusterer(markers) {
        markerCluster = new MarkerClusterer(map, markers, {
            imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
        });
    }

    function createMarkersFunction()
    {
        $.ajax({
            url: '{{ route('tractors.jimiData') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
            },
            beforeSend: function(){
                $('.liveView-loader-parent').removeClass('d-none');
            },
            success: function(response) {
                $.each(response.data, function(key, value) {
                    var icon = redIcon;
                    var dateTimeString = value.hbTime;
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

                    if (value.status == 1 && value.speed != 0) {
                        icon = greenIcon;
                    }else if (value.status == 0 && value.accStatus == 1) {
                        icon = yellowIcon;
                    }

                    var acc = 'OFF';
                    if(value.accStatus = 1){
                        acc = 'ON';
                    }

                    var lat = value.lat;
                    var lng = value.lng;

                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(lat, lng),
                        icon: icon,
                        map: map,
                    });

                    var gmtDate = new Date(); // Define or replace this with the actual GMT date
                    var diff = Math.round((gmtDate.getTime() - new Date(value.hbTime).getTime()) / 3600000, 1);
                    var dayDiff = diff >= 24 ? Math.round(diff / 24) : '';

                    var contentString =
                        '<div id="content">' +
                        '<div id="siteNotice" class="tractor-details">' +
                        '<h3 id="firstHeading" class="tractor-heading">' + value.device.device_name + '</h3>' +
                        '<h4 id="firstHeading" class="tractor-id">' + value.imei + '</h4>' +
                        '<div class="card mb-3">' +
                        '<div class="card-body">' +
                        '<div class="d-flex align-items-center justify-content-between">' +
                        '<div class="status">';

                    
                        if (value.status == 1 && value.speed == 0) {
                            contentString += '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' + acc + ')';
                        }else if (value.status == 1 && value.speed != 0) {
                            contentString += '<span><i class="fa-solid fa-tractor text-success me-2"></i></span>Moving (ACC: ' + acc + ')';
                        }else if (value.status == 0) {
                            contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                        }else if (value.status == 1 && value.speed === null) {
                            contentString += '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' + acc + ')';
                        }else{
                            contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                        }

                    contentString += '</div>';
                    contentString += '<div>';

                    if(value.status == 1 && value.speed != 0){
                        contentString += value.speed + ' km/h';
                    }else {
                        contentString += value.diff;
                    }

                    contentString += '</div></div></div></div>' +
                        '<div class="card  mb-3"> <div class="card-body">' +
                        '<h5 class="tractor-heading">Address</h5>' +
                        '<h4 class="tractor-id" id="device_address' + value.imei + '">' + lat + ', ' + lng + '</h4>' +
                        '</div></div>' +
                        '<div class="card  mb-3"><div class="card-body">' +
                        '<h3 class="tractor-id">Device</h3>' +
                        '<div class="d-flex flex-wrap justify-content-between">' +
                        '<div>GNSS</div>' +
                        '<div>' + value.posType + '</div>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap justify-content-between">' +
                        '<div>Visible Satelites</div>' +
                        '<div>' + value.gpsNum + '</div>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap justify-content-between gap-2">' +
                        '<div>Last online</div>' +
                        '<div>' + localTime + '</div>' +
                        '</div></div></div>';

                    if (value.bookingData) {
                        contentString += '<div class="card  mb-3">' +
                            '<div class="card-body">' +
                            '<h3 class="tractor-id">Vehicle</h3>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Name</div>' +
                            '<div>' + value.tractor.id_no + ' (' + value.tractor.model + ')</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>User</div>' +
                            '<div>' + (value.createdBy.name || value.createdBy.email) + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Group</div>' +
                            '<div>' + value.group + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Phone</div>' +
                            '<div>' + value.createdBy.phone + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Id</div>' +
                            '<div>' + value.tractor.id_no + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Model</div>' +
                            '<div>' + value.tractor.model + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>License plate</div>' +
                            '<div>' + value.tractor.no_plate + '</div>' +
                            '</div></div></div></div>';
                    }

                    var infowindow = new google.maps.InfoWindow({
                        content: contentString,
                        ariaLabel: "Uluru",
                    });

                    marker.addListener("click", () => {
                        infowindow.open({
                            anchor: marker,
                            map,
                        });
                    });

                    markers['marker_' + value.imei] = marker;
                    contents['infowindow_' + value.imei] = infowindow;
                    clusterMarkers.push(marker);
                });
                initializeMarkerClusterer(clusterMarkers);
                $('.liveView-loader-parent').addClass('d-none');


            }
        });
    }

    var is_hide = false;
    var is_marker = null;
    var latitude = null;
    var longitude = null;
    var is_refresh = true;
    var centerImei = null;


    var locationPath = null;
    const intervalDuration = 16;
    const pixelsToMovePerInterval = 0.5;
    var is_paused = false;
    let progress = 0;

    // Sets the map on all markers in the array.
    function hideMarkers(event) {
        let imei = $(event).data('imei');
        setMapOnAll(imei);
    }

    var old_imei = null;
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
                    if(markers['marker_' + imei]){
                        markers['marker_' + imei].setMap(map);
                    }else{
                        markers[index].setMap(null);
                        radiusCircle.setVisible(false);
                    }
                }
            });
        }
    }

    /* ------------------------- Handle Map Click Event ------------------------- */
    function mapClicked(event) {
        console.log(event.latLng.lat(), event.latLng.lng());
    }


    $(document).on('click','.current-device',function(){
        let object = $(this)[0];
        let imei = $(object).attr('data-imei')
        $.ajax({
            url: '{{ route('tractors.current-device-data') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
                'imei': imei
            },
            success: function(response) {
                if(response.status === 'OK'){
                    setMapOnAll(imei);
                    let value = response.data;
                    let fence = response.fence;
                    is_marker = 'marker_' + value.imei;
                    latitude = value.lat;
                    longitude = value.lng;
                    maps['map'].setCenter({
                        lat: value.lat,
                        lng: value.lng
                    });
                    var dateTimeString = value.hbTime; // Example: "2024-01-09 15:30:45"
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
                    markers['marker_' + value.imei].setPosition(new google.maps.LatLng(value.lat, value.lng));

                    icon = redIcon;
                    if (value.status == 1 && value.speed != 0) {
                        icon = greenIcon;
                    }else if (value.status == 0 && value.accStatus == 1) {
                        icon = yellowIcon;
                    }
                    markers['marker_' + value.imei].setIcon(icon);

                    var acc = 'OFF';
                    if(value.accStatus = 1){
                        acc = 'ON';
                    }
                    let contentString =
                        '<div id="content">' +
                        '<div id="siteNotice" class="tractor-details">' +
                        '<h3 id="firstHeading" class="tractor-heading">' + value.device.device_name + '</h3>' +
                        '<h4 id="firstHeading" class="tractor-id">' + value.imei +
                        '</h4>' +
                        '<div class="card mb-3">' +
                        '<div class="card-body">' +
                        '<div class="d-flex align-items-center justify-content-between">' +
                        '<div class="status">';

                    if (value.status == 0 && value.accStatus == 1) {
                        contentString += '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' + acc + ')';
                    }else if (value.status == 1 && value.speed != 0) {
                        contentString += '<span><i class="fa-solid fa-tractor text-success me-2"></i></span>Moving (ACC: ' + acc + ')';
                    }else if (value.status == 0) {
                        contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                    }else{
                        contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                    }
                    contentString +=
                        '</div>';
                    contentString += '<div>';

                    if(value.status == 1 && value.speed != 0){
                        contentString += value.speed + ' km/h';
                    }else {
                        contentString += value.diff;
                    }

                    contentString += '</div></div></div></div>' +
                        '<div class="card  mb-3"> <div class="card-body">' +
                        '<h5 class="tractor-heading">Address</h5>' +
                        '<h4 class="tractor-id" id="device_address' + value.imei +
                        '">' +
                        value.lat + ',' + value.lng + '</h4>' +
                        '</div></div>' +
                        '<div class="card  mb-3"><div class="card-body">' +
                        '<h3 class="tractor-id">Device</h3>' +
                        '<div class="d-flex flex-wrap justify-content-between">' +
                        '<div>GNSS</div>' +
                        '<div>' + value.posType + '</div>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap justify-content-between">' +
                        '<div>Visible Satelites</div>' +
                        '<div>' + value.gpsNum + '</div>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap justify-content-between gap-2">' +
                        '<div>Last online</div>' +
                        '<div>' + localTime + '</div>' +
                        '</div></div></div>';

                        if(value.bookingData){
                            contentString +=
                            '<div class="card  mb-3">' +
                            '<div class="card-body">' +
                            '<h3 class="tractor-id">Vehicle</h3>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Name</div>' +
                            '<div>' + value.tractor.id_no + ' (' + value.tractor.model + ')' +
                            '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Phone</div>' +
                            '<div>' + value.created_by.phone + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Id</div>' +
                            '<div>' + value.tractor.id_no + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>Model</div>' +
                            '<div>' + value.tractor.model + '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap justify-content-between">' +
                            '<div>License plate</div>' +
                            '<div>' + value.tractor.no_plate + '</div>' +
                            '</div>' +
                            '</div></div></div></div>';
                        }
                    contents['infowindow_' + value.imei].setContent(contentString);
                
                    if(fence){
                        let latlng = new google.maps.LatLng(fence.latitude, fence.longitude);
                        let radius = fence.radius*100;
                        if(is_hide == true){
                            radiusCircle.setMap(map);
                            radiusCircle.setCenter(latlng);
                            radiusCircle.setRadius(radius);
                            radiusCircle.setVisible(true);
                        }
                    }
                }else{
                    console.log(response.status);
                    Swal.fire({
                        title: "Opps!",
                        text: "No Data Found!",
                        icon: "error"
                    });
                }
            }
        });

    });

    function selectDevice(event){
        let id = $(event).val();
        $('.booking_details').html('');
        if(trackPath){
            trackPath.setMap(null);
        }
        $.ajax({
            url: "{{route('tractors.booking-data')}}",
            type: "POST",
            data: {
                'id':id
            },
            dataType: "json",
            success: function(response) {
                if(response.htmlArr.length === 0){
                    $('.booking_details').append('<div>No Data Found</div>');
                }
                $('#playbackControl').html('');
                if(playback_marker){
                    playback_marker.setMap(null);
                }
                if(playback_polyline){
                    playback_polyline.setMap(null);
                }
                clearInterval(animationInterval);
                jQuery.each(response.htmlArr, function(index, value) {
                    $('.booking_details').append(value);
                });
            },
        });
    }

    function deviceTrackHistory(event){
        let id = $(event).data('id');
        let imei = $(event).data('imei');

        $.ajax({
            url: "{{route('tractors.history-data')}}",
            type: "POST",
            data: {
                'id':id,
                'imei':imei
            },
            dataType: "json",
            success: function(response) {
                $('#playbackControl').html('');
                if(response.latlng.length === 0){
                    Swal.fire({
                        title: "Opps!",
                        text: "No Data Found!",
                        icon: "error"
                    });
                }else{
                    is_refresh = false;
                    $('#playbackControl').append(response.playbackControl);
                    $('#locate_button').addClass('d-none');

                    if(playback_marker){
                        playback_marker.setMap(null);
                    }
                    if (playback_polyline) {
                        playback_polyline.setMap(null);
                    }
                    clearInterval(animationInterval);
                    if(animationInterval){
                        animationInterval = null;
                    }
                    if(trackPath){
                        trackPath.setMap(null);
                    }
                    if(step!=0){
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
            },
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
        }else if(action == 'pause'){
            $('#playButton').removeClass('d-none');
            $('#pauseButton').addClass('d-none');
            is_paused = true;
            clearInterval(animationInterval);
            animationInterval = null;
        }
    }

    function updateProgressBar() {
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = progress + '%';
    }
    
    function playReplay(){
        if(is_paused){
            playback_marker.setMap(null);
            step = step;
        }else{
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
        const tractorPath = "M15 30C18.9782 30 22.7936 28.4196 25.6066 25.6066C28.4196 22.7936 30 18.9782 30 15C30 11.0218 28.4196 7.20644 25.6066 4.3934C22.7936 1.58035 18.9782 0 15 0C11.0218 0 7.20644 1.58035 4.3934 4.3934C1.58035 7.20644 0 11.0218 0 15C0 18.9782 1.58035 22.7936 4.3934 25.6066C7.20644 28.4196 11.0218 30 15 30ZM22.0898 15.8789C22.6406 16.4297 22.6406 17.3203 22.0898 17.8652C21.5391 18.4102 20.6484 18.416 20.1035 17.8652L15.0059 12.7676L9.9082 17.8652C9.35742 18.416 8.4668 18.416 7.92187 17.8652C7.37695 17.3145 7.37109 16.4238 7.92187 15.8789L14.0039 9.78516C14.5547 9.23438 15.4453 9.23438 15.9902 9.78516L22.0898 15.8789Z";

        if(is_paused){
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
        }else{
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
        if(is_paused){
            newCordinates.push(path[step]); // Initialize with the step position
        }else{
            newCordinates.push(path[0]); // Initialize with the first position
        }

        animationInterval = setInterval(function () {
            if (step >= path.length) {
                clearInterval(animationInterval);
                playback_marker.setMap(null);
                playback_polyline.setMap(null);
                $('#locate_button').addClass('d-none');
                return;
            }

            const newPosition = path[step];
            const currentRotation = direction[step-1];
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
            if(dateTimeString === undefined){
                var dateTimeString = gpsTime[step-1];
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

    function locateDevice(){
        maps['map'].setCenter({
            lat: locationPath.lat,
            lng: locationPath.lng
        });
    }

    let timeLeft = 16;

    function countdown() {
        timeLeft--;
        document.getElementById("seconds").innerHTML = String(timeLeft+'s');
        if (timeLeft > 0) {
            setTimeout(countdown, 1000);
        } else {
            timeLeft = 16;
            setTimeout(countdown, 1000);
        }
    }
    setTimeout(countdown, 1000);

    $(document).ready(function(){
        let interval =  setInterval(
            function getData(event) {
                if(is_refresh){
                $.ajax({
                    url: '{{ route('tractors.jimiData') }}',
                    type: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        $.each(response.data, function(index, value) {
                            var dateTimeString = value.hbTime; // Example: "2024-01-09 15:30:45"
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
                            if(is_hide == true && value.imei == centerImei && markers['marker_' + centerImei]){
                                maps['map'].setCenter({
                                    lat: value.lat,
                                    lng: value.lng
                                })
                            }
                            markers['marker_' + value.imei].setPosition(new google.maps.LatLng(value.lat, value.lng));
                            
                            icon = redIcon;
                            if (value.status == 1 && value.speed != 0) {
                                icon = greenIcon;
                            }else if (value.status == 0 && value.accStatus == 1) {
                                icon = yellowIcon;
                            }

                            markers['marker_' + value.imei].setIcon(icon);

                            let contentString =
                                '<div id="content">' +
                                '<div id="siteNotice" class="tractor-details">' +
                                '<h3 id="firstHeading" class="tractor-heading">' + value.device.device_name + '</h3>' +
                                '<h4 id="firstHeading" class="tractor-id">' + value.imei +
                                '</h4>' +
                                '<div class="card mb-3">' +
                                '<div class="card-body">' +
                                '<div class="d-flex align-items-center justify-content-between">' +
                                '<div class="status">';

                            var acc = 'OFF';
                            if(value.accStatus = 1){
                                acc = 'ON';
                            }
                            
                            if (value.status == 0 && value.accStatus == 1) {
                                contentString += '<span><i class="fa-solid fa-stopwatch text-warning me-2"></i></span>Idling (ACC: ' + acc + ')';
                            }else if (value.status == 1 && value.speed != 0) {
                                contentString += '<span><i class="fa-solid fa-tractor text-success me-2"></i></span>Moving (ACC: ' + acc + ')';
                            }else if (value.status == 0) {
                                contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                            }else{
                                contentString += '<span><i class="fa-solid fa-ban text-muted me-2"></i></span>Offline (ACC: ' + acc + ')';
                            }
                            
                            contentString +=
                                '</div>';
                            contentString += '<div>';

                            if(value.status == 1 && value.speed != 0){
                                contentString += value.speed + ' km/h';
                            }else {
                                contentString += value.diff;
                            }
                            var user = null;
                            if(value.bookingData){
                                if(value.created_by.name){
                                    user = value.created_by.name;
                                }else{
                                    user = value.created_by.email;
                                }
                            }
                            contentString += '</div></div></div></div>' +
                                '<div class="card  mb-3"> <div class="card-body">' +
                                '<h5 class="tractor-heading">Address</h5>' +
                                '<h4 class="tractor-id" id="device_address' + value.imei +
                                '">' +
                                value.lat + ',' + value.lng + '</h4>' +
                                '</div></div>' +
                                '<div class="card  mb-3"><div class="card-body">' +
                                '<h3 class="tractor-id">Device</h3>' +
                                '<div class="d-flex flex-wrap justify-content-between">' +
                                '<div>GNSS</div>' +
                                '<div>' + value.posType + '</div>' +
                                '</div>' +
                                '<div class="d-flex flex-wrap justify-content-between">' +
                                '<div>Visible Satelites</div>' +
                                '<div>' + value.gpsNum + '</div>' +
                                '</div>' +
                                '<div class="d-flex flex-wrap justify-content-between gap-2">' +
                                '<div>Last online</div>' +
                                '<div>' + localTime + '</div>' +
                                '</div></div></div>';
                                if(value.bookingData){
                                    contentString +=
                                    '<div class="card  mb-3">' +
                                    '<div class="card-body">' +
                                    '<h3 class="tractor-id">Vehicle</h3>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>Name</div>' +
                                    '<div>' + value.tractor.id_no + ' (' + value.tractor.model + ')' +
                                    '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>User</div>' +
                                    '<div>' + user + '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>Group</div>' +
                                    '<div>' + value.group + '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>Phone</div>' +
                                    '<div>' + value.created_by.phone + '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>Id</div>' +
                                    '<div>' + value.tractor.id_no + '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>Model</div>' +
                                    '<div>' + value.tractor.model + '</div>' +
                                    '</div>' +
                                    '<div class="d-flex flex-wrap justify-content-between">' +
                                    '<div>License plate</div>' +
                                    '<div>' + value.tractor.no_plate + '</div>' +
                                    '</div>' +
                                    '</div></div></div></div>';
                                }
                            contents['infowindow_' + value.imei].setContent(contentString);
                        });
                    }
                });
            }
        }, 15000);

        $("#search-box").bind('input',function() {
            
            $("#suggesstion-box").show();
            $("#suggesstion-box").html('');
            $.ajax({
                type: "POST",
                url: "{{ route('tractor-groups.search-group') }}",
                data: {
                    '_token': '{{ csrf_token() }}',
                    'search': $(this).val()
                },
                success: function(response) {
                    var list = [];
                    if(response.device){
                        if (response.device.length == 0) {
                            $("#suggesstion-box").html('');
                            $("#suggesstion-box").addClass('d-none');
                        } else {
                            $.each(response.device, function(index, value) {
                                var row =
                                    '<a href="javascript:void(0);" class = "text-secondary current-device search-result" data-imei = "' +
                                    value.imei_no + '">' +
                                    '<div><h5 class = "device-name">' + value.device_name +
                                    '<span class="d_imei d-block">' +
                                    value
                                    .imei_no + '</span></h5>' +
                                    '</div></a>';
                                $("#suggesstion-box").append(row);
                                $("#suggesstion-box").removeClass('d-none');
                            })
                        }
                    }else{
                        $("#suggesstion-box").html('');
                        $("#suggesstion-box").addClass('d-none');
                    }
                }
            });
        });

        $(document).on('click','.search-result',function(){
            let object = $(this)[0];
            let imei = $(object).attr('data-imei');
            $('#suggesstion-box').hide();
            $('#search-box').val(imei);
        });

        $('#home-tab').click(function(){
            is_refresh = true;
            if(trackPath != null){
                trackPath.setMap(null);
            }
            if(playback_marker != null){
                playback_marker.setMap(null);
            }
            if(playback_polyline){
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
        });

        $('#contact-tab').click(function(){
            is_refresh = false;
            $.each(markers, function(index, value) {
                markers[index].setMap(null);
            });
            markerCluster.clearMarkers();
            radiusCircle.setMap(null);
            $('#clock').addClass('d-none');

        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
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

        $('#searchDevice').click(function(){
            $("#overlay").fadeIn(300);
            $('#playbackControl').html('');
            let formData = $('#trackForm').serialize();
            if(trackPath){
                trackPath.setMap(null);
            }
            $.ajax({
                url:'{{route('tractors.getTractData')}}',
                type:'POST',
                data:formData,
                success: function(response) {
                    $("#overlay").fadeOut(300);
                    if(response.error){
                        Swal.fire({
                            title: "Opps!",
                            text: response.error,
                            icon: "error"
                        });
                    }else{
                        $('#playbackControl').html('');
                        if(playback_marker){
                            playback_marker.setMap(null);
                        }
                        if(playback_polyline){
                            playback_polyline.setMap(null);
                        }
                        clearInterval(animationInterval);

                        if(response.latlng.length === 0){
                            Swal.fire({
                                title: "Opps!",
                                text: "No Data Found!",
                                icon: "error"
                            });
                        }else{
                            is_refresh = false;
                            $('#playbackControl').append(response.playbackControl);
                            $('#locate_button').addClass('d-none');

                            if(playback_marker){
                                playback_marker.setMap(null);
                            }
                            if (playback_polyline) {
                                playback_polyline.setMap(null);
                            }
                            clearInterval(animationInterval);
                            if(animationInterval){
                                animationInterval = null;
                            }
                            if(trackPath){
                                trackPath.setMap(null);
                            }
                            if(step!=0){
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

        $('#resetDevice').click(function(){
            $('#trackForm')[0].reset();
            $('#selectDevice').val(null).trigger('change');
            $('#period').val(3).trigger('change');
            $('#playbackControl').html('');
            if(playback_marker){
                playback_marker.setMap(null);
            }
            if(playback_polyline){
                playback_polyline.setMap(null);
            }
            clearInterval(animationInterval);
            if(animationInterval){
                animationInterval = null;
            }
            if(trackPath){
                trackPath.setMap(null);
            }
            $('#locate_button').addClass('d-none');
        });
    });
</script>
@endpush

<script>
    // function computeCity(latitude, longitude) {
    //     const reverseGeocodingUrl = `https://api.geoapify.com/v1/geocode/reverse?lat=${latitude}&lon=${longitude}&apiKey=AIzaSyCY5uLcx5K-nph-4ayeV40CNbKligR1-xM`;
        
    //     // call Reverse Geocoding API - https://www.geoapify.com/reverse-geocoding-api/
    //     fetch(reverseGeocodingUrl).then(result => result.json())
    //     .then(featureCollection => {
    //         console.log(featureCollection);
    //     });
    // }
</script>
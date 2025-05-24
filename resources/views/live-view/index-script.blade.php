<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&callback=initMap" async defer>
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

    let timeLeft = 16; //Seconds
    const greenIcon = '{{ asset('assets/img/green_tractor.png') }}';
    const redIcon = '{{ asset('assets/img/red_tractor.png') }}';
    const yellowIcon = '{{ asset('assets/img/yellow_tractor.png') }}';

    //Initialize Map 
    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            center: {
                lat: 14.17092,
                lng: 121.291831,
            },
            zoom: 5,
        });

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
        const source = new EventSource('{{ route('liveview.markersData') }}');

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
                infowindow.open({
                    anchor: marker,
                    map,
                });
            });

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
            const source = new EventSource("{{ route('liveview.appendGroupDevices') }}");

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
            };

            source.onerror = function() {
                console.error('Error occurred in streaming');
                source.close();
            };
        }

        function getDevicesCount() {
            $.ajax({
                url: "{{ route('liveview.getDevicesCount') }}",
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

        let interval = setInterval(
            function getData(event) {
                if (is_refresh) {
                    const source = new EventSource('{{ route('liveview.markersData') }}');

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
            }, 15000);

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
        }

        //function to show countdown for 15 seconds on map
        function countdown() {
            timeLeft--;
            document.getElementById("seconds").innerHTML = String(timeLeft + 's');
            if (timeLeft > 0) {
                setTimeout(countdown, 1000);
            } else {
                timeLeft = 16;
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
                url: '{{ route('liveview.currentDevice') }}',
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
                url: "{{ route('liveview.search') }}",
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

            const source = new EventSource("{{ route('liveview.getDeviceWithState') }}?state=" + state);

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
                source = new EventSource("{{ route('liveview.getDeviceWithState') }}?state=" + 2);
            } else {
                source = new EventSource("{{ route('liveview.getFilteredDevices') }}?type=" + type);
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

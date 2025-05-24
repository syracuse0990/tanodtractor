<div>
    <div class="mb-3">
        <select class="form-control" id="selectDevice" wire:model.live="device_id">
            <option value=0 >Select Device </option>
            @foreach ($groups as $group)
                @php
                    $devices = $group->getDevices();
                @endphp
                @foreach ($devices as $device)
                    <option value={{$device->id}} >{{$device->imei_no}}</option>
                @endforeach
            @endforeach
        </select>
    </div>

    @foreach ($bookingData as $booking)
        <div class="d-flex justify-content-between my-3">
            <div class="d-flex gap-2">
                <div class="device-state-img bg-secondary">
                    <i class="fa-solid fa-tractor "></i>
                </div>
                <a href="javascript:void(0);"
                    class="text-secondary current-device history_submit"
                    data-id={{ $booking->id }} data-imei="{{$booking?->device?->imei_no}}" onClick="deviceTrackHistory(this)">
                    <div>
                        <h5 class="device-name">
                            {{ $booking?->tractor?->id_no . ' (' . $booking?->tractor?->model . ')' }}
                        </h5>
                        <p class="mb-0 date-time">
                            {{ $booking?->createdBy?->name??$booking?->createdBy?->email }}
                        </p>
                        <p class="mb-0 date-time">
                            {{ $booking?->device?->imei_no }}
                        </p>
                        <p class="mb-0 date-time">
                            {{ $booking?->date }}
                        </p>
                        <p class="mb-0 date-time">
                            {{ $booking?->purpose }}
                        </p>
                    </div>
                </a>
            </div>
        </div>
    @endforeach 
</div>

<script>
    
    function deviceTrackHistory(event){
            let id = $(event).data('id');
            let imei = $(event).data('imei');
            console.log(imei);
            $.ajax({
                url: "{{route('tractors.history-data')}}",
                type: "POST",
                data: {
                    'id': id,
                    'imei': imei
                },
                dataType: "json",
                success: function(response) {
                    console.log(response.latlng.length);
                    if(response.latlng.length === 0){
                        Swal.fire({
                            title: "Opps!",
                            text: "No Data Found!",
                            icon: "error"
                        });
                    }else{
                        is_refresh = false;
                        if(trackPath){
                            trackPath.setMap(null);
                        }
                        latitude = response.latlng[0]['lat'];
                        longitude = response.latlng[0]['lng'];
                        maps['map'].setCenter({
                            lat: latitude,
                            lng: longitude
                        });
                        maps['map'].setZoom(
                            15
                        );
                        var historyCordinates = response.latlng;
                        trackPath = new google.maps.Polyline({
                            path: historyCordinates,
                            geodesic: true,
                            strokeColor: "#FF0000",
                            strokeOpacity: 1.0,
                            strokeWeight: 2,
                        });
                        trackPath.setMap(map);
                    }
                },
            });
        }
</script>
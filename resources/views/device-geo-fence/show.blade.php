<x-app-layout title="{{ __($deviceGeoFence->fence_name) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $deviceGeoFence->imei }}</h2>

                    <form action="{{ route('device-geo-fences.destroy', [$deviceGeoFence->id,'is_delete'=>true]) }}"
                        method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Delete</button>
                    </form>

                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> ID <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->id }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Fence Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->fence_name }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Geo Fence Id <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->geo_fence_id }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> IMEI <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->imei }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Latitude <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->latitude }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Longitude <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->longitude }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Radius <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->radius }}
                            </td>
                        </tr>
                        {{-- <tr>
                            <td>
                                <strong class="fw-500"> Zoom Level <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->zoom_level }}
                            </td>
                        </tr> --}}
                        <tr>
                            <td>
                                <strong class="fw-500"> Date <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ date('Y-m-d', strtotime($deviceGeoFence->date)) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> State <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {!! $deviceGeoFence->getStateLabel() !!}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Created By <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $deviceGeoFence->createdBy?->name ?? $deviceGeoFence->createdBy?->email }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12 mb-2 mt-2">
        <div id="map"></div>
    </div>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&callback=initMap" async>
    </script>
    <script>
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat: 14.17092,
                    lng: 121.291831,
                },
                zoom: 5,
            });

        }
        var device_marker = null;

        function createRadius(latlng){
            var radius  = '{{$deviceGeoFence->radius}}';
            cityCircle = new google.maps.Circle({
                clickable : false,
                strokeColor: "#c3fc49",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#c3fc49",
                fillOpacity: 0.35,
                map: map,
                center: latlng,
                radius: radius*100 // in meters
            });
        }
        $(document).ready(function(){
            marker = new google.maps.Marker({
                position: {
                    lat: {{$deviceGeoFence->latitude}},
                    lng: {{$deviceGeoFence->longitude}}
                },
                map: map,
            });
            let latlng = new google.maps.LatLng({{$deviceGeoFence->latitude}},{{$deviceGeoFence->longitude}});
            createRadius(latlng);
            
            map.setZoom(
                5   
            );

            // $.ajax({
            //     url: '{{ route('device-geo-fences.device-data') }}',
            //     type: 'POST',
            //     data: {
            //     '_token': '{{ csrf_token() }}',
            //     'imei': '{{$deviceGeoFence->imei}}'
            //     },
            //     success: function(response) {
            //         if(response.status == 'NOK'){
            //             if(device_marker){
            //                 device_marker.setMap(null);
            //             }
            //         }else if(response.status =='No Device'){
            //             Swal.fire({
            //                 title: "Opps!",
            //                 text: "No Device Found!",
            //                 icon: "error"
            //             });
            //         }else{
            //             if(device_marker){
            //                 device_marker.setMap(null);
            //             }
            //             device_marker = new google.maps.Marker({
            //                 position: new google.maps.LatLng(response.data.lat,response.data.lng),
            //                 icon: '{{ asset('assets/img/green_tractor.png') }}',
            //                 map: map,
            //             });
            //             map.setZoom(
            //                15
            //             );
            //         }
            //     }
            // });
        });
    </script>
</x-app-layout>
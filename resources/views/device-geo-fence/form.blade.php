<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Fence Name') }}
                {{ Form::text('fence_name', $deviceGeoFence->fence_name, ["id"=>"fence_name", 'class' => 'form-control'
                .
                ($errors->has('fence_name') ? ' is-invalid' : ''), 'placeholder' => 'Fence Name']) }}
                {!! $errors->first('fence_name', '<div class="invalid-feedback">:message</div>') !!}
                <div id="fence_name_error" class="invalid-feedback"></div>
            </div>
        </div>
        {{-- <div class="col-md-6 mb-3 track_form_div" id="track_form">
            <div class="form-group">
                {{ Form::label('imei') }}
                <select class="form-control" id="imei" name="imei">
                    <option value="" selected disabled>Select Device</option>
                    @foreach ($devices as $device)
                    <option value={{$device->imei_no}} {{$deviceGeoFence && $deviceGeoFence->imei == $device->imei_no ?
                        'selected' : (isset($deviceImei) && $deviceImei == $device->imei_no ? 'selected' :
                        '')}}>{{$device->device_name .' ['. $device->imei_no.']'}}
                    </option>
                    @endforeach
                </select>
                {!! $errors->first('imei', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div> --}}

        <div class="col-md-6 mb-3 hidden-select" id="imei_select">
            <div class="form-group  custom-select-wrapper group-form-select">
                @php
                $deviceIds = $deviceGeoFence->imei ? explode(',',$deviceGeoFence->imei) : [];
                @endphp
                {{ Form::label('Device') }}
                <select class="form-control{{ $errors->has('imei') ? ' is-invalid' : '' }}" name="imei[]"
                    multiple="multiple" id="imei">
                    @foreach ($devices as $device)
                    <option value="{{ $device->imei_no }}" {{in_array($device->imei_no,is_array($deviceIds) ? $deviceIds
                        : []) ? 'selected' :
                        (in_array($device->imei_no,is_array(old('imei')) ? old('imei') : []) ? 'selected' :
                        '')}}>{{$device->device_name .' ['.
                        $device->imei_no.']'}}
                    </option>
                    @endforeach
                </select>
                {!! $errors->first('imei', '<div class="invalid-feedback">:message</div>') !!}
                <div id="imei_error" class="invalid-feedback"></div>

            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Latitude') }}
                {{ Form::text('latitude', $deviceGeoFence->latitude, ['id' => 'latitude', 'class' => 'form-control' .
                ($errors->has('latitude') ? ' is-invalid' : ''), 'placeholder' => 'Latitude','readOnly'=>true]) }}
                {!! $errors->first('latitude', '<div class="invalid-feedback">:message</div>') !!}
                <div id="latitude_error" class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Longitude') }}
                {{ Form::text('longitude', $deviceGeoFence->longitude, ['id' => 'longitude', 'class' => 'form-control' .
                ($errors->has('longitude') ? ' is-invalid' : ''), 'placeholder' => 'Longitude','readOnly'=>true]) }}
                {!! $errors->first('longitude', '<div class="invalid-feedback">:message</div>') !!}
                <div id="longitude_error" class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('radius') }}
                {{ Form::number('radius', $deviceGeoFence->radius, ['id'=>'radius','class' => 'form-control' .
                ($errors->has('radius') ? ' is-invalid' : ''), 'placeholder' =>
                'Radius (1～9999；unit: 100 meters)','min'=>"1", 'max'=>"9999"]) }}
                {!! $errors->first('radius', '<div class="invalid-feedback">:message</div>') !!}
                <div id="radius_error" class="invalid-feedback"></div>
            </div>
        </div>
        {{-- <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('zoom_level') }}
                {{ Form::number('zoom_level', $deviceGeoFence->zoom_level, ['id'=>'zoom_id','class' => 'form-control' .
                ($errors->has('zoom_level') ? ' is-invalid' : ''), 'placeholder' => 'Zoom Level (3-19)','min'=>"3",
                'max'=>"19"]) }}
                {!! $errors->first('zoom_level', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div> --}}
        @if (Route::currentRouteName() == 'device-geo-fences.edit')
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('date') }}
                {{ Form::text('date', $deviceGeoFence->date ? date('Y/m/d', strtotime($deviceGeoFence->date)) : null,
                ['id'=>'date', 'class' => 'form-control' . ($errors->has('date') ? ' is-invalid' : ''), 'placeholder'
                =>'Date']) }}
                {!! $errors->first('date', '<div class="invalid-feedback">:message</div>') !!}
                <div id="date_error" class="invalid-feedback"></div>
            </div>
        </div>
        @else
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('date') }}
                {{ Form::text('date', $deviceGeoFence->date ? date('Y/m/d', strtotime($deviceGeoFence->date)) : null,
                ['class' => 'form-control' . ($errors->has('date') ? ' is-invalid' : ''), 'placeholder' => 'Date', 'id'
                => 'datePicker']) }}
                {!! $errors->first('date', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-12 mb-2">
        <div id="map"></div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class=" mt-3">
                <button type="submit" class="btn btn-primary btn-icon text-white rounded-pill px-3">{{ __('Submit')
                    }}</button>
            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&callback=initMap" async></script>

<script>
    jQuery('#datePicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minDate: moment(),
            locale: {
                format: 'YYYY-MM-DD'
            },
        });

        jQuery('#date').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            locale: {
                format: 'YYYY-MM-DD'
            },
        });

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat: 14.17092,
                    lng: 121.291831,
                },
                zoom: 5,
            });

            map.addListener("click", function(event) {
                mapClicked(event);
            });
        }

        var marker;
        var cityCircle;
        /* ------------------------- Handle Map Click Event ------------------------- */
        function mapClicked(event) {
            var radius  = $('#radius').val();
            var zoom  = $('#zoom_id').val();

            // console.log(event.latLng.lat(), event.latLng.lng());
            var latlng = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());

            if (marker && marker.setMap) {
                marker.setMap(null);
                
            }
            if(cityCircle){
                cityCircle.setMap(null);
            }
            marker = new google.maps.Marker({
                position: {
                    lat: event.latLng.lat(),
                    lng: event.latLng.lng()
                },
                map: map,
                draggable: true
            });
            $('#latitude').val(event.latLng.lat());
            $('#longitude').val(event.latLng.lng());
            if(radius){
                createRadius(latlng);
            }
            marker.addListener('dragend', function(event) {
                var newLatLng = event.latLng;
                marker.setPosition(newLatLng);
                if(cityCircle){
                    cityCircle.setMap(null);
                }
                $('#latitude').val(event.latLng.lat());
                $('#longitude').val(event.latLng.lng());
                latlng = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());
                if(radius){
                    createRadius(latlng);
                }
            });
        }
        
        function createRadius(latlng){
            var radius  = $('#radius').val();
            cityCircle = new google.maps.Circle({
                clickable : false,
                strokeColor: "#FF6347",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#c3fc49",
                fillOpacity: 0.35,
                map: map,
                center: latlng,
                radius: radius*100, // in meters
                editable: true
            });
            cityCircle.addListener('radius_changed', function () {
                var newRadius = cityCircle.getRadius(); // Get the updated radius (in meters)
                var radius = Math.ceil(newRadius/100);
                $('#radius').val(radius);    
            });
        }
</script>

@push('js')
<script>
    // $('#imei').select2({
    //             closeOnSelect: true,
    //             placeholder: "Select Device",
    //             allowClear: true
    // });

    $('#imei').multiselect({
        search: true,
        selectAll: true,
        texts: {
            placeholder: 'Select Devices',
            search: 'Search'
        }
    });
    $('#imei_select').removeClass('hidden-select');

    // $('document').ready(function(){
    //     var lat = '{{isset($deviceGeoFence->latitude) ? $deviceGeoFence->latitude : ''}}';
    //     var lng = '{{isset($deviceGeoFence->longitude) ? $deviceGeoFence->longitude : ''}}';

    //     map.setCenter({
    //         lat: lat,
    //         lng: lng
    //     });
    // });

    var device_marker = null;
    // $('#imei').change(function(){
    //     var imei = $(this).find(":selected").val();
    //     $.ajax({
    //         url: '{{ route('device-geo-fences.device-data') }}',
    //         type: 'POST',
    //         data: {
    //         '_token': '{{ csrf_token() }}',
    //         'imei': imei
    //         },
    //         success: function(response) {
    //             if(response.status == 'NOK'){
    //                 if(device_marker){
    //                     device_marker.setMap(null);
    //                 }
    //             }else if(response.status =='No Device'){
    //                 Swal.fire({
    //                     title: "Opps!",
    //                     text: "No Device Found!",
    //                     icon: "error"
    //                 });
    //             }else{
    //                 if(device_marker){
    //                     device_marker.setMap(null);
    //                 }
    //                 device_marker = new google.maps.Marker({
    //                     position: new google.maps.LatLng(response.data.lat,response.data.lng),
    //                     icon: '{{ asset('assets/img/green_tractor.png') }}',
    //                     map: map,
    //                 });
    //                 map.setCenter({
    //                     lat: response.data.lat,
    //                     lng: response.data.lng
    //                 });
    //             }
    //         }
    //     });
    // });
 
    // $('document').ready(function(){
    //     var imei = '{{isset($deviceImei) ? $deviceImei : ''}}';
    //     if(imei.length){
    //         $.ajax({
    //             url: '{{ route('device-geo-fences.device-data') }}',
    //             type: 'POST',
    //             data: {
    //             '_token': '{{ csrf_token() }}',
    //             'imei': imei
    //             },
    //             success: function(response) {
    //                 console.log('response :>> ', response);
    //                 if(response.status == 'NOK'){
    //                     if(device_marker){
    //                         device_marker.setMap(null);
    //                     }
    //                 }else if(response.status =='No Device'){
    //                     Swal.fire({
    //                         title: "Opps!",
    //                         text: "No Device Found!",
    //                         icon: "error"
    //                     });
    //                 }else{
    //                     if(device_marker){
    //                         device_marker.setMap(null);
    //                     }
    //                     device_marker = new google.maps.Marker({
    //                         position: new google.maps.LatLng(response.data.lat,response.data.lng),
    //                         icon: '{{ asset('assets/img/green_tractor.png') }}',
    //                         map: map,
    //                     });
    //                     map.setZoom(
    //                        15
    //                     );
    //                     map.setCenter({
    //                         lat: response.data.lat,
    //                         lng: response.data.lng
    //                     });
    //                     device_marker.addListener('dblclick', function() {
    //                         // Increase the zoom level
    //                         map.setZoom(map.getZoom() + 1);
    //                         // Center the map on the marker
    //                         map.setCenter(marker.getPosition());
    //                     });
    //                 }
    //             }
    //         });
    //     }
    // });
</script>
@endpush
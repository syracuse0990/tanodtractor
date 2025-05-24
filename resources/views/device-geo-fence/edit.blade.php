<x-app-layout title="{{ __('Update Geo Fence') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Geo Fence') }}</span>
                    </div>
                    <div class="card-body">
                        <form id="geoFenceForm" method="POST"
                            action="{{ route('device-geo-fences.update', $deviceGeoFence->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf
                            @include('device-geo-fence.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function(){
            marker = new google.maps.Marker({
                position: {
                    lat: {{$deviceGeoFence->latitude}},
                    lng: {{$deviceGeoFence->longitude}}
                },
                map: map,
                draggable: true
            });
            latlng = new google.maps.LatLng({{$deviceGeoFence->latitude}},{{$deviceGeoFence->longitude}});
            createRadius(latlng);
            marker.addListener('dragend', function(event) {
                var newLatLng = event.latLng;
                marker.setPosition(newLatLng);
                if(cityCircle){
                    cityCircle.setMap(null);
                }
                $('#lat').val(event.latLng.lat());
                $('#lng').val(event.latLng.lng());
                latlng = new google.maps.LatLng(event.latLng.lat(), event.latLng.lng());
                if({{$deviceGeoFence->radius}}){
                    createRadius(latlng);
                }
            });

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
            //             device_marker.addListener('dblclick', function() {
            //                 // Increase the zoom level
            //                 map.setZoom(map.getZoom() + 1);
            //                 // Center the map on the marker
            //                 map.setCenter(marker.getPosition());
            //             });
            //         }
            //     }
            // });
        });

        $('#geoFenceForm').on('submit', function(e) {
        $("#overlay").fadeIn(300);
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            type: 'POST',
            url: '{{ route('device-geo-fences.update', $deviceGeoFence->id) }}',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $("#overlay").fadeOut(300);
                if(response.hasErrors == 1){
                    displayErrorMessages(response.errorMessage,response.url);
                }else{
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Geo fence updated successfully.",
                        showConfirmButton: false,
                        timer: 2000
                    });
                    setTimeout(function() {
                        window.location.href = response.url;
                    }, 1500); // 1500 milliseconds
                }
            },
            error: function(xhr) {
                $("#overlay").fadeOut(300);
                let errors = xhr.responseJSON.errors;
                $('.form-control').removeClass("is-invalid");
                $('.invalid-feedback').empty();
                if (errors) {
                    for (let key in errors) {
                        $('#' + key).addClass("is-invalid");
                        $('#' + key + '_error').html(errors[key][0]);
                    }
                }
            }
        });
    });

    function displayErrorMessages(errors,url) {
        // Join all error messages into a single string with line breaks
        const errorMessageString = errors.join('<br>');
        // Display the error messages using SweetAlert2
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: errorMessageString,
            confirmButtonText: 'OK'
            // showConfirmButton: false,
            // timer: 2000
        }).then((result) => {
            if (result.isConfirmed) {
                // Reload the page when OK is clicked
                window.location.href = url;
            }
        });
    }
    </script>
</x-app-layout>
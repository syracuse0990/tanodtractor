<x-app-layout title="{{ __('Create Geo Fence') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Create Geo Fence') }}</span>
                    </div>
                    <div class="card-body">
                        <form id="geoFenceForm" method="POST" action="{{ route('device-geo-fences.store') }}"
                            role="form" enctype="multipart/form-data">
                            @csrf
                            @include('device-geo-fence.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('js')
    <script>
        $('#geoFenceForm').on('submit', function(e) {
        $("#overlay").fadeIn(300);
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            type: 'POST',
            url: '{{ route('device-geo-fences.store') }}',
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
                        title: "Geo fence created successfully.",
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
    @endpush
</x-app-layout>
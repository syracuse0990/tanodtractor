<x-app-layout title="{{ __('Update Auto Report') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Auto Report') }}</span>
                    </div>
                    <div class="card-body">
                        <form id="autoReportForm" method="POST" action="{{ route('auto-reports.update', $autoReport->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf
                            @include('auto-report.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @push('js')
    <script>
        $(document).ready(function(){
            $('#submitForm').click(function(){
                var reportName = $('#report_name').val();
                var deviceIds = $('#device_ids').val();
                var selectedFrequency = $("input[name='frequency']:checked").val();
                var email = $('#email_addresses').val();


                var isValid = true;
                $('#report_name_error').html('');
                $('#email_addresses_error').html('');
                $('#device_ids_error').html('');
                $('#frequency_error').html('');
                if(!reportName || reportName.length === 0){
                    isValid = false;
                    $('#report_name_error').html('Report name is required.');
                }
                if (!deviceIds || deviceIds.length === 0) {
                    isValid = false;
                    console.log('deviceIds is empty');
                    $('#device_ids_error').html('Devices is required.');
                } 
                if (!selectedFrequency) {
                    isValid = false;
                    $('#frequency_error').html('Frequency is required.');
                } 
                if (!email || email.length === 0) {
                    isValid = false;
                    $('#email_addresses_error').html('Email is required.');
                } else {
                    // Regular expression for email validation
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        isValid = false;
                        $('#email_addresses_error').html('Enter a valid email address.');
                    }
                }

                if(isValid){
                    var formData = new FormData($('#autoReportForm')[0]);
                    let id = '{{$autoReport->id}}';
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: '/auto-reports/'+id,
                        type: 'post',
                        data: formData,
                        processData: false, 
                        contentType: false, 
                        success: function (response) {
                            location.href = response.url;
                        },
                        error: function (xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                console.error('Error message:', xhr.responseJSON.message);
                            } else if (xhr.statusText) {
                                console.error('Error status:', xhr.statusText);
                            } else {
                                console.error('An unknown error occurred.');
                            }
                            console.error('Full error response:', xhr);
                        }
                    });
                }
            });
        });
    </script>
    @endpush
</x-app-layout>

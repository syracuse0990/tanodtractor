@php
use App\Models\AutoReport;

$selectedDevices = $autoReport->device_ids;
if(!is_array($selectedDevices)){
$selectedDevices = explode(',',$selectedDevices);
}
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="form-group">
                {{ Form::label('report_name') }}
                {{ Form::text('report_name', (old('report_name') ?? $autoReport->report_name), ['class' =>
                'form-control' .
                ($errors->has('report_name') ? ' is-invalid' : ''), 'placeholder' => 'Report Name']) }}
                <span id="report_name_error" class="text-danger"></span>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="form-group device-multiselect-drop">
                {{ Form::label('Devices') }}
                <select class="w-100 hidden-select" id="device_ids" name="device_ids[]" autocomplete="device_ids" multiple>
                    @foreach ($deviceList as $key => $value)
                    <option value={{$value->id}} {{in_array($value->id, $selectedDevices) ? 'selected' : ''}}>{{$value->device_name.' ['. $value->imei_no .']'}}</option>
                    @endforeach
                </select>
                <span id="device_ids_error" class="text-danger"></span>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="form-group">
                {{ Form::label('frequency') }}
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="frequency" id="monthlyFrequency"
                    value="{{AutoReport::FREQUENCY_MONTHLY}}" {{ (old('frequency') ?? $autoReport->frequency) ==
                AutoReport::FREQUENCY_MONTHLY ? 'checked' : '' }}>
                <label class="form-check-label" for="monthlyFrequency">Monthly</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="frequency" id="weeklyFrequency"
                    value="{{AutoReport::FREQUENCY_WEEKLY}}" {{ (old('frequency') ?? $autoReport->frequency) ==
                AutoReport::FREQUENCY_WEEKLY ? 'checked' : '' }}>
                <label class="form-check-label" for="weeklyFrequency">Weekly</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="frequency" id="dailyFrequency"
                    value="{{AutoReport::FREQUENCY_DAILY}}" {{ (old('frequency') ?? $autoReport->frequency) ==
                AutoReport::FREQUENCY_DAILY ? 'checked' : '' }}>
                <label class="form-check-label" for="dailyFrequency">Daily</label>
            </div>
            <div>
                <span id="frequency_error" class="text-danger"></span>
            </div>
        </div>
        <div id="frequecyOptions"></div>

        <div class="col-md-12 mb-3">
            <div class="form-group">
                {{ Form::label('Email') }}
                {{ Form::text('email_addresses', (old('email_addresses') ?? $autoReport->email_addresses), ['id' =>
                'email_addresses', 'class' => 'form-control' .
                ($errors->has('email_addresses') ? ' is-invalid' : ''), 'placeholder' => 'Email']) }}
                <span id="email_addresses_error" class="text-danger"></span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class=" mt-3">
                <button type="button" class="btn btn-primary btn-icon text-white rounded-pill px-3" id="submitForm">{{
                    __('Submit')
                    }}</button>
            </div>
        </div>
    </div>
</div>

@push('js')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function(){
        var selectedFrequency = '{{$autoReport->frequency}}';
        if(selectedFrequency == 1){
            monthlyFrequency();
        }else if(selectedFrequency == 2){
            weeklyFrequency();
        }else if(selectedFrequency == 3){
            dailyFrequency();
        }
        $('#device_ids').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Devices',
                search: 'Search'
            }
        });

        toastr.options = {
            "closeButton": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-center",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }

        $('#device_ids').on('change', function(event) {
            var selectedOptions = $(this).val() || [];  // Get selected values (ensure it's an array)
            var selectedCount = selectedOptions.length;  // Count how many devices are selected

            // If the selected count exceeds 20, prevent the new selection
            if (selectedCount > 20) {
                // Prevent the change by deselecting the last selection
                event.preventDefault();
                $(this).val(selectedOptions.slice(0, 20)); // Keep only the first 20 options
                $(this).multiselect('refresh');  // Refresh the UI

                // Show a warning message
                toastr.warning("You can select a maximum of 20 devices.");
            }
        });

        $('.timepicker').timepicker({
            timeFormat: 'HH:mm:ss',
            interval: 1,            
            minTime: '00:00:00',      
            maxTime: '23:59:59',     
            dynamic: true,            
            dropdown: true,            
            scrollbar: true,          
            showSeconds: true,       
            showLeadingZero: true      
        });
            
            
        $(document).on('click', '#monthlyFrequency', function(){
            monthlyFrequency();
        });

        $(document).on('click', '#weeklyFrequency', function(){
            weeklyFrequency();
        });

        $(document).on('click', '#dailyFrequency', function(){
            dailyFrequency();
        });

        function monthlyFrequency()
        {
            let html = `<div class="col-md-12 mb-3">
                <div class="row">
                    {{ Form::label('Execution Time') }}
                    <div class="col-md-3">
                        <div class="form-group">
                            <select name="execution_day" id="execution_day" class="form-select">
                                @php
                                    $executionDay = $autoReport->execution_day ?? old('execution_day');
                                @endphp
                                @for ($i = 1; $i <= 31; $i++) 
                                    <option value="{{$i}}" {{ $i == ($executionDay ?? 1) ? 'selected' : ''}}>{{$i}}</option>
                                @endfor
                            </select>

                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <input type="text" name="execution_time" class="form-control timepicker" value="{{ $autoReport->execution_time ?? '00:00:00' }}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 mb-3 condition-options">
                <div class="row">
                    {{ Form::label('Report Query Conditions') }}
                    <div class="d-flex align-items-center gap-2">
                        <!-- From Day Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="from_day" id="from_day" class="form-select">
                                    @php
                                        $fromDay = $autoReport->from_day ?? old('from_day');
                                    @endphp
                                    @for ($i = 1; $i <= 31; $i++)
                                        <option value="{{$i}}" {{ $i == ($fromDay ?? 1) ? 'selected' : ''}}>{{$i}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
            
                        <!-- From Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="from_time" class="form-control timepicker" value="{{ $autoReport->from_time ?? '00:00:00' }}"/>
                            </div>
                        </div>
            
                        <!-- Minus Icon Centered -->
                        <div class="col-md-1 d-flex justify-content-center">
                            <i class="fa-solid fa-minus align-middle"></i>
                        </div>
            
                        <!-- To Day Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="to_day" id="to_day" class="form-select">
                                    @php
                                        $toDay = $autoReport->to_day ?? old('to_day');
                                    @endphp
                                    @for ($i = 1; $i <= 31; $i++)
                                        <option value="{{$i}}" {{ $i == ($toDay ?? 31) ? 'selected' : ''}}>{{$i}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
            
                        <!-- To Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="to_time" class="form-control timepicker" value="{{ $autoReport->to_time ?? '23:59:59' }}"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#frequecyOptions').html('');
            $('#frequecyOptions').html(html);
            $('.timepicker').timepicker({
                timeFormat: 'HH:mm:ss',
                interval: 1,            
                minTime: '00:00:00',      
                maxTime: '23:59:59',     
                dynamic: true,            
                dropdown: true,            
                scrollbar: true,          
                showSeconds: true,       
                showLeadingZero: true      
            });
        }

        function weeklyFrequency()
        {
            let html = `<div class="col-md-12 mb-3">
                <div class="row">
                    {{ Form::label('Execution Time') }}
                    <div class="col-md-3">
                        <div class="form-group">
                            <select name="execution_day" id="execution_day" class="form-select">
                                @php
                                    $executionDay = $autoReport->execution_day ?? old('execution_day');
                                @endphp
                                @foreach (AutoReport::dayOptions() as $key => $value)
                                    <option value="{{ $key }}" {{ $key == ($executionDay ?? AutoReport::MONDAY) ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>

                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <input type="text" name="execution_time" class="form-control timepicker" value="{{ $autoReport->execution_time ?? '00:00:00' }}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 mb-3 condition-options">
                <div class="row">
                    {{ Form::label('Report Query Conditions') }}
                    <div class="d-flex align-items-center gap-2">
                        <!-- From Day Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="from_day" id="from_day" class="form-select">
                                    @php
                                        $fromDay = $autoReport->from_day ?? old('from_day');
                                    @endphp
                                    @foreach (AutoReport::dayOptions() as $key => $value)
                                        <option value="{{ $key }}" {{ $key == ($fromDay ?? AutoReport::MONDAY) ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
            
                        <!-- From Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="from_time" class="form-control timepicker" value="{{ $autoReport->from_time ?? '00:00:00' }}"/>
                            </div>
                        </div>
            
                        <!-- Minus Icon Centered -->
                        <div class="col-md-1 d-flex justify-content-center">
                            <i class="fa-solid fa-minus align-middle"></i>
                        </div>
            
                        <!-- To Day Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="to_day" id="to_day" class="form-select">
                                    @php
                                        $toDay = $autoReport->to_day ?? old('to_day');
                                    @endphp
                                    @foreach (AutoReport::dayOptions() as $key => $value)
                                        <option value="{{ $key }}" {{ $key == ($toDay ?? AutoReport::SUNDAY) ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
            
                        <!-- To Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="to_time" class="form-control timepicker" value="{{ $autoReport->to_time ?? '23:59:59' }}"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#frequecyOptions').html('');
            $('#frequecyOptions').html(html);
            $('.timepicker').timepicker({
                timeFormat: 'HH:mm:ss',
                interval: 1,            
                minTime: '00:00:00',      
                maxTime: '23:59:59',     
                dynamic: true,            
                dropdown: true,            
                scrollbar: true,          
                showSeconds: true,       
                showLeadingZero: true      
            });
        }

        function dailyFrequency()
        {
            let html = `<div class="col-md-12 mb-3">
                <div class="row">
                    {{ Form::label('Execution Time') }}
                    <div class="col-md-3">
                        <div class="form-group">
                            <input type="text" name="execution_time" class="form-control timepicker" value="{{ $autoReport->execution_time ?? '00:00:00' }}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 mb-3 condition-options">
                <div class="row">
                    {{ Form::label('Report Query Conditions') }}
                    <div class="d-flex align-items-center gap-2">
                        <!-- From Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="from_time" class="form-control timepicker" value="{{ $autoReport->from_time ?? '00:00:00' }}"/>
                            </div>
                        </div>
            
                        <!-- Minus Icon Centered -->
                        <div class="col-md-1 d-flex justify-content-center">
                            <i class="fa-solid fa-minus align-middle"></i>
                        </div>
            
                        <!-- To Time Section -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="to_time" class="form-control timepicker" value="{{ $autoReport->to_time ?? '23:59:59' }}"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#frequecyOptions').html('');
            $('#frequecyOptions').html(html);
            $('.timepicker').timepicker({
                timeFormat: 'HH:mm:ss',
                interval: 1,            
                minTime: '00:00:00',      
                maxTime: '23:59:59',     
                dynamic: true,            
                dropdown: true,            
                scrollbar: true,          
                showSeconds: true,       
                showLeadingZero: true      
            });
        }
    });
</script>
@endpush
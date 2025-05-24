@php
use Illuminate\Support\Facades\Route;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group  custom-select-wrapper ">
                {{ Form::label('Tractors') }}
                <select class="multiple_tractors form-control{{ $errors->has('tractor_ids') ? ' is-invalid' : '' }}"
                    name="tractor_ids" id="tractors_select2">
                    <option></option>
                    @foreach ($tractors as $key => $tractor)
                    @php
                    $tractorName = $tractor?->id_no ?? null;
                    if(empty($tractorName)){
                        $tractorName = $tractor?->no_plate ?? null;
                    }
                    if($tractor->model){
                    $tractorName = $tractorName . ' (' . $tractor?->model . ')';
                    }
                    if($tractor?->imei){
                    $tractorName = $tractorName . ' ['. $tractor?->imei .']';
                    }
                    @endphp
                    <option value="{{ $tractor->id }}" {{ old('tractor_ids')==$tractor->id ? 'selected' : ($maintenance
                        && $maintenance->tractor_ids == $tractor->id ? 'selected': '') }} data-running-hours="{{
                        $tractor->running_km ? $tractor->running_km : '0' }}" >
                        {{ $tractorName ?? 'N/A'}}
                    </option>
                    @endforeach
                </select>
                {!! $errors->first('tractor_ids', '<div class="invalid-feedback">:message</div>') !!}

            </div>
        </div>
        @if (Route::currentRouteName() == 'maintenances.edit')
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Maintenance Date') }}
                {{ Form::text('maintenance_date', $maintenance->maintenance_date ? date('Y/m/d H:i',
                strtotime($maintenance->maintenance_date)) : '', ['id' => 'oldDatePicker', 'class' => 'form-control' .
                ($errors->has('maintenance_date') ? ' is-invalid' : ''), 'placeholder' => 'Maintenance Date' ,
                'readOnly' => 'true']) }}
                {!! $errors->first('maintenance_date', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        @else
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Maintenance Date') }}
                {{ Form::text('maintenance_date', $maintenance->maintenance_date ? date('Y/m/d H:i',
                strtotime($maintenance->maintenance_date)) : '', ['id' => 'datetimepicker', 'class' => 'form-control' .
                ($errors->has('maintenance_date') ? ' is-invalid' : ''), 'placeholder' => 'Maintenance Date' ,
                'readOnly' => 'true']) }}
                {!! $errors->first('maintenance_date', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        @endif
        <div class="col-md-6 mb-3">
            <div class="mb-3">

                <div class="form-group">
                    {{ Form::label('Technician Name') }}
                    {{ Form::text('tech_name', $maintenance->tech_name, ['class' => 'form-control' .
                    ($errors->has('tech_name') ? ' is-invalid' : ''), 'placeholder' => 'Technician Name']) }}
                    {!! $errors->first('tech_name', '<div class="invalid-feedback">:message</div>') !!}
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="mb-3">
                <div class="form-group">
                    {{ Form::label('Technician Email') }}
                    {{ Form::text('tech_email', $maintenance->tech_email, ['class' => 'form-control' .
                    ($errors->has('tech_email') ? ' is-invalid' : ''), 'placeholder' => 'Technician Email']) }}
                    {!! $errors->first('tech_email', '<div class="invalid-feedback">:message</div>') !!}
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group cellphone_field">
                <label class="form-label mb-0" for="tech_number">Technician Number</label><br>
                {{ Form::text('tech_number', $maintenance->tech_number, ['id'=>'tech_number', 'class' => 'form-control'
                .
                ($errors->has('tech_number') ? ' is-invalid'
                : ''), 'placeholder' => 'Technician Number']) }}
                @error('tech_number')
                <span class="text-danger">{{$message}}</span>
                @enderror
                <input type="hidden" name="tech_phone_code" id="tech_phone_code"
                    value="{{ (old('tech_phone_code')) ? (old('tech_phone_code')) : ($maintenance->tech_phone_code) }}" />
                <input type="hidden" name="tech_iso_code" id="tech_iso_code"
                    value="{{ (old('tech_iso_code')) ? (old('tech_iso_code')) : ($maintenance->tech_iso_code) }}" />

            </div>
        </div>
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
@push('js')
<script>
    //Tractor select2
        $('.multiple_tractors').select2({
            placeholder: "Select Tractors",
            templateResult: formatTractorOption
        });
        function formatTractorOption(option) {
            if (!option.id) {
                return option.text;
            }

            var runningHours = $(option.element).data('running-hours');
            console.log('runningHours :>> ', runningHours);
            if(runningHours >= 100){
                var $option = $(
                    '<span>' + option.text + '</span>' +
                    '<span class="tractor-info-red">' + runningHours + ' hours</span>'
                );
            }else if(runningHours >= 50){
                var $option = $(
                    '<span>' + option.text + '</span>' +
                    '<span class="tractor-info-yellow">' + runningHours + ' hours</span>'
                );
            }else if(runningHours >= 0){
                var $option = $(
                    '<span>' + option.text + '</span>' +
                    '<span class="tractor-info-green">' + runningHours + ' hours</span>'
                );
            }


            return $option;
        }

        jQuery('#datetimepicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: true,
            minDate: moment().startOf('day'),
            timePicker24Hour:true,
            locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
          },
        });
        jQuery('#oldDatePicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: true,
            timePicker24Hour:true,
            locale: {
            format: 'YYYY-MM-DD HH:mm:ss'
          },
        });

        var isoCode = ($("#tech_iso_code").val()) ? ($("#tech_iso_code").val()) : ('PH');
        var phoneInput = document.querySelector("#tech_number");
        var phoneInstance = window.intlTelInput(phoneInput, {
            autoPlaceholder: "off",
            separateDialCode: true,
            initialCountry: isoCode
            // utilsScript: '{{URL::asset("frontend/build/js/utils.js")}}',
        });

        $("#tech_phone_code").val(phoneInstance.getSelectedCountryData().dialCode);
        $("#tech_iso_code").val(phoneInstance.getSelectedCountryData().iso2);
        phoneInput.addEventListener("countrychange",function() {
            $("#tech_phone_code").val(phoneInstance.getSelectedCountryData().dialCode);
            $("#tech_iso_code").val(phoneInstance.getSelectedCountryData().iso2);
        });
</script>
@endpush

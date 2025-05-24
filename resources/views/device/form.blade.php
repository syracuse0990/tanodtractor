@php
use App\Models\Device;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('IMEI') }}
                {{ Form::text('imei_no', $device->imei_no, ['class' => 'form-control' . ($errors->has('imei_no') ? '
                is-invalid' : ''), 'placeholder' => 'IMEI']) }}
                {!! $errors->first('imei_no', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Device Model') }}
                {{ Form::text('device_modal', $device->device_modal, ['class' => 'form-control' .
                ($errors->has('device_modal') ? ' is-invalid' : ''), 'placeholder' => 'Device Model']) }}
                {!! $errors->first('device_modal', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Device Name') }}
                {{ Form::text('device_name', $device->device_name, ['class' => 'form-control' .
                ($errors->has('device_name') ? ' is-invalid' : ''), 'placeholder' => 'Device Name']) }}
                {!! $errors->first('device_name', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('SIM') }}
                {{ Form::text('sim', $device->sim, ['class' => 'form-control'. ($errors->has('sim') ? ' is-invalid' : ''), 'placeholder' => 'SIM']) }}
                {!! $errors->first('sim', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        {{-- <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Sales Time') }}
                {{ Form::text('sales_time', $device->sales_time, ['class' => 'form-control' .
                ($errors->has('sales_time') ? ' is-invalid' : ''), 'placeholder' => 'Sales Time']) }}
                {!! $errors->first('sales_time', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div> --}}
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Subscription Expiration') }}
                {{ Form::number('subscription_expiration', $device->subscription_expiration, ['class' => 'form-control'
                . ($errors->has('subscription_expiration') ? ' is-invalid' : ''), 'placeholder' => 'Years']) }}
                {!! $errors->first('subscription_expiration', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Expiration Date') }}
                {{ Form::text('expiration_date', $device->expiration_date ? date('Y/m/d',
                strtotime($device->expiration_date)) : null, ['class' => 'form-control' .
                ($errors->has('expiration_date') ? ' is-invalid' : ''), 'placeholder' => 'Expiration Date', 'id' =>
                'datetimepicker']) }}
                {!! $errors->first('expiration_date', '<div class="invalid-feedback">:message</div>') !!}
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
    jQuery('#datetimepicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: true,
            timePicker24Hour:true,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear'
            },
        });
</script>
@endpush
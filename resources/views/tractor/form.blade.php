@php
use App\Models\Tractor;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('IMEI') }}
                {{ Form::text('imei', $tractor->imei, ['class' => 'form-control' . ($errors->has('imei') ? '
                is-invalid' : ''), 'placeholder' => 'IMEI']) }}
                {!! $errors->first('imei', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Number Plate') }}
                {{ Form::text('no_plate', $tractor->no_plate, ['class' => 'form-control' . ($errors->has('no_plate') ? '
                is-invalid' : ''), 'placeholder' => 'Number Plate']) }}
                {!! $errors->first('no_plate', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('ID number') }}
                {{ Form::text('id_no', $tractor->id_no, ['class' => 'form-control' . ($errors->has('id_no') ? '
                is-invalid' : ''), 'placeholder' => 'ID number']) }}
                {!! $errors->first('id_no', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Engine Number') }}
                {{ Form::text('engine_no', $tractor->engine_no, ['class' => 'form-control' . ($errors->has('engine_no')
                ? ' is-invalid' : ''), 'placeholder' => 'Engine Number']) }}
                {!! $errors->first('engine_no', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Fuel/100km') }}
                {{ Form::text('fuel_consumption', $tractor->fuel_consumption, ['class' => 'form-control' .
                ($errors->has('fuel_consumption') ? ' is-invalid' : ''), 'placeholder' => 'Fuel/100km']) }}
                {!! $errors->first('fuel_consumption', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('First Maintenance Hours') }}
                {{ Form::text('first_maintenance_hr', $tractor->first_maintenance_hr, ['class' => 'form-control' .
                ($errors->has('first_maintenance_hr') ? ' is-invalid' : ''), 'placeholder' => 'First Maintenance
                Hours'])}}
                {!! $errors->first('first_maintenance_hr', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Subsequent Maintenance Hours') }}
                {{ Form::text('maintenance_kilometer', $tractor->maintenance_kilometer, ['class' => 'form-control' .
                ($errors->has('maintenance_kilometer') ? ' is-invalid' : ''), 'placeholder' => 'Subsequent Maintenance
                Hours'])}}
                {!! $errors->first('maintenance_kilometer', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Running Hours') }}
                {{ Form::text('running_km', $tractor->running_km, ['class' => 'form-control' .
                ($errors->has('running_km') ? ' is-invalid' : ''), 'placeholder' => 'Running Hours'])}}
                {!! $errors->first('running_km', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Tractor Brand') }}
                {{ Form::text('brand', $tractor->brand, ['class' => 'form-control' . ($errors->has('brand') ? '
                is-invalid' : ''), 'placeholder' => 'Tractor Brand']) }}
                {!! $errors->first('brand', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Tractor Model') }}
                {{ Form::text('model', $tractor->model, ['class' => 'form-control' . ($errors->has('model') ? '
                is-invalid' : ''), 'placeholder' => 'Tractor Model']) }}
                {!! $errors->first('model', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Manufacture Date') }}
                {{ Form::text('manufacture_date', $tractor->manufacture_date, ['id' => 'manufactureDate', 'class' =>
                'form-control' . ($errors->has('manufacture_date') ? ' is-invalid' : ''), 'placeholder' =>
                'Manufacture Date']) }}
                {!! $errors->first('manufacture_date', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Installation Time') }}
                {{ Form::text('installation_time', $tractor->installation_time, ['id' => 'installationDateTime', 'class'
                => 'form-control' . ($errors->has('installation_time') ? ' is-invalid' : ''), 'placeholder' =>
                'Installation Time']) }}
                {!! $errors->first('installation_time', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Installation Address') }}
                {{ Form::text('installation_address', $tractor->installation_address, ['class' => 'form-control' .
                ($errors->has('installation_address') ? ' is-invalid' : ''), 'placeholder' => 'Installation Address'])
                }}
                {!! $errors->first('installation_address', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('image', 'Images') }}
                {{ Form::file('path[]', ['class' => 'form-control' . ($errors->has('path') || $errors->has('path.*') ? '
                is-invalid' : ''), 'placeholder' => 'Images', 'multiple' => true]) }}
                {!! $errors->first('path', '<div class="invalid-feedback">:message</div>') !!}
                @if ($errors->has('path.*'))
                @foreach ($errors->get('path.*') as $error)
                <div class="invalid-feedback">
                    {{ $error[0] }}
                </div>
                @endforeach
                @endif
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
    jQuery('#manufactureDate').datepicker({
            dateFormat: 'yy/mm/dd',
            changeMonth: true,
            changeYear: true
        });
        jQuery('#installationDateTime').datetimepicker({
            dateFormat: 'yy/mm/dd',
            changeMonth: true,
            changeYear: true
        });
</script>
@endpush
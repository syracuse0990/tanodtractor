<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('tractor_id') }}
            {{ Form::text('tractor_id', $tractorBooking->tractor_id, ['class' => 'form-control' . ($errors->has('tractor_id') ? ' is-invalid' : ''), 'placeholder' => 'Tractor Id']) }}
            {!! $errors->first('tractor_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('device_id') }}
            {{ Form::text('device_id', $tractorBooking->device_id, ['class' => 'form-control' . ($errors->has('device_id') ? ' is-invalid' : ''), 'placeholder' => 'Device Id']) }}
            {!! $errors->first('device_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('slot_id') }}
            {{ Form::text('slot_id', $tractorBooking->slot_id, ['class' => 'form-control' . ($errors->has('slot_id') ? ' is-invalid' : ''), 'placeholder' => 'Slot Id']) }}
            {!! $errors->first('slot_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('purpose') }}
            {{ Form::text('purpose', $tractorBooking->purpose, ['class' => 'form-control' . ($errors->has('purpose') ? ' is-invalid' : ''), 'placeholder' => 'Purpose']) }}
            {!! $errors->first('purpose', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('state_id') }}
            {{ Form::text('state_id', $tractorBooking->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State Id']) }}
            {!! $errors->first('state_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('type_id') }}
            {{ Form::text('type_id', $tractorBooking->type_id, ['class' => 'form-control' . ($errors->has('type_id') ? ' is-invalid' : ''), 'placeholder' => 'Type Id']) }}
            {!! $errors->first('type_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('created_by') }}
            {{ Form::text('created_by', $tractorBooking->created_by, ['class' => 'form-control' . ($errors->has('created_by') ? ' is-invalid' : ''), 'placeholder' => 'Created By']) }}
            {!! $errors->first('created_by', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>
@php
use App\Models\TractorGroup;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('name') }}
                {{ Form::text('name', $tractorGroup->name, ['class' => 'form-control' . ($errors->has('name') ? '
                is-invalid' : ''), 'placeholder' => 'Name']) }}
                {!! $errors->first('name', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('State') }}
                {{ Form::select('state_id', TractorGroup::stateOptions(), $tractorGroup->state_id, ['class' =>
                'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State']) }}
                {!! $errors->first('state_id', '<div class="invalid-feedback">:message</div>') !!}
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
<script>
    $(document).ready(function() {
        //Farmer Select2
        $('.multiple_farmers').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Farmers',
                search: 'Search'
            }
        });
       $('#farmer_select').removeClass('hidden-select');
        //Tractor select2
        $('.multiple_tractors').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Tractors',
                search: 'Search'
            }
        });
        $('#tractor_select').removeClass('hidden-select');

        //Device select2
        $('.multiple_devices').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Devices',
                search: 'Search'
            }
        });
       $('#device_select').removeClass('hidden-select');

    });
</script>

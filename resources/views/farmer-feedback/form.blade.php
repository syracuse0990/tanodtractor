@php
    use App\Models\FarmerFeedback;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Technician Details') }}
                {{ Form::textarea('tech_details', $farmerFeedback->tech_details, ['class' => 'form-control' . ($errors->has('tech_details') ? ' is-invalid' : ''), 'placeholder' => 'Technician Details', 'style' => 'height: 150px;']) }}
                {!! $errors->first('tech_details', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Conclusion') }}
                {{ Form::textarea('conclusion', $farmerFeedback->conclusion, ['class' => 'form-control' . ($errors->has('conclusion') ? ' is-invalid' : ''), 'placeholder' => 'Conclusion', 'style' => 'height: 150px;']) }}
                {!! $errors->first('conclusion', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('State') }}
                {{ Form::Select('state_id', FarmerFeedback::stateOptions(), $farmerFeedback->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State']) }}
                {!! $errors->first('state_id', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class=" mt-3">
                <button type="submit"
                    class="btn btn-primary btn-icon text-white rounded-pill px-3">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
</div>

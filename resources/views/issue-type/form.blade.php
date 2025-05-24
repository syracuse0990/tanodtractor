@php
    use App\Models\IssueType;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('title') }}
                {{ Form::text('title', $issueType->title, ['class' => 'form-control' . ($errors->has('title') ? ' is-invalid' : ''), 'placeholder' => 'Title']) }}
                {!! $errors->first('title', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('State') }}
                {{ Form::Select('state_id', IssueType::stateOptionsChanged(), $issueType->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State']) }}
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

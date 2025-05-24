<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('from_email') }}
            {{ Form::text('from_email', $emailQueue->from_email, ['class' => 'form-control' . ($errors->has('from_email') ? ' is-invalid' : ''), 'placeholder' => 'From Email']) }}
            {!! $errors->first('from_email', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('to_email') }}
            {{ Form::text('to_email', $emailQueue->to_email, ['class' => 'form-control' . ($errors->has('to_email') ? ' is-invalid' : ''), 'placeholder' => 'To Email']) }}
            {!! $errors->first('to_email', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('message') }}
            {{ Form::text('message', $emailQueue->message, ['class' => 'form-control' . ($errors->has('message') ? ' is-invalid' : ''), 'placeholder' => 'Message']) }}
            {!! $errors->first('message', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('subject') }}
            {{ Form::text('subject', $emailQueue->subject, ['class' => 'form-control' . ($errors->has('subject') ? ' is-invalid' : ''), 'placeholder' => 'Subject']) }}
            {!! $errors->first('subject', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('date_published') }}
            {{ Form::text('date_published', $emailQueue->date_published, ['class' => 'form-control' . ($errors->has('date_published') ? ' is-invalid' : ''), 'placeholder' => 'Date Published']) }}
            {!! $errors->first('date_published', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('last_attempt') }}
            {{ Form::text('last_attempt', $emailQueue->last_attempt, ['class' => 'form-control' . ($errors->has('last_attempt') ? ' is-invalid' : ''), 'placeholder' => 'Last Attempt']) }}
            {!! $errors->first('last_attempt', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('date_sent') }}
            {{ Form::text('date_sent', $emailQueue->date_sent, ['class' => 'form-control' . ($errors->has('date_sent') ? ' is-invalid' : ''), 'placeholder' => 'Date Sent']) }}
            {!! $errors->first('date_sent', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('attempts') }}
            {{ Form::text('attempts', $emailQueue->attempts, ['class' => 'form-control' . ($errors->has('attempts') ? ' is-invalid' : ''), 'placeholder' => 'Attempts']) }}
            {!! $errors->first('attempts', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('status') }}
            {{ Form::text('status', $emailQueue->status, ['class' => 'form-control' . ($errors->has('status') ? ' is-invalid' : ''), 'placeholder' => 'Status']) }}
            {!! $errors->first('status', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('type') }}
            {{ Form::text('type', $emailQueue->type, ['class' => 'form-control' . ($errors->has('type') ? ' is-invalid' : ''), 'placeholder' => 'Type']) }}
            {!! $errors->first('type', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('model_id') }}
            {{ Form::text('model_id', $emailQueue->model_id, ['class' => 'form-control' . ($errors->has('model_id') ? ' is-invalid' : ''), 'placeholder' => 'Model Id']) }}
            {!! $errors->first('model_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('model_type') }}
            {{ Form::text('model_type', $emailQueue->model_type, ['class' => 'form-control' . ($errors->has('model_type') ? ' is-invalid' : ''), 'placeholder' => 'Model Type']) }}
            {!! $errors->first('model_type', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>
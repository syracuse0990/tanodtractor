<div class="box box-info padding-1">
    <div class="box-body">

        <div class="form-group">
            {{ Form::label('title') }}
            {{ Form::text('title', $ticket->title, ['class' => 'form-control' . ($errors->has('title') ? ' is-invalid' :
            ''), 'placeholder' => 'Title']) }}
            {!! $errors->first('title', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('description') }}
            {{ Form::text('description', $ticket->description, ['class' => 'form-control' . ($errors->has('description')
            ? ' is-invalid' : ''), 'placeholder' => 'Description']) }}
            {!! $errors->first('description', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('conclusion') }}
            {{ Form::text('conclusion', $ticket->conclusion, ['class' => 'form-control' . ($errors->has('conclusion') ?
            ' is-invalid' : ''), 'placeholder' => 'Conclusion']) }}
            {!! $errors->first('conclusion', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('type_id') }}
            {{ Form::text('type_id', $ticket->type_id, ['class' => 'form-control' . ($errors->has('type_id') ? '
            is-invalid' : ''), 'placeholder' => 'Type Id']) }}
            {!! $errors->first('type_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('state_id') }}
            {{ Form::text('state_id', $ticket->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? '
            is-invalid' : ''), 'placeholder' => 'State Id']) }}
            {!! $errors->first('state_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>
        <div class="form-group">
            {{ Form::label('created_by') }}
            {{ Form::text('created_by', $ticket->created_by, ['class' => 'form-control' . ($errors->has('created_by') ?
            ' is-invalid' : ''), 'placeholder' => 'Creted By']) }}
            {!! $errors->first('created_by', '<div class="invalid-feedback">:message</div>') !!}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</div>
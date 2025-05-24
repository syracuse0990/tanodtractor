@php
    use App\Models\Page;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Title') }}
                {{ Form::text('title', $page->title, ['class' => 'form-control' . ($errors->has('title') ? ' is-invalid' : ''), 'placeholder' => 'Title']) }}
                {!! $errors->first('title', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Page Type') }}
                {{ Form::select('page_type', Page::pageOptions() ,$page->page_type, ['class' => 'form-control' . ($errors->has('page_type') ? ' is-invalid' : ''), 'placeholder' => 'Page Type']) }}
                {!! $errors->first('page_type', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="form-group">
                {{ Form::label('Description') }}
                {{ Form::textarea('description', $page->description, ['class' => 'form-control description' . ($errors->has('description') ? ' is-invalid' : ''), 'placeholder' => 'Description']) }}
                {!! $errors->first('description', '<div class="invalid-feedback">:message</div>') !!}
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



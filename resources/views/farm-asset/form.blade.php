@php
use App\Models\FarmAsset;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('number_plate') }}
                {{ Form::text('number_plate', $farmAsset->number_plate, ['class' => 'form-control' .
                ($errors->has('number_plate') ? ' is-invalid' : ''), 'placeholder' => 'Number Plate']) }}
                {!! $errors->first('number_plate', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('mileage') }}
                {{ Form::text('mileage', $farmAsset->mileage, ['class' => 'form-control' . ($errors->has('mileage') ? '
                is-invalid' : ''), 'placeholder' => 'Mileage']) }}
                {!! $errors->first('mileage', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('Type') }}
                {{ Form::select('type_id', FarmAsset::typeOptions(),$farmAsset->type_id, ['class' => 'form-control' .
                ($errors->has('type_id') ? '
                is-invalid' : ''), 'placeholder' => 'Type']) }}

                {!! $errors->first('type_id', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('condition') }}
                <div class="form-check p-0 {{($errors->has('condition') ? '
                is-invalid' : '')}}">
                    <div class="d-flex gap-3">
                        <div>
                            <input class="form-check-input float-none ms-0" type="radio" name="condition" id="oldRadio"
                                value="1" {{ $farmAsset->condition == 1 ? 'checked' : ''}}>
                            <label class="form-check-label" for="oldRadio">
                                Old
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input float-none ms-0" type="radio" name="condition" id="newRadio"
                                value="2" {{$farmAsset->condition == 2 ? 'checked' : ''}}>
                            <label class="form-check-label" for="newRadio">
                                New
                            </label>
                        </div>
                    </div>
                </div>
                {!! $errors->first('condition', '<div class="invalid-feedback">:message</div>') !!}
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
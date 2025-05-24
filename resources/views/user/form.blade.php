@php
use App\Models\User;
@endphp
@if (request()->is('users/create'))
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('name') }}
                {{ Form::text('name', old('name')??$user->name, ['class' => 'form-control' . ($errors->has('name') ? '
                is-invalid' :
                ''), 'placeholder' => 'Name']) }}
                {!! $errors->first('name', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('email') }}
                {{ Form::text('email', old('email') ?? $user->email, ['class' => 'form-control' . ($errors->has('email')
                ? ' is-invalid'
                : ''), 'placeholder' => 'Email']) }}
                {!! $errors->first('email', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group cellphone_field">
                <label class="form-label mb-0" for="phone_number">Phone</label><br>
                {{ Form::text('phone', $user->phone, ['id'=>'phone_number', 'class' => 'form-control' .
                ($errors->has('phone') ? ' is-invalid'
                : ''), 'placeholder' => 'Phone']) }}
                @error('phone')
                <span class="text-danger">{{$message}}</span>
                @enderror
                <input type="hidden" name="phone_country" id="phone_country"
                    value="{{ (old('phone_country')) ? (old('phone_country')) : ($user->phone_country) }}" />
                <input type="hidden" name="country_code" id="country_code"
                    value="{{ (old('country_code')) ? (old('country_code')) : ($user->country_code) }}" />

            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('gender') }}
                {{ Form::select('gender', User::genderOptions(), old('gender') ?? $user->gender, ['class' =>
                'form-control' .
                ($errors->has('gender') ? ' is-invalid' : ''), 'placeholder' => 'Gender']) }}
                {!! $errors->first('gender', '<div class="invalid-feedback">:message</div>') !!}
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
@else
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('name') }}
                {{ Form::text('name', $user->name, ['class' => 'form-control' . ($errors->has('name') ? ' is-invalid' :
                ''), 'placeholder' => 'Name']) }}
                {!! $errors->first('name', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('email') }}
                {{ Form::text('email', $user->email, ['class' => 'form-control' . ($errors->has('email') ? ' is-invalid'
                : ''), 'placeholder' => 'Email', 'readonly' => 'readonly']) }}
                {!! $errors->first('email', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-group cellphone_field">
                <label class="form-label mb-0" for="phone_number">Phone</label><br>
                {{ Form::text('phone', $user->phone, ['id'=>'phone_number', 'class' => 'form-control' .
                ($errors->has('phone') ? ' is-invalid'
                : ''), 'placeholder' => 'Phone']) }}
                @error('phone')
                <span class="text-danger">{{$message}}</span>
                @enderror
                <input type="hidden" name="phone_country" id="phone_country"
                    value="{{ (old('phone_country')) ? (old('phone_country')) : ($user->phone_country) }}" />
                <input type="hidden" name="country_code" id="country_code"
                    value="{{ (old('country_code')) ? (old('country_code')) : ($user->country_code) }}" />

            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('gender') }}
                {{ Form::select('gender', User::genderOptions(), $user->gender, ['class' => 'form-control' .
                ($errors->has('gender') ? ' is-invalid' : ''), 'placeholder' => 'Gender']) }}
                {!! $errors->first('gender', '<div class="invalid-feedback">:message</div>') !!}
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
@endif

@push('js')
<script>
    var isoCode = ($("#country_code").val()) ? ($("#country_code").val()) : ('PH');
    var phoneInput = document.querySelector("#phone_number");
    var phoneInstance = window.intlTelInput(phoneInput, {
        autoPlaceholder: "off",
        separateDialCode: true,
        initialCountry: isoCode
        // utilsScript: '{{URL::asset("frontend/build/js/utils.js")}}',
    });

    $("#phone_country").val(phoneInstance.getSelectedCountryData().dialCode);
    $("#country_code").val(phoneInstance.getSelectedCountryData().iso2);
    phoneInput.addEventListener("countrychange",function() {
        $("#phone_country").val(phoneInstance.getSelectedCountryData().dialCode);
        $("#country_code").val(phoneInstance.getSelectedCountryData().iso2);
    });
</script>
@endpush
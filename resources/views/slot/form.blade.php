@php
    use App\Models\Slot;
@endphp
<div class="default-form">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="form-group custom-select-wrapper">
                {{ Form::label('Tractor') }}
                <select class="tractor_select form-control{{ $errors->has('tractor_id') ? ' is-invalid' : '' }}"
                    name="tractor_id" id="tractor_select2">
                    <option value="">Select Tractor</option>
                    @foreach ($tractors as $tractor)
                        <option value="{{ $tractor->id }}"
                            {{ $tractor->id == ($slot->tractor_id ?? old('tractor_id')) ? 'selected' : '' }}>
                            {{ $tractor->id_no . ' (' . $tractor->model . ')' }}</option>
                    @endforeach
                </select>
                {!! $errors->first('tractor_id', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('date') }}
                {{ Form::text('date', $slot->date, ['id' => 'datePicker', 'class' => 'form-control' . ($errors->has('date') ? ' is-invalid' : ''), 'placeholder' => 'Date']) }}
                {!! $errors->first('date', '<div class="invalid-feedback">:message</div>') !!}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="form-group">
                {{ Form::label('State') }}
                {{ Form::select('state_id', Slot::stateOptions(), $slot->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State']) }}
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


@push('js')
    <script>
        $(document).ready(function() {
            $('.tractor_select').select2();
        });

        $('#datePicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minDate: moment()
        });
    </script>
@endpush

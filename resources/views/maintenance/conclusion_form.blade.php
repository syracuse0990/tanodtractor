@php
    use App\Models\Maintenance;
@endphp

<x-app-layout title="{{ __('Create Maintenance') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ $maintenance->conclusion ?  __('Edit Conclusion') : __('Create Conclusion') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenances.update', $maintenance->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf
                            <div class="default-form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            {{ Form::label('Conclusion') }}
                                            {{ Form::textarea('conclusion', $maintenance->conclusion, ['class' => 'form-control' . ($errors->has('conclusion') ? ' is-invalid' : ''), 'placeholder' => 'Conclusion', 'style' => 'height: 150px;']) }}
                                            {!! $errors->first('conclusion', '<div class="invalid-feedback">:message</div>') !!}
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            {{ Form::label('State') }}
                                            {{ Form::Select('state_id', Maintenance::stateOptions(), $maintenance->state_id, ['class' => 'form-control' . ($errors->has('state_id') ? ' is-invalid' : ''), 'placeholder' => 'State']) }}
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
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>


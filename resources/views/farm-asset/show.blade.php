@extends('layouts.app')

@section('template_title')
    {{ $farmAsset->name ?? "{{ __('Show') Farm Asset" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Farm Asset</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('farm-assets.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Number Plate:</strong>
                            {{ $farmAsset->number_plate }}
                        </div>
                        <div class="form-group">
                            <strong>Mileage:</strong>
                            {{ $farmAsset->mileage }}
                        </div>
                        <div class="form-group">
                            <strong>Condition:</strong>
                            {{ $farmAsset->condition }}
                        </div>
                        <div class="form-group">
                            <strong>Type Id:</strong>
                            {{ $farmAsset->type_id }}
                        </div>
                        <div class="form-group">
                            <strong>State Id:</strong>
                            {{ $farmAsset->state_id }}
                        </div>
                        <div class="form-group">
                            <strong>Created By:</strong>
                            {{ $farmAsset->created_by }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

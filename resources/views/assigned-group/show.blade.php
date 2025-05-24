@extends('layouts.app')

@section('template_title')
    {{ $assignedGroup->name ?? "{{ __('Show') Assigned Group" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Assigned Group</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('assigned-groups.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>User Id:</strong>
                            {{ $assignedGroup->user_id }}
                        </div>
                        <div class="form-group">
                            <strong>Group Id:</strong>
                            {{ $assignedGroup->group_id }}
                        </div>
                        <div class="form-group">
                            <strong>Type Id:</strong>
                            {{ $assignedGroup->type_id }}
                        </div>
                        <div class="form-group">
                            <strong>State Id:</strong>
                            {{ $assignedGroup->state_id }}
                        </div>
                        <div class="form-group">
                            <strong>Created By:</strong>
                            {{ $assignedGroup->created_by }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

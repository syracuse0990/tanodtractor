@extends('layouts.app')

@section('template_title')
    {{ $emailQueue->name ?? "{{ __('Show') Email Queue" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Email Queue</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('email-queues.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>From Email:</strong>
                            {{ $emailQueue->from_email }}
                        </div>
                        <div class="form-group">
                            <strong>To Email:</strong>
                            {{ $emailQueue->to_email }}
                        </div>
                        <div class="form-group">
                            <strong>Message:</strong>
                            {{ $emailQueue->message }}
                        </div>
                        <div class="form-group">
                            <strong>Subject:</strong>
                            {{ $emailQueue->subject }}
                        </div>
                        <div class="form-group">
                            <strong>Date Published:</strong>
                            {{ $emailQueue->date_published }}
                        </div>
                        <div class="form-group">
                            <strong>Last Attempt:</strong>
                            {{ $emailQueue->last_attempt }}
                        </div>
                        <div class="form-group">
                            <strong>Date Sent:</strong>
                            {{ $emailQueue->date_sent }}
                        </div>
                        <div class="form-group">
                            <strong>Attempts:</strong>
                            {{ $emailQueue->attempts }}
                        </div>
                        <div class="form-group">
                            <strong>Status:</strong>
                            {{ $emailQueue->status }}
                        </div>
                        <div class="form-group">
                            <strong>Type:</strong>
                            {{ $emailQueue->type }}
                        </div>
                        <div class="form-group">
                            <strong>Model Id:</strong>
                            {{ $emailQueue->model_id }}
                        </div>
                        <div class="form-group">
                            <strong>Model Type:</strong>
                            {{ $emailQueue->model_type }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

<x-app-layout title="{{ __('Create Farmer Feedback') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Create Farmer Feedback') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('farmer-feedbacks.store') }}" role="form"
                            enctype="multipart/form-data">
                            @csrf
                            @include('farmer-feedback.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

<x-app-layout title="{{ __('Create Farm Asset') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Create Farm Asset') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('farm-assets.store') }}" role="form"
                            enctype="multipart/form-data">
                            @csrf
                            @include('farm-asset.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
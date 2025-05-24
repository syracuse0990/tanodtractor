<x-app-layout title="{{ __('Create Maintenance') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Create Maintenance') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenances.store') }}" role="form"
                            enctype="multipart/form-data">
                            @csrf
                            @include('maintenance.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

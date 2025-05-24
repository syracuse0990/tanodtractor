<x-app-layout title="{{ __('Update Maintenance') }}">
    <section class="content">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Maintenance') }} </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenances.update', $maintenance->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('maintenance.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

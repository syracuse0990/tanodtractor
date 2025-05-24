<x-app-layout title="{{ __('Update Tractor') }}">
    <section class="content">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Tractor') }} </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tractors.update', $tractor->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('tractor.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

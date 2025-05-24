<x-app-layout title="{{ __('Update Slot') }}">
    <section class="content">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Slot') }} </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('slots.update', $slot->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('slot.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

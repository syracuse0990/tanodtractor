<x-app-layout title="{{ $farmerFeedback->conclusion ? __('Update Conclusion') : __('Add Conclusion') }}">
    <section class="content">
        <div class="">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span
                            class="card-title">{{ $farmerFeedback->conclusion ? __('Update Conclusion') : __('Add Conclusion') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('farmer-feedbacks.update', $farmerFeedback->id) }}"
                            role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf
                            @include('farmer-feedback.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

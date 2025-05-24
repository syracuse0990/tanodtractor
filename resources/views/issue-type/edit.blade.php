<x-app-layout title="{{ __('Update Issue Type') }}">
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update Issue Type') }} </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('issue-types.update', $issueType->id) }}" role="form"
                            enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf
                            @include('issue-type.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

<x-app-layout title="{{ __('Create Issue Type') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Create Issue Type') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('issue-types.store') }}" role="form"
                            enctype="multipart/form-data">
                            @csrf
                            @include('issue-type.form')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

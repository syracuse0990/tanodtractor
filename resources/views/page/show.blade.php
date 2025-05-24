<x-app-layout
    title="{{ __($page->getPage()) }}">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500">
                        {!!$page->getPage()!!}
                    </h2>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Title <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $page->title }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Page Type <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {!! $page->getPage() !!}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $page->createdBy?->name ?? $page->createdBy?->email }} </td>
                        </tr>
                    </table>
                    <div class="form-group p-1 pb-0">
                        <strong>Description:</strong>
                        <div class="ck-content">
                            {!! $page->description !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

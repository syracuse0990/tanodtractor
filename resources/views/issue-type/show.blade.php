<x-app-layout
    title="{{ __(strlen($issueType->title) > 10 ? substr($issueType->title, 0, 10) . '...' : $issueType->title) }}">
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
                        {{ strlen($issueType->title) > 40 ? substr($issueType->title, 0, 40) . '...' : $issueType->title }}
                    </h2>
                    <a href="{{ route('issue-types.edit', [$issueType->id]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Title <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $issueType->title }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $issueType->getStateLabel() !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $issueType->createdBy?->name }} </td>
                        </tr>
                    </table>
                    @livewire('change-status', ['model' => $issueType])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

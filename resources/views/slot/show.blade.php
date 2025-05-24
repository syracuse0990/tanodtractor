<x-app-layout title="{{ __($slot->id) }}">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $slot->device_name }}</h2>
                    <a href="{{ route('slots.edit', [$slot->id]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Tractor <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $slot->tractor?->id_no . ' (' . $slot->tractor?->model . ')' }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Date <span class="float-end">:</span></strong></td>
                            <td>{!! date('d-M-Y', strtotime($slot->date)) !!} </td>
                        </tr>

                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $slot->getStatelabel() !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $slot->createdBy?->name }} </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>

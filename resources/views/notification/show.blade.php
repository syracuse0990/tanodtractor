<x-app-layout title="{{ __($notification->id) }}">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $notification->tractor?->model ? $notification->tractor?->id_no .' ('.$notification->tractor?->model .')' : ($notification->tractor?->id_no ? $notification->tractor?->id_no : $notification->id)}}</h2>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Title <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $notification->title }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Message <span class="float-end">:</span></strong></td>
                            <td>{{ $notification->message }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Tractor <span class="float-end">:</span></strong></td>
                            <td>{{ $notification->tractor?->model ? $notification->tractor?->id_no .' ('.$notification->tractor?->model .')' : $notification->tractor?->id_no}} </td>
                        </tr>
                         <tr>
                            <td><strong class="fw-500">Status <span class="float-end">:</span></strong></td>
                            <td>{!! $notification->getStateLabel()!!}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

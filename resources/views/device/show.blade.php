@php
    use App\Models\User;
@endphp
<x-app-layout title="{{ __($device->device_name ?? $device->imei_no) }}">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $device->device_name ?? $device->imei_no }}</h2>
                    @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                        <a href="{{ route('devices.edit', [$device->id]) }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                    @endif
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> IMEI <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $device->imei_no }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Device Modal <span class="float-end">:</span></strong></td>
                            <td>{!! $device->device_modal !!} </td>
                        </tr>

                        <tr>
                            <td><strong class="fw-500">Device Name <span class="float-end">:</span></strong></td>
                            <td>{{ $device->device_name }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Sim Card Number <span class="float-end">:</span></strong></td>
                            <td>{{ $device->sim ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Sim Card ICCID <span class="float-end">:</span></strong></td>
                            <td>{{ $device->sim_iccid ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Sim Registration Code <span class="float-end">:</span></strong></td>
                            <td>{{ $device->sim_registration_code ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Subscription Expiration <span class="float-end">:</span></strong>
                            </td>
                            <td>{{ $device->subscription_expiration ? $device->subscription_expiration . ' Years' : 'N/A' }}
                            </td>
                        </tr>

                        <tr>
                            <td><strong class="fw-500">Expiration Date <span class="float-end">:</span></strong></td>
                            <td>{{ $device->expiration_date ? date('d/M/Y H:i:s', strtotime($device->expiration_date)) : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $device->createdBy?->name }} </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Alerts</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Time</th>
                                    {{-- <th>IMEI</th> --}}
                                    <th>Alarm Name</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts as $alert)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ date('d-M-Y h:i A', strtotime($alert->alarm_time)) }}</td>
                                        {{-- <td>{{ $alert->imei }}</td> --}}
                                        <td>{{ $alert->alarm_name }}</td>
                                        <td>{{ $alert->user_id ? $alert->createdBy?->name ?? $alert->createdBy?->email : 'N/A' }}
                                        </td>
                                    </tr>

                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $alerts->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>

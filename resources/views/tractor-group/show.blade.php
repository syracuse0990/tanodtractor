@php
    use App\Models\User;
@endphp
<x-app-layout title="{{ __($tractorGroup->name) }}">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $tractorGroup->name }}</h2>
                    <div>
                        @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                            @if ($assignedGroup)
                                <a href="{{ route('users.assignUser', ['id' => $assignedGroup->group_id, 'user_id' => $assignedGroup->user_id, 'state' => 0]) }}"
                                    class="btn btn-danger danger-btn-icon text-white btn-sm rounded-pill px-3">
                                    <i class="fa-regular fa-trash-can"></i> Remove Sub Admin</a>
                            @else
                                <a href="{{ route('users.assignIndex', ['id' => $tractorGroup->id]) }}"
                                    class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                                    <i class="fa-regular fa-plus"></i> Assign Sub Admin</a>
                            @endif

                            <a href="{{ route('tractor-groups.edit', [$tractorGroup->id]) }}"
                                class="btn btn-success btn-icon text-white btn-sm rounded-pill px-3">
                                <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                        @endif

                    </div>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $tractorGroup->name }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $tractorGroup->getStateLabel() !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $tractorGroup->createdBy?->name ?? $tractorGroup->createdBy?->email }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Sub Admin <span class="float-end">:</span></strong></td>
                            <td>{{ $tractorGroup->subAdmin?->user?->name ?? 'N/A' }} </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 col-sm-12">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractors</h3>
                    </div>
                    {{-- <form id="searchForm" action="{{ route('tractor-groups.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form> --}}
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Number Plate</th>
                                    <th>Id Number</th>
                                    <th>Fuel/100km</th>
                                    <th>Tractor Brand</th>
                                    <th>Tractor Model</th>
                                    <th>Installation Time</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @if (count($tractors))
                                    @foreach ($tractors as $tractor)
                                        <tr>
                                            <td>{{ ++$i }}</td>

                                            <td>{{ $tractor->no_plate }}</td>
                                            <td>{{ $tractor->id_no }}</td>
                                            <td>{{ $tractor->fuel_consumption ? $tractor->fuel_consumption . ' ltr' : 0 }}
                                            </td>
                                            <td>{{ $tractor->brand }}</td>
                                            <td>{{ $tractor->model }}</td>
                                            <td>{{ date('d-M-Y h:iA', strtotime($tractor->installation_time)) }}
                                            </td>
                                            <td>{!! $tractor->getStateLabel() !!}</td>
                                            <td class="action-btn">
                                                <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('tractors.show', $tractor->id) }}"><i
                                                        class="fa-solid fa-eye"></i></a>
                                                @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                                    <a href="{{ route('tractors.edit', $tractor->id) }}"
                                                        class="btn primary text-primary btn-sm me-2 rounded-3">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- COL END -->
    </div>

    <div class="row mt-3">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Farmers</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>

                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Gender</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @if (count($farmers))
                                    @foreach ($farmers as $farmer)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $farmer->name }}</td>
                                            <td>{{ $farmer->email }}</td>
                                            <td>{{ $farmer->phone }}</td>
                                            <td>{{ $farmer->getRole() }}</td>
                                            <td>{{ $farmer->getGender() }}</td>
                                            <td>{!! $farmer->getStateLabel() !!}</td>
                                            <td class="action-btn">
                                                <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('users.show', $farmer->id) }}"><i
                                                        class="fa-solid fa-eye"></i></a>
                                                @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                                    <a href="{{ route('users.edit', $farmer->id) }}"
                                                        class="btn primary text-primary btn-sm me-2 rounded-3">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                @endif

                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- COL END -->
    </div>

    <div class="row mt-3">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Devices</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Imei No</th>
                                    <th>Device Modal</th>
                                    <th>Device Name</th>
                                    <th>Subscription Expiration</th>
                                    <th>Expiration Date</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @if (count($devices))
                                    @foreach ($devices as $device)
                                        <tr>
                                            <td>{{ ++$i }}</td>

                                            <td>{{ $device->imei_no }}</td>
                                            <td>{{ $device->device_modal }}</td>
                                            <td>{{ $device->device_name }}</td>
                                            <td>{{ $device->subscription_expiration . ' Years' }}</td>
                                            <td>{{ date('d/M/Y', strtotime($device->expiration_date)) }}</td>
                                            <td>{!! $device->getStateLabel() !!}</td>
                                            <td class="action-btn">
                                                <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('devices.show', $device->id) }}"><i
                                                        class="fa-solid fa-eye"></i></a>
                                                @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                                    <a href="{{ route('devices.edit', $device->id) }}"
                                                        class="btn primary text-primary btn-sm me-2 rounded-3">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- COL END -->
    </div>

    <div class="row mt-3">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Bookings</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>

                                    <th>Farmer</th>
                                    <th>Tractor</th>
                                    <th>Device</th>
                                    <th>Date</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @if (count($bookings))
                                    @foreach ($bookings as $booking)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $booking->createdBy?->name ? $booking->createdBy?->name : $booking->createdBy?->email }}
                                            <td>{{ $booking->tractor?->id_no . ' (' . $booking->tractor?->model . ')' }}
                                            </td>
                                            <td>{{ $booking->device?->device_name }}
                                            <td>{{ $booking->date }}</td>
                                            <td>{!! $booking->getStateLabel() !!}</td>
                                            <td>{{ $booking->createdBy?->name ?? $booking->createdBy?->email }}</td>
                                            <td class="action-btn">
                                                <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('tractor-bookings.show', $booking->id) }}"><i
                                                        class="fa-solid fa-eye"></i></a>

                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- COL END -->
    </div>

</x-app-layout>

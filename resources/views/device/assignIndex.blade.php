@php
use App\Models\User;
@endphp
<x-app-layout title="{{ __('Assign Devices') }}">
    <div class="row">
        <div class="col-12 col-sm-12">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Assign Devices</h3>
                    </div>
                    {{-- Search By Imei --}}
                    {{-- <form id="searchForm" action="{{ route('devices.assignIndex', ['id' => $userId]) }}" method="get">
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
                                    <th>Imei No</th>
                                    <th>Device Modal</th>
                                    <th>Device Name</th>
                                    {{-- <th>Sales Time</th> --}}
                                    <th>Subscription Expiration</th>
                                    <th>Expiration Date</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($devices))
                                @foreach ($devices as $device)
                                <tr>
                                    <td>{{ ++$i }}</td>

                                    <td>{{ $device->imei_no }}</td>
                                    <td>{{ $device->device_modal }}</td>
                                    <td>{{ $device->device_name }}</td>
                                    {{-- <td>{{ $device->sales_time }}</td> --}}
                                    <td>{{ $device->subscription_expiration ? $device->subscription_expiration . '
                                        Years' : 'N/A' }}</td>
                                    <td>{{ $device->expiration_date ? date('d/M/Y H:i:s',
                                        strtotime($device->expiration_date)):'N/A' }}</td>
                                    <td>{!! $device->getStateLabel() !!}</td>
                                    <td class="action-btn">
                                        @if (in_array($device->id,$assignedDevices))
                                        <a href="{{ route('devices.assignDevice',['id'=>$device->id, 'user_id' => $userId, 'state' => 2]) }}"
                                            class="btn primary text-primary btn-sm me-2 rounded-3">Unassign</a>
                                        @else
                                        <a href="{{ route('devices.assignDevice',['id'=>$device->id, 'user_id' => $userId, 'state' => 1]) }}"
                                            class="btn primary text-primary btn-sm me-2 rounded-3">Assign</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="15" class="text-center">No Devices Found</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $devices->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
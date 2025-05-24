@php
use App\Models\User;
use App\Models\Export;
@endphp
<x-app-layout title="{{ __('Devices') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Devices</h3>
                    </div>
                    {{-- Search By Imei --}}
                    <form id="searchForm" action="{{ route('devices.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="border-bottom">
                        <div class="d-flex justify-content-end gap-3 mb-2 align-items-start">
                            <form action="{{ route('devices.export-device') }}" method="get"
                                class="d-flex align-items-start tractor-multiselect-drop">
                                @csrf
                                <select class="w-100 hidden-select" id="device_ids" name="device_ids[]"
                                    autocomplete="device_ids" multiple>
                                    @foreach ($deviceList as $key => $value)
                                    <option value={{$value->id}}>{{$value->device_name.' ['. $value->imei_no .']'}}
                                    </option>
                                    @endforeach
                                </select>

                                <button class="btn btn-success ms-2" type="submit">Export</button>
                            </form>
                            <a class="btn btn-success float-end d-none" id="download_csv"
                                href="{{ route('devices.download-export-device',['type_id'=>Export::TYPE_DEVICE]) }}">Download</a>
                        </div>
                    </div>
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
                                    <th>Created By</th>
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
                                    <td>{{ $device->createdBy?->name }}</td>
                                    <td class="action-btn">
                                        @if (!in_array(Auth::user()->role_id,[User::ROLE_SUB_ADMIN]))
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('devices.show', $device->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('devices.edit', $device->id) }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                        @else
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('devices.show', $device->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
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
            {!! $devices->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    @push('js')
    <script>
        function checkFile(){
            $.ajax({
                url: '{{ route('devices.check-device-file') }}',
                type: 'GET',
                data : {
                    'type_id': 3
                },
                success: function(response) {
                    if(response.status == 'OK'){
                        $('#download_csv').removeClass('d-none');
                    }else{
                        $('#download_csv').addClass('d-none');
                    }
                }  
            })
        }
        var download =  setInterval(checkFile, 1000);

        $('#device_ids').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Devices',
                search: 'Search'
            }
        });
    </script>
    @endpush
</x-app-layout>
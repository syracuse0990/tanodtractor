@php
use App\Models\Device;
use App\Models\AssignedGroup;
use App\Models\TractorGroup;
use App\Models\User;
use App\Models\Export;

if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
$assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
$groups = TractorGroup::whereIn('id', $assignedGroups)->get();
$deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
$deviceIds = multiDimToSingleDim($deviceIds);
}
$deviceList = Device::query();
if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
$deviceList = $deviceList->whereIn('id', $deviceIds);
}
$deviceList = $deviceList->latest('id')->get();

// dd($isExport);
@endphp
<x-app-layout title="{{ __('Reports') }}">
    <section class="content container-fluid">
        @if ($sMessage = Session::get('success'))
        <div class="alert alert-success auto-close">
            <p>{{ $sMessage }}</p>
        </div>
        @endif
        @if ($eMessage = Session::get('error'))
        <div class="alert alert-danger auto-close">
            <p>{{ $eMessage }}</p>
        </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card card-default">
                    <div class="card-body">
                        <form action="{{ route('reports.index') }}" method="get">
                            <div class="default-form">
                                <div class="row">
                                    {{-- <div class="col-md-6 mb-3">
                                        <div class="form-group tractor-multiselect-drop">
                                            <label>Devices</label>
                                            <select class="w-100 hidden-select" id="device_ids" name="dev[]"
                                                autocomplete="dev" multiple>
                                                @foreach ($deviceList as $key => $value)
                                                <option value={{$value->id}}>{{$value->device_name.' ['.
                                                    $value->imei_no
                                                    .']'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div> --}}
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group custom-select-wrapper">
                                            <label>Device</label>
                                            <select class="form-select" id="device" name="device" autocomplete="device">
                                                <option></option>
                                                @foreach ($deviceList as $key => $value)
                                                <option value={{$value->id}} {{$value->id == request()->device ?
                                                    'selected' : ''}}>{{$value->device_name.' ['. $value->imei_no .']'}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-group custom-select-wrapper">
                                            <label>Period</label>
                                            <select class="form-select" id="period" name="period" autocomplete="period">
                                                <option></option>
                                                <option {{ 1==request()->period ? 'selected' : '' }} value="1">Today
                                                </option>
                                                <option {{ 2==request()->period ? 'selected' : '' }} value="2">This
                                                    Week
                                                </option>
                                                <option {{ 3==request()->period ? 'selected' : '' }} value="3">This
                                                    Month
                                                </option>
                                                <option {{ 4==request()->period ? 'selected' : '' }}
                                                    value="4">Custom
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-group custom-select-wrapper">
                                            <label>Date Range</label>
                                            <input type="text" id="date_range" name="date_range" class="form-control"
                                                value="{{ request()->date_range }}" placeholder="{{ __('Date Range') }}"
                                                autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class=" mt-3">
                                            <a href="{{route('reports.index')}}"
                                                class="btn btn-secondary text-white rounded-pill px-3">{{__('Reset')}}</a>
                                            <button type="submit"
                                                class="btn btn-primary btn-icon text-white rounded-pill px-3">{{__('Submit')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title mb-0 fw-500 me-3">Reports and analytics</h3>
                        </div>
                        <div class="d-flex justify-content-end gap-3 align-items-start">
                            <form action="{{ route('reports.index') }}" method="get" id="exportForm">
                                <input type="hidden" name="device" value="{{request()->device}}">
                                <input type="hidden" name="period" value="{{request()->period}}">
                                <input type="hidden" name="pdf" id="pdfInput" value="">
                                <input type="hidden" name="csv" id="csvInput" value="">
                                @if (request()->period == 4)
                                <input type="hidden" name="date_range" value="{{ request()->date_range }}">
                                @endif
                                @if (request()->page)
                                <input type="hidden" name="page" value="{{ request()->page }}">
                                @endif
                                <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                    <div class="btn-group" role="group">
                                        <button id="btnGroupDrop1" type="button" class="btn btn-success dropdown-toggle"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            Export
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('pdfInput').value = 1; document.getElementById('csvInput').value = 0; document.getElementById('exportForm').submit();">PDF</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="document.getElementById('csvInput').value = 1; document.getElementById('pdfInput').value = 0; document.getElementById('exportForm').submit();">CSV</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                            <a class="btn btn-success float-end d-none" id="download_pdf" href="{{ route('reports.download',['type_id'=>Export::TYPE_REPORT_PDF]) }}"><i class="fa-solid fa-download"></i>  PDF</a>
                            <a class="btn btn-success float-end d-none" id="download_csv" href="{{ route('reports.download',['type_id'=>Export::TYPE_REPORT_CSV]) }}"><i class="fa-solid fa-download"></i> CSV</a>

                        </div>
                    </div>
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        <th>Position Time</th>
                                        <th>Speed</th>
                                        <th>Azimuth</th>
                                        <th>Position type</th>
                                        <th>No. of satellites</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($paginatedDeviceData))
                                    @foreach ($paginatedDeviceData as $key => $deviceData)
                                    @php
                                    $positionType = 'N/A';
                                    if($deviceData['posType'] == 1){
                                    $positionType = 'GPS';
                                    }elseif($deviceData['posType'] == 2){
                                    $positionType = 'LBS';
                                    }elseif ($deviceData['posType'] == 3) {
                                    $positionType = 'WIFI';
                                    }
                                    @endphp
                                    <tr>
                                        <td>{{ $key+1 }}</td>

                                        <td>{{ gmdate('Y-m-d H:i:s', strtotime($deviceData['gpsTime'])) }}</td>
                                        <td>{{ $deviceData['gpsSpeed'] }}</td>
                                        <td>{{$deviceData['direction']}}</td>
                                        <td>{{ $positionType }}</td>
                                        <td>{{ $deviceData['satellite']}}</td>
                                        <td>{{ $deviceData['lat'] }}</td>
                                        <td>{{ $deviceData['lng'] }}</td>
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
                {!!
                $paginatedDeviceData->withPath(url()->current())->appends(request()->except('page'))->links('custom-pagination')
                !!}
            </div> <!-- COL END -->
        </div>
    </section>
    @push('js')
    <script>
        $('#device_ids').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Devices',
                search: 'Search'
            }
        });

        $('#device').select2({
            placeholder: 'Select Device',
            allowClear: true
        });

        $('#period').select2({
            placeholder: 'Select Period',
            allowClear: true
        });

        $('#period').on('change', function() {
            let value = $(this).val();
            if (value != 4) {
                $('input[name="date_range"]').val('');
                $('input[name="date_range"]').prop('disabled', true);
            } else {
                $('input[name="date_range"]').prop('disabled', false);
            }
        });

        let value = $('#period').val();
        if (value != 4) {
            $('input[name="date_range"]').val('');
            $('input[name="date_range"]').prop('disabled', true);
        } else {
            $('input[name="date_range"]').prop('disabled', false);
        }

        $('input[name="date_range"]').daterangepicker({
            autoUpdateInput: false,
            maxDate: "{{ date('Y/m/d') }}",
            locale: {
                format: 'YYYY/MM/DD',
                cancelLabel: 'Clear'
            }
        });

        $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format(
                'YYYY/MM/DD'));
        });

        $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        function checkPdf(){
            $.ajax({
                url: '{{ route('reports.check-file') }}',
                type: 'GET',
                data : {
                    'type_id': 6
                },
                success: function(response) {
                    if(response.status == 'OK'){
                        $('#download_pdf').removeClass('d-none');
                    }else if(response.status == 'NF'){
                        location.reload();
                    }else{
                        $('#download_pdf').addClass('d-none');
                    }
                }  
            })
        }
        function checkCsv(){
            $.ajax({
                url: '{{ route('reports.check-file') }}',
                type: 'GET',
                data : {
                    'type_id': 10
                },
                success: function(response) {
                    console.log('response :>> ', response);
                    if(response.status == 'OK'){
                        $('#download_csv').removeClass('d-none');
                    }else if(response.status == 'NF'){
                        location.reload();
                    }else{
                        $('#download_csv').addClass('d-none');
                    }
                }  
            })
        }
        var pdfExport = '{{isset($pdfExport) ? '1' : '0'}}';
        if(pdfExport == 1){
        var download =  setInterval(checkPdf, 1000);
        }
        var csvExport = '{{isset($csvExport) ? '1' : '0'}}';
        if(csvExport == 1){
        var download =  setInterval(checkCsv, 1000);
        }
    </script>
    @endpush
</x-app-layout>
@php
use App\Models\User;
use App\Models\Export;
use App\Models\Jimi;
use App\Helpers\CommonHelper;
@endphp
<x-app-layout title="{{ __('Overviews') }}">

    {{-- <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex gap-3 flex-wrap">
                <div class="percentage-card">
                    <div class="percent">
                        <svg>
                            <circle cx="105" cy="105" r="100"></circle>
                            <circle cx="105" cy="105" r="100" style="--percent: {{$data['total_device_percantage']}}">
                            </circle>
                        </svg>
                        <div class="number">
                            <h3>{{$data['total_device_percantage']}}<span>%</span></h3>
                        </div>
                    </div>
                    <div class="title">
                        <h5>{{$data['total_devices']}} Total</h5>
                    </div>
                </div>
                <div class="percentage-card">
                    <div class="percent">
                        <svg>
                            <circle cx="105" cy="105" r="100"></circle>
                            <circle cx="105" cy="105" r="100" style="--percent: {{$data['trips_percantage']}}"></circle>
                        </svg>
                        <div class="number">
                            <h3>{{$data['trips_percantage']}}<span>%</span></h3>
                        </div>
                    </div>
                    <div class="title">
                        <h5>{{$data['trips']}} Trips</h5>
                    </div>
                </div>
                <div class="percentage-card">
                    <div class="percent">
                        <svg>
                            <circle cx="105" cy="105" r="100"></circle>
                            <circle cx="105" cy="105" r="100" style="--percent: {{$data['kilometer_percantage']}}">
                            </circle>
                        </svg>
                        <div class="number">
                            <h3>{{$data['kilometer_percantage']}}<span>%</span></h3>
                        </div>
                    </div>
                    <div class="title">
                        <h5>{{$data['total_kilometers']}} Total Mileage(km)</h5>
                    </div>
                </div>
                <div class="percentage-card">
                    <div class="percent">
                        <svg>
                            <circle cx="105" cy="105" r="100"></circle>
                            <circle cx="105" cy="105" r="100" style="--percent: 40"></circle>
                        </svg>
                        <div class="number">
                            <h3>70<span>%</span></h3>
                        </div>
                    </div>
                    <div class="title">
                        <h2>CSS</h2>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
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
                        <h3 class="card-title mb-0 fw-500 me-3">Overviews</h3>
                    </div>
                    {{-- Search By Imei --}}
                    <form id="searchForm" action="{{ route('devices.overview') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="IMEI" onchange="javascript:this.form.submit();"
                                value="{{ isset(request()->search) ? request()->search : null }}">
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-end gap-3 mb-4 align-items-start">
                        <a href="{{ route('devices.exportOverview') }}" class="btn btn-success ms-2"
                            data-placement="left" id="export_btn">
                            {{ __('Export') }}
                        </a>
                        <a class="btn btn-success float-end d-none" id="download_csv"
                            href="{{ route('devices.download-export-device',['type_id'=>Export::TYPE_OVERVIEW]) }}">Download</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Device Name</th>
                                    <th>IMEI</th>
                                    <th>Model</th>
                                    <th>Driver Name</th>
                                    <th>Number Plate</th>
                                    <th>Sim</th>
                                    <th>Phone</th>
                                    <x-table.th sortable
                                        direction="{{ CommonHelper::getDirection(request()->all(), 'booking_date') }}">
                                        <a style="color: #495584;"
                                            href="{{ route('devices.overview', CommonHelper::getsortParams(request()->all(), 'booking_date')) }}">
                                            Booking Date
                                        </a>
                                    </x-table.th>
                                    <x-table.th sortable
                                        direction="{{ CommonHelper::getDirection(request()->all(), 'km') }}">
                                        <a style="color: #495584;"
                                            href="{{ route('devices.overview', CommonHelper::getsortParams(request()->all(), 'km')) }}">
                                            Today's Kilometer
                                        </a>
                                    </x-table.th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($bookings))
                                @foreach ($bookings as $booking)
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $booking->device?->device_name ?? 'N/A'}}</td>
                                    <td>{{ $booking->device?->imei_no ?? 'N/A'}}</td>
                                    <td>{{ $booking->device?->device_modal ?? 'N/A'}}</td>
                                    <td>{{ $booking->createdBy ? $booking->createdBy->name ?? $booking->createdBy->email
                                        : 'N/A' }}</td>
                                    <td>{{ $booking->tractor?->no_plate ?? 'N/A' }}</td>
                                    <td>{{ $booking->device?->sim ?? 'N/A' }}</td>
                                    <td>{{ $booking->createdBy?->phone ?? 'N/A' }}</td>
                                    <td>{{ $booking->date ?? 'N/A' }}</td>
                                    <td>{{$booking->kilometer}}</td>
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
            {!! $bookings->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    @push('js')
    <script>
        function checkFile(){
            $.ajax({
                url: '{{ route('devices.check-device-file') }}',
                type: 'GET',
                data : {
                    'type_id': 4
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

        $('#export_btn').on('click', function(e) {
                e.preventDefault();
                let formData = $('#searchForm').serialize();
                $.ajax({
                    type: 'GET',
                    url: '{{ route('devices.exportOverview') }}',
                    data: formData,
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(response, status, xhr) {
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        // Handle errors here
                        console.error(error);
                    }
                });
            });
    </script>
    @endpush
</x-app-layout>
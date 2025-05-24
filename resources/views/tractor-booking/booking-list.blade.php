@php
use App\Models\DeviceGeoFence;
@endphp
<x-app-layout title="{{ __('Tractor Bookings') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Bookings</h3>
                    </div>
                    <a href="{{ route('tractor-bookings.index') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        Calendar View</a>
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
                        <div>
                            <div class="form-group mb-3">
                                <select
                                    class="tractor_select form-control{{ $errors->has('tractor_id') ? ' is-invalid' : '' }}"
                                    name="tractor_id" id="tractor_select2">
                                    <option value="{{ route('tractor-bookings.booking-list') }}" selected="selected">
                                        Select an tractor</option>
                                    @foreach ($tractors as $tractor)
                                    @php
                                    $tractorName = $tractor?->id_no ?? null;
                                    if($tractorName && $tractor?->model){
                                    $tractorName = $tractor?->id_no . ' (' . $tractor?->model . ')';
                                    }
                                    @endphp
                                    <option
                                        value="{{ route('tractor-bookings.booking-list', ['tractor_id' => $tractor->id]) }}"
                                        {{ !empty($tractor_id) && $tractor_id==$tractor->id ? 'selected' : '' }}>
                                        {{ $tractorName }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('tractor_id', '<div class="invalid-feedback">:message</div>') !!}
                            </div>
                        </div>
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
                                @if (count($bookings))
                                @foreach ($bookings as $booking)
                                @php
                                $tractorName = $booking->tractor ? $booking->tractor?->id_no : null;
                                if($tractorName && $booking->tractor?->model){
                                $tractorName = $booking->tractor?->id_no . ' (' . $booking->tractor?->model . ')';
                                }
                                @endphp
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $booking->createdBy?->name ? $booking->createdBy?->name :
                                        $booking->createdBy?->email }}
                                    <td>
                                        @if ($booking->tractor)
                                        <a href="{{route('tractors.show',$booking->tractor?->id)}}" class="text-dark">
                                            {{$tractorName ?? $booking->tractor?->no_plate }}
                                        </a>
                                        @else
                                        N/A
                                        @endif

                                    </td>
                                    <td>{{ $booking->device?->device_name .'['.$booking->device?->imei_no.']' }}</td>
                                    <td>{{ $booking->date }}</td>
                                    <td>{!! $booking->getStateLabel() !!}</td>
                                    <td>{{ $booking->createdBy?->name ?? $booking->createdBy?->email }}</td>
                                    <td class="action-btn">
                                        <a class="btn primary text-success btn-sm me-2 rounded-3"
                                            href="{{ route('tractor-bookings.show', $booking->id) }}"><i
                                                class="fa-solid fa-eye"></i></a>
                                        {{-- @php
                                        $deviceGeoFence = DeviceGeoFence::where('imei',
                                        $booking->device?->imei_no)->where('state_id',
                                        DeviceGeoFence::STATE_ACTIVE)->first();
                                        @endphp
                                        @if($deviceGeoFence)
                                        <a class="btn primary text-success btn-sm me-2 rounded-3"
                                            href="{{ route('device-geo-fences.edit',$booking->device?->id) }}"><i
                                                class="fa-solid fa-location-crosshairs"></i> Geo Fence</a>
                                        @else
                                        <a class="btn primary text-success btn-sm me-2 rounded-3"
                                            href="{{ route('device-geo-fences.create', ['imei'=> $booking->device?->imei_no]) }}"><i
                                                class="fa-solid fa-location-crosshairs"></i> Geo Fence</a>
                                        @endif --}}

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
            {!! $bookings->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    @push('js')
    <script>
        $('#tractor_select2').change(function() {
                var url = $(this).val();
                if (url) {
                    window.location = url;
                }
                return false;
            });
    </script>
    @endpush
</x-app-layout>
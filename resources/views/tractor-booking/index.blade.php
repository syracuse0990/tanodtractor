@php
    use App\Models\Slot;
    use App\Models\TractorBooking;
@endphp
<x-app-layout title="{{ __('Tractor Bookings') }}">
    <div class="row">
        <div class="col-12 col-sm-12">
            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Bookings</h3>
                    </div>
                    <a href="{{ route('tractor-bookings.booking-list') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        List View</a>
                    {{-- <form id="searchForm" action="{{ route('tractor-groups.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name ="search"
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
                                    <option value="{{ route('tractor-bookings.index') }}" selected="selected">Select an
                                        tractor</option>
                                    @foreach ($tractors as $tractor)
                                        <option
                                            value="{{ route('tractor-bookings.index', ['tractor_id' => $tractor->id]) }}"
                                            {{ !empty($tractor_id) && $tractor_id == $tractor->id ? 'selected' : '' }}>
                                            {{ $tractor->id_no . ' (' . $tractor->model . ')' }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('tractor_id', '<div class="invalid-feedback">:message</div>') !!}
                            </div>
                        </div>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

        </div> <!-- COL END -->
    </div>
    @push('js')
        <script>
            $(document).ready(function() {
                // page is now ready, initialize the calendar...
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    themeSystem: 'bootstrap5',
                    // put your options and callbacks here
                    eventOrder: 'id',
                    events: [
                        @foreach ($bookingsData as $bookingData)
                            {
                                id: {{ $bookingData->state_id == TractorBooking::STATE_ACCEPTED ? '1' : '2' }},
                                title: "{{ $bookingData->createdBy?->name ? $bookingData->createdBy?->name : $bookingData->createdBy?->email }}",
                                start: "{{ date('Y-m-d', strtotime($bookingData->date)) }}",
                                url: "{{ route('tractor-bookings.show', $bookingData->id) }}",
                                color: "{{ $bookingData->state_id == TractorBooking::STATE_ACCEPTED ? 'green' : ($bookingData->state_id == TractorBooking::STATE_ACTIVE ? 'verdigris' : 'red') }}"
                            },
                        @endforeach

                    ]
                });
                calendar.render();
            });
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

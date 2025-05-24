@php
use App\Models\Alert;
@endphp
<x-app-layout title="{{ __('Alerts') }}">

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
                        <h3 class="card-title mb-0 fw-500 me-3">Alerts</h3>
                    </div>
                    <div class="search-filter-box w-25">
                        <select class="alert_type form-control" name="alarm_type" id="alert_select2">
                            <option value="{{ route('alerts.index') }}" selected="selected">All</option>
                            @foreach (Alert::alertOptions() as $key => $alert)
                            <option value="{{ route('alerts.index', ['alarm_type' => $key]) }}" {{ $key==request()->alarm_type ? 'selected': ''}}>
                                {{ $alert }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Time</th>
                                    <th>IMEI</th>
                                    <th>Alarm Type</th>
                                    <th>Alarm Name</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alerts as $alert)

                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ date('d-M-Y h:iA', strtotime($alert->alarm_time)) }}</td>
                                    <td>{{ $alert->imei }}</td>
                                    <td>{{ $alert->alarm_type }}</td>
                                    <td>{{ $alert->alarm_name }}</td>
                                    <!--     <td>
                                             <form action="{{ route('alerts.destroy', $alert->id) }}"
                                                    method="POST">
                                                    <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                        href="{{ route('alerts.show', $alert->id) }}"><i
                                                            class="fa-solid fa-eye"></i></a>
                                               
                                                </form>
                                     </td>  -->
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
    @push('js')
    <script>
        $('#alert_select2').change(function() {
            var url = $(this).val();
            if (url) {
                window.location = url;
            }
            return false;
        });
    </script>
    @endpush
</x-app-layout>
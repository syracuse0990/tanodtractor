<x-app-layout title="{{ __('Assign Tractors') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Assign Tractors</h3>
                    </div>
                    {{-- <form id="searchForm" action="{{ route('tractors.assignIndex', ['id' => $userId]) }}" method="get">
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
                                        @if (in_array($tractor->id,$assignedTractors))
                                        <a href="{{ route('tractors.assignTractor',['id'=>$tractor->id, 'user_id' => $userId, 'state' => 2]) }}"
                                            class="btn primary text-primary btn-sm me-2 rounded-3">Unassign</a>
                                        @else
                                        <a href="{{ route('tractors.assignTractor',['id'=>$tractor->id, 'user_id' => $userId, 'state' => 1]) }}"
                                            class="btn primary text-primary btn-sm me-2 rounded-3">Assign</a>
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
            {!! $tractors->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
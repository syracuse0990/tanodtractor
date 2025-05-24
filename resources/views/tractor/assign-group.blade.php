<x-app-layout title="{{ __('Tractor Groups') }}">
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
                        <h3 class="card-title mb-0 fw-500">Tractor Groups</h3>
                    </div>
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
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Assign</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($tractorGroups))
                                    @foreach ($tractorGroups as $tractorGroup)
                                        <tr>
                                            <td>{{ ++$i }}</td>

                                            <td>{{ $tractorGroup->name }}</td>
                                            <td>{!! $tractorGroup->getStateLabel() !!}</td>
                                            <td>{{ $tractorGroup->createdBy?->name }}</td>

                                            <td class="action-btn">
                                                <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('tractors.reassign', ['id' => $tractor_id, 'group_id' => $tractorGroup->id]) }}"><i
                                                        class="fa-solid fa-shuffle"></i> Assign</a>
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
            {!! $tractorGroups->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>

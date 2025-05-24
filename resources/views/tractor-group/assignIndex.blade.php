<x-app-layout title="{{ __('Assign Groups') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Assign Groups</h3>
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
                                    <th>Name</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($groups))
                                    @foreach ($groups as $group)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>
                                                <a href="{{ route('tractor-groups.show',$group->id) }}" class="text-dark"> {{ $group->name }}</a>
                                            </td>
                                            <td>{!! $group->getStateLabel() !!}</td>
                                            <td>{{ $group->createdBy?->name }}</td>
                                            <td class="action-btn">
                                                @if (in_array($group->id,$assignedGroups))
                                                <a href="{{ route('tractor-groups.assignGroup',['id'=>$group->id, 'user_id' => $userId, 'state' => 2]) }}"
                                                    class="btn primary text-primary btn-sm me-2 rounded-3">Unassign</a>
                                                @else
                                                <a href="{{ route('tractor-groups.assignGroup',['id'=>$group->id, 'user_id' => $userId, 'state' => 1]) }}"
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
            {!! $groups->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
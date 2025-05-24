<x-app-layout title="{{ __('Geo Fences') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Geo Fences</h3>
                        <a href="{{ route('device-geo-fences.create') }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-plus me-1"></i>Add</a>
                    </div>
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
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>

                                    <th>Imei</th>
                                    <th>Fence Name</th>
                                    <th>Geo Fence Id</th>
                                    <th>Date</th>
                                    <th>State Id</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($deviceGeoFences))
                                @foreach ($deviceGeoFences as $deviceGeoFence)
                                <tr>
                                    <td>{{ ++$i }}</td>

                                    <td>{{ $deviceGeoFence->imei }}</td>
                                    <td>{{ $deviceGeoFence->fence_name }}</td>
                                    <td>{{ $deviceGeoFence->geo_fence_id }}</td>
                                    <td>{{ $deviceGeoFence->date }}</td>
                                    <td>{!! $deviceGeoFence->getStateLabel() !!}</td>
                                    <td>{{ $deviceGeoFence->createdBy->name }}</td>
                                    <td class="action-btn">
                                        <form action="{{ route('device-geo-fences.destroy', $deviceGeoFence->id) }}"
                                            method="POST">
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('device-geo-fences.show', $deviceGeoFence->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            {{-- @if ($deviceGeoFence->date > date('Y-m-d')) --}}
                                            @if ($deviceGeoFence->state_id != 2)
                                            <a href="{{ route('device-geo-fences.edit', $deviceGeoFence->id) }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-pen"></i>
                                                {{-- @endif --}}
                                            </a>
                                            @endif
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn danger text-danger btn-sm rounded-3"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </form>
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
            {!! $deviceGeoFences->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
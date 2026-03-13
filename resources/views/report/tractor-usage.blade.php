<x-app-layout title="{{ __('Tractor Usage Report') }}">
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

        {{-- Page Header --}}
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end mb-4">
            <div>
                <a href="{{ route('reports.index') }}" class="text-decoration-none text-primary small">
                    <i class="fas fa-arrow-left me-1"></i> Back to Reports
                </a>
                <h3 class="mt-2 mb-1 fw-bold">Tractor Usage Report</h3>
                <p class="text-muted small mb-0">Fleet distance, running hours, and utilization summary</p>
            </div>
            <a href="{{ route('reports.exportTractorUsage', request()->query()) }}" class="btn btn-success btn-sm mt-2 mt-sm-0">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            {{-- Total Tractors --}}
            <div class="col-sm-6 col-lg">
                <div class="card rounded-4 border h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#eff6ff;">
                                <svg width="24" height="24" fill="none" stroke="#2563eb" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">Total Tractors</p>
                                <h4 class="fw-bold mb-0">{{ number_format($summary['total_tractors']) }}</h4>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2" style="font-size:12px;">
                            <span class="d-inline-flex align-items-center gap-1 text-success"><span class="rounded-circle d-inline-block" style="width:6px;height:6px;background:#22c55e;"></span>{{ $summary['online'] }} online</span>
                            <span class="d-inline-flex align-items-center gap-1 text-danger"><span class="rounded-circle d-inline-block" style="width:6px;height:6px;background:#ef4444;"></span>{{ $summary['offline'] }} offline</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Distance --}}
            <div class="col-sm-6 col-lg">
                <div class="card rounded-4 border h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#ecfdf5;">
                                <svg width="24" height="24" fill="none" stroke="#059669" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">Total Distance</p>
                                <h4 class="fw-bold mb-0">{{ number_format($summary['total_distance'], 2) }} <small class="fw-normal text-muted">km</small></h4>
                            </div>
                        </div>
                        <div class="mt-3 text-muted" style="font-size:12px;">Avg {{ number_format($summary['avg_distance'], 2) }} km / tractor</div>
                    </div>
                </div>
            </div>

            {{-- Running Hours --}}
            <div class="col-sm-6 col-lg">
                <div class="card rounded-4 border h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#fffbeb;">
                                <svg width="24" height="24" fill="none" stroke="#d97706" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">Running Hours</p>
                                <h4 class="fw-bold mb-0">{{ number_format($summary['total_hours'], 2) }} <small class="fw-normal text-muted">hrs</small></h4>
                            </div>
                        </div>
                        <div class="mt-3 text-muted" style="font-size:12px;">Avg {{ $summary['total_tractors'] > 0 ? number_format($summary['total_hours'] / $summary['total_tractors'], 1) : 0 }} hrs / tractor</div>
                    </div>
                </div>
            </div>

            {{-- With Usage Data --}}
            <div class="col-sm-6 col-lg">
                <div class="card rounded-4 border h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#faf5ff;">
                                <svg width="24" height="24" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">With Usage Data</p>
                                <h4 class="fw-bold mb-0">{{ $summary['with_data'] }}</h4>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar" style="width:{{ $summary['data_percent'] }}%;background:#8b5cf6;"></div>
                            </div>
                            <p class="text-muted mt-1 mb-0" style="font-size:12px;">{{ $summary['data_percent'] }}% of fleet reporting</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PMS Due --}}
            <div class="col-sm-6 col-lg">
                <div class="card rounded-4 border h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#fff7ed;">
                                <svg width="24" height="24" fill="none" stroke="#ea580c" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">PMS Due</p>
                                <h4 class="fw-bold mb-0 {{ $summary['pms_due'] > 0 ? 'text-danger' : '' }}">{{ $summary['pms_due'] }}</h4>
                            </div>
                        </div>
                        <div class="mt-3 text-muted" style="font-size:12px;">{{ $summary['total_maintenances'] }} total maintenance records</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Disclaimer --}}
        <p class="text-muted small fst-italic mb-3">* Some total hours are computed from total distance using statistical correlation.</p>

        {{-- Filters --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('reports.tractorUsage') }}" class="row g-2 align-items-end">
                    <div class="col-md">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Plate, brand, model, IMEI..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Group</label>
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">All Groups</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">PMS</label>
                        <select name="pms" class="form-select form-select-sm">
                            <option value="">All PMS</option>
                            <option value="due" {{ request('pms') === 'due' ? 'selected' : '' }}>Due Now</option>
                            <option value="ok" {{ request('pms') === 'ok' ? 'selected' : '' }}>OK</option>
                            <option value="nodata" {{ request('pms') === 'nodata' ? 'selected' : '' }}>No Data</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filter</button>
                        <a href="{{ route('reports.tractorUsage') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results Count & Sort --}}
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <p class="text-muted small mb-0">
                Showing <strong>{{ $tractors->firstItem() ?? 0 }}–{{ $tractors->lastItem() ?? 0 }}</strong> of <strong>{{ $tractors->total() }}</strong> tractors
            </p>
            <div class="small text-muted">
                Sort:
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'no_plate', 'dir' => (request('sort') === 'no_plate' && request('dir') === 'asc') ? 'desc' : 'asc']) }}"
                   class="{{ request('sort') === 'no_plate' ? 'fw-bold text-primary' : '' }} text-decoration-none">Name</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_distance', 'dir' => (request('sort') === 'total_distance' && request('dir') === 'desc') ? 'asc' : 'desc']) }}"
                   class="{{ request('sort') === 'total_distance' ? 'fw-bold text-primary' : '' }} text-decoration-none ms-2">Distance</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'running_km', 'dir' => (request('sort') === 'running_km' && request('dir') === 'desc') ? 'asc' : 'desc']) }}"
                   class="{{ request('sort') === 'running_km' ? 'fw-bold text-primary' : '' }} text-decoration-none ms-2">Hours</a>
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width:50px;">#</th>
                            <th>Tractor</th>
                            <th>Group</th>
                            <th>IMEI</th>
                            <th class="text-end">Distance (km)</th>
                            <th class="text-end">Hours</th>
                            <th class="text-center">Last PMS</th>
                            <th class="text-center">PMS Status</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tractors as $idx => $t)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $tractors->firstItem() + $idx }}</td>
                            <td>
                                @if($t['id'])
                                <a href="{{ route('tractors.show', $t['id']) }}" class="fw-semibold text-dark text-decoration-none">
                                    {{ $t['no_plate'] }}
                                </a>
                                @else
                                <span class="fw-semibold text-dark">{{ $t['no_plate'] }}</span>
                                @endif
                                <div class="text-muted small">{{ $t['brand'] }} {{ $t['model'] }}</div>
                            </td>
                            <td>
                                @if($t['group_name'])
                                    <span class="badge bg-light text-dark">{{ $t['group_name'] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><code class="text-muted small">{{ $t['imei'] ?: '—' }}</code></td>
                            <td class="text-end fw-semibold">{{ number_format($t['total_distance'], 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($t['running_hours'], 2) }}</td>
                            <td class="text-center small text-muted">{{ $t['last_pms_date'] ?: 'Never' }}</td>
                            <td class="text-center">
                                @if($t['pms_status'] === 'Due')
                                    <span class="badge bg-danger">{{ $t['pms_count'] > 0 ? $t['pms_count'] . 'x · ' : '' }}Due Now</span>
                                @elseif($t['pms_status'] === 'No Data')
                                    <span class="text-muted small">—</span>
                                @else
                                    <span class="badge bg-success bg-opacity-75">{{ $t['pms_count'] > 0 ? $t['pms_count'] . 'x · ' : '' }}{{ $t['pms_status'] }}</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                @if($t['status'] === 'online')
                                    <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size:6px;vertical-align:middle;"></i>Online</span>
                                @elseif($t['status'] === 'offline')
                                    <span class="badge bg-secondary"><i class="fas fa-circle me-1" style="font-size:6px;vertical-align:middle;"></i>Offline</span>
                                @else
                                    <span class="badge bg-light text-muted"><i class="fas fa-circle me-1" style="font-size:6px;vertical-align:middle;"></i>Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-chart-bar text-muted fs-1 mb-2 d-block"></i>
                                <p class="text-muted mb-0">No tractors match your filters.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($tractors->total() > 0)
                    <tfoot class="table-light">
                        <tr class="fw-semibold">
                            <td class="ps-4" colspan="4">Overall Totals ({{ $summary['total_tractors'] }} tractors)</td>
                            <td class="text-end">{{ number_format($summary['total_distance'], 2) }} km</td>
                            <td class="text-end">{{ number_format($summary['total_hours'], 2) }} hrs</td>
                            <td class="text-center text-danger">{{ $summary['pms_due'] }} due</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Disclaimer --}}
        <p class="text-muted small fst-italic text-center mt-3">* Some total hours are computed from total distance using statistical correlation.</p>

        {{-- Pagination --}}
        @if($tractors->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-3">
            <p class="text-muted small mb-0">Page {{ $tractors->currentPage() }} of {{ $tractors->lastPage() }}</p>
            {{ $tractors->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif

    </section>
</x-app-layout>

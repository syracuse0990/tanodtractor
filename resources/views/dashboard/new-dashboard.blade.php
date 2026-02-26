@php
    use App\Models\AssignedGroup;
    use App\Models\Device;
    use App\Models\FarmerFeedback;
    use App\Models\Maintenance;
    use App\Models\Tractor;
    use App\Models\TractorBooking;
    use App\Models\TractorGroup;
    use App\Models\User;
    use Illuminate\Support\Facades\Auth;

    $userId = Auth::id();
    $roleId = Auth::user()->role_id;

    $groupsQuery = TractorGroup::query();
    if ($roleId == User::ROLE_SUB_ADMIN) {
        $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id');
        $groupsQuery->whereIn('id', $assignedGroups);
    }

    $groups = $groupsQuery->latest('id')->get();
    $groupNameArray = $groupsQuery->pluck('name', 'id')->toArray();
    $state = Device::ALL_DEVICES;
    $selectedGroupId = request()->filled('group_id') ? (int) request('group_id') : null;

    $allDevices = Device::whereNotNull('activation_time');
    if ($roleId == User::ROLE_SUB_ADMIN) {
        $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
            ->pluck('device_ids')
            ->flatten()
            ->toArray();
        $deviceIds = array_unique($deviceIds);
        $allDevices->whereIn('id', $deviceIds);
    }
    $allDevices = $allDevices->latest('id')->get();

    $totalTractorsQuery = Tractor::query();
    if ($roleId == User::ROLE_SUB_ADMIN) {
        $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
        $totalTractorsQuery->whereIn('group_id', $assignedGroups);
    }
    if (!empty($selectedGroupId)) {
        $totalTractorsQuery->where('group_id', $selectedGroupId);
    }
    $totalTractors = $totalTractorsQuery->count();
    $bookedTractors = TractorBooking::where('state_id', TractorBooking::STATE_ACTIVE)->count();
    $pmsTractors = Maintenance::whereIn('state_id', [
        Maintenance::STATE_DOCUMENTATION,
        Maintenance::STATE_FILLED,
        Maintenance::STATE_INPROGRESS,
    ])->count();
    $groupsCount = TractorGroup::count();
    $feedbackCount = FarmerFeedback::where('state_id', FarmerFeedback::STATE_ACTIVE)->count();
@endphp

<x-app-layout>
    <style>
        .new-dashboard .dashboard-card {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 3px 9px 0 rgb(123 136 150 / 12%);
        }
        .new-dashboard .stat-card {
            border-radius: 10px;
            color: #fff;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            min-height: 82px;
        }
        .new-dashboard .stat-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: url("{{ asset('assets/img/circle.svg') }}") no-repeat right center / auto 100%;
            opacity: 0.42;
            pointer-events: none;
        }
        .new-dashboard .stat-value {
            position: relative;
            z-index: 1;
            font-size: 27px;
            line-height: 1;
            font-weight: 700;
        }
        .new-dashboard .stat-label {
            position: relative;
            z-index: 1;
            margin-top: 4px;
            font-size: 13px;
            opacity: 0.95;
        }
        .new-dashboard .bg-total { background: linear-gradient(135deg, #2f3237, #4a4f56); }
        .new-dashboard .bg-online { background: linear-gradient(135deg, #0ba360, #3cba92); }
        .new-dashboard .bg-offline { background: linear-gradient(135deg, #cb2d3e, #ef473a); }
        .new-dashboard .bg-inactive { background: linear-gradient(135deg, #5b5b5b, #7a7a7a); }
        .new-dashboard .bg-booked { background: linear-gradient(135deg, #1d7ff3, #2e9cff); }
        .new-dashboard .bg-pms { background: linear-gradient(135deg, #ff7f2a, #f25f2f); }
        .new-dashboard .bg-groups { background: linear-gradient(135deg, #7b2ff7, #9d50ff); }
        .new-dashboard .bg-feedbacks { background: linear-gradient(135deg, #00a1d9, #39b5ff); }
        .new-dashboard .section-title {
            background: #007f3d;
            color: #fff;
            border-radius: 6px 6px 0 0;
            padding: 10px 14px;
            font-size: 22px;
            font-weight: 600;
            line-height: 1.2;
        }
        .new-dashboard .chart-wrap {
            border: 1px solid #d9dde4;
            border-top: 0;
            border-radius: 0 0 8px 8px;
            background: #fff;
            padding: 18px 16px 12px;
        }
        .new-dashboard .alerts-chart-area {
            position: relative;
            min-height: 300px;
        }
        .new-dashboard #mainDiv .card {
            border-radius: 8px;
        }
        .new-dashboard #mainDiv .card-body {
            padding: 12px;
        }
        .new-dashboard #mainDiv .region-sidebar .nav-tabs {
            margin-bottom: 8px;
        }
        .new-dashboard #mainDiv .region-sidebar .nav-tabs .nav-link {
            font-size: 12px;
            padding: 6px 8px;
        }
        .new-dashboard #mainDiv #search-box,
        .new-dashboard #mainDiv #selectDevice,
        .new-dashboard #mainDiv #period,
        .new-dashboard #mainDiv #date_range {
            min-height: 34px;
            font-size: 12px;
        }
        .new-dashboard #mainDiv .state-tabs .nav-link {
            font-size: 11px;
            padding: 4px 6px;
        }
        .new-dashboard #mainDiv .state-tabs .text-dark {
            font-size: 11px;
        }
        .new-dashboard #mainDiv .accordion-button {
            font-size: 12px;
            padding: 8px 10px;
        }
        .new-dashboard #mainDiv .accordion .accordion-body {
            padding: 6px;
        }
        .new-dashboard #mainDiv .btn {
            padding-top: 6px;
            padding-bottom: 6px;
            font-size: 12px;
        }
        .new-dashboard .kpi-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .new-dashboard .dashboard-filter-row {
            margin-bottom: 12px;
        }
        .new-dashboard .dashboard-filter-control {
            height: 38px;
            font-size: 12px;
            border-radius: 6px;
        }
        .new-dashboard .dashboard-filter-row .form-control,
        .new-dashboard .dashboard-filter-row .form-select {
            height: 38px;
            line-height: 1.2;
        }
        .new-dashboard #dashboard-filter-apply {
            height: 38px;
            min-height: 38px;
            padding-top: 0;
            padding-bottom: 0;
            white-space: nowrap;
        }
        .new-dashboard .dashboard-filter-label {
            display: inline-block;
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #596273;
        }
    </style>

    <div class="new-dashboard container-fluid px-2 px-lg-3">
        <div class="dashboard-card bg-white p-3 p-lg-4">
            <div class="row g-2 dashboard-filter-row align-items-end">
                <div class="col-md-6 col-lg-6">
                    <label for="dashboard-filter-group" class="dashboard-filter-label">Farmer Group</label>
                    <select id="dashboard-filter-group" class="form-select dashboard-filter-control">
                        <option value="" {{ empty($selectedGroupId) ? 'selected' : '' }}>Filter by Group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" {{ $selectedGroupId === (int) $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="dashboard-filter-start-date" class="dashboard-filter-label">Start Date</label>
                    <input type="date" id="dashboard-filter-start-date" class="form-control dashboard-filter-control">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="dashboard-filter-end-date" class="dashboard-filter-label">End Date</label>
                    <input type="date" id="dashboard-filter-end-date" class="form-control dashboard-filter-control">
                </div>
                <div class="col-md-6 col-lg-1 d-flex justify-content-lg-end">
                    <button type="button" id="dashboard-filter-apply"
                        class="btn btn-success w-100 dashboard-filter-control d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-filter"></i>
                        <span>Filter</span>
                    </button>
                </div>
                <div class="col-md-6 col-lg-1 d-flex justify-content-lg-end">
                    <button type="button" id="dashboard-filter-clear"
                        class="btn btn-outline-secondary w-100 dashboard-filter-control d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6 col-lg-3">
                    <a href="{{ route('tractors.index') }}" class="kpi-link">
                        <div class="stat-card bg-total">
                            <div class="stat-value" id="kpi-total">{{ $totalTractors }}</div>
                            <div class="stat-label">Total Tractors</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('liveview.index') }}" class="kpi-link">
                        <div class="stat-card bg-online">
                            <div class="stat-value" id="kpi-online">0</div>
                            <div class="stat-label">Online Tractors</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('liveview.index') }}" class="kpi-link">
                        <div class="stat-card bg-offline">
                            <div class="stat-value" id="kpi-offline">0</div>
                            <div class="stat-label">Offline Tractors</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('liveview.index') }}" class="kpi-link">
                        <div class="stat-card bg-inactive">
                            <div class="stat-value" id="kpi-inactive">0</div>
                            <div class="stat-label">Inactive Tractors</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('tractor-bookings.booking-list') }}" class="kpi-link">
                        <div class="stat-card bg-booked">
                            <div class="stat-value" id="kpi-booked">{{ $bookedTractors }}</div>
                            <div class="stat-label">Booked Tractors</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('reports.maintenanceReports') }}" class="kpi-link">
                        <div class="stat-card bg-pms">
                            <div class="stat-value" id="kpi-pms">{{ $pmsTractors }}</div>
                            <div class="stat-label">Tractors for PMS</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('tractor-groups.index') }}" class="kpi-link">
                        <div class="stat-card bg-groups">
                            <div class="stat-value" id="kpi-groups">{{ $groupsCount }}</div>
                            <div class="stat-label">Groups</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="{{ route('farmer-feedbacks.index') }}" class="kpi-link">
                        <div class="stat-card bg-feedbacks">
                            <div class="stat-value" id="kpi-feedbacks">{{ $feedbackCount }}</div>
                            <div class="stat-label">Feedbacks</div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="mb-3">
                <div class="section-title">Live View</div>
                <div id="mainDiv" class="row g-2 mt-1">
                    <div class="col-lg-4">
                        <div class="card mb-2 h-100">
                            <div class="card-body">
                                <div class="region-sidebar">
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
                                                data-bs-target="#home" type="button" role="tab" aria-controls="home"
                                                aria-selected="true">
                                                <span class="me-2">
                                                    <i class="fa-solid fa-car"></i>
                                                </span>
                                                Objects
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                                type="button" role="tab" aria-controls="contact" aria-selected="false">
                                                <span class="me-2">
                                                    <i class="fa-solid fa-map-location-dot"></i>
                                                </span>
                                                Tracks
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="tab-content" id="myTabContent">
                                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                            <div class="mb-3 position-relative">
                                                <div class="input-group">
                                                    <input type="text" id="search-box" placeholder="Please enter device IMEI" class="form-control">
                                                </div>
                                                <div id="suggesstion-box" class="d-none"></div>
                                            </div>
                                            <div class="position-relative">
                                                <ul class="nav nav-tabs state-tabs" role="tablist">
                                                    <li class="nav-item d-flex" role="presentation">
                                                        <button class="nav-link {{ $state == Device::ALL_DEVICES ? ' active' : '' }}"
                                                            id="all-tab" type="button" role="tab" aria-controls="all"
                                                            aria-selected="true" data-state='{{ Device::ALL_DEVICES }}'>All</button>
                                                    </li>
                                                    <li class="nav-item d-flex" role="presentation">
                                                        <button class="nav-link pe-1 {{ $state == Device::ONLINE_DEVICES ? ' active' : '' }}"
                                                            id="online-tab" type="button" role="tab" aria-controls="online"
                                                            aria-selected="true" data-state='{{ Device::ONLINE_DEVICES }}'>
                                                            <span><i class="fa-solid fa-globe"></i></span> Online
                                                        </button>
                                                        <span id="onlineCount" class="text-dark pt-2">(0)</span>
                                                    </li>
                                                    <li class="nav-item d-flex" role="presentation">
                                                        <button class="nav-link pe-1 {{ $state == Device::OFFLINE_DEVICES ? ' active' : '' }}"
                                                            id="offline-tab" type="button" role="tab" aria-controls="offline"
                                                            aria-selected="true" data-state='{{ Device::OFFLINE_DEVICES }}'>
                                                            <span><i class="fa-solid fa-globe"></i></span> Offline
                                                        </button>
                                                        <span id="offlineCount" class="text-dark pt-2">(0)</span>
                                                    </li>
                                                    <li class="nav-item d-flex" role="presentation">
                                                        <button class="nav-link pe-1 {{ $state == Device::INACTIVE_DEVICES ? ' active' : '' }}"
                                                            id="inactive-tab" type="button" role="tab" aria-controls="inactive"
                                                            aria-selected="true" data-state='{{ Device::INACTIVE_DEVICES }}'>
                                                            <span><i class="fa-solid fa-globe"></i></span> Inactive
                                                        </button>
                                                        <span id="inactiveCount" class="text-dark pt-2">(0)</span>
                                                    </li>
                                                </ul>
                                                <div class="accordion accordion-flush" id="accordionFlushExample" style="max-height: 64vh; overflow-y: auto;">
                                                    <div class="listSections" id="grouplistSection">
                                                        @foreach ($groups as $group)
                                                            <div class="accordion-item border-0 mb-3">
                                                                <h2 class="accordion-header" id="flush-headingOne{{ $group->id }}">
                                                                    <button class="accordion-button collapsed" type="button"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#collapse{{ $group->id }}"
                                                                        aria-expanded="false"
                                                                        aria-controls="collapse{{ $group->id }}">
                                                                        {{ $group->name }}
                                                                    </button>
                                                                </h2>
                                                                <div id="collapse{{ $group->id }}"
                                                                    class="accordion-collapse collapse"
                                                                    aria-labelledby="flush-headingOne{{ $group->id }}"
                                                                    data-bs-parent="#accordionFlushExample">
                                                                    <div class="accordion-body">
                                                                        <div id="groupDevices{{ $group->id }}">
                                                                            <div class="d-flex justify-content-between my-3">
                                                                                <div class="d-flex gap-2">No Data Found</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="listSections d-none" id="onlinelistSection">
                                                        <div class="filter-section d-flex justify-content-end me-3">
                                                            <div class="dropdown">
                                                                <i class="fa-solid fa-filter" id="filterDropdown"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    style="cursor: pointer;"></i>
                                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                                                                    <li class="d-flex filterDevice">
                                                                        <a href="javascript:void(0);" class="dropdown-item filterDevices"
                                                                            id="allDevices" type="button" data-type="3">All</a>
                                                                        <span class="checkmark me-2 d-none">✔️</span>
                                                                    </li>
                                                                    <li class="d-flex filterDevice">
                                                                        <a href="javascript:void(0);" class="dropdown-item filterDevices"
                                                                            id="movingDevices" type="button" data-type="1">Moving</a>
                                                                        <span class="checkmark me-2 d-none">✔️</span>
                                                                    </li>
                                                                    <li class="d-flex filterDevice">
                                                                        <a href="javascript:void(0);" class="dropdown-item filterDevices"
                                                                            id="idleDevices" type="button" data-type="2">Idle</a>
                                                                        <span class="checkmark me-2 d-none">✔️</span>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <div id="appendDevices"></div>
                                                    </div>
                                                    <div class="listSections d-none" id="offlinelistSection"></div>
                                                    <div class="listSections d-none" id="inactivelistSection"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                            <form id="trackForm">
                                                <div class="mb-3">
                                                    <select class="form-select" id="selectDevice" name="device_imei">
                                                        <option value="" selected disabled>Select Device</option>
                                                        @foreach ($allDevices as $device)
                                                            @php
                                                                $deviceName = $device->device_name ? $device->device_name : '';
                                                                if ($deviceName && $device->imei_no) {
                                                                    $deviceName = $device->device_name . ' [' . $device->imei_no . ']';
                                                                } elseif ($device->imei_no) {
                                                                    $deviceName = $device->imei_no;
                                                                } else {
                                                                    continue;
                                                                }
                                                            @endphp
                                                            <option value="{{ $device->id }}">{{ $deviceName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <select class="form-select" id="period" name="period">
                                                        <option value="8">Custom</option>
                                                        <option value="1">Today</option>
                                                        <option value="2">Yesterday</option>
                                                        <option value="3" selected>Last 3 days</option>
                                                        <option value="4">This week</option>
                                                        <option value="5">Last week</option>
                                                        <option value="6">This month</option>
                                                        <option value="7">Last month</option>
                                                    </select>
                                                </div>
                                                <div class="form-group custom-select-wrapper mb-3">
                                                    <input type="text" id="date_range" name="date_range" class="form-control"
                                                        value="{{ request()->date_range }}" placeholder="{{ __('Date Range') }}"
                                                        autocomplete="off">
                                                </div>
                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-9">
                                                            <button class="btn btn-success w-100" type="button" id="searchDevice">Search</button>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button class="btn btn-secondary w-100" type="button" id="resetDevice">Reset</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 position-relative">
                        <div class="liveView-loader-parent d-none">
                            <div class="loader-box text-center">
                                <h6>Creating Markers</h6>
                                <div class="loader-13"></div>
                            </div>
                        </div>
                        <div id="map"></div>
                        <div id="playbackControl"></div>
                        <div class="card" id="clock" style="width: 4rem; bottom: 55px; left: 5px;">
                            <span id="seconds" class="text-center">15s</span>
                        </div>
                        <div class="position-relative d-none" id="locate_button" style="width: 6rem; bottom: 65px; left: 5px;">
                            <button class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3"
                                data-action="locate" onclick="locateDevice()">Locate</button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="section-title d-flex align-items-center justify-content-between gap-2">
                    <span>Alerts Statistics</span>
                    <span id="alerts-period-label" class="small fw-normal opacity-75"></span>
                </div>
                <div class="chart-wrap">
                    <div class="alerts-chart-area">
                        <canvas id="alertsStatisticsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        @include('live-view.index-script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let alertsStatisticsChart = null;
            let appliedDashboardFilters = {};

            function initAlertsChart() {
                const canvas = document.getElementById('alertsStatisticsChart');
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                const ctx = canvas.getContext('2d');
                alertsStatisticsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Alert Count',
                            data: [],
                            backgroundColor: '#ef473a',
                            borderRadius: 6,
                            barThickness: 34
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#576171',
                                    font: {
                                        weight: '600'
                                    },
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 10
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            function updateAlertsStatisticsGraph(filters = appliedDashboardFilters) {
                return $.ajax({
                    url: "{{ route('alerts.current-month-stats') }}",
                    type: "GET",
                    data: {
                        group_id: filters.group_id || '',
                        start_date: filters.start_date || '',
                        end_date: filters.end_date || ''
                    },
                    success: function(response) {
                        if (!alertsStatisticsChart) {
                            return;
                        }

                        const labels = Array.isArray(response.labels) ? response.labels : [];
                        const counts = Array.isArray(response.counts) ? response.counts : [];
                        alertsStatisticsChart.data.labels = labels;
                        alertsStatisticsChart.data.datasets[0].data = counts;
                        alertsStatisticsChart.update();

                        if (response.period_label) {
                            $('#alerts-period-label').text(response.period_label);
                        }
                    }
                });
            }

            function getDashboardFilters() {
                return {
                    group_id: $('#dashboard-filter-group').val() || '',
                    start_date: $('#dashboard-filter-start-date').val() || '',
                    end_date: $('#dashboard-filter-end-date').val() || '',
                };
            }

            function clearDashboardFilters() {
                $('#dashboard-filter-group').val('');
                $('#dashboard-filter-start-date').val('');
                $('#dashboard-filter-end-date').val('');
            }

            function updateDashboardKpis(filters = appliedDashboardFilters) {
                return $.ajax({
                    url: "{{ route('liveview.dashboardStats') }}",
                    type: "GET",
                    data: filters,
                    success: function(response) {
                        const data = response.data || {};
                        const total = Number(data.totalTractors || 0);
                        const online = Number(data.onlineCount || 0);
                        const offline = Number(data.offlineCount || 0);
                        const inactive = Number(data.inactiveCount || 0);
                        const booked = Number(data.bookedTractors || 0);
                        const pms = Number(data.pmsTractors || 0);
                        const groups = Number(data.groupsCount || 0);
                        const feedbacks = Number(data.feedbackCount || 0);

                        $('#kpi-total').text(total);
                        $('#kpi-online').text(online);
                        $('#kpi-offline').text(offline);
                        $('#kpi-inactive').text(inactive);
                        $('#kpi-booked').text(booked);
                        $('#kpi-pms').text(pms);
                        $('#kpi-groups').text(groups);
                        $('#kpi-feedbacks').text(feedbacks);
                    }
                });
            }

            function setButtonLoading($button, isLoading) {
                if (isLoading) {
                    if (!$button.data('original-html')) {
                        $button.data('original-html', $button.html());
                    }
                    $button.prop('disabled', true);
                    $button.html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Loading...</span>'
                    );
                    return;
                }

                $button.prop('disabled', false);
                if ($button.data('original-html')) {
                    $button.html($button.data('original-html'));
                }
            }

            $(document).ready(function() {
                appliedDashboardFilters = getDashboardFilters();
                initAlertsChart();
                updateDashboardKpis();
                updateAlertsStatisticsGraph(appliedDashboardFilters);
                setInterval(function() {
                    updateDashboardKpis(appliedDashboardFilters);
                }, 15000);
                setInterval(function() {
                    updateAlertsStatisticsGraph(appliedDashboardFilters);
                }, 60000);

                $('#dashboard-filter-apply').on('click', function() {
                    const $button = $(this);
                    appliedDashboardFilters = getDashboardFilters();
                    setButtonLoading($button, true);

                    $.when(
                        updateDashboardKpis(appliedDashboardFilters),
                        updateAlertsStatisticsGraph(appliedDashboardFilters)
                    ).always(function() {
                        setButtonLoading($button, false);
                    });
                });

                $('#dashboard-filter-clear').on('click', function() {
                    const $button = $(this);
                    clearDashboardFilters();
                    appliedDashboardFilters = getDashboardFilters();
                    setButtonLoading($button, true);

                    $.when(
                        updateDashboardKpis(appliedDashboardFilters),
                        updateAlertsStatisticsGraph(appliedDashboardFilters)
                    ).always(function() {
                        setButtonLoading($button, false);
                    });
                });

                // Keep this scoped to dashboard: once map instance is ready, switch to satellite.
                const satelliteTimer = setInterval(function() {
                    if (typeof maps !== 'undefined' && maps['map']) {
                        maps['map'].setMapTypeId('satellite');
                        clearInterval(satelliteTimer);
                    }
                }, 400);
            });
        </script>
    @endpush
</x-app-layout>

@php
    use App\Models\User;
@endphp
<style>
    /* ── Shared link / button flex base ───────────────────────────── */
    .sidenav ul > .nav-item > a,
    .sidenav .sidebar-acrordian .accordion-body a {
        display: flex !important;
        align-items: center !important;
        gap: 10px;
    }

    .sidenav .accordion-button {
        display: flex !important;
        align-items: center !important;
        gap: 10px;
        /* extra right padding so text never slides under the chevron */
        padding-right: 36px !important;
    }

    /* ── Icon fixed-width column ──────────────────────────────────── */
    .sidenav ul > .nav-item > a > i,
    .sidenav .sidebar-acrordian .accordion-body a > i,
    .sidenav .accordion-button > i {
        flex-shrink: 0;
        width: 18px;
        text-align: center;
        font-size: 0.875rem;
        line-height: 1;
        margin-right: 0; /* overrides the global "margin-right:5px" */
    }

    /* ── Text label: truncate cleanly ────────────────────────────── */
    .sidenav ul > .nav-item > a > span,
    .sidenav .sidebar-acrordian .accordion-body a > span,
    .sidenav .accordion-button > .nav-label {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
    }

    /* ── Sub-nav: indent each level ──────────────────────────────── */
    .sidenav .sidebar-acrordian .accordion-body a {
        padding-left: 38px !important; /* icon-col-width + gap + parent-indent */
    }
    .sidenav .sidebar-acrordian .accordion-body a > span {
        font-size: inherit;
    }

    /* ── Nested (level-3) sub-nav extra indent ────────────────────── */
    .sidenav .sidebar-acrordian .sidebar-acrordian .accordion-body a {
        padding-left: 56px !important;
    }
    .sidenav .sidebar-acrordian .sidebar-acrordian .accordion-button {
        padding-left: 38px !important;
    }

    /* ── Logo header: align left edge with nav items ─────────────── */
    .sidenav-header .navbar-brand {
        padding-left: 20px !important;
        padding-right: 20px !important;
        justify-content: flex-start !important;
    }

    /* ── Accordion chevron positioned from right ─────────────────── */
    .sidenav .accordion-button::after {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        margin-right: 0;
    }
    .sidenav .accordion-button:not(.collapsed)::after {
        transform: translateY(-50%) rotate(90deg);
    }

    /* ── Scrollable nav region ───────────────────────────────────── */
    #sidenav-collapse-main {
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    .sidenav ul.navbar-nav {
        padding: 6px 0;
    }

    .sidenav ul.navbar-nav > .nav-item {
        margin: 2px 0;
    }
</style>

<aside class="sidenav navbar navbar-expand-xs navbar-vertical align-items-start p-0" id="sidenav">

    {{-- Logo --}}
    <div class="sidenav-header d-none d-sm-block w-100">
        <a class="navbar-brand m-0 d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo.png') }}" alt="logo">
        </a>
    </div>

    {{-- Navigation --}}
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav w-100">

            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="fa-fw fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            {{-- Administration & Access Control --}}
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenNew">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingTwo">
                            <button class="accordion-button sidebar-acc-btn bg-transparent border-0 shadow-none
                                {{ request()->is('sub-admin') || request()->is('tractor-groups*') || request()->is('pages*') || request()->is('technicians') || request()->is('tagging') ? 'parent-active' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseTwo"
                                aria-expanded="{{ request()->is('sub-admin') || request()->is('tractor-groups*') || request()->is('pages*') || request()->is('technicians') || request()->is('tagging') ? 'true' : 'false' }}"
                                aria-controls="panelsStayOpen-collapseTwo">
                                <i class="fa-fw fa-solid fa-user-tie"></i>
                                <span class="nav-label">Administration</span>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseTwo"
                             class="accordion-collapse collapse {{ request()->is('sub-admin') || request()->is('tractor-groups*') || request()->is('pages*') || request()->is('technicians') || request()->is('tagging') ? 'show' : '' }}"
                             aria-labelledby="panelsStayOpen-headingTwo">
                            <div class="accordion-body">
                                <a href="{{ route('tractor-groups.index') }}"
                                   class="{{ request()->is('tractor-groups*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-users-gear"></i>
                                    <span>Groups & Users</span>
                                </a>
                            </div>
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                <div class="accordion-body">
                                    <a href="{{ route('users.subAdmin') }}"
                                       class="{{ request()->is('sub-admin') ? 'active' : '' }}">
                                        <i class="fa-fw fa-solid fa-user-shield"></i>
                                        <span>Sub-Admins</span>
                                    </a>
                                </div>
                                <div class="accordion-body">
                                    <a href="{{ route('users.technicians') }}"
                                       class="{{ request()->is('technicians') ? 'active' : '' }}">
                                        <i class="fa-fw fa-solid fa-hard-hat"></i>
                                        <span>Technicians</span>
                                    </a>
                                </div>
                                <div class="accordion-body">
                                    <a href="{{ route('tractors.tagging') }}"
                                       class="{{ request()->is('tagging') ? 'active' : '' }}">
                                        <i class="fa-fw fa-solid fa-tag"></i>
                                        <span>Tagging</span>
                                    </a>
                                </div>
                                <div class="accordion-body">
                                    <a href="{{ route('pages.index') }}"
                                       class="{{ request()->is('pages*') ? 'active' : '' }}">
                                        <i class="fa-fw fa-solid fa-file-lines"></i>
                                        <span>Pages</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </li>

            {{-- Fleet & Assets --}}
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenFleet">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingThree">
                            <button class="accordion-button sidebar-acc-btn bg-transparent border-0 shadow-none
                                {{ request()->is('devices*') || request()->is('tractors') || request()->is('tractors*') || request()->is('farm-assets*') || request()->is('auto-reports*') ? 'parent-active' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseThree"
                                aria-expanded="{{ request()->is('devices*') || request()->is('tractors*') || request()->is('farm-assets*') || request()->is('auto-reports*') ? 'true' : 'false' }}"
                                aria-controls="panelsStayOpen-collapseThree">
                                <i class="fa-fw fa-solid fa-truck-fast"></i>
                                <span class="nav-label">Fleet & Assets</span>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseThree"
                             class="accordion-collapse collapse {{ request()->is('devices*') || request()->is('tractors') || request()->is('tractors*') || request()->is('farm-assets*') || request()->is('auto-reports*') ? 'show' : '' }}"
                             aria-labelledby="panelsStayOpen-headingThree">
                            <div class="accordion-body">
                                <a href="{{ route('devices.index') }}"
                                   class="{{ request()->is('devices*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-microchip"></i>
                                    <span>Devices</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('tractors.index') }}"
                                   class="{{ request()->is('tractors') || request()->is('tractors*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-tractor"></i>
                                    <span>Tractors</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('farm-assets.index') }}"
                                   class="{{ request()->is('farm-assets*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-layer-group"></i>
                                    <span>Farm Assets</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('auto-reports.index') }}"
                                   class="{{ request()->is('auto-reports*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-file-lines"></i>
                                    <span>Auto Reports</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>

            {{-- Operations & Maintenance --}}
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenOps">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingFour">
                            <button class="accordion-button sidebar-acc-btn bg-transparent border-0 shadow-none
                                {{ request()->is('tractor-bookings*') || request()->is('maintenances*') || request()->is('issue-types*') || request()->is('tickets*') ? 'parent-active' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseFour"
                                aria-expanded="{{ request()->is('tractor-bookings*') || request()->is('maintenances*') || request()->is('issue-types*') || request()->is('tickets*') ? 'true' : 'false' }}"
                                aria-controls="panelsStayOpen-collapseFour">
                                <i class="fa-fw fa-solid fa-screwdriver-wrench"></i>
                                <span class="nav-label">Operations & Maintenance</span>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseFour"
                             class="accordion-collapse collapse {{ request()->is('tractor-bookings*') || request()->is('maintenances*') || request()->is('issue-types*') || request()->is('tickets*') ? 'show' : '' }}"
                             aria-labelledby="panelsStayOpen-headingFour">
                            <div class="accordion-body">
                                <a href="{{ route('tractor-bookings.booking-list') }}"
                                   class="{{ request()->is('tractor-bookings*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-calendar-check"></i>
                                    <span>Bookings</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('maintenances.index') }}"
                                   class="{{ request()->is('maintenances*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-gear"></i>
                                    <span>Maintenance</span>
                                </a>
                            </div>
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                <div class="accordion-body">
                                    <a href="{{ route('issue-types.index') }}"
                                       class="{{ request()->is('issue-types*') ? 'active' : '' }}">
                                        <i class="fa-fw fa-solid fa-circle-exclamation"></i>
                                        <span>Issue Types</span>
                                    </a>
                                </div>
                            @endif
                            <div class="accordion-body">
                                <a href="{{ route('tickets.index') }}"
                                   class="{{ request()->is('tickets*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-ticket"></i>
                                    <span>Tickets</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>

            {{-- Monitoring & Tracking --}}
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenMonitor">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingFive">
                            <button class="accordion-button sidebar-acc-btn bg-transparent border-0 shadow-none
                                {{ request()->is('liveview*') || request()->is('device-geo-fences*') ? 'parent-active' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseFive"
                                aria-expanded="{{ request()->is('liveview*') || request()->is('device-geo-fences*') ? 'true' : 'false' }}"
                                aria-controls="panelsStayOpen-collapseFive">
                                <i class="fa-fw fa-solid fa-tower-observation"></i>
                                <span class="nav-label">Monitoring & Tracking</span>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseFive"
                             class="accordion-collapse collapse {{ request()->is('liveview*') || request()->is('device-geo-fences*') ? 'show' : '' }}"
                             aria-labelledby="panelsStayOpen-headingFive">
                            <div class="accordion-body">
                                <a href="{{ route('liveview.index') }}"
                                   class="{{ request()->is('liveview*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-map-location-dot"></i>
                                    <span>Live View</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('device-geo-fences.index') }}"
                                   class="{{ request()->is('device-geo-fences*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-location-crosshairs"></i>
                                    <span>Geo Fences</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>

            {{-- Data & Reporting --}}
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenData">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingSix">
                            <button class="accordion-button sidebar-acc-btn bg-transparent border-0 shadow-none
                                {{ request()->is('overview*') || request()->is('farmer-feedbacks*') || request()->is('api-documentation*') || request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') ? 'parent-active' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseSix"
                                aria-expanded="{{ request()->is('overview*') || request()->is('farmer-feedbacks*') || request()->is('api-documentation*') || request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') ? 'true' : 'false' }}"
                                aria-controls="panelsStayOpen-collapseSix">
                                <i class="fa-fw fa-solid fa-chart-pie"></i>
                                <span class="nav-label">Data & Reporting</span>
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseSix"
                             class="accordion-collapse collapse {{ request()->is('overview*') || request()->is('farmer-feedbacks*') || request()->is('api-documentation*') || request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') ? 'show' : '' }}"
                             aria-labelledby="panelsStayOpen-headingSix">
                            <div class="accordion-body">
                                <a href="{{ route('devices.overview') }}"
                                   class="{{ request()->is('overview*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-circle-info"></i>
                                    <span>Overview</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('farmer-feedbacks.index') }}"
                                   class="{{ request()->is('farmer-feedbacks*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-comments"></i>
                                    <span>Tractor Reports</span>
                                </a>
                            </div>
                            <div class="accordion-body">
                                <a href="{{ route('reports.apiDocumentation') }}"
                                   class="{{ request()->is('api-documentation*') ? 'active' : '' }}">
                                    <i class="fa-fw fa-solid fa-book"></i>
                                    <span>API Documentation</span>
                                </a>
                            </div>

                            {{-- Reports & Analytics nested group --}}
                            <div class="accordion-body p-0">
                                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenReports">
                                    <div class="accordion-item bg-transparent border-0">
                                        <h2 class="accordion-header m-0" id="panelsStayOpen-headingOne">
                                            <button class="accordion-button bg-transparent border-0 shadow-none
                                                {{ request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') || request()->is('tractor-usage*') ? 'parent-active' : 'collapsed' }}"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#panelsStayOpen-collapseOne"
                                                aria-expanded="{{ request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') || request()->is('tractor-usage*') ? 'true' : 'false' }}"
                                                aria-controls="panelsStayOpen-collapseOne">
                                                <i class="fa-fw fa-solid fa-chart-line"></i>
                                                <span class="nav-label">Reports & Analytics</span>
                                            </button>
                                        </h2>
                                        <div id="panelsStayOpen-collapseOne"
                                             class="accordion-collapse collapse {{ request()->is('reports*') || request()->is('maintenance-reports*') || request()->is('device-reports*') || request()->is('tractor-usage*') ? 'show' : '' }}"
                                             aria-labelledby="panelsStayOpen-headingOne">
                                            <div class="accordion-body">
                                                <a href="{{ route('reports.index') }}"
                                                   class="{{ request()->is('reports*') && !request()->is('reports/*') ? 'active' : '' }}">
                                                    <i class="fa-fw fa-solid fa-chart-bar"></i>
                                                    <span>Reports</span>
                                                </a>
                                            </div>
                                            <div class="accordion-body">
                                                <a href="{{ route('reports.maintenanceReports') }}"
                                                   class="{{ request()->is('maintenance-reports*') ? 'active' : '' }}">
                                                    <i class="fa-fw fa-solid fa-wrench"></i>
                                                    <span>Maintenance Report</span>
                                                </a>
                                            </div>
                                            <div class="accordion-body">
                                                <a href="{{ route('reports.deviceReports') }}"
                                                   class="{{ request()->is('device-reports*') ? 'active' : '' }}">
                                                    <i class="fa-fw fa-solid fa-chart-pie"></i>
                                                    <span>Device Reports</span>
                                                </a>
                                            </div>
                                            <div class="accordion-body">
                                                <a href="{{ route('reports.tractorUsage') }}"
                                                   class="{{ request()->is('tractor-usage*') ? 'active' : '' }}">
                                                    <i class="fa-fw fa-solid fa-tractor"></i>
                                                    <span>Tractor Usage</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </li>

            {{-- Alerts --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->is('alerts*') ? 'active' : '' }}"
                   href="{{ route('alerts.index') }}">
                    <i class="fa-fw fa-solid fa-triangle-exclamation"></i>
                    <span>Alerts</span>
                </a>
            </li>

        </ul>
    </div>
</aside>

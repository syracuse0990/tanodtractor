@php
    use App\Models\User;

@endphp
<aside class="sidenav navbar navbar-expand-xs navbar-vertical align-items-start p-0" id="sidenav">
    <div class="sidenav-header  d-none d-sm-block w-100">
        <a class="navbar-brand m-0 text-center d-flex align-items-center justify-content-center"
            href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo.png') }}" alt="logo">
        </a>
    </div>

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenNew">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                            <button
                                class="accordion-button sidebar-acc-btn bg-transparent border-0 
    @if (request()->is('sub-admin') || request()->is('tractor-groups') || request()->is('pages')) parent-active
    @else
        collapsed @endif"
                                type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo"
                                aria-expanded="true" aria-controls="panelsStayOpen-collapseTwo">
                                <i class="fa-solid fa-user-tie"></i>
                                Administration & Access Control
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseTwo"
                            class="accordion-collapse collapse {{ request()->is('sub-admin') || request()->is('tractor-groups') || request()->is('pages') ? 'show' : '' }}"
                            aria-labelledby="panelsStayOpen-headingTwo">

                            <div class="accordion-body">
                                <a href="{{ route('tractor-groups.index') }}"
                                    class="{{ request()->is('tractor-groups*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-users"></i>
                                    <span>User Management</span>
                                </a>
                            </div>
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                <div class="accordion-body">
                                    <a href="{{ route('users.subAdmin') }}"
                                        class="{{ request()->is('sub-admin') ? 'active' : '' }}">
                                        <i class="fa-solid fa-user-shield"></i>
                                        <span>Sub Admin</span>
                                    </a>
                                </div>
                            @endif
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                <div class="accordion-body">
                                    <a href="{{ route('pages.index') }}"
                                        class="{{ request()->is('pages') ? 'active' : '' }}">
                                        <i class="fa-solid fa-file-alt"></i>
                                        <span>Pages</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenfleet">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                            <button
                                class="accordion-button sidebar-acc-btn bg-transparent border-0 
    @if (request()->is('devices*') ||
            request()->is('tractors') ||
            request()->is('farm-assets') ||
            request()->is('auto-reports')) parent-active
    @else
        collapsed @endif"
                                type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree"
                                aria-expanded="true" aria-controls="panelsStayOpen-collapseThree">
                                <i class="fa-solid fa-chart-line"></i>
                                Fleet & Assets
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseThree"
                            class="accordion-collapse collapse {{ request()->is('devices*') || request()->is('tractors') || request()->is('farm-assets') || request()->is('auto-reports') ? 'show' : '' }}"
                            aria-labelledby="panelsStayOpen-headingThree">

                            <div class="accordion-body">
                                <a href="{{ route('devices.index') }}"
                                    class="{{ request()->is('devices*') ? 'active' : '' }}">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <span>Devices</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a class="{{ request()->is('tractors') ? 'active' : '' }}"
                                    href="{{ route('tractors.index') }}">
                                    <i class="fa-solid fa-tractor"></i>
                                    <span>Tractors</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a href="{{ route('farm-assets.index') }}"
                                    class="{{ request()->is('farm-assets') ? 'active' : '' }}">
                                    <i class="fa-solid fa-users"></i>
                                    <span>Farm Assets</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a class="{{ request()->is('auto-reports') ? 'active' : '' }}"
                                    href="{{ route('auto-reports.index') }}">
                                    <i class="fa-solid fa-file-lines"></i>
                                    <span>Auto Report</span>
                                </a>
                            </div>

                        </div>

                    </div>
                </div>
            </li>

            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenfleet">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header" id="panelsStayOpen-headingFour">
                            <button
                                class="accordion-button sidebar-acc-btn bg-transparent border-0 
    @if (request()->is('tractor-bookings/booking-list') ||
            request()->is('maintenances') ||
            request()->is('issue-types') ||
            request()->is('tickets')) parent-active
    @else
        collapsed @endif"
                                type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour"
                                aria-expanded="true" aria-controls="panelsStayOpen-collapseFour">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                                Operations & Maintenance
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseFour"
                            class="accordion-collapse collapse {{ request()->is('tractor-bookings/booking-list') || request()->is('maintenances') || request()->is('issue-types') || request()->is('tickets') ? 'show' : '' }}"
                            aria-labelledby="panelsStayOpen-headingFour">

                            <div class="accordion-body">
                                <a class="{{ request()->is('tractor-bookings/booking-list') ? 'active' : '' }}"
                                    href="{{ route('tractor-bookings.booking-list') }}">
                                    <i class="fa-solid fa-calendar-check"></i>
                                    <span>Bookings</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a class="{{ request()->is('maintenances') ? 'active' : '' }}"
                                    href="{{ route('maintenances.index') }}">
                                    <i class="fa-solid fa-gear"></i>
                                    <span>Maintenance</span>
                                </a>
                            </div>
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                <div class="accordion-body">
                                    <a class="{{ request()->is('issue-types') ? 'active' : '' }}"
                                        href="{{ route('issue-types.index') }}">
                                        <i class="fa-sharp fa-solid fa-circle-exclamation"></i>
                                        <span>Issue Types</span>
                                    </a>
                                </div>
                            @endif
                            <div class="accordion-body">
                                <a class="{{ request()->is('tickets') ? 'active' : '' }}"
                                    href="{{ route('tickets.index') }}">
                                    <i class="fa-solid fa-ticket"></i>
                                    <span>Tickets</span>
                                </a>
                            </div>

                        </div>

                    </div>
                </div>
            </li>

            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenMonitor">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header" id="panelsStayOpen-headingFive">
                            <button
                                class="accordion-button sidebar-acc-btn bg-transparent border-0 
    @if (request()->is('liveview') || request()->is('device-geo-fences')) parent-active
    @else
        collapsed @endif"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#panelsStayOpen-collapseFive" aria-expanded="true"
                                aria-controls="panelsStayOpen-collapseFive">
                                <i class="fa-solid fa-tower-observation"></i>
                                Monitoring & Tracking
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseFive"
                            class="accordion-collapse collapse {{ request()->is('liveview') || request()->is('device-geo-fences') ? 'show' : '' }}"
                            aria-labelledby="panelsStayOpen-headingFive">

                            <div class="accordion-body">
                                <a class="{{ request()->is('liveview') ? 'active' : '' }}"
                                    href="{{ route('liveview.index') }}">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                    <span>Live View</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a class="{{ request()->is('device-geo-fences') ? 'active' : '' }}"
                                    href="{{ route('device-geo-fences.index') }}">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                    <span>Geo Fences</span>
                                </a>
                            </div>

                        </div>

                    </div>
                </div>
            </li>

            <li class="nav-item">
                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenData">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header" id="panelsStayOpen-headingSix">
                            <button
                                class="accordion-button sidebar-acc-btn bg-transparent border-0 
    @if (request()->is('overview') || request()->is('farmer-feedbacks') || request()->is('auto-reports')) parent-active
    @else
        collapsed @endif"
                                type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSix"
                                aria-expanded="true" aria-controls="panelsStayOpen-collapseSix">
                                <i class="fa-solid fa-chart-line"></i>
                                Data & Reporting
                            </button>
                        </h2>
                        <div id="panelsStayOpen-collapseSix"
                            class="accordion-collapse collapse {{ request()->is('overview') || request()->is('farmer-feedbacks') || request()->is('auto-reports') ? 'show' : '' }}"
                            aria-labelledby="panelsStayOpen-headingSix">
                            <div class="accordion-body">
                                <a class="{{ request()->is('overview') ? 'active' : '' }}"
                                    href="{{ route('devices.overview') }}">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Overviews</span>
                                </a>
                            </div>

                            <div class="accordion-body">
                                <a class="{{ request()->is('farmer-feedbacks') ? 'active' : '' }}"
                                    href="{{ route('farmer-feedbacks.index') }}">
                                    <i class="fa-sharp fa-solid fa-comments"></i>
                                    <span>Tractor Reports</span>
                                </a>
                            </div>

                            <div class="accordion-body">

                                <div class="accordion sidebar-acrordian" id="accordionPanelsStayOpenExample">
                                    <div class="accordion-item bg-transparent border-0">
                                        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                                            <button
                                                class="accordion-button bg-transparent border-0 {{ request()->is('reports*') || request()->is('maintenance-reports') || request()->is('device-reports') ? '' : 'collapsed' }}"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true"
                                                aria-controls="panelsStayOpen-collapseOne">
                                                <i class="fa-solid fa-chart-line"></i>
                                                Reports And Analytics
                                            </button>
                                        </h2>
                                        <div id="panelsStayOpen-collapseOne"
                                            class="accordion-collapse collapse {{ request()->is('reports*') || request()->is('maintenance-reports') || request()->is('device-reports') ? 'show' : '' }}"
                                            aria-labelledby="panelsStayOpen-headingOne">
                                            <div class="accordion-body">
                                                <a class="{{ request()->is('reports*') ? 'active' : '' }}"
                                                    href="{{ route('reports.index') }}">
                                                    <i class="fa-solid fa-chart-line"></i>
                                                    <span>Reports</span>
                                                </a>
                                            </div>
                                            <div class="accordion-body">
                                                <a class="{{ request()->is('maintenance-reports') ? 'active' : '' }}"
                                                    href="{{ route('reports.maintenaceReports') }}">
                                                    <i class="fa-solid fa-chart-line"></i>
                                                    <span>Mainteance Report</span>
                                                </a>
                                            </div>
                                            <div class="accordion-body">
                                                <a class="{{ request()->is('device-reports') ? 'active' : '' }}"
                                                    href="{{ route('reports.deviceReports') }}">
                                                    <i class="fa-solid fa-chart-pie"></i>
                                                    <span>Devices Reports</span>
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

            <li class="nav-item">
                <a class="nav-link {{ request()->is('alerts*') ? 'active' : '' }}"
                    href="{{ route('alerts.index') }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Alerts</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

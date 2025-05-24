@php
    use App\Models\Device;
@endphp
<x-app-layout title="{{ __('Live View') }}">
    <div id="mainDiv" class="row">
        <div class="col-lg-4">
            <div class="card mb-4 h-100">
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
                                    Objects</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                    type="button" role="tab" aria-controls="contact" aria-selected="false">
                                    <span class="me-2">
                                        <i class="fa-solid fa-map-location-dot"></i>
                                    </span>
                                    Tracks</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home" role="tabpanel"
                                aria-labelledby="home-tab">
                                <div class="mb-3 position-relative">
                                    <div class="input-group">
                                        <input type="text" id="search-box" placeholder="Please enter device IMEI"
                                            class="form-control">
                                    </div>
                                    <div id="suggesstion-box" class="d-none"></div>
                                </div>
                                <div class="position-relative">
                                    <ul class="nav nav-tabs state-tabs" role="tablist">
                                        <li class="nav-item d-flex" role="presentation">
                                            <button
                                                class="nav-link {{ $state == Device::ALL_DEVICES ? ' active' : '' }}"
                                                id="all-tab" type="button" role="tab" aria-controls="all"
                                                aria-selected="true" data-state='{{ Device::ALL_DEVICES }}'>All</button>
                                        </li>
                                        <li class="nav-item d-flex" role="presentation">
                                            <button
                                                class="nav-link pe-1 {{ $state == Device::ONLINE_DEVICES ? ' active' : '' }}"
                                                id="online-tab" type="button" role="tab" aria-controls="online"
                                                aria-selected="true" data-state='{{ Device::ONLINE_DEVICES }}'>
                                                <span>
                                                    <i class="fa-solid fa-globe"></i>
                                                </span>
                                                Online
                                            </button>
                                            <span id="onlineCount" class="text-dark pt-2">{{ '(0)' }}</span>
                                        </li>
                                        <li class="nav-item d-flex" role="presentation">
                                            <button
                                                class="nav-link pe-1 {{ $state == Device::OFFLINE_DEVICES ? ' active' : '' }}"
                                                id="offline-tab" type="button" role="tab" aria-controls="offline"
                                                aria-selected="true" data-state='{{ Device::OFFLINE_DEVICES }}'>
                                                <span>
                                                    <i class="fa-solid fa-globe"></i>
                                                </span>
                                                Offline
                                            </button>
                                            <span id="offlineCount" class="text-dark pt-2">{{ '(0)' }}</span>
                                        </li>
                                        <li class="nav-item d-flex" role="presentation">
                                            <button
                                                class="nav-link pe-1 {{ $state == Device::INACTIVE_DEVICES ? ' active' : '' }}"
                                                id="inactive-tab" type="button" role="tab" aria-controls="inactive"
                                                aria-selected="true" data-state='{{ Device::INACTIVE_DEVICES }}'>
                                                <span>
                                                    <i class="fa-solid fa-globe"></i>
                                                </span>
                                                Inactive
                                            </button>
                                            <span id="inactiveCount" class="text-dark pt-2">{{ '(0)' }}</span>
                                        </li>
                                    </ul>
                                    <div class="accordion accordion-flush" id="accordionFlushExample"
                                        style="max-height: 62vh; overflow-y: auto;">
                                        <div class="listSections" id="grouplistSection">
                                            @foreach ($groups as $group)
                                                <div class="accordion-item border-0 mb-3">
                                                    <h2 class="accordion-header"
                                                        id="flush-headingOne{{ $group->id }}">
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
                                                    <ul class="dropdown-menu dropdown-menu-end"
                                                        aria-labelledby="filterDropdown">
                                                        <li class="d-flex filterDevice">
                                                            <a href="javascript:void(0);"
                                                                class="dropdown-item filterDevices" id="allDevices"
                                                                type="button" data-type="3">
                                                                All
                                                            </a>
                                                            <span class="checkmark me-2 d-none">✔️</span>
                                                        </li>
                                                        <li class="d-flex filterDevice">
                                                            <a href="javascript:void(0);"
                                                                class="dropdown-item filterDevices" id="movingDevices"
                                                                type="button" data-type="1">
                                                                Moving
                                                            </a>
                                                            <span class="checkmark me-2 d-none">✔️</span>
                                                        </li>
                                                        <li class="d-flex filterDevice">
                                                            <a href="javascript:void(0);"
                                                                class="dropdown-item filterDevices" id="idleDevices"
                                                                type="button" data-type="2">
                                                                Idle
                                                            </a>
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
                                                        $deviceName =
                                                            $device->device_name . ' [' . $device->imei_no . ']';
                                                    } elseif ($device->imei_no) {
                                                        $deviceName = $device->imei_no;
                                                    } else {
                                                        continue;
                                                    }
                                                @endphp
                                                <option value={{ $device->id }}>{{ $deviceName }}</option>
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
                                                <button class="btn btn-success w-100" type="button"
                                                    id="searchDevice">Search</button>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-secondary w-100" type="button"
                                                    id="resetDevice">Reset</button>
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
                {{-- <div class="liveView-stateloader"></div> --}}
                <div class="loader-box text-center">
                    <h6>Creating Markers</h6>
                    <div class="loader-13"></div>
                </div>
            </div>
            <div id="map"></div>
            <div id="playbackControl"></div>
            <div class="card" id="clock" style="width: 4rem;bottom: 55px;left: 5px;">
                <span id="seconds" class="text-center">15s</span>
            </div>
            <div class="position-relative d-none" id="locate_button"
                style="width: 6rem;bottom: 65px;left: 5px;/*! border-color: #198754; */">
                <button class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3" data-action="locate"
                    onclick="locateDevice()">Locate</button>
            </div>
        </div>
    </div>

    @push('js')
        @include('live-view.index-script')
    @endpush
</x-app-layout>

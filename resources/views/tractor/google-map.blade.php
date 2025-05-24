<x-app-layout title="{{ __('Tractors') }}">
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
                            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                                <div class="mb-3">
                                    <input type="text" id="search-box" placeholder="Please enter device imei"
                                        class="form-control">
                                    <div id="suggesstion-box" class="d-none"></div>
                                </div>
                                <div class="position-relative">
                                    @livewire('object-group-list', ['groups' => $groups])
                                </div>
                            </div>
                            {{-- Select device and get the booking list --}}
                            {{-- <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                <div>
                                    <div class="mb-3">
                                        <select class="form-select" id="selectDevice" name="device_imei"
                                            onchange="selectDevice(this)">
                                            <option value="" selected disabled>Select Device</option>
                                            @foreach ($groups as $group)
                                            @php
                                            $devices = $group->getDevices();
                                            @endphp
                                            @foreach ($devices as $device)
                                            <option value={{$device->id}}>{{$device->device_name .'
                                                ['.$device->imei_no.']'}}</option>
                                            @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-3 booking_details"></div>
                                </div>
                            </div> --}}
                            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                <form id="trackForm">
                                    <div class="mb-3">
                                        <select class="form-select" id="selectDevice" name="device_imei">
                                            <option value="" selected disabled>Select Device</option>
                                            @foreach ($allDevices as $device)
                                            @php
                                            $deviceName = $device->device_name ? $device->device_name : '';
                                            if($deviceName && $device->imei_no){
                                            $deviceName = $device->device_name .' ['.$device->imei_no.']';
                                            }elseif($device->imei_no){
                                            $deviceName = $device->imei_no;
                                            }else{
                                            continue;
                                            }
                                            @endphp
                                            <option value={{$device->id}}>{{$deviceName}}</option>
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
    @include('tractor.google-map-js')
</x-app-layout>
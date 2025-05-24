@php
    $deviceStateClass = 'bg-secondary';
@endphp
<div id="onlineOfflineDevices">
    <div class="filter-section d-flex justify-content-end me-3">
        <div class="card mb-3">
            <div class="card-body">
                <div class="accordion-item border-0">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="device-state-img {{ $deviceStateClass }}">
                                    <i class="fa-solid fa-tractor"></i>
                                </div>
                                <a href="javascript:void({{ $device->imei_no }});" class="text-secondary"
                                    data-imei="{{ $device->imei_no }}">
                                    <div style="cursor: no-drop;">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="device-name">{{ $device['tractor']->no_plate ?? $device->imei_no }}</h5>
                                            <p class="mb-0">Inactive</p>
                                        </div>
                                        @if (!empty($device['tractor']) && !empty($device['tractor']->imei))
                                            <p class="mb-0 date-time">{{ $device['tractor']->imei }}</p>
                                        @else
                                            <p class="mb-0 date-time">{{ $device->device_name }}</p>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

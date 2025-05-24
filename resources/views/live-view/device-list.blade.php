@php
    use App\Models\Device;

    $deviceStateClass = 'bg-danger';
    if ($device['minutes'] > 8) {
        $deviceStateClass = 'bg-danger';
    } else {
        if (
            $device['apiData']['status'] == 1 &&
            $device['apiData']['accStatus'] == 1 &&
            $device['apiData']['speed'] != 0
        ) {
            $deviceStateClass = 'bg-success';
        } elseif (
            $device['apiData']['status'] == 1 &&
            ($device['apiData']['speed'] == 0 || $device['apiData']['speed'] == null)
        ) {
            $deviceStateClass = 'bg-warning';
        }
    }
@endphp
<div class="card mb-3">
    <div class="card-body">
        <div class="accordion-item border-0">
            <div class="accordion-body">
                <div class="d-flex justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="device-state-img {{ $deviceStateClass }}">
                            <i class="fa-solid fa-tractor"></i>
                        </div>
                        <a href="javascript:void({{ $device->imei_no }});" class="text-secondary current-device"
                            data-imei="{{ $device->imei_no }}">
                            <div>
                                <h5 class="device-name">{{ $device['tractor']->no_plate ?? $device->imei_no }}</h5>
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

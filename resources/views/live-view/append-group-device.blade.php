@php
    use App\Models\Device;
    use App\Models\Tractor;
@endphp
@foreach ($apiData as $data)
    @php
        $device = Device::where('imei_no', $data['imei'])->select('id', 'activation_time', 'device_name')->first();
        $tractor = Tractor::where([
            'device_id' => $device->id,
            'group_id' => $group?->id,
        ])->first();

        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $diffInSeconds = strtotime($gmt_date) - strtotime($data['hbTime']);
        $minutes = floor($diffInSeconds / 60);
        $deviceStateClass = 'bg-danger';
        if (isset($data['imei'])) {
            if ($minutes > 8) {
                $deviceStateClass = 'bg-danger';
            } else {
                if ($data['status'] == 1 && $data['accStatus'] == 1 && $data['speed'] != 0) {
                    $deviceStateClass = 'bg-success';
                } elseif ($data['status'] == 1 && ($data['speed'] == 0 || $data['speed'] == null)) {
                    $deviceStateClass = 'bg-warning';
                }
            }
        }
        if ($device->activation_time == null) {
            $deviceStateClass = 'bg-secondary';
        }
    @endphp
    <div class="d-flex justify-content-between my-3">
        <div class="d-flex gap-2">
            <div class="device-state-img {{ $deviceStateClass }}">
                <i class="fa-solid fa-tractor"></i>
            </div>
            @if ($device->activation_time != null)
                <a href="javascript:void({{ $data['imei'] }});" class="text-secondary current-device"
                    data-imei="{{ $data['imei'] }}">
                    <div>
                        <h5 class="device-name">{{ $tractor->no_plate ?? $data['imei'] }}</h5>
                        @if (!empty($tractor) && !empty($tractor->imei))
                            <p class="mb-0 date-time">{{ $tractor->imei }}</p>
                        @else
                            <p class="mb-0 date-time">{{ $device->device_name }}</p>
                        @endif
                    </div>
                </a>
                @if ($tractor)
                    <div class="dropdown me-3">
                        <button class="btn btn-link text-secondary p-0 border-0" type="button" aria-expanded="false"
                            data-imei="{{ $data['imei'] }}" data-old-group="{{ $tractor->group_id }}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                @endif
            @else
                <a href="javascript:void({{ $data['imei'] }});" class="text-secondary" data-imei="{{ $data['imei'] }}">
                    <div style="cursor: no-drop;">
                        <div class="d-flex justify-content-between">
                            <h5 class="device-name">{{ $tractor->no_plate ?? $data['imei'] }}</h5>
                            <p class="mb-0">Inactive</p>
                        </div>
                        @if (!empty($tractor) && !empty($tractor->imei))
                            <p class="mb-0 date-time">{{ $tractor->imei }}</p>
                        @else
                            <p class="mb-0 date-time">{{ $device->device_name }}</p>
                        @endif
                    </div>
                </a>
                @if ($tractor)
                    <div class="dropdown me-3">
                        <button class="btn btn-link text-secondary p-0 border-0" type="button" aria-expanded="false"
                            data-imei="{{ $data['imei'] }}" data-old-group="{{ $tractor->group_id }}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endforeach

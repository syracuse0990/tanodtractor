@php
use App\Models\Device;

if (is_null($state)) {
$state = Device::ALL_DEVICES;
}
@endphp
<div class=" position-relative">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item d-flex" role="presentation">
            <button href="javascript:void(0);" class="nav-link {{$state==Device::ALL_DEVICES ? ' active': '' }}"
                id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all"
                aria-selected="true" data-state='{{Device::ALL_DEVICES}}'
                wire:click="updateData({{Device::ALL_DEVICES}})">
                All</button>
                {{-- <span id="totalCount" class="text-dark pt-2">{{'(0)'}}</span> --}}
        </li>
        <li class="nav-item d-flex" role="presentation">
            <a href="javascript:void(0);" class="nav-link pe-1 {{$state==Device::ONLINE_DEVICES ? ' active': '' }}"
                id="online-tab" data-bs-toggle="tab" data-bs-target="#online" type="button" role="tab"
                aria-controls="online" aria-selected="false" data-state='{{Device::ONLINE_DEVICES}}'
                wire:click="updateData({{Device::ONLINE_DEVICES}})">
                <span>
                    <i class="fa-solid fa-globe"></i>
                </span>
                Online </a><span id="onlineCount" class="text-dark pt-2">{{'(0)'}}</span>
        </li>
        <li class="nav-item d-flex" role="presentation">
            <a href="javascript:void(0);" class="nav-link pe-1 {{$state==Device::OFFLINE_DEVICES ? ' active': '' }}"
                id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline" type="button" role="tab"
                aria-controls="offline" aria-selected="false" data-state='{{Device::OFFLINE_DEVICES}}'
                wire:click="updateData({{Device::OFFLINE_DEVICES}})">
                <span><i class="fa-solid fa-ban"></i></span> Offline</a>
            <span id="offlineCount" class="text-dark pt-2">{{'(0)'}}</span>
        </li>
        <li class="nav-item d-flex" role="presentation">
            <a href="javascript:void(0);" class="nav-link pe-1 {{$state==Device::INACTIVE_DEVICES ? ' active': '' }}"
                id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive" type="button" role="tab"
                aria-controls="inactive" aria-selected="false" data-state='{{Device::INACTIVE_DEVICES}}'
                wire:click="updateData({{Device::INACTIVE_DEVICES}})">
                <span><i class="fa-regular fa-circle-xmark"></i></span> Inactive</a>
            <span id="inactiveCount" class="text-dark pt-2">{{'(0)'}}</span>
        </li>

    </ul>
    <div class="liveView-loader-parent" style="display: none;" id="manualLoader">
        <div class="loader-box text-center">
            <h6>Fetching Data</h6>
            <div class="loader-13"></div>
        </div>
    </div>

    <div class="accordion accordion-flush" id="accordionFlushExample" style="max-height: 62vh; overflow-y: auto;">
        @if ($state == 1)
        @foreach ($groups as $group)
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="flush-headingOne{{ $group->id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#flush-collapseOne{{ $group->id }}" aria-expanded="false"
                    aria-controls="flush-collapseOne{{ $group->id }}">
                    {{ $group->name }}
                </button>
            </h2>
            <div id="flush-collapseOne{{ $group->id }}" class="accordion-collapse collapse"
                aria-labelledby="flush-headingOne{{ $group->id }}" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">
                    <div id="groupDevices{{$group->id}}">
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @else
        <div id="onlineOfflineDevices">
            <div class="filter-section d-flex justify-content-end me-3">
                @if($state != 4)
                <div class="dropdown">
                    <i class="fa-solid fa-filter" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;"></i>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item" id="movingDevices" type="button" wire:click="getFilteredDevices(1)">
                                Moving
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item" id="idleDevices" type="button" wire:click="getFilteredDevices(2)">
                                Idle
                            </a>
                        </li>
                    </ul>
                </div>
                @endif
            </div>

        </div>
        @endif

    </div>
    @push('js')
    <script>

        var isAuto = true;
        document.addEventListener('DOMContentLoaded', function () {
            const manualLoader = document.getElementById('manualLoader');

            // Show loader only for the initial data fetch
            manualLoader.style.display = 'block';
            Livewire.dispatch('apiData');

            // Schedule subsequent data fetches without showing the loader
            // setInterval(() => {
            //     if(isAuto){
            //         manualLoader.style.display = 'none';
            //         Livewire.dispatch('apiData');
            //     }
            // }, 15000);
        });


        document.addEventListener('click', function (event) {
            if (event.target && (event.target.matches('#all-tab') || event.target.matches('#online-tab') || event.target.matches('#offline-tab') || event.target.matches('#inactive-tab'))) {
                manualLoader.style.display = 'block';
                isAuto = false;
                Livewire.dispatch('apiData');
            }else if (event.target && (event.target.matches('#movingDevices') || event.target.matches('#idleDevices'))) {
                manualLoader.style.display = 'block';
                Livewire.dispatch('apiData');
            }
        });

        Livewire.on('dataFetched', function(data) {
            console.log('data :>> ', data);
            let updatedDevices = data[0].updatedDevices;
            let deviceData = data[0].deviceData;
            setTimeout(() => {
                let span1 = $('#onlineCount');
                let span2 = $('#offlineCount');
                let span3 = $('#inactiveCount');
                // let span3 = $('#totalCount');
                let movingDevices = $('#movingDevices');
                let idleDevices = $('#idleDevices');

                let online = data[0].onlineCount;
                let offline = data[0].offlineCount;
                let inactive = data[0].inactiveCount;
                let total = data[0].totalCount;

                let moving = data[0].movingCount;
                let idle = data[0].idleCount;

                span1.text(function(i, text) {
                    return '(' + online + ')';
                });
                span2.text(function(i, text) {
                    return '(' + offline + ')';
                });
                span3.text(function(i, text) {
                    return '(' + inactive + ')';
                });
                movingDevices.text(function(i, text){
                    return 'Moving ('+ moving +')'
                });
                idleDevices.text(function(i, text){
                    return 'Idle ('+ idle +')'
                });
                // span3.text(function(i, text) {
                //     return '(' + total + ')';
                // });
            }, 100);
            //Group Devices
            $.each(updatedDevices, function (group_id, devices) {
                let devicesLength = Object.keys(devices).length;
                if (devicesLength) {
                    $.each(devices, function (index, device) {
                        let imei_no = device.imei_no;
                        let deviceName = device.device_name;

                        // Determine the device state class and icon based on deviceData
                        let deviceStateClass = 'bg-danger'; // Default state
                        let iconClass = 'fa-tractor';

                        if (deviceData && deviceData[imei_no]) {
                            let deviceInfo = deviceData[imei_no];
                            if(deviceInfo.minutes > 8){
                                deviceStateClass = 'bg-danger';
                            }else{
                                if (deviceInfo.status == 0 && deviceInfo.accStatus == 1) {
                                    deviceStateClass = 'bg-warning';
                                } else if (deviceInfo.status == 1 && deviceInfo.speed != 0 && deviceInfo.speed !== null) {
                                    deviceStateClass = 'bg-success';
                                } else if (deviceInfo.status == 0) {
                                    deviceStateClass = 'bg-danger';
                                }
                            }
                        }

                        // Construct HTML string
                        let deviceHtml = `
                            <div class="d-flex justify-content-between my-3">
                                <div class="d-flex gap-2">
                                    <div class="device-state-img ${deviceStateClass}">
                                        <i class="fa-solid ${iconClass}"></i>
                                    </div>
                                    <a href="javascript:void(0);" class="text-secondary current-device" data-imei="${imei_no}">
                                        <div>
                                            <h5 class="device-name">
                                                ${imei_no}
                                            </h5>
                                            ${deviceName ? `<p class="mb-0 date-time">${deviceName}</p>` : ''}
                                        </div>
                                    </a>
                                </div>
                            </div>`;
                        // Append the HTML to the group container
                        setTimeout(() => {
                            let div = $('#groupDevices' + group_id);
                            div.html(function(i, html) {
                                return html + deviceHtml;
                            });
                        }, 100);

                    });
                } else {
                    setTimeout(() => {
                        let div = $('#groupDevices' + group_id);
                        div.html(function(i, html) {
                            return html + '<div class="d-flex justify-content-between my-3 mb-5"><div class="d-flex gap-2">No Data Found</div></div>';
                        });
                    }, 100); // Delay to ensure DOM update
                }
            });

            //State Devices
            let state = data[0].state;
            if(state == 2 || state == 3){
                let onlineOfflineDevices = data[0].deviceList[state];
                let allDevicesLength = Object.keys(onlineOfflineDevices).length;
                if (allDevicesLength) {
                    $.each(onlineOfflineDevices, function (index, device) {
                        let imei_no = device.imei_no;
                        let deviceName = device.device_name;
                        // Determine the device state class and icon based on deviceData
                        let deviceStateClass = 'bg-danger'; // Default state
                        let iconClass = 'fa-tractor';

                        if (deviceData && deviceData[imei_no]) {
                            let deviceInfo = deviceData[imei_no];

                            if(deviceInfo.minutes > 8){
                                deviceStateClass = 'bg-danger';
                            }else{
                                if (deviceInfo.status == 0 && deviceInfo.accStatus == 1) {
                                    deviceStateClass = 'bg-warning';
                                } else if (deviceInfo.status == 1 && deviceInfo.speed != 0 && deviceInfo.speed !== null) {
                                    deviceStateClass = 'bg-success';
                                } else if (deviceInfo.status == 0) {
                                    deviceStateClass = 'bg-danger';
                                }
                            }
                        }

                        // Construct HTML string

                        let onlineOfflineHtml = `
                        <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="accordion-item border-0">
                                            <div class="accordion-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="d-flex gap-2">
                                                        <div class="device-state-img ${deviceStateClass}">
                                                                <i class="fa-solid ${iconClass}"></i>
                                                            </div>
                                                            <a href="javascript:void(0);" class="text-secondary current-device" data-imei="${imei_no}">
                                                            <div>
                                                                <h5 class="device-name">
                                                                    ${imei_no}
                                                                </h5>
                                                                ${deviceName ? `<p class="mb-0 date-time">${deviceName}</p>` : ''}
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        // Append the HTML to the group container
                        setTimeout(() => {
                            let div = $('#onlineOfflineDevices');
                            div.html(function(i, html) {
                                return html + onlineOfflineHtml;
                            });
                        }, 100);

                    });
                }else{
                    setTimeout(() => {
                        let div = $('#onlineOfflineDevices');
                        div.html(function(i, html) {
                            return html + '<div class="d-flex justify-content-between my-3 mb-5"><div class="d-flex gap-2">No Data Found</div></div>';
                        });
                    }, 100); // Delay to ensure DOM update
                }
            }else if(state == 4){
                let onlineOfflineDevices = data[0].deviceList[state];
                let allDevicesLength = Object.keys(onlineOfflineDevices).length;
                console.log(allDevicesLength);
                if (allDevicesLength) {
                    $.each(onlineOfflineDevices, function (index, device) {
                        let imei_no = device.imei_no;
                        let deviceName = device.device_name;
                        // Determine the device state class and icon based on deviceData
                        let deviceStateClass = 'bg-secondary'; // Default state
                        let iconClass = 'fa-tractor';

                        // Construct HTML string

                        let inactiveHtml = `
                        <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="accordion-item border-0">
                                            <div class="accordion-body">
                                                <div class="d-flex justify-content-between">
                                                    <div class="d-flex gap-2">
                                                        <div class="device-state-img ${deviceStateClass}">
                                                                <i class="fa-solid ${iconClass}"></i>
                                                            </div>
                                                            <a href="javascript:void(0);" class="text-secondary" data-imei="${imei_no}">
                                                            <div>
                                                                <h5 class="device-name">
                                                                    ${imei_no}
                                                                </h5>
                                                                ${deviceName ? `<p class="mb-0 date-time">${deviceName}</p>` : ''}
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                        // Append the HTML to the group container
                        setTimeout(() => {
                            let div = $('#onlineOfflineDevices');
                            div.html(function(i, html) {
                                return html + inactiveHtml;
                            });
                        }, 100);

                    });
                }else{
                    setTimeout(() => {
                        let div = $('#onlineOfflineDevices');
                        div.html(function(i, html) {
                            return html + '<div class="d-flex justify-content-between my-3 mb-5"><div class="d-flex gap-2">No Data Found</div></div>';
                        });
                    }, 100); // Delay to ensure DOM update
                }
            }
            isAuto = true;
        });


    </script>
    @endpush
</div>

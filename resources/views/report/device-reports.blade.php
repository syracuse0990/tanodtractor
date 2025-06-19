<x-app-layout title="{{ __('Reports') }}">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card card-default">
                    <div class="card-body">
                        <div style="margin: auto;">
                            <div class="d-flex vsc_roundbox">

                                <div class="progressbar">
                                    <div class="second circle" data-percent="100" data-color="#8486ff">
                                        <strong></strong>
                                        <span>Total : {{ $totalDevices }}</span>
                                    </div>
                                </div>
                                <div class="progressbar">
                                    <div class="second circle" data-percent="{{ number_format(($activeDevices / $totalDevices) * 100, 2) }}" data-color="#5a88fc">
                                        <strong></strong>
                                        <span>Activated : {{ $activeDevices }}</span>
                                    </div>
                                </div>

                                <div class="progressbar">
                                    <div class="second circle" data-percent="{{ number_format(($inactiveDevices / $totalDevices) * 100, 2) }}" data-color="#43dcaf">
                                        <strong></strong>
                                        <span>Inactivated : {{ $inactiveDevices }}</span>
                                    </div>
                                </div>

                                <div class="progressbar">
                                    <div class="second circle" data-percent="{{ number_format(($expiredDevices / $totalDevices) * 100, 2) }}" data-color="#ff976e">
                                        <strong></strong>
                                        <span>Expired : {{ $expiredDevices }}</span>
                                    </div>
                                </div>

                                <div class="progressbar">
                                    <div class="second circle" data-percent="{{ number_format(($expiringSoonDevices / $totalDevices) * 100, 2) }}" data-color="#ff607b">
                                        <strong></strong>
                                        <span>Expiring soon : {{ $expiringSoonDevices }}</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <!-- Activated Devices Table -->
    <div class="row my-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: #5a88fc;">
                    <h5 class="mb-0"><i class="fas fa-tablet-alt me-2"></i>Activated Devices</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="activatedDevicesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Options
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="activatedDevicesDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Export Data</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-filter me-2"></i>Filter Devices</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Device Name</th>
                                    <th scope="col">IMEI</th>
                                    <th scope="col">Total Distance (km)</th>
                                    <th scope="col">Average Speed (km/h)</th>
                                    <th scope="col">Total Trips</th>
                                    <th scope="col">Total Duration (Hr)</th>
                                    <th scope="col" class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activatedDevices as $device)
                                <tr>
                                    <th scope="row" class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tablet-alt text-primary me-2"></i>
                                            <span>{{ $device->device_name }}</span>
                                        </div>
                                    </th>
                                    <td>{{ $device->imei_no }}</td>
                                    <td>{{ $activeDeviceMetrics[$device->id]['total_distance'] }}</td>
                                    <td>{{ $activeDeviceMetrics[$device->id]['average_speed'] }}</td>
                                    <td>{{ $activeDeviceMetrics[$device->id]['total_trips'] ?? 0 }}</td>
                                    <td>{{ $activeDeviceMetrics[$device->id]['total_duration'] ?? 0 }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-success bg-opacity-10 text-white">Active</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                {!! $activatedDevices->appends(request()->except('active_page'))->links('custom-pagination') !!}
            </div>
        </div>
    </div>
    <div class="row my-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: #43dcaf;">
                    <h5 class="mb-0"><i class="fas fa-tablet-alt me-2"></i>Inactivated Devices</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="devicesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Options
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="devicesDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Export Data</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-filter me-2"></i>Filter Devices</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Device Name</th>
                                    <th scope="col">IMEI</th>
                                    <th scope="col">Total Distance (km)</th>
                                    <th scope="col">Average Speed (km/h)</th>
                                    <th scope="col">Total Trips</th>
                                    <th scope="col">Total Duration (Hr)</th>
                                    <th scope="col" class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inActivatedDevices as $device)
                                <tr>
                                    <th scope="row" class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tablet-alt text-primary me-2"></i>
                                            <span>{{ $device->device_name }}</span>
                                        </div>
                                    </th>
                                    <td>{{ $device->imei_no }}</td>
                                    <td>{{ $inActiveDeviceMetrics[$device->id]['total_distance'] }}</td>
                                    <td>{{ $inActiveDeviceMetrics[$device->id]['average_speed'] }}</td>
                                    <td>{{ $inActiveDeviceMetrics[$device->id]['total_trips'] ?? 0 }}</td>
                                    <td>{{ $inActiveDeviceMetrics[$device->id]['total_duration'] ?? 0 }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-success bg-opacity-10 text-white">Inactive</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                {!! $inActivatedDevices->appends(request()->except('inactive_page'))->links('custom-pagination') !!}
            </div>
        </div>
    </div>
    </section>
    @push('js')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.10.2/umd/popper.min.js"></script>
        <script src="https://rawgit.com/kottenator/jquery-circle-progress/1.2.2/dist/circle-progress.js"></script>
        <script>
            $(document).ready(function() {
                function animateElements() {
                    $('.progressbar').each(function() {
                        var elementPos = $(this).offset().top;
                        var topOfWindow = $(window).scrollTop();
                        var percent = $(this).find('.circle').attr('data-percent');
                        var animate = $(this).data('animate');
                        var color = $(this).find('.circle').attr('data-color');
                        if (elementPos < topOfWindow + $(window).height() - 30 && !animate) {
                            $(this).data('animate', true);
                            $(this).find('.circle').circleProgress({
                                // startAngle: -Math.PI / 2,
                                value: percent / 100,
                                size: 350,
                                thickness: 25,
                                fill: {
                                    color: color
                                }
                            }).on('circle-animation-progress', function(event, progress, stepValue) {
                                var displayValue = stepValue * 100;
                                if (displayValue === 100) { // Check if it's an integer
                                    $(this).find('strong').text(Math.round(displayValue) + "%"); // Round to integer
                                } else {
                                    $(this).find('strong').text(displayValue.toFixed(2) + "%"); // Show 2 decimal places
                                }
                                $(this).find('strong').css('color', color);
                            }).stop();
                        }
                    });
                }

                animateElements();
                $(window).scroll(animateElements);
            });
        </script>
    @endpush
</x-app-layout>

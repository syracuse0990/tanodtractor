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
            <div class="row my-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header text-white d-flex justify-content-between align-items-center bg-success">
                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Maintenance Report</h5>
                <div class="d-flex">
                    <div class="dropdown me-2">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-4">Device</th>
                                <th scope="col">IMEI</th>
                                <th scope="col">Total Hours</th>
                                <th scope="col">Total Distance (km)</th>
                                <th scope="col">Last Active</th>
                                <th scope="col">PMS Due</th>
                                <th scope="col" class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenanceData as $device)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tractor text-primary me-2"></i>
                                            <div>
                                                <strong>{{ $device['device_name'] }}</strong>
                                                <div class="text-muted small">{{ $device['vehicleNumber'] ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code>{{ $device['imei'] }}</code></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info text-gray-600" role="progressbar"
                                                 style="width: {{ min(($device['total_hours'] / 100) * 100, 100) }}%"
                                                 aria-valuenow="{{ $device['total_hours'] }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ $device['total_hours'] }} hrs
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-warning text-gray-600" role="progressbar"
                                                 style="width: {{ min(($device['total_distance'] / 1000) * 100, 100) }}%"
                                                 aria-valuenow="{{ $device['total_distance'] }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="1000">
                                                {{ $device['total_distance'] }} km
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($device['last_active'])
                                            {{ \Carbon\Carbon::parse($device['last_active'])->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device['needs_pms'])
                                            <span class="badge bg-danger bg-opacity-10 text-danger">
                                                <i class="fas fa-exclamation-circle me-1"></i> Due Now
                                            </span>
                                        @else
                                            @php
                                                $hoursLeft = 100 - $device['total_hours'];
                                                $kmLeft = 1000 - $device['total_distance'];
                                                $nextPms = min($hoursLeft, $kmLeft);
                                            @endphp
                                            <span class="badge bg-success bg-opacity-10 text-white">
                                                <i class="fas fa-check-circle me-1"></i> {{ ceil($nextPms) }} left
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($device['status'] == '1')
                                            <span class="badge bg-success bg-opacity-10 text-white">
                                                <i class="fas fa-circle me-1"></i> Online
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-white">
                                                <i class="fas fa-circle me-1"></i> Offline
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">No devices found or data unavailable</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light d-flex justify-content-end align-items-center">


                    {{ $maintenanceData->links('pagination::bootstrap-5') }}



            </div>

        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PMS Status</label>
                        <select class="form-select" name="pms_status">
                            <option value="all">All</option>
                            <option value="due">Due for PMS</option>
                            <option value="ok">PMS Not Due</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device Status</label>
                        <select class="form-select" name="device_status">
                            <option value="all">All</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success">Apply Filters</button>
            </div>
        </div>
    </div>
</div>
            {{-- <div class="col-md-12">
                <div class="card card-default">
                    <div class="card-body">
                        <div style="width: 40%; margin: auto;">
                            <canvas id="myPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>
    </section>
    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('myPieChart').getContext('2d');
            var myPieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Documentation', 'Filled','In Progress','Completed','Cancelled'],
                    datasets: [{
                        data: [{{ $data['documentation'] }}, {{ $data['filled'] }}, {{ $data['inprogress'] }}, {{ $data['completed'] }}, {{ $data['cancelled'] }}],
                        backgroundColor: ['#b9b9be','#0d6efd', '#ffc107','#198754','#dc3545'],
                        hoverBackgroundColor: ['#b9b9be','#0d6efd', '#ffc107','#198754','#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let percentage = (value / {{ $data['total'] }} * 100).toFixed(2);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        centerText: {
                            text: '{{ $data['total'] }}',
                            color: '#000', // Default is #000000
                            fontStyle: 'Arial', // Default is Arial
                            sidePadding: 20 // Default is 20 (as a percentage)
                        }
                    }
                },
                plugins: [{
                    id: 'centerText',
                    beforeDraw: function(chart) {
                        var width = chart.width,
                            height = chart.height,
                            ctx = chart.ctx;

                        ctx.restore();
                        var fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";

                        var text = chart.config.options.plugins.centerText.text,
                            textX = Math.round((width - ctx.measureText(text).width) / 2),
                            textY = height / 2;

                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }]
            });
        });
    </script> --}}
    @endpush
</x-app-layout>

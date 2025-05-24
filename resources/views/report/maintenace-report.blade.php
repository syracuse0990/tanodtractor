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
                        <div style="width: 40%; margin: auto;">
                            <canvas id="myPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
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
    </script>
    @endpush
</x-app-layout>
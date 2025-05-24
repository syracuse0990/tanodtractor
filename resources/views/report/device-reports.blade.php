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

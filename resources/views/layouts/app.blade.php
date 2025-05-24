@php
    use App\Models\User;
    use App\Models\Notification;

    $notifications = Notification::where('user_id', Auth::user()->id)
        ->where('is_read', Notification::IS_NOT_READ)
        ->get();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ !empty($attributes['title']) ? $attributes['title'] . ' - ' : '' }}{{ config('app.name', 'Laravel') }}
    </title>

    <!-- Font family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800&family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap"
        rel="stylesheet">

    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- font awesome link -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Styles -->
    @livewireStyles
    <link href="{{ asset('assets/css/index.css?ver=0.008') }}" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <link rel="stylesheet" type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css">

    {{-- slick slider --}}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />

    {{-- Select 2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>

    {{-- date range picker --}}
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style src="{{ asset('assets/ckeditor/ckeditor.css') }}"></style>
    <link href="/assets/css/jquery.multiselect.css?ver=0.001" rel="stylesheet" />
    <link href="{{ asset('/assets/css/intlTelInput.css') }}" rel="stylesheet">
    <!-- CSS for full calender -->
    <!-- bootstrap css and js -->

</head>

<body class="antialiased">

    <div class="main-admin">
        @include('layouts.sidenav')
    </div>

    <div class="main-wrapper position-relative">
        <!-- header start here -->
        <header class="header-wrapper d-flex justify-content-between align-items-center px-3">
            <div class="d-flex align-items-center h-100">
                <button class="navbar-toggler toggle-btn me-3 d-block d-lg-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars navbar-toggler-icon mb-0"></i>
                </button>

            </div>
            <ul class="navbar-nav mob_right_head d-flex align-items-center">
                <li class="nav-item dropdown d-flex align-items-center justify-content-start">
                    {{-- <span class="notification-highlighter position-absolute d-none"></span> --}}
                    <a href="javascript:void(0);" class="btn action_btn p-1 m-0 me-3" id="notificationButton"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none noti-count">0
                        </span>
                        <i class="fa fa-bell"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end px-2 me-sm-n4 notification-dropdown notification-ul-section z-index-3"
                        aria-labelledby="notificationButton">
                        <div class="notification-list-sec"></div>
                        <li class="mb-2 view-all">
                            <div class="d-flex py-1 justify-content-center">
                                <div class="d-flex flex-column justify-content-center">
                                    <a class="border-radius-md" href="{{ route('notifications.index') }}">View All</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown d-flex align-items-center justify-content-start">
                    <a href="javascript:void(0);" class="nav-link p-0 d-flex align-items-center justify-content-start"
                        id="profileButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user_img1">
                            @if (Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="img">
                            @else
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTyFmBCFfrKGnUCXabdJm-oQmQ-fwUU23HOrlYVKqbA1njKWnjVvMAcFhcPYEzXm_ehfNg&usqp=CAU"
                                    alt="img">
                            @endif
                        </span>
                        <span class="ps-2">
                            {{ Auth::user()->name }}
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end px-2  me-sm-n4 profile-dropdown"
                        aria-labelledby="profileButton">
                        <li>
                            <a class="dropdown-item rounded-2" href="{{ url('/profile') }}">
                                <h6 class="text-sm font-weight-normal mb-0">
                                    <span class="font-weight-bold">Profile</span>
                                </h6>
                            </a>
                            <a class="dropdown-item rounded-2" href="{{ url('/password-update') }}">
                                <h6 class="text-sm font-weight-normal  mb-0">
                                    <span class="font-weight-bold">Change Password</span>
                                </h6>
                            </a>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit" class="dropdown-item rounded-2">
                                    <h6 class="text-sm font-weight-normal mb-0">
                                        <span class="font-weight-bold">Logout</span>
                                    </h6>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>

            </ul>
        </header>
        <!-- header end here -->
        <!-- Page Content -->
        <main>
            <div class="main-contnent">
                {{ $slot }}
            </div>
        </main>
        <!-- page content end -->

        <!-- footer start -->
        <div class="footerbar border-top text-center w-100">
            <footer class="footer">
                <p class="mb-0">&copy; {{ date('Y') }} <a
                        href="{{ route('dashboard') }}">{{ env('APP_NAME') }}</a> - All
                    Rights Reserved.</p>
            </footer>
        </div>
        <!-- footer end -->

    </div>

    <div class="notification-alert-wrapp notification_alert_window makemedragable">
    </div>

    <div id="overlay">
        <div class="cv-spinner">
            <span class="spinner"></span>
        </div>
    </div>
    @stack('modals')

    @livewireScripts

    <!-- js file -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/common.js') }}"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/ckeditor/ckeditor.js') }}"></script>
    <script src="/assets/js/jquery.multiselect.js?ver=0.001"></script>
    {{-- <script src="/assets/js/autoclose.js?ver=0.001"></script> --}}
    <script src="/assets/js/intlTelInput.min.js"></script>
    @stack('js')
</body>

</html>

<script>
    if (document.querySelector('.description')) {
        ClassicEditor
            .create(document.querySelector('.description'), {

            })
            .catch(error => {
                console.error(error);
            });
    }

    $(document).ready(function() {

        // getDeviceDetail();

        $(".makemedragable").draggable({
            stop: function(event, ui) {
                let left = (100 * parseFloat(ui.position.left / parseFloat($(this).parent()
                .width()))) + "%";
                let top = (100 * parseFloat(ui.position.top / parseFloat($(this).parent()
                .height()))) + "%";
            }
        });

        $(document).on('click', '#closeAllBtn', function(e) {
            var id = {{ Auth::id() }};
            $.ajax({
                url: '{{ route('notifications.closeAllAlerts') }}',
                type: 'POST',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'user_id': id
                },
                success: function(response) {
                    if (response.status == 'OK') {
                        $('.notification_alert_window').html('');
                        $('.notification_alert_window').addClass('d-none');
                    }
                }
            });
        });

        let currentPage = window.location.pathname;

        // List of pages where the functions should NOT run
        let excludedPages = [
            "/liveview",
        ];

        // Only run the functions if the current page is NOT in the excluded list
        if (!excludedPages.includes(currentPage)) {
            getNotification();
            getAlerts();
            var notificationInterval = setInterval(getNotification, 5000);
            var alertsInterval = setInterval(getAlerts, 15000);
        }

    });

    function getDeviceDetail() {
        $.ajax({
            url: '{{ route('admins.getDeviceDetail') }}',
            type: 'GET',
            success: function(response) {
                //
            }
        });
    }

    function getNotification() {
        $.ajax({
            url: '{{ route('notifications.notification-data') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
            },
            success: function(response) {
                $('.notification-list-sec').html('');
                if (response) { // Checking if response is valid
                    if (response.count == 0) {
                        let html =
                            '<li class="mb-2"><div class="d-flex py-1"><div class="d-flex flex-column justify-content-center"><h6 class="text-sm font-weight-normal mb-1">No New Notifications</h6></div></div></li>';
                        $('.notification-list-sec').append(html);
                        $('.view-all').addClass('d-none');
                    } else {
                        $('.noti-count').removeClass('d-none');
                        $('.noti-count').text(response.count);
                        $.each(response.html, function(index, value) {
                            $('.notification-list-sec').append(value);
                        });
                    }
                } else {
                    let html =
                        '<li class="mb-2"><div class="d-flex py-1"><div class="d-flex flex-column justify-content-center"><h6 class="text-sm font-weight-normal mb-1">No New Notifications</h6></div></div></li>';
                    $('.notification-list-sec').append(html);
                    $('.noti-count').addClass('d-none');
                }
            }
        });
    }

    function getAlerts() {
        $.ajax({
            url: '{{ route('notifications.notification-alert') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
            },
            success: function(response) {
                $('.notification_alert_window').html('');
                $('.notification_alert_window').addClass('d-none');
                if (response.count != 0) {
                    $('.notification_alert_window').removeClass('d-none');
                    jQuery.each(response.html, function(index, value) {
                        var notificationWindow = $('.notification_alert_window');
                        notificationWindow.append(value);
                    });
                    $('.notification_alert_window').prepend(response.clearAll);
                    if (response.closeModalIds) {
                        jQuery.each(response.closeModalIds, function(index, value) {
                            $.ajax({
                                url: '{{ route('notifications.close-alert') }}',
                                type: 'POST',
                                data: {
                                    '_token': '{{ csrf_token() }}',
                                    'id': value
                                },
                                success: function(response) {
                                    if (response.status == 'OK') {
                                        $('#close_alert' + value).remove();
                                    }
                                }
                            });
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Error occurred: ", error);
            }
        });
    }



    function closeAlert(event) {
        var id = $(event).data('id');
        $.ajax({
            url: '{{ route('notifications.close-alert') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': id
            },
            success: function(response) {
                if (response.status == 'OK') {
                    // Remove the parent div when the close button is clicked
                    $('#close_alert' + id).remove();
                }
            }
        });
    }
</script>

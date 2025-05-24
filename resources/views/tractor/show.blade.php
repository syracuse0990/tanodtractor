@php
use App\Models\User;
@endphp
<x-app-layout title="{{ __($tractor->model) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">
                    @if (count($images))
                    <div id="image-slider" class="splide">
                        <div class="splide__track w-50 m-auto">
                            <ul class="splide__list ps-0">

                                @foreach ($images as $image)
                                <li class="splide__slide">
                                    <img src="{{ asset('storage/' . $image?->path) }}" class="img-fluid"
                                        alt="stadium-img">
                                </li>
                                @endforeach

                            </ul>
                        </div>
                    </div>
                    @else
                    No Images
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $tractor->model }}</h2>
                    @if (!in_array(Auth::user()->role_id,[User::ROLE_SUB_ADMIN]))
                    <div>
                        <a href="{{ route('tractors.edit', [$tractor->id]) }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <table cellpadding="7" class="profile-detail-table w-100">
                                <tr>
                                    <td>
                                        <strong class="fw-500"> IMEI <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->imei }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Number Plate <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->no_plate }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> ID Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->id_no }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Engine Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->engine_no }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Fuel/100KM <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->fuel_consumption ? $tractor->fuel_consumption . ' ltr' : 0 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Brand <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->brand }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> First Maintenance Hours <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor->first_maintenance_hr) ? $tractor->first_maintenance_hr . '
                                        Hrs' :'0 Hrs' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Subsequent Maintenance Hours <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor->maintenance_kilometer) ? $tractor->maintenance_kilometer . '
                                        Hrs' :'0 Hrs' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Running Hours <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor->running_km) ? $tractor->running_km . ' Hrs' : '0 Hrs'
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Total Hours <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor->total_distance) ? $tractor->total_distance . ' Hrs' : '0
                                        Hrs'
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Model <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->model ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Manufacture Date <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->manufacture_date ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Time <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->installation_time ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Address <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->installation_address ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> DR Date <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->dr_date ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Actual Delivery Date <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->actual_delivery_date ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> DR Number <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->dr_no ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Front Loader SN <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->front_loader_sn ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Rotary Tiller SN <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->rotary_tiller_sn ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Rotating Disc Plow SN <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->rotating_disc_plow_sn ?? 'NA' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                                    <td>{!! $tractor->getStateLabel() !!} </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                                    <td>{{ $tractor->createdBy?->name }} </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-lg-4">
                            <div class="maintenance_alert_div d-none">
                                <div class="position-relative">
                                    <div
                                        class="alert alert-danger alert-notification-div p-3 shadow me-2 maintenance-alert">
                                        <div class="text-center">
                                            <i
                                                class="fa-solid fa-screwdriver-wrench h1 text-danger my-2 alert-close-icon"></i>
                                        </div>
                                        <div class="title-heading">
                                            <h5 class="text-center">Maintenance Required</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Bookings</h3>
                    </div>
                    {{-- <form id="searchForm" action="{{ route('tractor-groups.show',[$tractorGroup->id]) }}"
                        method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form> --}}
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>

                                    <th>Farmer</th>
                                    <th>Tractor</th>
                                    <th>Device</th>
                                    <th>Date</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $i = 0;
                                @endphp
                                @if (count($bookings))
                                @foreach ($bookings as $booking)
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $booking->createdBy?->name ? $booking->createdBy?->name :
                                        $booking->createdBy?->email }}
                                    <td>{{ $booking->tractor?->id_no . ' (' . $booking->tractor?->model . ')' }}
                                    </td>
                                    <td>{{ $booking->device?->device_name }}
                                    <td>{{ $booking->date }}</td>
                                    <td>{!! $booking->getStateLabel() !!}</td>
                                    <td>{{ $booking->createdBy?->name ?? $booking->createdBy?->email }}</td>
                                    <td class="action-btn">
                                        <a class="btn primary text-success btn-sm me-2 rounded-3"
                                            href="{{ route('tractor-bookings.show', $booking->id) }}"><i
                                                class="fa-solid fa-eye"></i></a>

                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="15" class="text-center">No Records Found</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $bookings->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    <script>
        $('.splide__list').slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 1,
            adaptiveHeight: true
        });

        $(document).ready(function(){
            maintenanceRequired();
        });
        
        function maintenanceRequired(){
            var id = '{{$tractor->id}}';
            $.ajax({
            url: '{{ route('notifications.maintenance-notification') }}',
            type: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
                'id':id
            },
            success: function(response) {
                if(response.status== "ALERT"){
                    $('.maintenance_alert_div').removeClass('d-none');
                }
            }

            })   
        }
    </script>
</x-app-layout>
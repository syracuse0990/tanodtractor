@php
use App\Models\Maintenance;

$images = $tractor->images ??[];
@endphp
<x-app-layout title="{{ __($maintenance->id) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-end align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $maintenance->device_name }}</h2>
                    <a href="{{ route('maintenances.edit', [$maintenance->id]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                    <a href="{{ route('maintenances.edit', [$maintenance->id,'conclusion'=>true]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i
                            class="{{ !$maintenance->conclusion ? 'fa-solid fa-plus' : 'fa-regular fa-pen-to-square' }} me-1"></i>{{
                        !$maintenance->conclusion ? 'Add Conclusion' : 'Edit Conclusion' }}</a>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Date of Maintenance <span class="float-end">:</span></strong>
                            </td>

                            <td>{!! date('Y-m-d h:i A', strtotime($maintenance->maintenance_date)) !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Technician Name <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->tech_name ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Technician Email <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->tech_email ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Technician Number <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->tech_number ?? 'N/A' }} </td>
                        </tr>
                        {{-- <tr>
                            <td><strong class="fw-500">Farmer Name <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->farmer_name ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Farmer Email <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->farmer_email ?? 'N/A' }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Farmer Number <span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->farmer_number ?? 'N/A' }} </td>
                        </tr> --}}
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $maintenance->getStatelabel() !!} </td>
                        </tr>
                        @if ($maintenance->conclusion)
                        <tr>
                            <td><strong class="fw-500">Conclusion<span class="float-end">:</span></strong></td>
                            <td>{{ $maintenance->conclusion ?? 'N/A' }} </td>
                        </tr>
                        @endif
                    </table>
                    @if (!in_array($maintenance->state_id,[Maintenance::STATE_COMPLETED, Maintenance::STATE_CANCELLED]))
                    @livewire('change-status', ['model' => $maintenance])
                    @endif

                </div>
            </div>
        </div>
    </div>
    @if ($tractor)
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
                    <h2 class="card-title mb-0 fw-500"> {{ $tractor->model ?? 'N/A' }}</h2>
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
                                        {{ $tractor->imei ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Number Plate <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->no_plate ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> ID Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->id_no ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Engine Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->engine_no ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Fuel/100KM <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor && $tractor->fuel_consumption ? $tractor->fuel_consumption . ' ltr'
                                        : 0 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Brand <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->brand ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Maintenance Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor && $tractor->maintenance_kilometer) ?
                                        $tractor->maintenance_kilometer . 'km' : 0
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Running Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor && $tractor->running_km) ? $tractor->running_km . ' km' :
                                        '0 km' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Total Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($tractor && $tractor->total_distance) ? $tractor->total_distance . '
                                        km' : '0 km'
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Model <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->model ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Manufacture Date <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->manufacture_date ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Time <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->installation_time ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Address <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $tractor->installation_address ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                                    <td>{!! $tractor ? $tractor->getStateLabel() : 'N/A' !!} </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                                    <td>{{ $tractor->createdBy?->name ?? 'N/A' }} </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endif
    <script>
        $('.splide__list').slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 1,
            adaptiveHeight: true
        });
    </script>
</x-app-layout>
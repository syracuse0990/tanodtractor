<x-app-layout
    title="{{ __(strlen($farmerFeedback->issueType?->title) > 10 ? substr($farmerFeedback->issueType?->title, 0, 10) . '...' : $farmerFeedback->issueType?->title) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">

                    @if (count($farmerFeedback->images))
                    <div id="image-slider" class="splide">
                        <div class="splide__track w-50 m-auto">
                            <ul class="splide__list ps-0">

                                @foreach ($farmerFeedback->images as $image)
                                @php
                                @endphp
                                <li class="splide__slide">
                                    <img src="{{ asset('storage/' . $image?->path) }}" class="img-fluid" alt="image">
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500">
                        {{ strlen($farmerFeedback->issueType?->title) > 40 ? substr($farmerFeedback->issueType?->title,
                        0, 40) : $farmerFeedback->issueType?->title }}
                    </h2>

                    <a href="{{ route('farmer-feedbacks.edit', [$farmerFeedback->id]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i
                            class="{{ !$farmerFeedback->conclusion ? 'fa-solid fa-plus' : 'fa-regular fa-pen-to-square' }} me-1"></i>{{
                        !$farmerFeedback->conclusion ? 'Add Conclusion' : 'Edit Conclusion' }}</a>

                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $farmerFeedback->name }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Email <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $farmerFeedback->email }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Issue Type <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $farmerFeedback->issueType->title ?? 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $farmerFeedback->getStateLabel() !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $farmerFeedback->createdBy?->name ?? $farmerFeedback->createdBy?->email }} </td>
                        </tr>
                    </table>
                    <div class="form-group p-1 pb-0">
                        <strong>Description:</strong>
                        <div class="ck-content">
                            {!! $farmerFeedback->description !!}
                        </div>
                    </div>
                    @if ($farmerFeedback->conclusion)
                    <div class="form-group p-1 pb-0">
                        <strong>Technician Details:</strong>
                        <div class="ck-content">
                            {!! $farmerFeedback->tech_details !!}
                        </div>
                    </div>
                    <div class="form-group p-1 pb-0">
                        <strong>Conclusion:</strong>
                        <div class="ck-content">
                            {!! $farmerFeedback->conclusion !!}
                        </div>
                    </div>
                    @endif
                    @livewire('change-status', ['model' => $farmerFeedback])
                </div>
            </div>
        </div>
    </div>
    @if ($farmerFeedback->tractor)
    <div class="row mt-3">
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">
                    @if (count($farmerFeedback->tractor?->images))
                    <div id="image-slider" class="splide">
                        <div class="splide__track w-50 m-auto">
                            <ul class="splide__list ps-0">

                                @foreach ($farmerFeedback->tractor?->images as $image)
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
                    <h2 class="card-title mb-0 fw-500"> {{ $farmerFeedback->tractor?->model ?? 'N/A' }}</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <table cellpadding="7" class="profile-detail-table w-100">
                                <tr>
                                    <td>
                                        <strong class="fw-500"> ID <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->id ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Number Plate <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->no_plate ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> ID Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->id_no ?? 'N/A'}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Engine Number <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->engine_no ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Fuel/100KM <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor && $farmerFeedback->tractor?->fuel_consumption ?
                                        $farmerFeedback->tractor?->fuel_consumption . ' ltr'
                                        : 0 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Brand <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->brand ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Maintenance Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($farmerFeedback->tractor &&
                                        $farmerFeedback->tractor?->maintenance_kilometer) ?
                                        $farmerFeedback->tractor?->maintenance_kilometer . 'km' : 0
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Running Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($farmerFeedback->tractor && $farmerFeedback->tractor?->running_km) ?
                                        $farmerFeedback->tractor?->running_km . ' km' :
                                        '0 km' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Total Kilometer <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ !empty($farmerFeedback->tractor &&
                                        $farmerFeedback->tractor?->total_distance) ?
                                        $farmerFeedback->tractor?->total_distance . '
                                        km' : '0 km'
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Tractor Model <span class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->model ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Manufacture Date <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->manufacture_date ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Time <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->installation_time ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong class="fw-500"> Installation Address <span
                                                class="float-end">:</span></strong>
                                    </td>
                                    <td>
                                        {{ $farmerFeedback->tractor?->installation_address ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                                    <td>{!! $farmerFeedback->tractor ? $farmerFeedback->tractor?->getStateLabel() :
                                        'N/A' !!} </td>
                                </tr>
                                <tr>
                                    <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                                    <td>{{ $farmerFeedback->tractor?->createdBy?->name ?? 'N/A' }} </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endif
    @push('js')
    <script>
        $('.splide__list').slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 1,
            adaptiveHeight: true
        });
    </script>

    @endpush
</x-app-layout>
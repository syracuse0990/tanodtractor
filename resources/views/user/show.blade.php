@php
use App\Models\User;
use App\Models\TractorBooking;
use App\Models\TotalHours;

@endphp
<x-app-layout title="{{ __($user->name) }}">
    <div class="row">
        <div class="col-lg-12">

            <div class="page-heading">
                <h4>Profile Details</h4>
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card">
                <form method="POST" action="{{ route('users.update-image') }}" role="form"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">

                        <div class="avatar-upload">
                            @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                            <div class="avatar-edit">
                                <input type='file' id="imageUpload" name="profile_photo_path" />
                                <label for="imageUpload"></label>
                            </div>
                            @endif
                            <div class="avatar-preview">
                                <img class="w-100 profile_image"
                                    src="{{ empty($user->profile_photo_path) ? asset('assets/img/user.png') : asset('storage/' . $user->profile_photo_path) }}"
                                    alt="logo">
                                <img class="w-100 chaged_image d-none" id="change_image_id" src="#" alt="your image" />

                            </div>
                        </div>

                        <div class="mt-3 profile-content text-center">
                            <h4 class="fw-500">{{ $user->name }}</h4>
                            <p>{{ $user->getRole() }}</p>
                        </div>

                        <div class="text-center mt-2">
                            {{ Form::hidden('id', $user->id) }}
                            <button class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3 d-none"
                                id="upload_image_btn">
                                <span> <i class="fa-solid fa-camera me-1"></i> </span> Update Image</button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> Personal Information</h2>
                    <div>
                        @if (!in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                        @if (in_array($user->role_id, [User::ROLE_SUB_ADMIN]))
                        <a href="{{ route('tractor-groups.assignIndex', ['id' => $user->id]) }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-plus"></i> Assign Groups</a>
                        @endif

                        <a href="{{ route('users.edit', [$user->id]) }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                        @endif

                    </div>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table">
                        <tr>
                            <td>
                                <strong class="fw-500"> Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $user->name }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Phone <span class="float-end">:</span></strong></td>
                            <td>{{ $user->phone }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Email <span class="float-end">:</span></strong></td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Role <span class="float-end">:</span></strong></td>
                            <td>{{ $user->getRole() }}</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Gender <span class="float-end">:</span></strong></td>
                            <td>{{ $user->getGender() }}</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $user->getStateLabel() !!}</td>
                        </tr>
                        @if ($user->created_at)
                        <tr>
                            <td><strong class="fw-500">Created On <span class="float-end">:</span></strong></td>
                            <td>{{ $user->created_at }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    @if (!in_array($user->role_id,[
    User::ROLE_ADMIN,
    User::ROLE_GOVERNMENT,
    User::ROLE_SUB_ADMIN,
    User::ROLE_SYSTEM_ADMIN
    ]))
    <div class="row mt-4">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead">
                                <tr>
                                    <th class="text-center">Tractor</th>
                                    <th class="text-center">Number of times booked</th>
                                    <th class="text-center">Hours</th>
                                    <th>Reasons</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($tractors as $tractor)
                                @php
                                $bookingCount = TractorBooking::where('tractor_id',$tractor?->id)->count();
                                $bookings = TractorBooking::where(['tractor_id'=>$tractor?->id,
                                'created_by'=>$user->id])->pluck('reason')->toArray();
                                $totalHours = TotalHours::where(['tractor_id'=>$tractor?->id,
                                'user_id'=>$user->id])->select('hours')->first();
                                @endphp
                                <tr>
                                    <td class="text-center">{{$tractor?->id_no. ' ('.$tractor?->model.')' }}</td>
                                    <td class="text-center">{{$bookingCount}}</td>
                                    <td class="text-center">{{$totalHours->hours ?? '0'}}hrs
                                    </td>
                                    <td>
                                        <ol class="ps-0">
                                            @foreach ($bookings as $booking)
                                            @if ($booking)
                                            <li>
                                                <div class="reasons_section">
                                                    {!!$booking!!}
                                                </div>
                                            </li>
                                            @else
                                            <li>
                                                <div class="reasons_section">
                                                    <p>No Resons Found</P>
                                                </div>
                                            </li>

                                            @endif
                                            @endforeach
                                        </ol>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center">No Records Found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $tractors->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    @endif

    <script>
        $('#imageUpload').change(function(e) {
            const file = imageUpload.files[0];
            var extension = file.name.split(".")[1]
            var arrayExtension = ["jpeg", "png", "jpg"];
            if (jQuery.inArray(extension, arrayExtension) > 0) {
                if (file) {
                    change_image_id.src = URL.createObjectURL(file)
                    $('#change_image_id').removeClass('d-none');
                    $('#upload_image_btn').removeClass('d-none');
                    $('.profile_image').addClass('d-none');
                }
            }else{
                Swal.fire({
                    title: "Opps!",
                    text: "File must be an Image(jpeg,png,jpg).",
                    icon: "error"
                }).then(function() {
                    location.reload();
                });
            }
            
        });
    </script>
</x-app-layout>
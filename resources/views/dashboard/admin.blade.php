@php
use App\Models\User;
use App\Models\TractorGroup;
use App\Models\TractorBooking;
use App\Models\FarmerFeedback;

$userCount = User::whereNotIn('role_id', [User::ROLE_ADMIN,User::ROLE_GOVERNMENT,User::ROLE_SYSTEM_ADMIN, User::ROLE_SUB_ADMIN])->count();
$users = User::whereNotIn('role_id', [User::ROLE_ADMIN,User::ROLE_GOVERNMENT,User::ROLE_SYSTEM_ADMIN, User::ROLE_SUB_ADMIN])
->latest('id')
->take(5)
->get();
$groupCount = TractorGroup::count();
$tractorGroups = TractorGroup::latest('id')
->take(5)
->get();
$bookingCount = TractorBooking::where('state_id', TractorBooking::STATE_ACTIVE)->count();
$bookings = TractorBooking::where('state_id', TractorBooking::STATE_ACTIVE)
->latest('id')
->take(5)
->get();
$feedbackCount = FarmerFeedback::where('state_id', TractorBooking::STATE_ACTIVE)->count();
$farmerFeedbacks = FarmerFeedback::where('state_id', TractorBooking::STATE_ACTIVE)
->latest('id')
->take(5)
->get();
@endphp
<x-app-layout>
    <!-- card design  -->
    <div class="row">
        @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>{{ $message }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>{{ $message }}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        <div class="col-sm-12 col-md-6 col-lg-6 col-xl-3 mb-3">
            <a href="{{ route('users.index') }}">
                <div class="card img-card box-primary-shadow border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="text-white">
                                <h2 class="mb-0 number-font">{{ $userCount }}</h2>
                                <p class="text-white mb-0">User{{ $userCount > 1 ? 's' : '' }} </p>
                            </div>
                            <div class="ms-auto"> <i class="fa-solid fa-users text-white fs-30 me-2 mt-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-6 col-xl-3 mb-3">
            <a href="{{ route('tractor-groups.index') }}">
                <div class="card img-card box-secondary-shadow border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="text-white">
                                <h2 class="mb-0 number-font">{{ $groupCount }}</h2>
                                <p class="text-white mb-0">Group{{ $groupCount > 1 ? 's' : '' }} </p>
                            </div>
                            <div class="ms-auto"> <i
                                    class="fa-solid fa-group-arrows-rotate text-white fs-30 me-2 mt-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-6 col-xl-3 mb-3">
            <a href="{{ route('tractor-bookings.booking-list') }}">
                <div class="card img-card box-success-shadow border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="text-white">
                                <h2 class="mb-0 number-font">{{ $bookingCount }}</h2>
                                <p class="text-white mb-0">Booking{{ $bookingCount > 1 ? 's' : '' }} </p>
                            </div>
                            <div class="ms-auto"> <i class="fa-solid fa-calendar-check text-white fs-30 me-2 mt-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-12 col-md-6 col-lg-6 col-xl-3 mb-3">
            <a href="{{ route('farmer-feedbacks.index') }}">
                <div class="card img-card box-info-shadow border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="text-white">
                                <h2 class="mb-0 number-font">{{ $feedbackCount }}</h2>
                                <p class="text-white mb-0">Report{{ $feedbackCount > 1 ? 's' : '' }} </p>
                            </div>
                            <div class="ms-auto"> <i
                                    class="fa-sharp fa-solid fa-comments text-white fs-30 me-2 mt-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <!-- COL END -->
    </div>
    <!-- card design end -->

    <!-- table deisgn  -->
    <div class="row mt-4">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Users</h3>
                    </div>
                    <a href="{{ route('users.index') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="me-1"></i>View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Gender</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $i = 0;
                                @endphp
                                @if (count($users))
                                @foreach ($users as $user)
                                <tr>
                                    <td>{{ ++$i }}</td>

                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone }}</td>
                                    <td>{{ $user->getRole() }}</td>
                                    <td>{{ $user->getGender() }}</td>
                                    <td>{!! $user->getStateLabel() !!}</td>
                                    <td class="action-btn">
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('users.show', $user->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('users.edit', $user->id) }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn danger text-danger btn-sm rounded-3"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </form>
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
        </div> <!-- COL END -->
    </div>
    <div class="row mt-4">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Groups</h3>
                    </div>
                    <a href="{{ route('tractor-groups.index') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="me-1"></i>View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $i = 0;
                                @endphp
                                @if (count($tractorGroups))
                                @foreach ($tractorGroups as $tractorGroup)
                                <tr>
                                    <td>{{ ++$i }}</td>

                                    <td>{{ $tractorGroup->name }}</td>
                                    <td>{!! $tractorGroup->getStateLabel() !!}</td>
                                    <td>{{ $tractorGroup->createdBy?->name }}</td>


                                    <td class="action-btn">
                                        <form action="{{ route('tractor-groups.destroy', $tractorGroup->id) }}"
                                            method="POST">
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('tractor-groups.show', $tractorGroup->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('tractor-groups.edit', $tractorGroup->id) }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn danger text-danger btn-sm rounded-3"><i
                                                    class="fa-solid fa-trash-can"></i></button>
                                        </form>
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
        </div> <!-- COL END -->
    </div>
    <div class="row mt-4">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Active Bookings</h3>
                    </div>
                    <a href="{{ route('tractor-bookings.booking-list') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="me-1"></i>View All</a>
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
        </div> <!-- COL END -->
    </div>
    <div class="row mt-4">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Reports</h3>
                    </div>
                    <a href="{{ route('farmer-feedbacks.index') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="me-1"></i>View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Issue Type</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $i = 0;
                                @endphp
                                @if (count($farmerFeedbacks))
                                @foreach ($farmerFeedbacks as $farmerFeedback)
                                <tr>
                                    <td>{{ ++$i }}</td>

                                    <td>{{ $farmerFeedback->name }}</td>
                                    <td>{{ $farmerFeedback->email }}</td>

                                    <td>{{ strlen($farmerFeedback->issueType?->title) > 10 ?
                                        substr($farmerFeedback->issueType?->title, 0, 10) . '...' :
                                        $farmerFeedback->issueType?->title }}
                                    </td>
                                    <td>{!! $farmerFeedback->getStateLabel() !!}</td>
                                    <td>{{ $farmerFeedback->createdBy?->name ?? $farmerFeedback->createdBy?->email }}
                                    </td>

                                    <td class="action-btn">
                                        <a class="btn primary text-success btn-sm me-2 rounded-3"
                                            href="{{ route('farmer-feedbacks.show', $farmerFeedback->id) }}"><i
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
        </div> <!-- COL END -->
    </div>
</x-app-layout>
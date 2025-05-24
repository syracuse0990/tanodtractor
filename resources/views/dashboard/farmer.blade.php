@php
    use App\Models\User;

    $userCount = User::where('role_id', '!=', User::ROLE_ADMIN)->count();
    $users = User::where('role_id', '!=', User::ROLE_ADMIN)
        ->latest('id')
        ->take(5)
        ->get();
@endphp
<x-app-layout>
    <!-- card design  -->
    <div class="row">
        <div class="col-sm-12 col-md-6 col-lg-6 col-xl-3 mb-3">
            <div class="card img-card box-primary-shadow border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="text-white">
                            <h2 class="mb-0 number-font">{{ $userCount }}</h2>
                            <p class="text-white mb-0">User{{ $userCount > 1 ? 's' : '' }} </p>
                        </div>
                        <div class="ms-auto"> <i class="fa-regular fa-paper-plane text-white fs-30 me-2 mt-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- COL END -->
    </div>
    <!-- card design end -->
</x-app-layout>

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
            <a href="{{ route('users.index') }}">
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
            </a>
        </div>
        <!-- COL END -->
    </div>
    <!-- card design end -->

    <!-- table deisgn  -->
    <div class="row">
        <div class="col-12 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0 fw-500">Users</h3>
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
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                @endphp
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ ++$i }}</td>

                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->phone }}</td>
                                        <td>{{ $user->getRole() }}</td>
                                        <td>{{ $user->getGender() }}</td>
                                        <td>{!! $user->getStateLabel() !!}</td>
                                        <td>{{ $user->created_by }}</td>

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
                                                <button type="submit"
                                                    class="btn danger text-danger btn-sm rounded-3"><i
                                                        class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <!-- COL END -->
    </div>
</x-app-layout>

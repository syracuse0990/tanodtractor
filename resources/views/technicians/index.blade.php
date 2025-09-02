@php
    use App\Models\User;
@endphp
<x-app-layout title="{{ request()->is('sub-admin') ? 'Sub Admin' : 'Farmer groups/Recipients' }}">
    <div class="row">
        <div class="col-12">
            {{-- Flash Messages --}}
            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($message = Session::get('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i>{{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3 align-items-center justify-content-between w-100">
                        <h3 class="card-title mb-0 fw-semibold">
                            <i class="fa-solid fa-users-gear me-2 text-success"></i>Technicians
                        </h3>
                        @if (request()->is('sub-admin') || request()->is('technicians'))
                            <a href="{{ route('users.create') }}"
                                class="btn btn-success btn-sm rounded-pill px-4">
                                <i class="fa-regular fa-plus me-1"></i>Add
                            </a>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        @if (!request()->is('sub-admin') && !in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && !request()->is('technicians') && !in_array(Auth::user()->role_id, [User::ROLE_TECHNICIANS]))
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#importUsersModal">
                                <i class="fa-solid fa-file-import me-1"></i> Import
                            </button>
                            <a class="btn btn-success btn-sm" href="{{ route('users.export-farmers') }}">
                                <i class="fa-solid fa-file-export me-1"></i> Export
                            </a>
                            <a class="btn btn-success btn-sm d-none" id="download_csv"
                                href="{{ route('users.download-farmers') }}">
                                <i class="fa-solid fa-download me-1"></i> Download
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-striped table-bordered align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Assigned Area</th>
                                    <th>State</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $index => $user)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->phone }}</td>
                                        <td>{{ $user->getRole() }}</td>
                                        <td>{{ $user->tractor_groups[0]['name'] }}</td>
                                        <td>{!! $user->getStateLabel() !!}</td>
                                        <td class="text-center">
                                            @if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                                <a class="btn btn-outline-success btn-sm me-1"
                                                    href="{{ route('users.show', $user->id) }}">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            @else
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                    class="d-inline">
                                                    <a class="btn btn-outline-success btn-sm me-1"
                                                        href="{{ route('users.show', $user->id) }}">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('users.edit', $user->id) }}"
                                                        class="btn btn-outline-primary btn-sm me-1">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-outline-danger btn-sm">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTables --}}
    @push('css')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    @endpush

    @push('js')
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

        <script>
            $(document).ready(function() {
                $('#usersTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'copy', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'csv', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'excel', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'pdf', className: 'btn btn-sm btn-outline-secondary' },
                        { extend: 'print', className: 'btn btn-sm btn-outline-secondary' }
                    ],
                    pageLength: 10,
                    order: [[0, 'asc']]
                });
            });
        </script>
    @endpush
</x-app-layout>

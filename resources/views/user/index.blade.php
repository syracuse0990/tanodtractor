@php
    use App\Models\User;
@endphp
<x-app-layout title="{{ request()->is('sub-admin') ? 'Sub Admin' : 'Farmer groups/Recepients' }}">
    <div class="row">
        <div class="col-12 col-sm-12">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3">
                        <h3 class="card-title mb-0 fw-500">
                            {{ request()->is('sub-admin')
                                ? 'Sub Admin'
                                : 'Farmer groups/Recepients' }}
                        </h3>
                        @if (request()->is('sub-admin'))
                            <div>
                                <a href="{{ route('users.create') }}"
                                    class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                                    <i class="fa-regular fa-plus me-1"></i>Add</a>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <form id="searchForm" action="{{ route('users.index') }}" method="get">
                            <div class="search-filter-box w-100">
                                <input id="searchField" type="text" class="form-control form-control-sm"
                                    name="search" placeholder="search..." onchange="javascript:this.form.submit();"
                                    value="{{ $search }}">
                            </div>
                        </form>
                        @if (!request()->is('sub-admin') && !in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                            <div class="">
                                <button class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#importUsersModal">Import</button>
                            </div>
                            <div class="">
                                <a class="btn btn-success" href="{{ route('users.export-farmers') }}">Export</a>
                            </div>
                            <div class="">
                                <a class="btn btn-success float-end d-none" id="download_csv"
                                    href="{{ route('users.download-farmers') }}">Download</a>
                            </div>
                        @endif
                    </div>
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
                                    {{-- <th>Gender</th> --}}
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($users))
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                           
                                            <td>{{ $user->phone }}</td>
                                            <td>{{ $user->getRole() }}</td>
                                            {{-- <td>{{ $user->getGender() }}</td> --}}
                                            <td>{!! $user->getStateLabel() !!}</td>
                                            <td class="action-btn">
                                                @if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
                                                    <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                        href="{{ route('users.show', $user->id) }}"><i
                                                            class="fa-solid fa-eye"></i></a>
                                                @else
                                                    <form action="{{ route('users.destroy', $user->id) }}"
                                                        method="POST">
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
                                                @endif

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
            {!! $users->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    <!--Import User Modal -->
    <div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importUsersModalLabel">Import Data</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="javascript:void(0);" class="text-muted" title="Click to watch demo." id="playDemoBtn">
                            <i class="fa-solid fa-circle-play fs-5"></i>
                        </a>
                        <a href="{{ route('users.getFormat') }}" class="text-muted" title="Click to download Format.">
                            <i class="fa-solid fa-circle-info fs-5"></i>
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <form action="{{ route('users.import') }}" method="POST" id="importForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x-label for="fileInput" value="{{ __('Choose File:') }}" />
                        <input id="fileInput" type="file" class="form-control" name="fileInput" autofocus
                            autocomplete="off" />
                        <div id="fileInput_error" class="invalid-feedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Demo Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <video id="demoVideo" controls class="w-100">
                        <source src="{{ asset('assets/demos/all-import-demo.webm') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </div>
    @push('js')
        <script>
            document.getElementById('playDemoBtn').addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default behavior of the link

                // Close the Import Modal
                $('#importUsersModal').modal('hide');

                // Open the Video Modal
                $('#videoModal').modal('show');
            });

            $('document').ready(function() {
                $('#importForm').on('submit', function(e) {
                    e.preventDefault();
                    $("#overlay").fadeIn(300);
                    let formData = new FormData(this);
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('users.import') }}',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            $('#importUsersModal').toggleClass('show');
                            $("#overlay").fadeOut(300);
                            if (response.error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.error,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Import request has been added to the queue. Please check back shortly.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Reload page
                                setTimeout(function() {
                                    location.reload();
                                }, 2000); // 1500 milliseconds
                            }
                        },
                        error: function(xhr) {
                            $("#overlay").fadeOut(300);
                            let errors = xhr.responseJSON.errors;
                            $('.form-control').removeClass("is-invalid");
                            $('.invalid-feedback').empty();
                            if (errors) {
                                for (let key in errors) {
                                    $('#' + key).addClass("is-invalid");
                                    $('#' + key + '_error').html(errors[key][0]);
                                }
                            } else {
                                let message = xhr.responseJSON.message;
                                $('#fileInput').addClass("is-invalid");
                                $('#fileInput_error').html(message);
                            }
                        }
                    });
                });
            });

            function checkFile() {
                $.ajax({
                    url: '{{ route('users.check-farmers-file') }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.status == 'OK') {
                            $('#download_csv').removeClass('d-none');
                        } else {
                            $('#download_csv').addClass('d-none');
                        }
                    }
                })
            }
            var download = setInterval(checkFile, 1000);
        </script>
    @endpush
</x-app-layout>

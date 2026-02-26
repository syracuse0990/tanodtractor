@php
    use App\Models\User;
@endphp
<x-app-layout title="{{ request()->is('sub-admin') ? 'Sub Admin' : 'Tractor Groups' }}">
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
                            {{ request()->is('sub-admin') ? 'Sub Admin' : 'Tractor Groups' }}
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
                        <form id="searchForm" action="{{ route('tractor-groups.index') }}" method="get">
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
                            <div class=""><a class="btn btn-success"
                                    href="{{ route('users.export-farmers') }}">Export</a></div>
                            <div class="">
                                <a class="btn btn-success float-end d-none" id="download_csv"
                                    href="{{ route('users.download-farmers') }}">Download</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div class="table-responsive tractor-group-table-wrap">
                        <table class="table table-hover align-middle mb-0 tractor-group-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">No</th>
                                    <th>Group Name</th>
                                    <th class="text-center text-nowrap">List of Members</th>
                                    <th class="text-nowrap">State</th>
                                    <th class="text-nowrap">Created By</th>
                                    <th class="text-center text-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($tractorGroups))
                                    @foreach ($tractorGroups as $tractorGroup)
                                        <tr>
                                            <td class="text-muted fw-semibold">{{ ++$i }}</td>

                                            <td class="fw-semibold">{{ $tractorGroup->name }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('users.export-farmers', ['id' => $tractorGroup->id]) }}"
                                                    class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                                    <i class="fa-solid fa-download me-1"></i>Export
                                                </a>
                                            </td>
                                            <td>{!! $tractorGroup->getStateLabel() !!}</td>
                                            <td class="text-muted">{{ $tractorGroup->createdBy?->name ?? 'N/A' }}</td>

                                            <td class="text-center action-btn">
                                                <a class="btn btn-sm btn-outline-success rounded-circle d-inline-flex align-items-center justify-content-center view-btn"
                                                    href="{{ route('tractor-groups.show', $tractorGroup->id) }}"
                                                    title="View group">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted d-inline-flex align-items-center gap-2">
                                                <i class="fa-regular fa-folder-open"></i>
                                                <span>No records found</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $tractorGroups->appends(request()->except('page'))->links('custom-pagination') !!}
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
    @push('css')
        <style>
            .tractor-group-table-wrap {
                border-top: 1px solid #f1f3f7;
                border-left: 1px solid #f1f3f7;
                border-right: 1px solid #f1f3f7;
                border-bottom: 1px solid #f1f3f7;
                border-radius: 0.6rem;
            }

            .tractor-group-table thead th {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.02em;
                color: #6c757d;
                border-bottom: 1px solid #e9ecef;
                padding: 1rem 1.2rem;
                text-transform: uppercase;
            }

            .tractor-group-table tbody td {
                padding: 1rem 1.2rem;
                border-color: #f1f3f7;
                vertical-align: middle;
                line-height: 1.35;
            }

            .tractor-group-table tbody tr:hover {
                background-color: #f8fafc;
            }

            .tractor-group-table .view-btn {
                width: 30px;
                height: 30px;
                padding: 0;
            }
        </style>
    @endpush
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

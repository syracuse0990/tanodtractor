@php
use App\Models\User;
@endphp
<x-app-layout title="{{ __('Maintenances') }}">
    <div class="row">
        <div class="col-12 col-sm-12">
            @if ($message = Session::get('success'))
            <div class="alert alert-success">
                <p>{{ $message }}</p>
            </div>
            @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Maintenances</h3>
                        <a href="{{ route('maintenances.create') }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-plus me-1"></i>Create</a>
                    </div>
                    {{-- <form id="searchForm" action="{{ route('tractor-groups.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form> --}}
                </div>

                <div class="card-body">
                    <div class="border-bottom">
                        <div class="d-flex justify-content-end gap-3 mb-2 align-items-start">
                            @if (!request()->is('sub-admin') && !in_array(Auth::user()->role_id,[User::ROLE_SUB_ADMIN]))
                            <div class="">
                                <button class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#importMaintenanceModal">Import</button>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Tractor</th>
                                    <th>Date of Maintenance</th>
                                    <th>Technician Name</th>
                                    <th>Technician Email</th>
                                    <th>Technician Number</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($maintenances))
                                @foreach ($maintenances as $maintenance)
                                @php
                                $tractorName = $maintenance->tractor?->id_no ?? null;
                                if($maintenance->tractor?->model){
                                $tractorName = $maintenance->tractor?->id_no . ' (' . $maintenance->tractor?->model .
                                ')';
                                }
                                if($maintenance->tractor?->imei){
                                $tractorName = $maintenance->tractor?->id_no . ' (' . $maintenance->tractor?->model . ')
                                ['. $maintenance->tractor?->imei .']';
                                }
                                @endphp
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>
                                        @if ($maintenance->tractor)
                                        <a href="{{route('tractors.show',$maintenance->tractor?->id)}}"
                                            class="text-dark">{{$tractorName}}</a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                    <td>{{ date('Y-m-d', strtotime($maintenance->maintenance_date)) }}</td>
                                    <td>{{ $maintenance->tech_name ?? 'N/A' }}</td>
                                    <td>{{ $maintenance->tech_email ?? 'N/A' }}</td>
                                    <td>{{ '+'.$maintenance->tech_phone_code . ' '.$maintenance->tech_number ?? 'N/A' }}
                                    <td>{!! $maintenance->getStateLabel() !!}</td>
                                    <td>{{ $maintenance->createdBy?->name }}</td>
                                    <td class="action-btn">
                                        <form action="{{ route('maintenances.destroy', $maintenance->id) }}"
                                            method="POST">
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('maintenances.show', $maintenance->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('maintenances.edit', $maintenance->id) }}"
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
            {!! $maintenances->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>

    <!--Import Maintenance Modal -->
    <div class="modal fade" id="importMaintenanceModal" tabindex="-1" aria-labelledby="importMaintenanceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importMaintenanceModalLabel">Import Data</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="javascript:void(0);" class="text-muted" title="Click to watch demo." id="playDemoBtn">
                            <i class="fa-solid fa-circle-play fs-5"></i>
                        </a>
                        <a href="{{route('maintenances.getFormat')}}" class="text-muted"
                            title="Click to download Format.">
                            <i class="fa-solid fa-circle-info fs-5"></i>
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <form action="{{route('maintenances.import')}}" method="POST" id="importForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x-label for="fileInput" value="{{ __('Chose File:') }}" />
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
                        <source src="{{ asset('assets/demos/maintenance-import-demo.webm') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </div>

    @if ($importInfo)
    <div class="download_dialouge" id="warehouse_download_dialouge">
        <div class="position-relative p-4">
            <h3>{{ substr($importInfo?->file_name, 0, 20) }}</h3>
            <div id="progressBar">
                <p class="text-muted mb-1"><span id="import_progress_view">{{ $importInfo?->progress ?? 0 }}%</span> /
                    100%
                </p>
                <div class="progress mb-3">
                    <div class="progress-bar bg-danger" id="importProgressBar" role="progressbar"
                        style="width: {{ $importInfo?->progress }}%" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <h6 class="text-center text-success successMessage {{$importInfo?->progress == 100 ? '' : 'd-none'}}">File
                imported successfully!!</h6>
            <div class="text-center">
                <button class="btn btn-success btn-sm close-progress {{$importInfo?->progress == 100 ? '' : 'd-none'}}"
                    data-type='{{ $importInfo?->type_id }}'> {{ __('Ok') }}</button>
            </div>
            <span class="cross position-absolute close-progress {{$importInfo?->progress == 100 ? '' : 'd-none'}}"
                data-type='{{ $importInfo?->type_id }}'>
                <i class="fa-solid fa-xmark"></i>
            </span>
        </div>
    </div>
    @endif

    @push('js')
    <script>
        document.getElementById('playDemoBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default behavior of the link

            // Close the Import Modal
            $('#importMaintenanceModal').modal('hide');

            // Open the Video Modal
            $('#videoModal').modal('show');
        });
        $('document').ready(function(){
            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                $("#overlay").fadeIn(300);
                let formData = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: '{{ route('maintenances.import') }}',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#importTractorModal').toggleClass('show');
                        $("#overlay").fadeOut(300);
                        if(response.error){
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.error,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }else{
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

        $(document).on('click','.close-progress',function(){
            let type = $(this).data('type');
            if(type && type == 9){
                $.ajax({
                    type: 'POST',
                    url: '{{ route('maintenances.closeProgress') }}',
                    data: {
                        '_token':'{{csrf_token()}}',
                        'type':type
                    },
                    success: function(response) {
                        if(response.status=='OK'){
                            location.reload();
                        }else{
                            console.log('response :>> ', response);
                        }
                    },
                    
                });
            }else{
                alert('Something went wrong!!');
            }
        });

        function updateProgress() {
            let type = 9;
            $.ajax({
                url: '{{ route('maintenances.ImportStatus') }}',
                type: 'GET',
                data: {
                    '_token':'{{csrf_token()}}',
                    'type':type
                },
                success: function(response) {
                    if (response.progress == 100) {
                        // $.ajax({
                        //     type: 'POST',
                        //     url: '{{ route('maintenances.closeProgress') }}',
                        //     data: {
                        //         '_token':'{{csrf_token()}}',
                        //         'type':type
                        //     },
                        //     success: function(response) {
                        //         if(response.status=='OK'){
                        //             location.reload();
                        //         }else{
                        //             console.log('response :>> ', response);
                        //         }
                        //     },
                        // });
                        $('.successMessage').removeClass('d-none');
                        $('.close-progress').removeClass('d-none');
                        $('#importProgressBar').css('width', response.progress + '%');
                        $('#import_progress_view').text(response.progress + '%');
                    }else{
                        $('#importProgressBar').css('width', response.progress + '%');
                        $('#import_progress_view').text(response.progress + '%');
                    }
                }
            });
        }
        @if ($importInfo)
            var progress = setInterval(updateProgress, 1000);
        @endif
    </script>
    @endpush
</x-app-layout>
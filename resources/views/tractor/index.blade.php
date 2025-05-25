@php
use App\Models\User;
@endphp
<x-app-layout title="{{ __('Tractors') }}">
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
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Tractors</h3>
                    </div>
                    <form id="searchForm" action="{{ route('tractors.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="border-bottom">
                        <div class="d-flex justify-content-end gap-3 mb-2 align-items-start">
                            <form action="{{ route('tractors.export') }}" method="get"
                                class="d-flex align-items-start tractor-multiselect-drop">
                                @csrf
                                <select class="w-100 hidden-select" id="tractor_ids" name="tractor_ids[]"
                                    autocomplete="tractor_ids" multiple>
                                    @foreach ($tractorList as $key => $value)
                                    @php
                                    $tractorName = $value ? $value?->id_no : null;
                                    if($tractorName && $value?->model){
                                    $tractorName = $value?->id_no . ' (' . $value?->model . ')';
                                    }
                                    @endphp
                                    <option value={{$value->id}}>{{$tractorName ?? $value?->no_plate }}</option>
                                    @endforeach
                                </select>

                                <button class="btn btn-success ms-2" type="submit">Export</button>
                            </form>
                            <button class="btn btn-success ms-2 import-button" type="btn">Import</button>
                            <a class="btn btn-success float-end d-none" id="download_csv"
                                href="{{ route('tractors.download') }}">Download</a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>IMEI</th>
                                    <th>Number Plate</th>
                                    <th>Id Number</th>
                                    <th>Fuel/100km</th>
                                    <th>Tractor Brand</th>
                                    <th>Tractor Model</th>
                                    <th>Installation Time</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($tractors))
                                @foreach ($tractors as $tractor)
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>
                                        {{ $tractor->imei ?? 'N/A' }}
                                    </td>
                                    <td>{{ $tractor->no_plate }}</td>
                                    <td>{{ $tractor->id_no }}</td>
                                    <td>{{ $tractor->fuel_consumption ? $tractor->fuel_consumption . ' ltr' : 0 }}
                                    </td>
                                    <td>{{ $tractor->brand }}</td>
                                    <td>{{ $tractor->model }}</td>
                                    <td>{{ $tractor->installation_time ? date('d-M-Y h:iA', strtotime($tractor->installation_time)) : 'NA' }}
                                    </td>
                                    <td>{!! $tractor->getStateLabel() !!}</td>
                                    <td>{{ $tractor->createdBy?->name }}</td>
                                    <td class="action-btn">
                                        @if (!in_array(Auth::user()->role_id,[User::ROLE_SUB_ADMIN]))
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('tractors.show', $tractor->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('tractors.edit', $tractor->id) }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                        @else
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('tractors.show', $tractor->id) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
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
            {!! $tractors->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>

    <!--Import Tractor Modal -->
    <div class="modal fade" id="importTractorModal" tabindex="-1" aria-labelledby="importTractorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importTractorModalLabel">Import Data</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="javascript:void(0);" class="text-muted" title="Click to watch demo." id="playDemoBtn">
                            <i class="fa-solid fa-circle-play fs-5"></i>
                        </a>
                        <a href="{{route('tractors.getFormat')}}" class="text-muted" title="Click to download Format.">
                            <i class="fa-solid fa-circle-info fs-5"></i>
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <form action="{{route('tractors.import')}}" method="POST" id="importForm" enctype="multipart/form-data">
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
                        <source src="{{ asset('assets/demos/tractor-import-demo.webm') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importTractor" action="{{route('tractors.newImport')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Select File (XLSX, CSV)</label>
                            <input class="form-control" type="file" id="importFile" name="import_file" accept=".xlsx,.csv" required>
                        </div>

                        <div class="import-progress" style="display: none;">
                            <div class="progress mb-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Importing...</span>
                                </div>
                                <p class="mt-2">Processing your file, please wait...</p>
                            </div>
                        </div>
                        <div id="importMessage" class="alert" style="display: none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitImport">Import</button>
                    </div>
                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
    $(document).ready(function() {
            $('.import-button').click(function() {
                $('#importModal').modal('show');
                resetImportForm();
            });

            $('#importTractor').submit(async function(e) {
                e.preventDefault();

                const fileInput = document.getElementById('importFile');
                const file = fileInput.files[0];

                if (!file) {
                    alert('Please select a file first');
                    return;
                }

                try {
                    $('.import-progress').show();
                    $('#submitImport').prop('disabled', true);
                    $('.progress-bar').css('width', '0%');

                    const data = await readExcelFile(file);

                    const formData = new FormData();
                    formData.append('_token', $('input[name="_token"]').val());
                    formData.append('excel_data', JSON.stringify(data));

                    formData.append('import_file', file);

                    $.ajax({
                        url: $('#importTractor').attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        xhr: function() {
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    var percent = Math.round((e.loaded / e.total) * 100);
                                    $('.progress-bar').css('width', percent + '%');
                                    $('#progress-text').text(percent + '% Uploaded');
                                }
                            }, false);

                            xhr.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    var percent = Math.round((e.loaded / e.total) * 100);
                                    $('.processing-bar').css('width', percent + '%');
                                    $('#processing-text').text(percent + '% Processed');
                                }
                            }, false);

                            return xhr;
                        },
                        success: function(response) {
                            $('.progress-bar').css('width', '100%');

                            $('#importMessage').removeClass('alert-danger').addClass('alert-success')
                                .html('<i class="fas fa-check-circle"></i> Import completed successfully!')
                                .show();

                            setTimeout(function() {
                                resetImportForm();
                                $('#importModal').modal('hide');
                                window.location.replace(window.location.href);
                            }, 3000);
                        },
                        error: function(xhr) {
                            $('.progress-bar').css('width', '100%').removeClass('progress-bar-animated').addClass('bg-danger');

                            var errorMessage = xhr.responseJSON?.message || 'An error occurred during import.';
                            $('#importMessage').removeClass('alert-success').addClass('alert-danger')
                                .html('<i class="fas fa-exclamation-circle"></i> ' + errorMessage)
                                .show();

                            $('#submitImport').prop('disabled', false);
                        }
                    });
                } catch (error) {
                    console.error('Error reading file:', error);
                    $('#importMessage').removeClass('alert-success').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-circle"></i> Error reading file: ' + error.message)
                        .show();
                    $('#submitImport').prop('disabled', false);
                    $('.import-progress').hide();
                }
            });

            // Function to read Excel file
            function readExcelFile(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        try {
                            const data = new Uint8Array(e.target.result);
                            const workbook = XLSX.read(data, { type: 'array' });

                            // Get the first worksheet
                            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

                            // Convert to JSON
                            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                            resolve(jsonData);
                        } catch (error) {
                            reject(error);
                        }
                    };

                    reader.onerror = function(error) {
                        reject(error);
                    };

                    reader.readAsArrayBuffer(file);
                });
            }

            // Reset form when modal is closed
            $('#importModal').on('hidden.bs.modal', function() {
                resetImportForm();
            });

            function resetImportForm() {
                $('#importForm')[0].reset();
                $('.import-progress').hide();
                $('#importMessage').hide();
                $('#submitImport').prop('disabled', false);
                $('.progress-bar').css('width', '0%')
                    .removeClass('bg-danger')
                    .addClass('progress-bar-animated');
            }
        });
        document.getElementById('playDemoBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default behavior of the link

            // Close the Import Modal
            $('#importTractorModal').modal('hide');

            // Open the Video Modal
            $('#videoModal').modal('show');
        });
        function checkFile(){
            $.ajax({
                url: '{{ route('tractors.check-file') }}',
                type: 'GET',
                success: function(response) {
                    if(response.status == 'OK'){
                        $('#download_csv').removeClass('d-none');
                    }else{
                        $('#download_csv').addClass('d-none');
                    }
                }
            })
        }
        @if ($exportInfo)
        var download =  setInterval(checkFile, 10000);
        @endif

        $('#tractor_ids').multiselect({
            search: true,
            selectAll: true,
            texts: {
                placeholder: 'Select Tractor',
                search: 'Search'
            }
        });

        $('document').ready(function(){
            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                $("#overlay").fadeIn(300);
                let formData = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: '{{ route('tractors.import') }}',
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
            if(type && type == 7){
                $.ajax({
                    type: 'POST',
                    url: '{{ route('tractors.closeProgress') }}',
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
            }
        });

        function updateProgress() {
            let type = 7;
            $.ajax({
                url: '{{ route('tractors.ImportStatus') }}',
                type: 'GET',
                data: {
                    '_token':'{{csrf_token()}}',
                    'type':type
                },
                success: function(response) {
                    if (response.progress == 100) {
                        // $.ajax({
                        //     type: 'POST',
                        //     url: '{{ route('tractors.closeProgress') }}',
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

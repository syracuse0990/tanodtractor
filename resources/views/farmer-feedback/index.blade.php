<x-app-layout title="{{ __('Tractor Reports') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Tractor Reports</h3>
                    </div>
                    {{-- <form id="searchForm" action="{{ route('tractor-groups.index') }}" method="get">
                        <div class="search-filter-box w-100">
                            <input id="searchField" type="text" class="form-control form-control-sm" name="search"
                                placeholder="search..." onchange="javascript:this.form.submit();"
                                value="{{ isset($search) ? $search : null }}">
                        </div>
                    </form> --}}
                    <div class="">
                        <a class="btn btn-success me-3"
                            href="{{ route('farmer-feedbacks.export-feedback') }}">Export</a>
                        <a class="btn btn-success float-end d-none" id="download_csv"
                            href="{{ route('farmer-feedbacks.download-feedback') }}">Download</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Tractor</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Issue Type</th>
                                    <th>State</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($farmerFeedbacks))
                                @foreach ($farmerFeedbacks as $farmerFeedback)
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $farmerFeedback->tractor?->id_no . ' (' . $farmerFeedback->tractor?->model . ')' }}
                                    </td>
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
            {!! $farmerFeedbacks->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
    @push('js')
    <script>
        function checkFile(){
            $.ajax({
                url: '{{ route('farmer-feedbacks.check-feedback-file') }}',
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
        var download =  setInterval(checkFile, 1000);
    </script>
    @endpush
</x-app-layout>
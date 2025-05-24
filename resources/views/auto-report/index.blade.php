<x-app-layout title="{{ __('Auto Report') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Auto Report</h3>
                        <a href="{{ route('auto-reports.create') }}" class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-plus me-1"></i>
                            Add
                        </a>
                    </div>
                    
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Report Name</th>
                                    <th>Frequency</th>
                                    <th>Email</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($autoReports as $autoReport)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        
                                        <td>{{ $autoReport->report_name }}</td>
                                        <td>{{ $autoReport->getFrequency() }}</td>
                                        <td>{{ $autoReport->email_addresses }}</td>
                                        <td>{{ $autoReport->createdBy?->name }}</td>
                                        <td class="action-btn">
                                            <form action="{{ route('auto-reports.destroy', $autoReport->id) }}" method="POST">
                                                {{-- <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                    href="{{ route('auto-reports.show', $autoReport->id) }}"><i
                                                        class="fa-solid fa-eye"></i></a> --}}
                                                <a href="{{ route('auto-reports.edit', $autoReport->id) }}"
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $autoReports->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
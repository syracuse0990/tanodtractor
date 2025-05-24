@php
use App\Models\Notification;
@endphp
<x-app-layout title="{{ __('Tickets') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Tickets</h3>
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
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Title</th>
                                    <th>State Id</th>
                                    <th>Creted By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($tickets))
                                @foreach ($tickets as $ticket)
                                @php
                                $notification = Notification::where('ticket_id',$ticket->id)->first();
                                $ID = $ticket?->id.'-'.$notification?->id;
                                @endphp
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{$ticket->title }}
                                    </td>
                                    <td>{!! $ticket->getStateLabel() !!}</td>
                                    <td>{{ $ticket->createdBy?->name ?? $ticket->createdBy?->email }}</td>

                                    <td class="action-btn">
                                        <form action="{{ route('tickets.destroy', $ticket->id) }}" method="POST">
                                            <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                href="{{ route('tickets.show',$ID) }}"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('tickets.edit', $ticket->id) }}"
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
            {!! $tickets->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
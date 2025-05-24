@extends('layouts.app')

@section('template_title')
    Assigned Group
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Assigned Group') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('assigned-groups.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Create New') }}
                                </a>
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
										<th>User Id</th>
										<th>Group Id</th>
										<th>Type Id</th>
										<th>State Id</th>
										<th>Created By</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assignedGroups as $assignedGroup)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $assignedGroup->user_id }}</td>
											<td>{{ $assignedGroup->group_id }}</td>
											<td>{{ $assignedGroup->type_id }}</td>
											<td>{{ $assignedGroup->state_id }}</td>
											<td>{{ $assignedGroup->created_by }}</td>

                                            <td>
                                                <form action="{{ route('assigned-groups.destroy',$assignedGroup->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('assigned-groups.show',$assignedGroup->id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Show') }}</a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('assigned-groups.edit',$assignedGroup->id) }}"><i class="fa fa-fw fa-edit"></i> {{ __('Edit') }}</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i> {{ __('Delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $assignedGroups->links() !!}
            </div>
        </div>
    </div>
@endsection

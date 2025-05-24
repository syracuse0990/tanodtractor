@extends('layouts.app')

@section('template_title')
    Email Queue
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Email Queue') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('email-queues.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
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
                                        
										<th>From Email</th>
										<th>To Email</th>
										<th>Message</th>
										<th>Subject</th>
										<th>Date Published</th>
										<th>Last Attempt</th>
										<th>Date Sent</th>
										<th>Attempts</th>
										<th>Status</th>
										<th>Type</th>
										<th>Model Id</th>
										<th>Model Type</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($emailQueues as $emailQueue)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $emailQueue->from_email }}</td>
											<td>{{ $emailQueue->to_email }}</td>
											<td>{{ $emailQueue->message }}</td>
											<td>{{ $emailQueue->subject }}</td>
											<td>{{ $emailQueue->date_published }}</td>
											<td>{{ $emailQueue->last_attempt }}</td>
											<td>{{ $emailQueue->date_sent }}</td>
											<td>{{ $emailQueue->attempts }}</td>
											<td>{{ $emailQueue->status }}</td>
											<td>{{ $emailQueue->type }}</td>
											<td>{{ $emailQueue->model_id }}</td>
											<td>{{ $emailQueue->model_type }}</td>

                                            <td>
                                                <form action="{{ route('email-queues.destroy',$emailQueue->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('email-queues.show',$emailQueue->id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Show') }}</a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('email-queues.edit',$emailQueue->id) }}"><i class="fa fa-fw fa-edit"></i> {{ __('Edit') }}</a>
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
                {!! $emailQueues->links() !!}
            </div>
        </div>
    </div>
@endsection

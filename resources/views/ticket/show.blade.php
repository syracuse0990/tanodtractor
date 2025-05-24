@php
use App\Models\Ticket;
@endphp
<x-app-layout title="{{ __($ticket->id) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500">Ticket Detail</h2>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Title <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $ticket->title }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Description <span class="float-end">:</span></strong></td>
                            <td>{{ $ticket->description }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $ticket->getStatelabel() !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $ticket->createdBy?->name }} </td>
                        </tr>
                        @if ($ticket->conclusion)
                        <tr>
                            <td><strong class="fw-500">Conclusion <span class="float-end">:</span></strong></td>
                            <td>{!! $ticket->conclusion !!}</td>
                        </tr>
                        @endif
                    </table>
                    <div class="row">
                        <div class="col-md-12 text-end">
                            @if ($ticket->state_id == Ticket::STATE_ACTIVE)
                            <a href='{{ route('tickets.changeState', ['id'=> $ticket->id, 'state' =>
                                Ticket::STATE_INPROGRESS]) }}'
                                class="btn btn-{{ $ticket->getColor(Ticket::STATE_INPROGRESS) }} text-white btn-sm
                                rounded-pill px-3
                                state-icon">{{ ('In Progress') }}</a>
                            <a href='javascript:void(0);'
                                class="btn btn-success text-white btn-sm rounded-pill px-3 state-icon conclusion"
                                data-bs-toggle="modal" data-bs-target="#conclusionModel"
                                data-state="{{Ticket::STATE_COMPLETED}}">Complete</a>
                            <a href='javascript:void(0);'
                                class="btn btn-danger text-white btn-sm rounded-pill px-3 state-icon conclusion"
                                data-bs-toggle="modal" data-bs-target="#conclusionModel"
                                data-state="{{Ticket::STATE_REJECTED}}">Reject</a>
                            @elseif ($ticket->state_id == Ticket::STATE_INPROGRESS)
                            <a href='javascript:void(0);'
                                class="btn btn-success text-white btn-sm rounded-pill px-3 state-icon conclusion"
                                data-bs-toggle="modal" data-bs-target="#conclusionModel"
                                data-state="{{Ticket::STATE_COMPLETED}}">Complete</a>
                            <a href='javascript:void(0);'
                                class="btn btn-danger text-white btn-sm rounded-pill px-3 state-icon conclusion"
                                data-bs-toggle="modal" data-bs-target="#conclusionModel"
                                data-state="{{Ticket::STATE_REJECTED}}">Reject</a>
                            @endif
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="conclusionModel" tabindex="-1" aria-labelledby="conclusionModelLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="conclusionModelLabel">Conclusion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reason_form" action="#" method="POST">
                        {{ method_field('PATCH') }}
                        @csrf
                        <div class="default-form">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        {{-- {{ Form::label('Reason') }} --}}
                                        {{ Form::textarea('conclusion', $ticket->conclusion, ['id'=>'conclusion',
                                        'class' =>
                                        'form-control description',
                                        'placeholder' => 'Conclusion', 'style' => 'height: 150px;']) }}
                                        <div id="conclusion_error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                {{ Form::hidden('id', $ticket->id)}}
                                <input type="hidden" name="state" class="state">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger text-white px-3 state-icon"
                                data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary btn-icon text-white px-3">{{ __('Submit')
                                }}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    @push('js')
    <script>
        $('document').ready(function(){

            $('.conclusion').click(function(){
                let state = $(this).data('state');
                $('.state').val(state);
            });

            $('#reason_form').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                let url = "{{route('tickets.changeState')}}";
                $.ajax({
                    type: 'GET',
                    url: url,
                    data: formData,
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function(xhr) {
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
                            $('#conclusion').addClass("is-invalid");
                            $('#conclusion_error').html(message);
                        }
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
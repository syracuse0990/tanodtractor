@php
use App\Models\TractorBooking;
@endphp
<x-app-layout title="{{ __($tractorBooking->id) }}">
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td><strong class="fw-500">Farmer <span class="float-end">:</span></strong></td>
                            <td>{{ $tractorBooking->createdBy?->name ? $tractorBooking->createdBy?->name :
                                $tractorBooking->createdBy?->email }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Tractor <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $tractorBooking->tractor?->id_no . ' (' . $tractorBooking->tractor?->model . ')' }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="fw-500"> Device <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $tractorBooking->device?->device_name }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Date <span class="float-end">:</span></strong></td>
                            <td>{!! date('d-M-Y', strtotime($tractorBooking->date)) !!} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">State <span class="float-end">:</span></strong></td>
                            <td>{!! $tractorBooking->getStatelabel() !!} </td>
                        </tr>
                    </table>
                    @if ($tractorBooking->state_id == TractorBooking::STATE_REJECTED)
                        <div class="form-group p-1 pb-0">
                            <strong>Reason:</strong>
                            <div class="ck-content">
                                {!! $tractorBooking->reason !!}
                            </div>
                        </div>
                        @endif
                    @if ($tractorBooking->state_id == TractorBooking::STATE_ACTIVE)
                    <div>
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <a href='{{route('tractor-bookings.change-status',['id'=>$tractorBooking->id,
                                    'state_id'=>TractorBooking::STATE_ACCEPTED])}}'
                                    class="btn btn-success text-white btn-sm rounded-pill px-3 state-icon">Accept</a>
                                <a href='javascript:void(0);'
                                    class="btn btn-danger text-white btn-sm rounded-pill px-3 state-icon"
                                    data-bs-toggle="modal" data-bs-target="#reasonModal">Reject</a>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonModalLabel">Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reason_form" action="{{route('tractor-bookings.change-status',['id'=>$tractorBooking->id,
                        'state_id'=>TractorBooking::STATE_ACCEPTED])}}" method="POST">
                        {{ method_field('PATCH') }}
                        @csrf
                        <div class="default-form">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        {{-- {{ Form::label('Reason') }} --}}
                                        {{ Form::textarea('reason', $tractorBooking->reason, ['id'=>'reason', 'class' =>
                                        'form-control description',
                                        'placeholder' => 'Reason', 'style' => 'height: 150px;']) }}
                                        <div id="reason_error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                {{ Form::hidden('id', $tractorBooking->id)}}
                                {{ Form::hidden('state_id',TractorBooking::STATE_REJECTED)}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger text-white px-3 state-icon" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary btn-icon text-white px-3">{{ __('Submit') }}</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
<script>
    $('document').ready(function(){
        $('#reason_form').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let url = "{{route('tractor-bookings.change-status')}}";
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
                        $('#reason').addClass("is-invalid");
                        $('#reason_error').html(message);
                    }
                }
            });
        });
    });
</script>
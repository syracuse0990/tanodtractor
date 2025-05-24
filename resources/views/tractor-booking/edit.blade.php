@extends('layouts.app')

@section('template_title')
    {{ __('Update') }} Tractor Booking
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Update') }} Tractor Booking</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tractor-bookings.update', $tractorBooking->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('tractor-booking.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

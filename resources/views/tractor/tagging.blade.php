@php
use App\Models\User;
use App\Models\TractorGroup;
use App\Models\Device;
use App\Models\Tractor;
@endphp
<x-app-layout title="{{ __('Tagging') }}">
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                @includeif('partials.errors')
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
                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Tagging') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tractors.tagUnit') }}" role="form"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    {{ Form::label('group_id', 'Group') }}
                                    {{ Form::select(
                                        'group_id',
                                        TractorGroup::pluck('name', 'id'),
                                        old('group_id'),
                                        [
                                            'class' => 'form-select select2' . ($errors->has('group_id') ? ' is-invalid' : ''),
                                            'placeholder' => 'Select Group'
                                        ]
                                    ) }}
                                    {!! $errors->first('group_id', '<div class="invalid-feedback">:message</div>') !!}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    {{ Form::label('user_id', 'Recipient ') }}
                                    {{ Form::select(
                                        'user_id',
                                        User::where('role_id', User::ROLE_FARMER)->pluck('name', 'id'),
                                        old('user_id'),
                                        [
                                            'class' => 'form-select select2' . ($errors->has('user_id') ? ' is-invalid' : ''),
                                            'placeholder' => 'Select Recipient'
                                        ]
                                    ) }}
                                    {!! $errors->first('user_id', '<div class="invalid-feedback">:message</div>') !!}
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    {{ Form::label('device_id', 'Tractor') }}
                                    {{ Form::select(
                                        'tractor_id',
                                        Tractor::all()->mapWithKeys(function ($tractor) {
                                            return [$tractor->id => $tractor->imei ?? $tractor->no_plate];
                                        }),
                                        old('tractor_id'),
                                        [
                                            'class' => 'form-select select2' . ($errors->has('tractor_id') ? ' is-invalid' : ''),
                                            'placeholder' => 'Select Tractor'
                                        ]
                                    ) }}
                                    {!! $errors->first('tractor_id', '<div class="invalid-feedback">:message</div>') !!}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    {{ Form::label('device_id', 'Device') }}
                                    {{ Form::select(
                                        'device_id',
                                        Device::all()->mapWithKeys(function ($device) {
                                            return [$device->id => $device->imei_no . ' - ' . $device->device_name];
                                        }),
                                        old('device_id'),
                                        [
                                            'class' => 'form-select select2' . ($errors->has('device_id') ? ' is-invalid' : ''),
                                            'placeholder' => 'Select Device'
                                        ]
                                    ) }}
                                    {!! $errors->first('device_id', '<div class="invalid-feedback">:message</div>') !!}
                                </div>
                            </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class=" mt-3">
                                        <button type="submit" class="btn btn-primary btn-icon text-white rounded-pill px-3">{{ __('Submit')
                                            }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card card-default mt-5">
                    <div class="card-header">
                        <span class="card-title">{{ __('Tagging History') }}</span>
                    </div>
                    <div class="card-body">
                       <table id="tagging-history-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th>Tractor</th>
                                <th>Device</th>
                                <th>Recipient</th>
                                <th>Date Tagged</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $item)
                                <tr>
                                    <td>{{ $item->group->name ?? 'N/A' }}</td>
                                    <td>{{ $item->tractor->imei ?? $item->tractor->no_plate ?? 'N/A' }}</td>
                                    <td>{{ $item->device->imei_no ?? 'N/A' }}</td>
                                    <td>{{ $item->user->name ?? 'N/A' }}</td>
                                    <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

{{-- DataTables CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

{{-- DataTables JS --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select Tractor",
        allowClear: true
    });
     $('#tagging-history-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[4, 'desc']], // sort by Date Tagged
    });
});
</script>


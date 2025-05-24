@php
use App\Models\User;
@endphp
<x-app-layout title="{{ __($autoReport->report_name) }}">
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> {{ $autoReport->report_name }}</h2>
                    <a href="{{ route('auto-reports.edit', [$autoReport->id]) }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td>
                                <strong class="fw-500"> Report Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $autoReport->report_name }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Frequency <span class="float-end">:</span></strong></td>
                            <td>{{ $autoReport->getFrequency() }} </td>
                        </tr>

                        <tr>
                            <td><strong class="fw-500">Email <span class="float-end">:</span></strong></td>
                            <td>{{ $autoReport->email_addresses }} </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Execution Time <span class="float-end">:</span></strong></td>
                            <td>{{ $autoReport->execution_day .' '. $autoReport->execution_time}}</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Report Query Conditions <span class="float-end">:</span></strong></td>
                            <td>{{ $autoReport->getFromDay() .' '. $autoReport->from_time . ' - '.  $autoReport->getToDay() .' '. $autoReport->to_time}} </td>
                        </tr>
                        
                        <tr>
                            <td><strong class="fw-500">Created By <span class="float-end">:</span></strong></td>
                            <td>{{ $autoReport->createdBy?->name }} </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
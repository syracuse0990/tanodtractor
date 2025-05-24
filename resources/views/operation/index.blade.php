@php
@endphp
<x-app-layout title="Settings">
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
    <div class="main-table table_mg">
        <div class="row mb-5">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        <th>Command</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table_content">
                                    <tr class="position-relative">
                                        <td>
                                            <div class="serial-num">1</div>
                                        </td>
                                        <td class="white-space-collapse">Run Migration</td>
                                        <td class="action-btn">
                                            <a href="{{ route('settings.runMigration') }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-wrench"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr class="position-relative">
                                        <td>
                                            <div class="serial-num">2</div>
                                        </td>
                                        <td class="white-space-collapse">Composer Install</td>
                                        <td class="action-btn">
                                            <a href="{{ route('settings.composerInstall') }}"
                                                class="btn primary text-primary btn-sm me-2 rounded-3">
                                                <i class="fa-solid fa-wrench"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
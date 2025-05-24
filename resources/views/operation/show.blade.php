<x-app-layout title="Setting Detail">
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
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table w-50">
                        <tr>
                            <td style="vertical-align: top">
                                <strong class="fw-500"> Command <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                {{ $command }}
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top">
                                <strong class="fw-500">Description <span class="float-end">:</span></strong>
                            </td>
                            <td>{!! $output !!} </td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
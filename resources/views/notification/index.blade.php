@php
use App\Models\Notification;
@endphp
<x-app-layout title="{{ __('Notifications') }}">
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
                        <h3 class="card-title mb-0 fw-500 me-3">Notifications</h3>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Title</th>
                                    <th>Tractor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($notifications))
                                @foreach ($notifications as $notification)
                                @php
                                $url = 'javascript:void(0);';
                                if($notification->geofence_id){
                                $url = route('device-geo-fences.show',[$notification->geofence_id,'notification_id'=>$notification->id]);
                                }elseif ($notification->tractor_id && $notification->type_id != Notification::TYPE_INACTIVE) {
                                $url = route('tractors.show',[$notification->tractor_id,'notification_id'=>$notification->id]);
                                }elseif ($notification->type_id == Notification::TYPE_INACTIVE) {
                                $url = route('tractors.show',[$notification->tractor_id,'notification_id'=>$notification->id]);
                                }elseif ($notification->type_id == Notification::TYPE_TICKET) {
                                $url = route('tickets.show',[$notification->ticket_id,'notification_id'=>$notification->id]);
                                }

                                    $tractorName = $notification->tractor?->id_no;
                                    if ($tractorName) {
                                        $tractorName = !empty($notification->tractor->model) ? $tractorName . ' (' . $notification->tractor?->model . ')' : $tractorName;
                                    } else {
                                        $tractorName = $notification->tractor->no_plate;
                                        if ($tractorName) {
                                            $tractorName = !empty($notification->tractor->model) ? $tractorName . ' (' . $notification->tractor?->model . ')' : $tractorName;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ ++$i }}</td>
                                    <td>{{ $notification->title }}</td>
                                    <td>{{ $tractorName }}</td>
                                    <td>{!! $notification->getStateLabel()!!}</td>
                                    <td class="action-btn">
                                        <a class="btn primary text-success btn-sm me-2 rounded-3" href="{{ $url }}"><i
                                                class="fa-solid fa-eye"></i></a>
                                        </a>
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
            {!! $notifications->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>

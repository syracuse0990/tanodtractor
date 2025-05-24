@php
use App\Models\User;
@endphp
@if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SYSTEM_ADMIN]))
@include('dashboard.admin')
@elseif(in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]))
@include('dashboard.subAdmin')
@endif
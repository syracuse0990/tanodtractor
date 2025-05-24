<x-app-layout>
    <div class="row">
        <div class="col-lg-12">
            <div class="page-heading">
                <h4>Profile Details</h4>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">

                    <div class="avatar-upload">
                        <div class="avatar-edit">
                            <input type='file' id="imageUpload"/>
                            <label for="imageUpload"></label>
                        </div>
                        <div class="avatar-preview">
                            <div id="imagePreview">
                            <img src="{{ asset('assets/img/user.png') }}" alt="logo">

                            </div>
                        </div>
                    </div>
                   
                    <div class="mt-3 profile-content text-center">
                        <h4 class="fw-500">Michael Jorden</h4>
                        <p>Admin</p>
                    </div>

                    <div class="text-center mt-2">
                        <a href="javascript:void(0);"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <span> <i class="fa-solid fa-camera me-1"></i> </span> Update Image</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0 fw-500"> Personal Information</h2>
                    <a href="{{ url('/update-profile') }}"
                        class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                        <i class="fa-regular fa-pen-to-square me-1"></i>Edit</a>
                </div>
                <div class="card-body">
                    <table cellpadding="7" class="profile-detail-table">
                        <tr>
                            <td>
                                <strong class="fw-500"> Full Name <span class="float-end">:</span></strong>
                            </td>
                            <td>
                                Michael Jorden
                            </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Phone <span class="float-end">:</span></strong></td>
                            <td>+125 254 3562 </td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Email <span class="float-end">:</span></strong></td>
                            <td>example@example.com</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Role <span class="float-end">:</span></strong></td>
                            <td>Admin</td>
                        </tr>
                        <tr>
                            <td><strong class="fw-500">Created On <span class="float-end">:</span></strong></td>
                            <td>12/05/2023</td>
                        </tr>

                        <tr>
                            <td><strong class="fw-500">Role <span class="float-end">:</span></strong></td>
                            <td>Admin</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout title="{{ __('Update Password') }}">
    <div class="row">
        <div class="col-lg-12">
            <div class="page-heading">
                <h4>Change Password</h4>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row justify-content-center py-5">
                        <div class="col-md-5">

                            <form action="{{ route('update-password') }}" method="POST">
                                @csrf
                                <div class="mb-3 position-relative password-field-wrapper">
                                    <label for="oldPasswordInput" class="form-label">Old Password</label>
                                    <input name="old_password" type="password"
                                        class="form-control @error('old_password') is-invalid @enderror"
                                        id="oldPasswordInput" placeholder="Old Password"
                                        value="{{ old('old_password') }}">
                                    <span onclick="oldPasswordShowHide();" class="toggle-eye">
                                        <i class="fas fa-eye" id="show_eye"></i>
                                        <i class="fas fa-eye-slash d-none" id="hide_eye"></i>
                                    </span>
                                    @error('old_password')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3  position-relative password-field-wrapper">
                                    <label for="newPasswordInput" class="form-label">New Password</label>
                                    <input name="new_password" type="password"
                                        class="form-control @error('new_password') is-invalid @enderror"
                                        id="newPasswordInput" placeholder="New Password">
                                    <span onclick="newPasswordShowHide();" class="toggle-eye">
                                        <i class="fas fa-eye" id="newPasswordInput_show_eye"></i>
                                        <i class="fas fa-eye-slash d-none" id="newPasswordInput_hide_eye"></i>
                                    </span>
                                    @error('new_password')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3  position-relative password-field-wrapper">
                                    <label for="confirmNewPasswordInput" class="form-label">Confirm Password</label>
                                    <input name="confirm_password" type="password" class="form-control"
                                        id="confirmNewPasswordInput" placeholder="Confirm Password">
                                    <span onclick="confirmPasswordShowHide();" class="toggle-eye">
                                        <i class="fas fa-eye" id="confirmNewPasswordInput_show_eye"></i>
                                        <i class="fas fa-eye-slash d-none" id="confirmNewPasswordInput_hide_eye"></i>
                                    </span>
                                    @error('confirm_password')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mt-4">
                                    <button
                                        class="btn btn-primary btn-icon text-white rounded-pill px-3">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function oldPasswordShowHide(event) {
        var x = document.getElementById("oldPasswordInput");
        var show_eye = document.getElementById("show_eye");
        var hide_eye = document.getElementById("hide_eye");
        hide_eye.classList.remove("d-none");
        if (x.type === "password") {
            x.type = "text";
            show_eye.style.display = "none";
            hide_eye.style.display = "block";
        } else {
            x.type = "password";
            show_eye.style.display = "block";
            hide_eye.style.display = "none";
        }
    }

    function newPasswordShowHide(event) {
        var x = document.getElementById("newPasswordInput");
        var show_eye = document.getElementById("newPasswordInput_show_eye");
        var hide_eye = document.getElementById("newPasswordInput_hide_eye");
        hide_eye.classList.remove("d-none");
        if (x.type === "password") {
            x.type = "text";
            show_eye.style.display = "none";
            hide_eye.style.display = "block";
        } else {
            x.type = "password";
            show_eye.style.display = "block";
            hide_eye.style.display = "none";
        }
    }

    function confirmPasswordShowHide(event) {
        var x = document.getElementById("confirmNewPasswordInput");
        var show_eye = document.getElementById("confirmNewPasswordInput_show_eye");
        var hide_eye = document.getElementById("confirmNewPasswordInput_hide_eye");
        hide_eye.classList.remove("d-none");
        if (x.type === "password") {
            x.type = "text";
            show_eye.style.display = "none";
            hide_eye.style.display = "block";
        } else {
            x.type = "password";
            show_eye.style.display = "block";
            hide_eye.style.display = "none";
        }
    }
</script>
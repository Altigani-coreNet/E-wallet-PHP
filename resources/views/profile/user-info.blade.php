@extends('layouts.merchant.merchant_layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <x-profile-header 
        :user="$user"
        :activeTab="'profile'"
        :profileCompletion="$profileCompletion"
    />
    <form action="{{ route('user.info.update') }}" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <div class="card card-flush h-md-100">
                        <div class="card-header">
                            <div class="card-title">
                                <h2>Profile Picture</h2>
                            </div>
                        </div>
                        <div class="card-body text-center pt-0">
                            <div class="text-center mb-10">
                                <div class="image-input image-input-outline" data-kt-image-input="true">
                                    <div class="image-input-wrapper w-150px h-150px" style="background-image: url('{{ $user->profile_image ? asset($user->profile_image) : asset('assets/media/avatars/300-1.jpg') }}')"></div>
                                    <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-35px h-35px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                        <i class="bi bi-pencil-fill fs-7"></i>
                                        <input type="file" name="profile_image" accept=".png, .jpg, .jpeg, .gif" />
                                        <input type="hidden" name="avatar_remove" />
                                    </label>
                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-35px h-35px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                        <i class="bi bi-x fs-2"></i>
                                    </span>
                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-35px h-35px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                        <i class="bi bi-x fs-2"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="text-center mb-10">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="d-flex flex-column">    
                                        <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary mb-1">{{ $user->name }}</a>
                                        <div class="fs-6 fw-semibold text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
            <div class="col-lg-8">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
    
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>Profile Information</h2>
                        </div>
                    </div>
                    <div class="card-body">
                            @csrf
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Full Name</label>
                                <div class="col-lg-8">
                                    <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror" placeholder="Enter full name" value="{{ old('name', $user->name) }}" required />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Email Address</label>
                                <div class="col-lg-8">
                                    <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" placeholder="Enter email address" value="{{ old('email', $user->email) }}" required />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label fw-semibold fs-6">Phone Number</label>
                                <div class="col-lg-8">
                                    <input type="text" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror" placeholder="Enter phone number" value="{{ old('phone', $user->phone) }}" />
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="submit" class="btn btn-primary">
                                    <span class="indicator-label">Update Profile</span>
                                    <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </form>
        <div class="row">
            <div class="col-md-4"></div>
            <div class="col-md-8">
                <div class="card mt-8">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>Change Password</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user.change-password') }}" method="POST">
                            @csrf
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Current Password</label>
                                <div class="col-lg-8">
                                    <input type="password" name="current_password" class="form-control form-control-solid @error('current_password') is-invalid @enderror" placeholder="Enter current password" required />
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">New Password</label>
                                <div class="col-lg-8">
                                    <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" placeholder="Enter new password" required />
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Confirm New Password</label>
                                <div class="col-lg-8">
                                    <input type="password" name="password_confirmation" class="form-control form-control-solid" placeholder="Confirm new password" required />
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="submit" class="btn btn-primary">
                                    <span class="indicator-label">Change Password</span>
                                    <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

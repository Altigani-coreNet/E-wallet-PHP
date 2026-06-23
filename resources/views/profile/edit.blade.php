@extends('layouts.merchant.merchant_layout')

@section('content')
    <x-profile-header 
        :user="$user"
        :activeTab="'profile'"
        :profileCompletion="$profileCompletion"
    />

    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('translation.edit_profile') }}</h3>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profile_form" class="form">
            @csrf
            @method('PUT')
            
            <div class="card-body border-top p-9">
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.name') }}</label>
                    <div class="col-lg-8">
                        <input type="text" name="name" class="form-control form-control-lg form-control-solid @error('name') is-invalid @enderror" 
                               placeholder="{{ __('translation.enter_name') }}" value="{{ old('name', $user->name) }}" />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-bold fs-6">{{ __('translation.email') }}</label>
                    <div class="col-lg-8">
                        <input type="email" name="email" class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" 
                               placeholder="{{ __('translation.enter_email') }}" value="{{ old('email', $user->email) }}" />
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.address') }}</label>
                    <div class="col-lg-8">
                        <input type="text" name="address" class="form-control form-control-lg form-control-solid @error('address') is-invalid @enderror" 
                               placeholder="{{ __('translation.enter_address') }}" value="{{ old('address', $user->address) }}" />
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.avatar') }}</label>
                    <div class="col-lg-8">
                        <div class="image-input image-input-outline" data-kt-image-input="true">
                            <div class="image-input-wrapper w-125px h-125px" 
                                 style="background-image: url({{ $user->avatar_url ?? asset('assets/media/avatars/blank.png') }})">
                            </div>
                            
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                   data-kt-image-input-action="change" 
                                   data-bs-toggle="tooltip" 
                                   title="{{ __('translation.change_avatar') }}">
                                <i class="bi bi-pencil-fill fs-7"></i>
                                <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                            </label>

                            @if($user->avatar_url)
                                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                                      data-kt-image-input-action="remove" 
                                      data-bs-toggle="tooltip" 
                                      title="{{ __('translation.remove_avatar') }}">
                                    <i class="bi bi-x fs-2"></i>
                                </span>
                            @endif
                        </div>
                        @error('avatar')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="submit" class="btn btn-primary" id="profile_submit">
                    {{ __('translation.save_changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection

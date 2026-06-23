@extends('layouts.admin.merchant_layout')

@section('content')
    <x-profile-header 
        :user="$user" 
        :activeTab="$activeTab"
        :profileCompletion="$profileCompletion"
    />

    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0">
            <div class="card-title m-0">
                <h3 class="fw-bolder m-0">{{ __('translation.profile_details') }}</h3>
            </div>
        </div>

        <div class="card-body border-top p-9">
            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.name') }}</label>
                <div class="col-lg-8">
                    <div class="fw-bold fs-6 text-gray-800">{{ $user->name }}</div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.email') }}</label>
                <div class="col-lg-8">
                    <div class="fw-bold fs-6 text-gray-800">{{ $user->email }}</div>
                </div>
            </div>

            @if($user->address)
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.address') }}</label>
                    <div class="col-lg-8">
                        <div class="fw-bold fs-6 text-gray-800">{{ $user->address }}</div>
                    </div>
                </div>
            @endif

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.created_at') }}</label>
                <div class="col-lg-8">
                    <div class="fw-bold fs-6 text-gray-800">{{ $user->created_at->format('M d, Y') }}</div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-bold fs-6">{{ __('translation.last_login') }}</label>
                <div class="col-lg-8">
                    <div class="fw-bold fs-6 text-gray-800">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('translation.never') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

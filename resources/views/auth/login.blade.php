@extends('layouts.auth.auth_layout')

@section('title', 'Login')

@section('content')
<!--begin::Authentication - Sign-in -->
<div class="d-flex flex-column flex-column-fluid flex-lg-row">
    <!--begin::Aside-->
    <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
        <!--begin::Aside-->
        <div class="d-flex flex-center flex-lg-start flex-column">
            <!--begin::Logo-->
            <a href="{{ url('/') }}" class="mb-7">
                <img alt="Logo" src="{{ asset('logo_dark.png') }}" style="max-width: 200px; height: auto;" />
            </a>
            <!--end::Logo-->
            <!--begin::Title-->
            <h2 class="text-white fw-normal m-0 fs-2 fw-bold">
                <i class="fas fa-chart-line me-3"></i>
                Welcome to Fast POS System
            </h2>
            <p class="text-white-75 mt-3 fs-6">
                <i class="fas fa-shield-alt me-2"></i>
                Secure, Fast & Reliable Point of Sale Solution
            </p>
            <!--end::Title-->
        </div>
        <!--begin::Aside-->
    </div>
    <!--begin::Aside-->
    <!--begin::Body-->
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
        <!--begin::Card-->
        <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20 shadow-lg border-0" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
            <!--begin::Wrapper-->
            <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                <!--begin::Form-->
                <form class="form w-100" method="POST" action="{{ route('login') }}">
                    @csrf
                    <!--begin::Heading-->
                    <div class="text-center mb-11">
                        <!--begin::Title-->
                        <div class="mb-4">
                            <i class="fas fa-user-circle text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="text-gray-900 fw-bolder mb-3 fs-2x">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Welcome Back!
                        </h1>
                        <!--end::Title-->
                        <!--begin::Subtitle-->
                        <div class="text-gray-500 fw-semibold fs-6">
                            <i class="fas fa-lock me-2"></i>
                            Sign in to your account to continue
                        </div>
                        <!--end::Subtitle=-->
                    </div>
                    <!--begin::Heading-->
                    
                    @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Login Failed!</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!--begin::Input group=-->
                    <div class="fv-row mb-8">
                        <!--begin::Email-->
                        <div class="position-relative">
                            <div class="position-absolute top-50 start-0 translate-middle-y ms-3">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" placeholder="Enter your email address" name="email" value="{{ old('email') }}" autocomplete="off" class="form-control bg-transparent ps-5 py-3 @error('email') is-invalid @enderror" style="border: 2px solid #e1e5e9; border-radius: 10px; transition: all 0.3s ease;" />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <!--end::Email-->
                    </div>
                    <!--end::Input group=-->
                    <div class="fv-row mb-6">
                        <!--begin::Password-->
                        <div class="position-relative">
                            <div class="position-absolute top-50 start-0 translate-middle-y ms-3">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" placeholder="Enter your password" name="password" autocomplete="off" class="form-control bg-transparent ps-5 py-3 @error('password') is-invalid @enderror" style="border: 2px solid #e1e5e9; border-radius: 10px; transition: all 0.3s ease;" />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <!--end::Password-->
                    </div>
                    <!--end::Input group=-->
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            <label class="form-check-label" for="remember" style="cursor: pointer;">
                                <i class="fas fa-check-circle me-2"></i>
                                Remember me
                            </label>
                        </div>
                        <!--begin::Link-->
                        <a href="{{ route('password.request') }}" class="link-primary text-decoration-none fw-bold">
                            <i class="fas fa-key me-1"></i>
                            Forgot Password?
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Submit button-->
                    <div class="d-grid mb-10">
                        <button type="submit" class="btn btn-primary py-3 fw-bold fs-6 shadow-sm" style="border-radius: 10px; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); border: none; transition: all 0.3s ease;">
                            <!--begin::Indicator label-->
                            <span class="indicator-label">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </span>
                            <!--end::Indicator label-->
                            <!--begin::Indicator progress-->
                            <span class="indicator-progress">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Please wait... 
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicator progress-->
                        </button>
                    </div>
                    <!--end::Submit button-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Wrapper-->
            <!--begin::Footer-->
            <div class="d-flex flex-stack px-lg-10">
                <!--begin::Languages-->
                <div class="me-0">
                    <!--begin::Toggle-->
                    <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base border-0" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start" data-kt-menu-offset="0px, 0px" style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 16px;">
                        <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3" src="{{ asset('assets/media/flags/united-states.svg') }}" alt="" />
                        <span data-kt-element="current-lang-name" class="me-1">English</span>
                        <i class="ki-duotone ki-down fs-5 text-muted rotate-180 m-0"></i>
                    </button>
                    <!--end::Toggle-->
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7 shadow-lg" data-kt-menu="true" id="kt_auth_lang_menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="English">
                                <span class="symbol symbol-20px me-4">
                                    <img data-kt-element="lang-flag" class="rounded-1" src="{{ asset('assets/media/flags/united-states.svg') }}" alt="" />
                                </span>
                                <span data-kt-element="lang-name">English</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="Arabic">
                                <span class="symbol symbol-20px me-4">
                                    <img data-kt-element="lang-flag" class="rounded-1" src="{{ asset('assets/media/flags/saudi-arabia.svg') }}" alt="" />
                                </span>
                                <span data-kt-element="lang-name">Arabic</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->
                </div>
                <!--end::Languages-->
                <!--begin::Links-->
                <div class="d-flex fw-semibold text-primary fs-base gap-5">
                    <a href="#" target="_blank" class="text-decoration-none">
                        <i class="fas fa-file-contract me-1"></i>
                        Terms
                    </a>
                    <a href="#" target="_blank" class="text-decoration-none">
                        <i class="fas fa-tags me-1"></i>
                        Plans
                    </a>
                    <a href="#" target="_blank" class="text-decoration-none">
                        <i class="fas fa-phone me-1"></i>
                        Contact Us
                    </a>
                </div>
                <!--end::Links-->
            </div>
            <!--end::Footer-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Body-->
</div>
<!--end::Authentication - Sign-in-->
@endsection

@section('styles')
<style>
    .form-control:focus {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        transform: translateY(-2px);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4) !important;
    }
    
    .alert {
        border-radius: 10px;
        border: none;
    }
    
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .link-primary:hover {
        color: #0056b3 !important;
        text-decoration: underline !important;
    }
    
    .shadow-lg {
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
    }
</style>
@endsection

@section('scripts')
<!--begin::Custom Javascript(used for this page only)-->
<script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>
<!--end::Custom Javascript-->
<script>
// Add smooth animations and interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add focus effects to form inputs
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endsection 
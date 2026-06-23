@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.role_details'))
@section('content')
    <div class="content-wrapper">
        <div id="kt_content_container" class="container-xxl">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>{{ __('translation.role_details') }}: {{ $role->name }}</h2>
                    </div>
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                            <a href="{{ route('merchant.roles.index') }}" class="btn btn-light-danger me-3">
                                <i class="ki-duotone ki-arrow-left fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.back') }}
                            </a>
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-5">
                                <label class="fs-6 fw-bold mb-2">{{ __('translation.role_name') }}</label>
                                <div class="form-control form-control-solid">{{ $role->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-5">
                                <label class="fs-6 fw-bold mb-2">{{ __('translation.guard_name') }}</label>
                                <div class="form-control form-control-solid">{{ $role->guard_name }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label class="fs-6 fw-bold mb-2">{{ __('translation.permissions') }}</label>
                        <div class="row">
                            @foreach($rolePermissions as $permission)
                                <div class="col-md-4 mb-2">
                                    <span class="badge badge-light-primary">{{ __('translation.' . $permission->name) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection

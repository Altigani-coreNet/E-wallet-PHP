@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.merchant_details'))
@section('page_title', __('translation.merchant_details'))
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.dashboard') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchants.index') }}" class="text-muted text-hover-primary">{{ __('translation.merchants') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.merchant_details') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
    @if(isset($merchant))
        <a href="{{ route('merchants.edit', $merchant->id) }}" class="btn btn-sm btn-primary me-3">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.edit') }}
        </a>
    @endif
    <a href="{{ route('merchants.index') }}" class="btn btn-sm btn-light">
        <i class="ki-duotone ki-arrow-left fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.back') }}
    </a>
@endsection

@section('content')
    <!--begin::Row-->
    <div class="row gy-5 g-xl-8">
        <!--begin::Col-->
        <div class="col-xl-12">
            @if(isset($merchant))
                <!--begin::Merchant Profile Header-->
                <x:merchant-profile-header :merchant="$merchant" active-tab="{{ request()->get('tab', 'overview') }}" />
                <!--end::Merchant Profile Header-->

                <!--begin::Merchant Stats Card-->
                <x:merchant-stats-card 
                    :merchant="$merchant" 
                    title="{{ __('translation.merchant_overview') }}"
                    subtitle="{{ __('translation.key_metrics_and_statistics') }}"
                    action-url="{{ route('merchants.edit', $merchant->id) }}"
                    action-text="{{ __('translation.view_details') }}"
                />
                <!--end::Merchant Stats Card-->

                <!--begin::Merchant Quick Actions-->
                <x:merchant-quick-actions 
                    :merchant="$merchant"
                    title="{{ __('translation.quick_actions') }}"
                    subtitle="{{ __('translation.common_merchant_operations') }}"
                />
                <!--end::Merchant Quick Actions-->

                <!--begin::Merchant Info Summary-->
                <x:merchant-info-summary 
                    :merchant="$merchant"
                    title="{{ __('translation.detailed_information') }}"
                    subtitle="{{ __('translation.comprehensive_merchant_details') }}"
                    action-url="{{ route('merchants.edit', $merchant->id) }}"
                    action-text="{{ __('translation.edit_merchant') }}"
                />
                <!--end::Merchant Info Summary-->

                <!--begin::Merchant Activity Timeline-->
                <x:merchant-activity-timeline 
                    :merchant="$merchant"
                    title="{{ __('translation.activity_history') }}"
                    subtitle="{{ __('translation.merchant_timeline_and_events') }}"
                    action-url="{{ route('merchants.edit', $merchant->id) }}"
                    action-text="{{ __('translation.view_all') }}"
                />
                <!--end::Merchant Activity Timeline-->

                <!--begin::Additional Details Section-->
                <div class="row">
                    <!--begin::User Account Details-->
                    @if($merchant->user)
                    <div class="col-lg-12">
                        <div class="card mb-5 mb-xl-10" id="kt_merchant_user_details">
                            <!--begin::Card header-->
                            <div class="card-header cursor-pointer">
                                <!--begin::Card title-->
                                <div class="card-title m-0">
                                    <h3 class="fw-bolder m-0">{{ __('translation.associated_user_account') }}</h3>
                                </div>
                                <a href="{{ route('users.edit', $merchant->user->id) }}" class="btn btn-primary align-self-center">
                                    {{ __('translation.view_user') }}
                                </a>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body p-9">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.user_name') }}</label>
                                        <p class="form-control-plaintext">{{ $merchant->user->name ?? __('translation.not_available') }}</p>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.user_email') }}</label>
                                        <p class="form-control-plaintext">{{ $merchant->user->email ?? __('translation.not_available') }}</p>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.user_status') }}</label>
                                        <p class="form-control-plaintext">
                                            @if(method_exists($merchant->user, 'getStatusWithSpan'))
                                                {!! $merchant->user->getStatusWithSpan() !!}
                                            @else
                                                <span class="badge badge-light-{{ $merchant->user->status ? 'success' : 'danger' }}">
                                                    {{ $merchant->user->status ? __('translation.active') : __('translation.inactive') }}
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.user_created') }}</label>
                                        <p class="form-control-plaintext">{{ $merchant->user->created_at->format('M d, Y H:i') ?? __('translation.not_available') }}</p>
                                    </div>
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    @endif
                    <!--end::User Account Details-->

                    <!--begin::Branch Details-->
                    @if($merchant->branches && count($merchant->branches) > 0)
                    <div class="col-lg-12">
                        <div class="card mb-5 mb-xl-10" id="kt_merchant_branch_details">
                            <!--begin::Card header-->
                            <div class="card-header cursor-pointer">
                                <!--begin::Card title-->
                                <div class="card-title m-0">
                                    <h3 class="fw-bolder m-0">{{ __('translation.branch_details') }}</h3>
                                </div>
                                <a href="{{ route('branches.index') }}?merchant_id={{ $merchant->id }}" class="btn btn-primary align-self-center">
                                    {{ __('translation.view_branches') }}
                                </a>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body p-9">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.total_branches') }}</label>
                                        <p class="form-control-plaintext">{{ count($merchant->branches) }} {{ __('translation.branches') }}</p>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.active_branches') }}</label>
                                        <p class="form-control-plaintext">{{ $merchant->branches->where('is_active', true)->count() }} {{ __('translation.branches') }}</p>
                                    </div>
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    @endif
                    <!--end::Branch Details-->

                    <!--begin::Terminal Details-->
                    @if($merchant->terminals && count($merchant->terminals) > 0)
                    <div class="col-lg-12">
                        <div class="card mb-5 mb-xl-10" id="kt_merchant_terminal_details">
                            <!--begin::Card header-->
                            <div class="card-header cursor-pointer">
                                <!--begin::Card title-->
                                <div class="card-title m-0">
                                    <h3 class="fw-bolder m-0">{{ __('translation.terminal_details') }}</h3>
                                </div>
                                <a href="{{ route('terminals.index') }}?merchant_id={{ $merchant->id }}" class="btn btn-primary align-self-center">
                                    {{ __('translation.view_terminals') }}
                                </a>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body p-9">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.total_terminals') }}</label>
                                        <p class="form-control-plaintext">{{ count($merchant->terminals) }} {{ __('translation.terminals') }}</p>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label fw-bold text-muted">{{ __('translation.active_terminals') }}</label>
                                        <p class="form-control-plaintext">{{ $merchant->terminals->where('is_active', true)->count() }} {{ __('translation.terminals') }}</p>
                                    </div>
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                    </div>
                    @endif
                    <!--end::Terminal Details-->
                </div>
                <!--end::Additional Details Section-->

            @else
                <!--begin::Empty State-->
                <div class="text-center py-10">
                    <div class="mb-7">
                        <i class="ki-duotone ki-shop fs-5x text-gray-500">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                    </div>
                    <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_merchant_found') }}</h3>
                    <p class="text-gray-500 fs-6">{{ __('translation.merchant_not_found_description') }}</p>
                    <a href="{{ route('merchants.index') }}" class="btn btn-primary">
                        {{ __('translation.back_to_merchants') }}
                    </a>
                </div>
                <!--end::Empty State-->
            @endif
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
@endsection

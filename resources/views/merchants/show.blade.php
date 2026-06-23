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
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
    <!--begin::Row-->
    <div class="row gy-5 g-xl-8">
        <!--begin::Col-->
        <div class="col-xl-12">
            <!--begin::Body-->
            <div class="card-body py-3">
                @if(isset($merchant))
                    <x:merchant-profile-header :merchant="$merchant" active-tab="{{ request()->get('tab', 'overview') }}" />
                    
                    <!--end::Merchant Overview-->

                    <!--begin::Details-->
                    <div class="row">
                        <!--begin::Basic Information-->
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_merchant_basic_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.basic_information') }}</h3>
                                    </div>
                                    <a href="{{ route('merchants.edit', $merchant->id) }}" class="btn btn-primary align-self-center">
                                        {{ __('translation.edit') }}
                                    </a>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.business_name') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                    
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.owner_name') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->owner_name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant_email') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->email ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant_phone') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->phone ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.business_type') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->business_type_display_name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant_code') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="badge badge-primary fs-6">{{ $merchant->merchant_code ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant_address') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->address ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant_status') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            {!! $merchant->getSpanStatus() !!}
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.created_date') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->created_at->format('M d, Y H:i') ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        <!--end::Basic Information-->

                        <!--begin::User Account Details-->
                        @if($merchant->user)
                       <div class="row">
                        <div class="col-lg-8">
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
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.user_name') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->user->name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                    
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.user_email') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->user->email ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.user_phone') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->user->phone ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.user_status') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            @if(method_exists($merchant->user, 'getStatusWithSpan'))
                                                {!! $merchant->user->getStatusWithSpan() !!}
                                            @else
                                                <span class="badge badge-light-{{ $merchant->user->status ? 'success' : 'danger' }}">
                                                    {{ $merchant->user->status ? __('translation.active') : __('translation.inactive') }}
                                                </span>
                                            @endif
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.user_created') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $merchant->user->created_at->format('M d, Y H:i') ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-xl-stretch mb-xl-10">
                                {{--                                        @dd($user->logs_counts)--}}
                                <!--begin::Header-->
                                <div class="card-header align-items-center border-0 mt-4">
                                    <h3 class="card-title align-items-start flex-column">
                                    <span
                                        class="fw-bolder mb-2 text-dark">{{__('translation.events')}}</span>
                                        <span
                                            class="text-muted fw-bold fs-7">{{$merchant->logs_counts}} </span>
                                    </h3>
                                    <div class="card-toolbar">
                                        <!--begin::Menu-->
                                        <a href="{{route('merchants.sections', ['merchant' => $merchant->id , "tab" => "events", "type" => $merchant->type])}}"
                                           class="btn btn-light-success btn-sm"> show All
                                            Events</a>
                                    </div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-5">
                                    <!--begin::Timeline-->
                                    <div class="timeline-label">
                                        @foreach($merchant->LatestLogs as $event)
                                            <!--begin::Item-->
                                            <div class="timeline-item">
                                                <!--begin::Label-->
                                                <style>
                                                    .timeline-label:before {
                                                        left: 101px;
                                                    }
                                                </style>
                                                <div
                                                    class="timeline-label fw-bolder text-gray-800 fs-6"
                                                    style="width: 100px">{{ $event->time }}</div>
                                                <!--end::Label-->
                                                <!--begin::Badge-->
                                                <div class="timeline-badge">
                                                    <i class="fa fa-genderless text-{{ $event->label }} fs-1"></i>
                                                </div>
                                                <!--end::Badge-->
                                                <!--begin::Text or Link-->
                                                <div
                                                    class="fw-normal timeline-content text-muted ps-3">
                                                    {!! $event->text !!}
                                                    {{-- <span class="fw-bold fs-6 text-gray-800">{{ $event->message }}</span> --}}
                                                </div>
                                                <!--end::Text or Link-->
                                            </div>
                                            <!--end::Item-->
                                        @endforeach
                                    </div>
                                    <!--end::Timeline-->
                                </div>
                                <!--end: Card Body-->
                            </div>
                        </div>
                       </div>
                        <!--end: List Widget 5-->
                    </div>
                        @else
                        <!--begin::No User Alert-->
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10">
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Alert-->
                                    <div class="alert alert-warning d-flex align-items-center p-5">
                                        <!--begin::Icon-->
                                        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <!--end::Icon-->
                                        <!--begin::Wrapper-->
                                        <div class="d-flex flex-column">
                                            <!--begin::Title-->
                                            <h4 class="mb-1 text-warning">{{ __('translation.no_user_account_associated') }}</h4>
                                            <!--end::Title-->
                                            <!--begin::Content-->
                                            <span>{{ __('translation.no_user_account_description') }}</span>
                                            <!--end::Content-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Alert-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        <!--end::No User Alert-->
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
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.total_branches') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ count($merchant->branches) }} {{ __('translation.branches') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
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
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.total_terminals') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ count($merchant->terminals) }} {{ __('translation.terminals') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        @endif
                        <!--end::Terminal Details-->

                    </div>
                    <!--end::Details-->

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
            
        <!--end::Card-->
        </div>
        <!--end::Col-->
    </div>
    </div>
</div>
    <!--end::Row-->
@endsection 
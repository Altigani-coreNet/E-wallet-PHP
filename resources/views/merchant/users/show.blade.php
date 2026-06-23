@extends("layouts.merchant.merchant_layout")
@section('main-head', __('translation.user_details'))
@section('page_title', __('translation.user_details'))
@section('breadcrumbs')

    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.dashboard') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.users.index') }}" class="text-muted text-hover-primary">{{ __('translation.users') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.user_details') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
    @if(isset($user))
        <a href="{{ route('merchant.users.edit', $user->id) }}" class="btn btn-sm btn-primary me-3">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.edit') }}
        </a>
    @endif
    <a href="{{ route('merchant.users.index') }}" class="btn btn-sm btn-light">
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
            <!--begin::Body-->
            <div class="card-body py-3">
                @if(isset($user))
                    <x:user-profile-header :user="$user" active-tab="{{ request()->get('tab', 'overview') }}" />
                    
                    <!--end::User Overview-->

                    <!--begin::Details-->
                    <div class="row">
                        <!--begin::Basic Information-->
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_user_basic_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.basic_information') }}</h3>
                                    </div>
                                    <a href="{{ route('merchant.users.edit', $user->id) }}" class="btn btn-primary align-self-center">
                                        {{ __('translation.edit') }}
                                    </a>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.full_name') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                    
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.email') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->email ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.phone') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->phone ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.gender') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ ucfirst($user->gender ?? __('translation.not_available')) }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.status') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            @if(method_exists($user, 'getStatusWithSpan'))
                                                {!! $user->getStatusWithSpan() !!}
                                            @else
                                                <span class="badge badge-light-{{ $user->status ? 'success' : 'danger' }}">
                                                    {{ $user->status ? __('translation.active') : __('translation.inactive') }}
                                                </span>
                                            @endif
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    @if(isset($user->email_verified_at))
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.email_verified') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            @if($user->email_verified_at)
                                                <span class="badge badge-light-success">{{ __('translation.verified') }}</span>
                                                <span class="fw-bold fs-6 text-gray-600 ms-2">{{ $user->email_verified_at->format('Y-m-d H:i:s') }}</span>
                                            @else
                                                <span class="badge badge-light-warning">{{ __('translation.not_verified') }}</span>
                                            @endif
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                    @endif

                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        <!--end::Basic Information-->

                        <!--begin::Branch Details-->
                        @if($user->branch)
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_user_branch_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.branch_details') }}</h3>
                                    </div>
                                    <a href="{{ route('merchant.branches.edit', $user->branch->id) }}" class="btn btn-primary align-self-center">
                                        {{ __('translation.view_branch') }}
                                    </a>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.branch_name') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->branch->name ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                    
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.branch_address') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->branch->address ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.branch_phone') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->branch->phone ?? __('translation.not_available') }}</span>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.branch_status') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            @if(method_exists($user->branch, 'getStatusWithSpan'))
                                                {!! $user->branch->getStatusWithSpan() !!}
                                            @else
                                                <span class="badge badge-light-{{ $user->branch->is_active ? 'success' : 'danger' }}">
                                                    {{ $user->branch->is_active ? __('translation.active') : __('translation.inactive') }}
                                                </span>
                                            @endif
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

                        <!--begin::Roles Details-->
                        @if($user->roles->count() > 0)
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_user_roles_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.roles_details') }}</h3>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.assigned_roles') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            @foreach($user->roles as $role)
                                                <span class="badge badge-light-primary me-1">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                        @endif
                        <!--end::Roles Details-->

                        <!--begin::Terminal Details-->
                        @if(count($user->getTerminalIds()) > 0)
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_user_terminal_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.terminal_details') }}</h3>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                        <label class="col-lg-4 fw-bold text-muted">{{ __('translation.assigned_terminals') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                            <span class="fw-bolder fs-6 text-gray-800">{{ count($user->getTerminalIds()) }} {{ __('translation.terminals') }}</span>
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
                            <i class="ki-duotone ki-user fs-5x text-gray-500">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </div>
                        <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_user_found') }}</h3>
                        <p class="text-gray-500 fs-6">{{ __('translation.user_not_found_description') }}</p>
                        <a href="{{ route('merchant.users.index') }}" class="btn btn-primary">
                            {{ __('translation.back_to_users') }}
                        </a>
                    </div>
                    <!--end::Empty State-->
                @endif
            </div>
            
        <!--end::Card-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
@endsection 
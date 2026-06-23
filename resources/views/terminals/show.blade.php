@extends('layouts.admin.admin_layout')

@section('title', __('translation.terminal_details'))

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('terminals.index') }}" class="text-muted text-hover-primary">{{ __('translation.terminals_management') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.terminal_details') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        {{-- <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button> --}}
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Primary button-->
    <a href='{{ route('terminals.edit', $terminal->id)}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-pencil fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.edit') }}</a>

        <a href='{{ route('terminals.events', $terminal->id)}}' class="btn btn-sm fw-bold btn-primary">
            {{-- <i class="ki-duotone ki-calendar fs-3">
                <span class="path1"></span>
                <span class="path2"></span>
            </i> --}}
    
            {{ __('translation.events') }}</a>
    <!--end::Primary button-->
    <!--begin::Secondary button-->
    <a href='{{ route('terminals.index')}}' class="btn btn-sm fw-bold btn-light-danger">
        <i class="ki-duotone ki-arrow-left fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.back_to_list') }}</a>
    <!--end::Secondary button-->
</div>
@endsection

@section('content')
<div class="card mb-5 mb-xl-10" id="kt_terminal_basic_details">
    <!--begin::Card header-->
    <div class="card-header cursor-pointer">
        <!--begin::Card title-->
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ __('translation.basic_information') }}</h3>
        </div>
        <a href="{{ route('terminals.edit', $terminal->id) }}" class="btn btn-primary align-self-center">
            {{ __('translation.edit') }}
        </a>
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body p-9">
        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.terminal_name') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->name ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
        
        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.terminal_id') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="badge badge-primary fs-6">{{ $terminal->terminal_id ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.merchant') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->merchant->name ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.branch') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->branch->name ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.model') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->model ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.manufacturer') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->manufacturer ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

        <!--begin::Row-->
        <div class="row mb-7">
            <!--begin::Label-->
            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.serial_no') }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8">
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->serial_no ?? __('translation.not_available') }}</span>
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
                <span class="badge badge-light-{{ $terminal->is_active ? 'success' : 'danger' }} fw-bold">
                    {{ $terminal->is_active ? __('translation.active') : __('translation.in_active') }}
                </span>
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
                <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->created_at->format('M d, Y H:i') ?? __('translation.not_available') }}</span>
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->

    </div>
    <!--end::Card body-->
</div>

<!-- Additional Terminal Information -->
<div class="row">
    <div class="col-md-4">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ __('translation.device_information') }}</h3>
                </div>
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body p-9">
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.brand') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->brand ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.device_id') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->device_id ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.sdk_id') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->sdk_id ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.sdk_version') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->sdk_version ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.android_os') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->android_os ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.add_type') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->add_type ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

            </div>
            <!--end::Card body-->
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ __('translation.terminal_status') }}</h3>
                </div>
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body p-9">
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.terminal_status') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        {!! $terminal->getTerminalStatus() !!}
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.current_user') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->currentUser->name ?? __('translation.no_user_assigned') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                {{-- <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.last_user') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->lastUser->name ?? __('translation.no_user_assigned') }}</span>
                    </div>
                    <!--end::Col-->
                </div> --}}
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.updated_at') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bolder fs-6 text-gray-800">{{ $terminal->updated_at->format('M d, Y H:i') ?? __('translation.not_available') }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.terminal_groups') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        @if($terminal->terminalGroups->count() > 0)
                            @foreach($terminal->terminalGroups as $group)
                                <span class="badge badge-light-info me-1">{{ $group->name }}</span>
                            @endforeach
                        @else
                            <span class="fw-bolder fs-6 text-gray-800">{{ __('translation.no_groups_assigned') }}</span>
                        @endif
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-bold text-muted">{{ __('translation.assigned_users') }}</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        @if($terminal->users->count() > 0)
                            @foreach($terminal->users as $user)
                                <span class="badge badge-light-primary me-1">{{ $user->name }}</span>
                            @endforeach
                        @else
                            <span class="fw-bolder fs-6 text-gray-800">{{ __('translation.no_users_assigned') }}</span>
                        @endif
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->

            </div>
            <!--end::Card body-->
        </div>
    </div>

    <div class="col-md-4">
        
<!-- Terminal Activity Timeline -->
<div class="card mb-5 mb-xl-10">
    <!--begin::Card header-->
    <div class="card-header">
        <div class="card-title m-0">
            <h3 class="fw-bolder m-0">{{ __('translation.activity_timeline') }}</h3>
        </div>
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body pt-5">
        <!--begin::Timeline-->
        <div class="timeline-label">
            @foreach($terminal->LatestLogs as $event)
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
                    </div>
                    <!--end::Text or Link-->
                </div>
                <!--end::Item-->
            @endforeach
        </div>
        <!--end::Timeline-->
    </div>
    <!--end::Card body-->
</div>
    </div>
</div>

@endsection 
@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.user_details'))
@section('page_title', __('translation.user_details'))
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
            <a href="{{ route('users.index') }}" class="text-muted text-hover-primary">{{ __('translation.users') }}</a>
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
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-primary me-3">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.edit') }}
        </a>
    @endif
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-light">
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
                    <x:user-profile-header :user="$user" active-tab="{{ $tab ?? 'overview' }}" />
                    
                    <!--end::User Overview-->

                    <!--begin::Details-->
                    <div class="row">
                        @if($tab === 'overview')
                        <!--begin::Basic Information-->
                        <div class="col-lg-8">
                            <div class="card mb-5 mb-xl-10" id="kt_user_basic_details">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.basic_information') }}</h3>
                                    </div>
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary align-self-center">
                                        {{ __('translation.edit') }}
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
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->name ?? __('translation.not_available') }}</span>
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
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->email ?? __('translation.not_available') }}</span>
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
                                            <span class="fw-bolder fs-6 text-gray-800">{{ $user->phone ?? __('translation.not_available') }}</span>
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
                                                <span class="fw-bolder fs-6 text-gray-800">{{ $user->merchant->name ?? __('translation.not_available') }}</span>
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
                                                <span class="fw-bolder fs-6 text-gray-800">{{ $user->branch->name ?? __('translation.not_available') }}</span>
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

                                    <!--begin::Row-->
                                    <div class="row mb-7">
                                        <!--begin::Label-->
                                            <label class="col-lg-4 fw-bold text-muted">{{ __('translation.created_date') }}</label>
                                        <!--end::Label-->
                                        <!--begin::Col-->
                                        <div class="col-lg-8">
                                                <span class="fw-bolder fs-6 text-gray-800">{{ $user->created_at->format('M d, Y H:i') ?? __('translation.not_available') }}</span>
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
                                            class="text-muted fw-bold fs-7">{{$user->LatestLogs?->count()}} </span>
                                    </h3>
                                    <div class="card-toolbar">
                                        <!--begin::Menu-->
                                        <a href="{{route('users.sections', ['user' => $user->id , "type" => "events"])}}"
                                           class="btn btn-light-success btn-sm"> show All
                                            Events</a>
                                    </div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-5">
                                    @if($user->LatestLogs)
                                    <!--begin::Timeline-->
                                    <div class="timeline-label">
                                        @foreach($user->LatestLogs as $event)
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

                                    @endif
                                    <!--end::Timeline-->
                                </div>
                                <!--end: Card Body-->
                            </div>
                        </div>
                        <!--end::Basic Information-->

                        @elseif($tab === 'terminals')
                            <!--begin::Terminals Information-->
                        <div class="col-lg-12">
                                <div class="card mb-5 mb-xl-10" id="kt_user_terminals">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                            <h3 class="fw-bolder m-0">{{ __('translation.assigned_terminals') }}</h3>
                                        </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                        @if($user->terminals && count($user->terminals) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th class="w-25px">
                                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check"/>
                                                                </div>
                                                            </th>
                                                            <th class="min-w-150px">{{ __('translation.terminal_name') }}</th>
                                                            <th class="min-w-140px">{{ __('translation.device_id') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.terminal_id') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($user->terminals as $terminal)
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                        <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="symbol symbol-45px me-5">
                                                                            <img src="{{ asset('assets/media/avatars/300-1.jpg') }}" alt=""/>
                                    </div>
                                                                        <div class="d-flex justify-content-start flex-column">
                                                                            <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $terminal->name }}</a>
                                                                            <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $terminal->serial_no ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                                                </td>
                                                                <td>
                                                                    <a href="#" class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $terminal->device_id }}</a>
                                                                </td>
                                                                <td>
                                                                    <span class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $terminal->terminal_id }}</span>
                                                                </td>
                                                                <td>
                                                                    @if(method_exists($terminal, 'getStatusWithSpan'))
                                                                        {!! $terminal->getStatusWithSpan() !!}
                                                                    @else
                                                                        <span class="badge badge-light-{{ $terminal->is_active ? 'success' : 'danger' }}">
                                                                            {{ $terminal->is_active ? __('translation.active') : __('translation.inactive') }}
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-end">
                                                                    <a href="{{ route('terminals.show', $terminal->id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                                        <i class="ki-duotone ki-eye fs-2">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                        </i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                        </div>
                                        @else
                                            <div class="text-center py-10">
                                                <div class="mb-7">
                                                    <i class="ki-duotone ki-truck fs-5x text-gray-500">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                        <span class="path4"></span>
                                                        <span class="path5"></span>
                                                    </i>
                                    </div>
                                                <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_terminals_assigned') }}</h3>
                                                <p class="text-gray-500 fs-6">{{ __('translation.no_terminals_description') }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    <!--end::Card body-->
                                        </div>
                                    </div>
                            <!--end::Terminals Information-->

                        @elseif($tab === 'events')
                            <!--begin::Events Information-->
                        <div class="col-lg-12">
                                <div class="card mb-5 mb-xl-10" id="kt_user_events">
                                <!--begin::Card header-->
                                <div class="card-header border-0 cursor-pointer" role="button">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                            <h3 class="fw-bold m-0">{{ __('translation.events') }}</h3>
                                        </div>
                                    <!--end::Card title-->
                                </div>
                                <!--end::Card header-->
                                
                                <!--begin::Content-->
                                <div class="collapse show">
                                    <!--begin::Card body-->
                                    <div class="card-body border-top p-9">
                                        <!--end::Card header-->
                                        <!--begin::Card body-->
                                        <div class="card-body py-0">
                                            <!--begin::Table wrapper-->
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed fs-6 gy-5" id="user-events-table">
                                                    <!--begin::Table head-->
                                                    <thead>
                                                        <!--begin::Table row-->
                                                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                            <th class="min-w-70px">{{ __('translation.id') }}</th>
                                                            <th class="min-w-125px">{{ __('translation.time') }}</th>
                                                            <th class="min-w-125px">{{ __('translation.events') }}</th>
                                                            <th class="min-w-200px">{{ __('translation.event_description') }}</th>
                                                            <th class="min-w-200px">{{ __('translation.message') }}</th>
                                                            <th class="min-w-70px">{{ __('translation.actions') }}</th>
                                                        </tr>
                                                        <!--end::Table row-->
                                                    </thead>
                                                    <!--end::Table head-->
                                                </table>
                                            </div>
                                            <!--end::Table wrapper-->
                                        </div>
                                        <!--end::Card body-->
                                    </div>
                                    <!--end::Card body-->
                                </div>
                                <!--end::Content-->
                                        </div>
                                    </div>
                            <!--end::Events Information-->

                        @elseif($tab === 'transactions')
                            <!--begin::Transactions Information-->
                            <div class="col-lg-12">
                                <div class="card mb-5 mb-xl-10" id="kt_user_transactions">
                                    <!--begin::Card header-->
                                    <div class="card-header cursor-pointer">
                                        <!--begin::Card title-->
                                        <div class="card-title m-0">
                                            <h3 class="fw-bolder m-0">{{ __('translation.user_transactions') }}</h3>
                                        </div>
                                    </div>
                                    <!--end::Card header-->
                                    <!--begin::Card body-->
                                    <div class="card-body p-9">
                                        @php
                                            $transactions = collect();
                                            foreach($user->terminals as $terminal) {
                                                if($terminal->transactions) {
                                                    $transactions = $transactions->merge($terminal->transactions);
                                                }
                                            }
                                            $transactions = $transactions->sortByDesc('created_at')->take(100);
                                        @endphp

                                        @if($transactions->count() > 0)
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th class="w-25px">
                                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check"/>
                                        </div>
                                                            </th>
                                                            <th class="min-w-150px">{{ __('translation.transaction_id') }}</th>
                                                            <th class="min-w-140px">{{ __('translation.amount') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.terminal') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.date') }}</th>
                                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($transactions as $transaction)
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                        <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                    </div>
                                                                </td>
                                                                <td>
                                                                    <a href="#" class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $transaction->transaction_id }}</a>
                                                                    <span class="text-muted fw-semibold text-muted d-block fs-7">RRN: {{ $transaction->rrn ?? 'N/A' }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-dark fw-bold text-hover-primary d-block fs-6">${{ number_format($transaction->amount, 2) }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-light-{{ $transaction->status === 'approved' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}">
                                                                        {{ ucfirst($transaction->status) }}
                                                </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $transaction->created_at->format('M d, Y H:i') }}</span>
                                                                </td>
                                                                <td class="text-end">
                                                                    <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                                        <i class="ki-duotone ki-eye fs-2">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                        </i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                        </div>
                        @else
                                            <div class="text-center py-10">
                                                <div class="mb-7">
                                                    <i class="ki-duotone ki-chart-simple fs-5x text-gray-500">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                                        <span class="path4"></span>
                                                    </i>
                                                </div>
                                                <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_transactions_found') }}</h3>
                                                <p class="text-gray-500 fs-6">{{ __('translation.no_transactions_description') }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    <!--end::Card body-->
                                </div>
                            </div>
                            <!--end::Transactions Information-->

                        @elseif($tab === 'user_groups')
                            <!--begin::User Groups Information-->
                        <div class="col-lg-12">
                                <div class="card mb-5 mb-xl-10" id="kt_user_groups">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                            <h3 class="fw-bolder m-0">{{ __('translation.user_groups') }}</h3>
                                        </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                        @if($user->userGroups && count($user->userGroups) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th class="w-25px">
                                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check"/>
                                        </div>
                                                            </th>
                                                            <th class="min-w-150px">{{ __('translation.group_name') }}</th>
                                                            <th class="min-w-140px">{{ __('translation.description') }}</th>
                                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($user->userGroups as $userGroup)
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                                        <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="symbol symbol-45px me-5">
                                                                            <div class="symbol-label bg-light-primary">
                                                                                <i class="ki-duotone ki-abstract-26 text-primary fs-2x">
                                                                                    <span class="path1"></span>
                                                                                    <span class="path2"></span>
                                                                                </i>
                                        </div>
                                    </div>
                                                                        <div class="d-flex justify-content-start flex-column">
                                                                            <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $userGroup->name }}</a>
                                        </div>
                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="text-dark fw-bold text-hover-primary d-block fs-6">{{ $userGroup->description ?? 'N/A' }}</span>
                                                                </td>
                                                                <td>
                                                                    @if(method_exists($userGroup, 'getStatusWithSpan'))
                                                                        {!! $userGroup->getStatusWithSpan() !!}
                                            @else
                                                                        <span class="badge badge-light-{{ $userGroup->is_active ? 'success' : 'danger' }}">
                                                                            {{ $userGroup->is_active ? __('translation.active') : __('translation.inactive') }}
                                                </span>
                                            @endif
                                                                </td>
                                                                <td class="text-end">
                                                                    <a href="{{ route('user-groups.show', $userGroup->id) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                                        <i class="ki-duotone ki-eye fs-2">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                        </i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-10">
                                                <div class="mb-7">
                                                    <i class="ki-duotone ki-abstract-26 fs-5x text-gray-500">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_user_groups') }}</h3>
                                                <p class="text-gray-500 fs-6">{{ __('translation.no_user_groups_description') }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    <!--end::Card body-->
                                </div>
                            </div>
                            <!--end::User Groups Information-->

                        @elseif($tab === 'attachments')
                            <!--begin::Attachments Information-->
                        <div class="col-lg-12">
                                <div class="card mb-5 mb-xl-10" id="kt_user_attachments">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                            <h3 class="fw-bolder m-0">{{ __('translation.attachments') }}</h3>
                                        </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                        @if($user->attachments && count($user->attachments) > 0)
                                            <div class="row g-6 g-xl-9">
                                                @foreach($user->attachments as $attachment)
                                                    <div class="col-md-6 col-xl-4">
                                                        <div class="card border-hover-primary">
                                                            <div class="card-header border-0 pt-9">
                                                                <div class="card-title m-0">
                                                                    <div class="symbol symbol-50px w-50px bg-light">
                                                                        @if(in_array(strtolower(pathinfo($attachment->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                                                                            <img src="{{ asset($attachment->file_path) }}" alt="Attachment" class="w-100 h-100 object-fit-cover"/>
                                                                        @else
                                                                            <i class="ki-duotone ki-file fs-2x text-primary">
                                                                                <span class="path1"></span>
                                                                                <span class="path2"></span>
                                                                            </i>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="card-toolbar">
                                                                    <div class="dropdown">
                                                                        <button class="btn btn-icon btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                                                            <i class="ki-duotone ki-gear fs-2">
                                                                                <span class="path1"></span>
                                                                                <span class="path2"></span>
                                                                                <span class="path3"></span>
                                                                                <span class="path4"></span>
                                                                                <span class="path5"></span>
                                                                            </i>
                                                                        </button>
                                                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold w-125px" data-kt-menu="true">
                                                                            <div class="menu-item">
                                                                                <a href="{{ asset($attachment->file_path) }}" target="_blank" class="menu-link">
                                                                                    <i class="ki-duotone ki-eye fs-2 me-2">
                                                                                        <span class="path1"></span>
                                                                                        <span class="path2"></span>
                                                                                    </i>
                                                                                    {{ __('translation.view') }}
                                                                                </a>
                                                                            </div>
                                                                            <div class="menu-item">
                                                                                <a href="{{ asset($attachment->file_path) }}" download class="menu-link">
                                                                                    <i class="ki-duotone ki-download fs-2 me-2">
                                                                                        <span class="path1"></span>
                                                                                        <span class="path2"></span>
                                                                                    </i>
                                                                                    {{ __('translation.download') }}
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card-body p-6">
                                                                <div class="fs-7 fw-bold text-gray-800 mb-1">{{ $attachment->file_name }}</div>
                                                                <div class="fs-7 fw-semibold text-gray-500">{{ $attachment->file_type ?? 'Unknown' }}</div>
                                                                <div class="fs-7 fw-semibold text-gray-500">{{ number_format($attachment->file_size / 1024, 2) }} KB</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-10">
                                                <div class="mb-7">
                                                    <i class="ki-duotone ki-file fs-5x text-gray-500">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_attachments') }}</h3>
                                                <p class="text-gray-500 fs-6">{{ __('translation.no_attachments_description') }}</p>
                                        </div>
                                        @endif
                                    </div>
                                    <!--end::Card body-->
                                </div>
                            </div>
                            <!--end::Attachments Information-->
                        @endif
                    </div>
                    <!--end::Details-->

                @else
                    <!--begin::Empty State-->
                    <div class="text-center py-10">
                        <div class="mb-7">
                            <i class="ki-duotone ki-user fs-5x text-gray-500">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_user_found') }}</h3>
                        <p class="text-gray-500 fs-6">{{ __('translation.user_not_found_description') }}</p>
                        <a href="{{ route('users.index') }}" class="btn btn-primary">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize User Events DataTable only when events tab is active
    @if($tab === 'events')
    let userEventsTable = $('#user-events-table').DataTable({
        dom: "tiplr",
        serverSide: true,
        processing: true,
        language: {
            url: "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        },
        ajax: {
            url: '{{ route("logs.data") }}',
            data: function(d) {
                d.loggable_id = '{{ $user->id }}';
                d.loggable_type = '{{ addslashes(get_class($user)) }}';
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'time', name: 'time' },
            { 
                data: 'action', 
                name: 'action',
                render: function(data) {
                    return data; // The action column is already formatted with HTML from the server
                }
            },
            { data: 'text', name: 'text' },
            { data: 'message', name: 'message' },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            $('.record__select').prop('checked', false);
            $('#record__select-all').prop('checked', false);
            $('#record-ids').val();
            $('#bulk-delete').attr('disabled', true);
        }
    });
    @endif
});
</script>
@endpush


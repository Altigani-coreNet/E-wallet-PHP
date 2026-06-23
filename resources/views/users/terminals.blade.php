@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.user_terminals'))
@section('page_title', __('translation.user_terminals'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.user_terminals') }}</li>
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
            {{ __('translation.edit_user') }}
        </a>

        <a href="{{ route('terminal-assignments.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-success me-3">
            <i class="ki-duotone ki-plus fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.assign_terminals') }}
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
    @if(isset($user))
        <x:user-profile-header :user="$user" active-tab="terminals" />

        <!--begin::Row-->
        <div class="row gy-5 g-xl-8">
            
            <!--begin::User Groups Card-->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10">
                    <!--begin::Card header-->
                    <div class="card-header cursor-pointer">
                        <div class="card-title m-0">
                            <h3 class="fw-bolder m-0">{{ __('translation.user_groups') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $user->userGroups()->count() }} {{ __('translation.groups') }}</span>
                        </div>
                    </div>
                    <!--end::Card header-->
                    
                    <!--begin::Card body-->
                    <div class="card-body p-3">
                        @if($user->userGroups()->count() > 0)
                            <!--begin::Table-->
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th class="w-25px">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check" />
                                                </div>
                                            </th>
                                            <th class="min-w-150px">{{ __('translation.group_name') }}</th>
                                            <th class="min-w-140px">{{ __('translation.group_id') }}</th>
                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->userGroups as $userGroup)
                                        <tr>
                                            <td>
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input widget-9-check" type="checkbox" value="1" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex justify-content-start flex-column">
                                                        <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $userGroup->name }}</a>
                                                        <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $userGroup->description ?? __('translation.no_description') }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light-info fs-7">{{ $userGroup->group_id }}</span>
                                            </td>
                                            <td>
                                                {!! $userGroup->getStatusWithSpan() !!}
                                            </td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
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
                            <!--end::Table-->
                        @else
                            <!--begin::Empty State-->
                            <div class="text-center py-10">
                                <i class="ki-duotone ki-users fs-3x text-gray-400 mb-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <h4 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_user_groups') }}</h4>
                                <p class="text-gray-500 fs-6">{{ __('translation.user_not_assigned_to_groups') }}</p>
                            </div>
                            <!--end::Empty State-->
                        @endif
                    </div>
                    <!--end::Card body-->
                </div>
            </div>
            <!--end::User Groups Card-->

            <!--begin::User Devices Card-->
        
            <!--end::User Devices Card-->

            <!--begin::Terminal Groups Card-->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10 p-3">
                    <!--begin::Card header-->
                    <div class="card-header cursor-pointer">
                        <div class="card-title m-0">
                            <h3 class="fw-bolder m-0">{{ __('translation.terminal_groups') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $user->terminalGroups()->count() }} {{ __('translation.groups') }}</span>
                        </div>
                    </div>
                    <!--end::Card header-->
                    
                    <!--begin::Card body-->
                    <div class="card-body p-3">
                        @if($user->terminalGroups()->count() > 0)
                            <!--begin::Table-->
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th class="w-25px">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check" />
                                                </div>
                                            </th>
                                            <th class="min-w-150px">{{ __('translation.group_name') }}</th>
                                            <th class="min-w-140px">{{ __('translation.group_id') }}</th>
                                            <th class="min-w-120px">{{ __('translation.terminals_count') }}</th>
                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->terminalGroups as $terminalGroup)
                                        <tr>
                                            <td>
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input widget-9-check" type="checkbox" value="1" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex justify-content-start flex-column">
                                                        <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $terminalGroup->name }}</a>
                                                        <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $terminalGroup->description ?? __('translation.no_description') }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light-info fs-7">{{ $terminalGroup->group_id }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-light-success fs-7">{{ $terminalGroup->terminals()->count() }} {{ __('translation.terminals') }}</span>
                                            </td>
                                            <td>
                                                {!! $terminalGroup->getStatusWithSpan() !!}
                                            </td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
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
                            <!--end::Table-->
                        @else
                            <!--begin::Empty State-->
                            <div class="text-center py-10">
                                <i class="ki-duotone ki-terminal fs-3x text-gray-400 mb-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h4 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_terminal_groups') }}</h4>
                                <p class="text-gray-500 fs-6">{{ __('translation.user_not_assigned_to_terminal_groups') }}</p>
                            </div>
                            <!--end::Empty State-->
                        @endif
                    </div>
                    <!--end::Card body-->
                </div>
            </div>
            <!--end::Terminal Groups Card-->

            <!--begin::User Terminals Card-->
            <div class="col-xl-12">
                <div class="card mb-5 mb-xl-10 p-3">
                    <!--begin::Card header-->
                    <div class="card-header cursor-pointer">
                        <div class="card-title m-0">
                            <h3 class="fw-bolder m-0">{{ __('translation.user_terminals') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ count($user->getTerminalIds()) }} {{ __('translation.terminals') }}</span>
                        </div>
                    </div>
                    <!--end::Card header-->
                    
                    <!--begin::Card body-->
                    <div class="card-body p-3">
                        @if(count($user->getTerminalIds()) > 0)
                            @php
                                $userTerminals = \App\Models\Terminal::whereIn('id', $user->getTerminalIds())->get();
                            @endphp
                            
                            <!--begin::Table-->
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th class="w-25px">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" value="1" data-kt-check="true" data-kt-check-target=".widget-9-check" />
                                                </div>
                                            </th>
                                            <th class="min-w-150px">{{ __('translation.terminal_name') }}</th>
                                            <th class="min-w-140px">{{ __('translation.terminal_id') }}</th>
                                            <th class="min-w-120px">{{ __('translation.model') }}</th>
                                            <th class="min-w-120px">{{ __('translation.manufacturer') }}</th>
                                            <th class="min-w-120px">{{ __('translation.serial_number') }}</th>
                                            <th class="min-w-120px">{{ __('translation.status') }}</th>
                                            <th class="min-w-100px text-end">{{ __('translation.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userTerminals as $terminal)
                                        <tr>
                                            <td>
                                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input widget-9-check" type="checkbox" value="1" />
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="d-flex justify-content-start flex-column">
                                                        <a href="#" class="text-dark fw-bold text-hover-primary fs-6">{{ $terminal->name }}</a>
                                                        @if($terminal->merchant)
                                                            <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $terminal->merchant->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light-info fs-7">{{ $terminal->terminal_id }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold fs-6 text-gray-800">{{ $terminal->model ?? __('translation.not_available') }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold fs-6 text-gray-800">{{ $terminal->manufacturer ?? __('translation.not_available') }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold fs-6 text-gray-800">{{ $terminal->serial_no ?? __('translation.not_available') }}</span>
                                            </td>
                                            <td>
                                                @if($terminal->is_active)
                                                    <span class="badge badge-light-success fs-7">{{ __('translation.active') }}</span>
                                                @else
                                                    <span class="badge badge-light-warning fs-7">{{ __('translation.inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
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
                            <!--end::Table-->
                        @else
                            <!--begin::Empty State-->
                            <div class="text-center py-10">
                                <i class="ki-duotone ki-terminal fs-3x text-gray-400 mb-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h4 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_terminals_assigned') }}</h4>
                                <p class="text-gray-500 fs-6">{{ __('translation.user_has_no_terminals') }}</p>
                            </div>
                            <!--end::Empty State-->
                        @endif
                    </div>
                    <!--end::Card body-->
                </div>
            </div>
            <!--end::User Terminals Card-->

        </div>
        <!--end::Row-->

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
            <a href="{{ route('users.index') }}" class="btn btn-primary">
                {{ __('translation.back_to_users') }}
            </a>
        </div>
        <!--end::Empty State-->
    @endif
@endsection


@extends('layouts.merchant.merchant_layout')
@section('main-head', __('translation.roles_managements'))
@section('content')
    <div class="content-wrapper">
        <div id="kt_content_container" class="container-xxl">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>{{ __('translation.edit_role') }}</h2>
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
                    <form action="{{ route('merchant.roles.update', $role->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("PUT")
                        <div class="row">
                            <x:text-input class="col-md-12" name='role_name'
                                          filedname="name"
                                          value="{{$role->name}}"
                            />
                            <label class="fs-6 fw-bold mb-2">{{ __('translation.permissions') }}</label>
                            <div class="form-group row last">
                                <div class="col-md-12">
                                    @php
                                        $webPermissions = [
                                            'dashboard' => [
                                                'view_dashboard',
                                                'charts',
                                                'recent_activities',
                                            ],
                                            'branches' => [
                                                'view_branches',
                                                'request_branches',
                                                'edit_branches',
                                                'delete_branches'
                                            ],
                                            'terminals' => [
                                                'view_terminals',
                                                'create_terminals',
                                                'edit_terminals',
                                                'delete_terminals'
                                            ],
                                            'terminal_groups' => [
                                                'view_terminal_assignments',
                                                'edit_terminal_assignments',
                                                'delete_terminal_assignments',
                                                'assign_terminals',
                                            ],
                                            'settings' => [
                                                'view_settings',
                                                'create_settings',
                                                'edit_settings',
                                                'delete_settings'
                                            ],
                                            'users' => [
                                                'view_users',	
                                                'create_users',
                                                'edit_users',
                                                'delete_users',
                                                'activate_users',
                                                'deactivate_users',
                                                'reset_password',
                                            ],
                                            'users_groups' => [
                                                'view_users_groups',
                                                'create_users_groups',
                                                'edit_users_groups',
                                                'delete_users_groups', 
                                                'add_users_to_groups',
                                                'remove_users_from_groups', 
                                            ],
                                           'roles' => [
                                                'view_roles',
                                                'create_roles',
                                                'edit_roles',
                                                'delete_roles',
                                                'assign_roles',
                                           ],
                                           'transactions' => [
                                               'view_transactions',
                                               'delete_transactions',
                                               'void_transactions',
                                               'refund_transactions',
                                               'view_transactions_history',
                                           ],
                                            'settlements' => [
                                                'view_settlements',
                                           ],
                                           'link_payments' => [
                                                'view_link_payments',
                                                'create_link_payments',
                                                'edit_link_payments',
                                                'delete_link_payments',
                                           ],
                                           'customers' => [
                                                'view_customers',
                                                'create_customers',
                                                'edit_customers',
                                                'delete_customers',
                                           ],
                                           'reports_management' => [
                                                'view_reports',
                                            ],
                                            'attachments_management' => [
                                                'show_attachments',
                                                'delete_attachments'
                                            ],
                                            'notifications' => [
                                                'view_notifications',
                                                'delete_notifications'
                                            ],
                                            'contract_terms' => [
                                                'view_contract_terms',
                                           ],
                                           'services_fees' => [
                                                'view_services_fees',
                                           ], 
                                        ];
                                    @endphp
                                    
                                    @foreach($webPermissions as $groupName => $permissions)
                                        <div class="row mb-3">
                                            <div class="col-3">
                                                <div class="animated-checkbox">
                                                    <label class="m-0">
                                                        <input type="checkbox" 
                                                               class="form-check-input group-checkbox" 
                                                               data-group="{{ $groupName }}"
                                                               id="group_{{ $groupName }}">
                                                        <span class="label-text fs-6 fw-bold text-capitalize">
                                                            {{ __('translation.' . $groupName) }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-9">
                                                <div class="row">
                                                    @foreach($permissions as $permissionName)
                                                        @php
                                                            $permissionItem = $permission->where('name', $permissionName)->first();
                                                        @endphp
                                                        @if($permissionItem)
                                                            <div class="col-md-4 mb-2">
                                                                <div class="animated-checkbox">
                                                                    <label class="m-0">
                                                                        <input type="checkbox" name="permission[]"
                                                                               class="record_select form-check-input permission-checkbox"
                                                                               data-group="{{ $groupName }}"
                                                                               value="{{ $permissionItem->id }}"
                                                                               @checked(in_array($permissionItem->id, $rolePermissions))
                                                                        >
                                                                        <span class="label-text">
                                                                            {{ __('translation.' . $permissionName) }}
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    {{__('translation.save')}}
                                </button>
                                <a href="{{ route('merchant.roles.index') }}" class="btn btn-light-danger">
                                    {{__('translation.cancel')}}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle group checkbox functionality
    $('.group-checkbox').on('change', function() {
        var groupName = $(this).data('group');
        var isChecked = $(this).is(':checked');
        
        // Check/uncheck all permissions in this group
        $('.permission-checkbox[data-group="' + groupName + '"]').prop('checked', isChecked);
    });
    
    // Handle individual permission checkbox changes
    $('.permission-checkbox').on('change', function() {
        var groupName = $(this).data('group');
        var groupCheckbox = $('#group_' + groupName);
        var totalPermissions = $('.permission-checkbox[data-group="' + groupName + '"]').length;
        var checkedPermissions = $('.permission-checkbox[data-group="' + groupName + '"]:checked').length;
        
        // Update group checkbox state based on individual permissions
        if (checkedPermissions === 0) {
            groupCheckbox.prop('checked', false);
            groupCheckbox.prop('indeterminate', false);
        } else if (checkedPermissions === totalPermissions) {
            groupCheckbox.prop('checked', true);
            groupCheckbox.prop('indeterminate', false);
        } else {
            groupCheckbox.prop('checked', false);
            groupCheckbox.prop('indeterminate', true);
        }
    });
    
    // Initialize group checkbox states on page load
    $('.group-checkbox').each(function() {
        var groupName = $(this).data('group');
        var totalPermissions = $('.permission-checkbox[data-group="' + groupName + '"]').length;
        var checkedPermissions = $('.permission-checkbox[data-group="' + groupName + '"]:checked').length;
        
        if (checkedPermissions === 0) {
            $(this).prop('checked', false);
            $(this).prop('indeterminate', false);
        } else if (checkedPermissions === totalPermissions) {
            $(this).prop('checked', true);
            $(this).prop('indeterminate', false);
        } else {
            $(this).prop('checked', false);
            $(this).prop('indeterminate', true);
        }
    });
});
</script>
@endpush

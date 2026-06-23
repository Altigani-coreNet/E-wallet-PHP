@extends('layouts.admin.admin_layout')

@section('main-head', 'Add Terminal Group')

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('terminal-groups.index') }}" class="text-muted text-hover-primary">{{ __('translation.terminals_management') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.terminal_groups') }}</li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.add_terminal_group') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('terminal-groups.index') }}" class="btn btn-light-danger btn-sm me-3">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.back') }}
        </a>
    </div>
</div>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <!-- React component will be mounted here -->
            
            <div id="terminal-group-form-root" 
                 data-admin-token="{{ session('admin_api_token') }}">
            </div>
        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
<script>
    // The React component will automatically mount when the page loads
    // Make sure @vite(['resources/css/app.css', 'resources/js/app.jsx']) is included in the layout
</script>
@endpush


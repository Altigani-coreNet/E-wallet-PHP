@extends('layouts.admin.admin_layout')
@section('main-head', __('translation.roles_managements'))
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1" >
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.roles') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.edit_role') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
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
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-light-danger me-3">
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
                        <form action="{{auth()->guard("admin")->check() ? route("admin.roles.update", $role->id) : route("roles.update", $role->id)}}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method("PUT")
                                    <div class="row">
                                        <x:text-input class="col-md-12" name='role_name'
                                                      filedname="name"
                                                      value="{{$role->name}}"
                                        />
                            <label class="fs-6 fw-bold mb-2">{{ __('translation.permissions') }}</label>
                                        <div class="form-group row last">
                                <div class="col-md-12 row">
                                                @foreach($permission as $value)
                                                    <div class="animated-checkbox col-6 p-1">
                                                        <label class="m-0">
                                                            <input type="checkbox" name="permission[]"
                                                                   class="record_select form-check-input"
                                                                   value="{{ $value->id }}"
                                                                @checked(in_array($value->id, $rolePermissions))
                                                            >
                                                            <span class="label-text">
                                                    {{ __('translation.' . $value->name) }}
                                                            </span>
                                                        </label>
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
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-light-danger">
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


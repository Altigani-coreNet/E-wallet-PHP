{{-- @extends('layouts.admin.admin') --}}
@extends('layouts.admin.admin_layout')

@section('main-head' , __('translation.edit_user_information'))
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('users.index') }}" class="text-muted text-hover-primary">{{ __('translation.users_management') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.edit_user') }}</li>
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
    <a href='{{ route('users.index')}}' class="btn btn-sm fw-bold btn-light-danger">
        <i class="ki-duotone ki-arrow-left fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.back_to_list') }}</a>
    <!--end::Primary button-->
</div>
@endsection

@section('content')

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <form action="{{route('users.update' , $user->id)}}" method="post" enctype="multipart/form-data">
                @csrf
                @method("put")
                <div class="row">
                    <div class="row col-md-9">
                        <div class="card ">
                            <div class="card-header border-0 ">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>{{ __('translation.edit_user_information') }}</h2>
                                </div>
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">
                                    <!--begin::Toolbar-->
                                    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                                        {{-- <a href="{{ route('users.index') }}" class="btn btn-light-danger me-3">
                                            <i class="ki-duotone ki-arrow-left fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ __('translation.back') }}
                                        </a> --}}
                                    </div>
                                    <!--end::Toolbar-->
                                </div>
                                <!--end::Card toolbar-->
                            </div>
                            
                            <div class="card-body p-3">
                                <div class="col-md-12">
                                    <div class="">
                                        <div class="row">
                                            <x:text-input class="col-md-6" name='name' filedname="name"
                                                          value="{{$user->name}}"/>
                                           
                                            <x:text-input class="col-md-6" name='email' filedname="email"
                                                          value="{{$user->email}}"/>
                                            <x:text-input class="col-md-6" name='password' filedname="password"/>
                                            <x:text-input class="col-md-6" name='phone' filedname="phone"
                                                          value="{{$user->phone}}"/>
                                            
                                            <x:select-options class="col-md-6" name="gender" filed-name="gender"
                                                              :options="['male', 'female']"/>
                                            <x:select2-input class="col-md-6" name="merchant" filed-name="merchant_id"
                                                              url="{{route('merchants.select')}}" 
                                                              value="{{$user->merchant_id}}" />
                                            <x:select2-input class="col-md-6" name="branch" filed-name="branch_id"
                                                              url="{{route('branches.select')}}" 
                                                              value="{{$user->branch_id}}" />
                                            <x:select2-multiple class="col-md-6" name="roles"
                                                              filed-name="roles[]"
                                                              url="{{route('roles.select')}}" />

                                                              @if ($errors->any())
                                                <div class="alert alert-danger mt-3">
                                                    <ul>
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            <div class="mt-10">
                                                <button class="btn-primary btn">
                                                    {{__('translation.update')}}
                                                </button>
                                                <a href="#" onclick="window.history.back()"
                                                   class="btn btn-light-danger">
                                                    {{__('translation.cancel')}}
                                                </a>
                                            </div>
                                        </div>
                                    </div><!-- end of tile -->
                                </div><!-- end of col -->
                            </div>
                        </div>

                    </div><!-- en of row -->
                    <div class="col-md-3">
                        <div class="card-body p-3">
                            <div class="col-md-12">
                                <div class="card p-4">
                                    <x:image-picker class="col-md-12" name="user_profile_image"
                                                    value="{{$user->getProfileImageApi()}}" filed-name='image2'
                                                    real-filed-id="another_file"/>
                                    <input type="file" name="profile_image" id="another_file" class="d-none">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </div>

@endsection

@push("scripts")
    <script>
        // Handle merchant selection and update branch dropdown
        $('#merchant_id').on('change', function () {
            var merchantId = $(this).val();
            var branchSelect = $('#branch_id');

            // Clear and disable branch dropdown
            branchSelect.empty().append('<option value="">Select Branch</option>').prop('disabled', true);

            if (merchantId) {
                // Enable branch dropdown and load branches for selected merchant
                branchSelect.prop('disabled', false);
                
                branchSelect.select2({
                    ajax: {
                        url: '{{route("branches.select")}}',
                        type: 'get',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term,
                                merchant_id: merchantId,
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response
                            };
                        },
                        cache: true
                    }
                });
            }
        });

        // Initialize branch dropdown as disabled if no merchant is selected
        $(document).ready(function() {
            var merchantId = $('#merchant_id').val();
            if (!merchantId) {
                $('#branch_id').prop('disabled', true);
            }
        });
    </script>
@endpush



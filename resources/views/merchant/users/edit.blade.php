@extends('layouts.merchant.merchant_layout')

@section('main-head' , __('translation.edit_user_information'))
@section('content')

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <form action="{{route('merchant.users.update' , $user->id)}}" method="post" enctype="multipart/form-data">
                @csrf
                @method("put")
                <div class="row">
                    <div class="row col-md-9">
                        <div class="card ">
                            <div class="card-header border-0 ">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>{{ __('translation.add_new_users') }}</h2>
                                </div>
                                <!--begin::Card toolbar-->
                                <div class="card-toolbar">
                                    <!--begin::Toolbar-->
                                    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                                        <a href="{{ route('merchant.users.index') }}" class="btn btn-light-danger ">
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
                            <div class="card-body p-3">
                                <div class="col-md-12">
                                    <div class="">
                                        <div class="row">
                                            <x:text-input class="col-md-6" name='name' filedname="name"
                                                          value="{{$user->name}}"/>
                                           
                                            <x:text-input class="col-md-6" name='email' filedname="email"
                                                          value="{{$user->email}}"/>
                                            {{-- <x:text-input class="col-md-6" name='password' filedname="password"/> --}}
                                            <x:text-input class="col-md-6" name='phone' filedname="phone"
                                                          value="{{$user->phone}}"/>
                                            
                                            <x:select-options class="col-md-6" name="gender" filed-name="gender"
                                                              :options="['male', 'female']" :value="$user->gender"/>
                                            <x:select2-input class="col-md-6" name="branch" filed-name="branch_id"
                                                              url="{{route('merchant.branches.select')}}" 
                                                              value="{{$user->branch_id}}" />
                                            <x:select2-multiple class="col-md-6" name="roles"
                                                              filed-name="roles[]"
                                                              url="{{route('merchant.roles.select')}}" 
                                                              :value="implode(',', $user->roles->pluck('id')->toArray())" />

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
        // Handle branch selection for merchant
        $('#branch_id').on('change', function () {
            var branchId = $(this).val();
            // You can add any branch-specific logic here if needed
        });

        // Initialize branch dropdown
        $(document).ready(function() {
            // Any initialization code can go here
        });
    </script>
@endpush 
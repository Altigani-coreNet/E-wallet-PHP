@extends('layouts.admin.admin_layout')

@section('main-head' , ___('translation.add_new_category'))
@section('page_title',  'create category')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1" >
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">Home</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">Categories</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">Edit Category</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{__('translation.edit_category')}}</h3>
                    <div class="card-toolbar">
                        <a href="{{ url()->previous() }}" class="btn btn-sm btn-light-danger">
                            <i class="fas fa-arrow-left"></i> {{__('translation.back')}}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{route('categories.update', $category->id)}}" method="post" enctype="multipart/form-data" class="formHasFilePond" id="formHasFilePond">
                        @csrf
                        @method('put')
                        <div class="row">
                            <x:text-input class="col-md-6" name="category_name_in_arabic"
                                          filedname='name[ar]'
                                          value="{{$category->getTranslation('name', 'ar')}}"/>
                            <x:text-input class="col-md-6" name="category_name_in_english"
                                          filedname='name[en]'
                                          value="{{$category->getTranslation('name', 'en')}}"/>
                            <x:status-filed name="category_status" filed-name='status' class="col-md-6"
                                            value="{{$category->status}}"/>
                            <x:image-picker class="col-md-6" name="category_image" filed-name='image2'
                                            real-filed-id="another_file" value="{{$category->image}}"/>
                            <input type="file" name="image" id="another_file" class="d-none">
                            
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            
                            <div class="col-12 mt-5">
                                <button type="submit" class="btn btn-primary">
                                    {{__('translation.Save')}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('admin_assets/js/custom/index.js')}}"></script>
@endpush

{{-- @extends('layouts.admin.admin') --}}
@extends('layouts.admin.admin_layout')
@section('main-head', __('translation.settings'))

@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.dashboard') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.contract_terms') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-light-primary fw-bold">
            <i class="fas fa-eye"></i>
            {{ __('translation.preview') }}
        </button>
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
   
   
    <!--end::Primary button-->
</div>
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <!--begin::Breadcrumb-->
        
            <!--end::Breadcrumb-->
            <!--begin::Card-->

            <form method="post" action="{{route("settings.update.terms")}}">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">{{ __("translation.contract_terms") }} ({{ __("translation.english") }})</h3>
                        <div class="card-toolbar">
                            <div class="d-flex gap-2">
                                <a href="{{ route('settings.preview.terms', ['lang' => 'en']) }}" target="_blank" class="btn btn-sm btn-light-primary">
                                    <i class="fas fa-eye"></i>
                                    {{ __('translation.preview') }}
                                </a>
                                <a href="{{ route('settings.preview.terms', ['lang' => 'en']) }}?download=1" target="_blank" class="btn btn-sm btn-light-success">
                                    <i class="fas fa-download"></i>
                                    {{ __('translation.download_pdf') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <textarea id="privcy_policy_en" name="terms_en" class="tox-target">{{ $terms_en?->value }} </textarea>
                    </div>
                </div>

                <div class="card shadow-sm mt-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __("translation.contract_terms") }} ({{ __("translation.arabic") }})</h3>
                        <div class="card-toolbar">
                            <div class="d-flex gap-2">
                                <a href="{{ route('settings.preview.terms', ['lang' => 'ar']) }}" target="_blank" class="btn btn-sm btn-light-primary">
                                    <i class="fas fa-eye"></i>
                                    {{ __('translation.preview') }}
                                </a>
                                <a href="{{ route('settings.preview.terms', ['lang' => 'ar']) }}?download=1" target="_blank" class="btn btn-sm btn-light-success">
                                    <i class="fas fa-download"></i>
                                    {{ __('translation.download_pdf') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <textarea id="privcy_policy_ar" name="terms_ar" class="tox-target">{{ $terms_ar?->value }} </textarea>
                    </div>
                    <div class="flex" style="    flex-direction: row-reverse">
                        <div class="mt-3 p-8">
                            <button class="btn-primary btn">
                                {{__('translation.update')}}
                            </button>
                            <a href="#" onclick="window.history.back()" class="btn btn-light-danger">
                                {{__('translation.cancel')}}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
            <!--end::Card-->

        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-classic.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-inline.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-balloon.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-balloon-block.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-document.bundle.js') }}"></script>
    
    <script>
        ClassicEditor
            .create(document.querySelector('#privcy_policy_en'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'bulletedList', 'numberedList', '|',
                        'alignment', '|',
                        'link', 'blockQuote', '|',
                        'undo', 'redo'
                    ]
                },
                fontSize: {
                    options: [9, 11, 13, 'default', 17, 19, 21]
                },
                fontFamily: {
                    options: [
                        'default',
                        'Arial, Helvetica, sans-serif',
                        'Courier New, Courier, monospace',
                        'Georgia, serif',
                        'Lucida Sans Unicode, Lucida Grande, sans-serif',
                        'Tahoma, Geneva, sans-serif',
                        'Times New Roman, Times, serif',
                        'Trebuchet MS, Helvetica, sans-serif',
                        'Verdana, Geneva, sans-serif'
                    ]
                },
                fontColor: {
                    colors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Black' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Dim grey' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Grey' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Light grey' },
                        { color: 'hsl(0, 0%, 100%)', label: 'White', hasBorder: true },
                        { color: 'hsl(0, 75%, 60%)', label: 'Red' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Orange' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Yellow' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Light green' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Green' },
                        { color: 'hsl(150, 75%, 60%)', label: 'Aquamarine' },
                        { color: 'hsl(180, 75%, 60%)', label: 'Turquoise' },
                        { color: 'hsl(210, 75%, 60%)', label: 'Light blue' },
                        { color: 'hsl(240, 75%, 60%)', label: 'Blue' },
                        { color: 'hsl(270, 75%, 60%)', label: 'Purple' }
                    ]
                },
                fontBackgroundColor: {
                    colors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Black' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Dim grey' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Grey' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Light grey' },
                        { color: 'hsl(0, 0%, 100%)', label: 'White', hasBorder: true },
                        { color: 'hsl(0, 75%, 60%)', label: 'Red' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Orange' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Yellow' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Light green' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Green' },
                        { color: 'hsl(150, 75%, 60%)', label: 'Aquamarine' },
                        { color: 'hsl(180, 75%, 60%)', label: 'Turquoise' },
                        { color: 'hsl(210, 75%, 60%)', label: 'Light blue' },
                        { color: 'hsl(240, 75%, 60%)', label: 'Blue' },
                        { color: 'hsl(270, 75%, 60%)', label: 'Purple' }
                    ]
                }
            })
            .then(editor => {
                console.log('English editor initialized:', editor);
            })
            .catch(error => {
                console.error('Error initializing English editor:', error);
            });

        ClassicEditor
            .create(document.querySelector('#privcy_policy_ar'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                        'bulletedList', 'numberedList', '|',
                        'alignment', '|',
                        'link', 'blockQuote', '|',
                        'undo', 'redo'
                    ]
                },
                fontSize: {
                    options: [9, 11, 13, 'default', 17, 19, 21]
                },
                fontFamily: {
                    options: [
                        'default',
                        'Arial, Helvetica, sans-serif',
                        'Courier New, Courier, monospace',
                        'Georgia, serif',
                        'Lucida Sans Unicode, Lucida Grande, sans-serif',
                        'Tahoma, Geneva, sans-serif',
                        'Times New Roman, Times, serif',
                        'Trebuchet MS, Helvetica, sans-serif',
                        'Verdana, Geneva, sans-serif'
                    ]
                },
                fontColor: {
                    colors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Black' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Dim grey' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Grey' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Light grey' },
                        { color: 'hsl(0, 0%, 100%)', label: 'White', hasBorder: true },
                        { color: 'hsl(0, 75%, 60%)', label: 'Red' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Orange' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Yellow' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Light green' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Green' },
                        { color: 'hsl(150, 75%, 60%)', label: 'Aquamarine' },
                        { color: 'hsl(180, 75%, 60%)', label: 'Turquoise' },
                        { color: 'hsl(210, 75%, 60%)', label: 'Light blue' },
                        { color: 'hsl(240, 75%, 60%)', label: 'Blue' },
                        { color: 'hsl(270, 75%, 60%)', label: 'Purple' }
                    ]
                },
                fontBackgroundColor: {
                    colors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Black' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Dim grey' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Grey' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Light grey' },
                        { color: 'hsl(0, 0%, 100%)', label: 'White', hasBorder: true },
                        { color: 'hsl(0, 75%, 60%)', label: 'Red' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Orange' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Yellow' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Light green' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Green' },
                        { color: 'hsl(150, 75%, 60%)', label: 'Aquamarine' },
                        { color: 'hsl(180, 75%, 60%)', label: 'Turquoise' },
                        { color: 'hsl(210, 75%, 60%)', label: 'Light blue' },
                        { color: 'hsl(240, 75%, 60%)', label: 'Blue' },
                        { color: 'hsl(270, 75%, 60%)', label: 'Purple' }
                    ]
                }
            })
            .then(editor => {
                console.log('Arabic editor initialized:', editor);
            })
            .catch(error => {
                console.error('Error initializing Arabic editor:', error);
            });
    </script>
@endpush


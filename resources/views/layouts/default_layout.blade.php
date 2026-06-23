<!DOCTYPE html>

<html lang="" direction="{{ app()->getLocale() == 'en' ?: 'rtl' }} " dir="{{ app()->getLocale() == 'en' ?: 'rtl' }}"
      style="{{ app()->getLocale() == 'en' ?: 'direction: rtl' }}">
<!--begin::Head-->

<head>
    <base href="">
    <title> CoreNet Banking Platforms </title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/png">
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/>
    <!--end::Fonts-->
    <link href="{{ asset("assets/plugins/global/plugins.bundle.css") }}" rel="stylesheet" type="text/css"/>

    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
          type="text/css"/>
    @if (app()->getLocale() == 'en')
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css"/>
    @else
        {{-- <link href="{{ asset('assets/plugins/global/plugins.bundle.rtl.css') }}" rel="stylesheet" type="text/css"/> --}}
        {{-- <link href="{{ asset('assets/css/style.bundle.rtl.css') }}" rel="stylesheet" type="text/css"/> --}}
    @endif
    @notifyCss
    @notifyJs
    <!-- FilePond styles -->
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet"/>

    <!-- FilePond JavaScript -->
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>

    <!-- FilePond Plugin (for images) -->
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css"
          rel="stylesheet"/>

    <link href="{{asset("assets/plugins/global/plugins.bundle.css")}}" rel="stylesheet" type="text/css"/>
    {{--    <script src="{{asset("assets/plugins/global/plugins.bundle.js")}}"></script>--}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

    <style>
        .contact_info {
            font-size: 13px;
        }

        .select2-container .select2-selection--single {
            height: 100%;
        }

        * {
            text-transform: capitalize;
        }
    </style>
    @yield('css')
    @stack('links')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body"
      class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed"
      style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
<!--begin::Main-->
<!--begin::Root-->
<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class=" d-flex flex-row flex-column-fluid" style="
        flex: 0 0 auto;
    ">
        <!--begin::Aside-->
        <!--end::Aside-->
        <!--begin::Wrapper-->
        <div class=" d-flex flex-column flex-row-fluid " id="kt_wrapper">
            <!--begin::Header-->
            <!--end::Header-->
            <!--begin::Content-->
            <div class=" d-flex flex-column flex-column-fluid" id="kt_content">
                @yield('content')
                <!--end::Post-->
            </div>
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</div>

<script>
    var hostUrl = "assets/";
</script>
<!--begin::Global Javascript Bundle(used by all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script src="{{ asset('assets/plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script>
<script src="{{ asset('datatable/select2.min.js') }}"></script>
<script>

    function DeleteApp(val) {
        Swal.fire({
            title: "@lang('translation.Are_you_sure')",
            text: "@lang('translation.You_will_not_be_able_to_back_down_from_this')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "@lang('translation.YES_Delete_it')",
            cancelButtonText: "@lang('translation.cancel')"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    " @lang('translation.Deleted')",
                    " @lang('translation.success')",
                    " @lang('translation.Your_file_has_been_deleted.')"
                );
                document.getElementById(val).submit();
            }
        });
    }
</script>

@stack('scripts')
</body>

<!--end::Body-->

</html>

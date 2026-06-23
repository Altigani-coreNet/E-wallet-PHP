@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}

    </div>
{{--    <script>--}}
{{--        toastr.options = {--}}
{{--            "closeButton": true,--}}
{{--            "debug": false,--}}
{{--            "newestOnTop": true,--}}
{{--            "progressBar": true,--}}
{{--            "positionClass": "toast-top-right",--}}
{{--            "preventDuplicates": false,--}}
{{--            "onclick": null,--}}
{{--            "showDuration": "300",--}}
{{--            "hideDuration": "1000",--}}
{{--            "timeOut": "5000",--}}
{{--            "extendedTimeOut": "1000",--}}
{{--            "showEasing": "swing",--}}
{{--            "hideEasing": "linear",--}}
{{--            "showMethod": "fadeIn",--}}
{{--            "hideMethod": "fadeOut"--}}
{{--        };--}}

{{--        toastr.success("Are you the six fingered man?");--}}

{{--    </script>--}}
@endif

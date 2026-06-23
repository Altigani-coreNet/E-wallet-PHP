<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Success - Fast POS Platform</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/png">
    
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <!--end::Fonts-->
    
    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css">
    <!--end::Global Stylesheets Bundle-->
</head>

<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-center min-vh-100 p-10">
            <div class="card card-flush w-md-400px">
                {{-- <div class="card-header text-center">
                    {{-- <h1 class="text-dark fw-bold">Registration Successful!</h1> --}}
                {{-- </div> --}}
                <div class="card-body text-center p-10">
                    @if(session('success'))
                        <div class="alert alert-success mb-5">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="mb-5">
                        <i class="ki-duotone ki-check-circle fs-2hx text-success">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    
                    {{-- <h3 class="text-dark mb-5">Thank You for Registering!</h3> --}}
                    
                    <p class="text-muted mb-5">
                        Your merchant registration has been submitted successfully. Our team will review your application 
                        and contact you within 24-48 hours for approval.
                    </p>
                    
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-5">
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-bold">
                                <h4 class="text-gray-900 fw-bolder">Important Notice</h4>
                                <div class="fs-6 text-gray-700">
                                    To start using our platform, please wait for admin approval. 
                                    You will receive an email with your login credentials once approved.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-column gap-3">
                        <a href="{{ route('merchant.login') }}" class="btn btn-primary">
                            Go to Login
                        </a>
                        <a href="{{ route('merchant.register') }}" class="btn btn-light">
                            Register Another Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--begin::Global Javascript Bundle(used by all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
</body>
</html>

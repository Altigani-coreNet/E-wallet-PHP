@extends("layouts.default_layout")
@section("content")

    <div class="d-flex justify-center align-center items-center">
        <div class="card mt-10">
            <div class="card-body">
                <div style="max-width: 1200px" class="modal-body pt-0 pb-15 px-5 px-xl-20 ">
                    <!--begin::Heading-->
                    <div class="mb-13 text-center">
                        <h1 class="mb-3">CoreNet Banking Platforms  </h1>
                        <div class="text-muted fw-bold fs-5">If you need more info, please contact
                            <a href="#" class="link-success fw-bolder">Our Company</a>.
                        </div>
                    </div>
                    <!--end::Heading-->
                    <!--begin::Plans-->
                    <div class="-body">
                        <!--begin::Heading-->
                        <div class="card-px text-center pt-15 pb-15">
                            <!--begin::Title-->
                            <h2 class="fs-2x fw-bolder mb-0">You have successfully paid.</h2>
                            <!--end::Title-->
                            <!--end::Action-->
                        </div>
                        <!--end::Heading-->
                        <!--begin::Illustration-->
                        <div class="text-center pb-15 px-5 d-flex flex justify-center align-center">
                            <img src="{{asset("assets/media/illustrations/sketchy-1/7.png")}}" alt=""
                                 class="mw-100 h-200px h-sm-325px">
                        </div>
                        <!--end::Illustration-->
                    </div>
                    <div class="text-center">
                        <a class="btn btn-primary" href="{{route('login')}}">
                            Login
                        </a>
                    </div>
                    <!--end::Plans-->
                    <!--begin::Actions-->
                    <!--end::Actions-->
                </div>
            </div>
        </div>
    </div>
@endsection

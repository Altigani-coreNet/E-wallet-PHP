<div class="card-rounded bg-light d-flex flex-stack flex-wrap p-5">
    <!--begin::Nav-->
    <ul class="nav flex-wrap border-transparent fw-bolder">
        <!--begin::Nav item-->
        <li class="nav-item my-1 ">
            <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase {{request()->routeIs("settings.index")  ?"active":""}}"
               href="{{route('settings.index')}}">General Setting</a>
        </li>
        <li class="nav-item my-1">
            <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase"
               href="{{route("settings.notification")}}"> Notification Settings</a>
        </li>
        <li class="nav-item my-1">
            <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase"
               href="{{route("settings.titles")}}"> Titles Settings</a>
        </li>
        <!--end::Nav item-->
        <!--begin::Nav item-->
        {{--                            <li class="nav-item my-1">--}}
        {{--                                <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase"--}}
        {{--                                   href="../../demo1/dist/apps/support-center/tickets/list.html">tickets</a>--}}
        {{--                            </li>--}}
        {{--                            <!--end::Nav item-->--}}
        {{--                            <!--begin::Nav item-->--}}
        {{--                            <li class="nav-item my-1">--}}
        {{--                                <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase"--}}
        {{--                                   href="../../demo1/dist/apps/support-center/tutorials/list.html">Tutorials</a>--}}
        {{--                            </li>--}}
        {{--                            <!--end::Nav item-->--}}
        {{--                            <!--begin::Nav item-->--}}
        {{--                            <li class="nav-item my-1">--}}
        {{--                                <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase"--}}
        {{--                                   href="../../demo1/dist/apps/support-center/faq.html">FAQ</a>--}}
        {{--                            </li>--}}
        {{--                            <!--end::Nav item-->--}}
        {{--                            <!--begin::Nav item-->--}}
        {{--                            <li class="nav-item my-1">--}}
        {{--                                <a class="btn btn-color-gray-600 btn-active-white btn-active-color-primary fw-boldest fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase active"--}}
        {{--                                   href="../../demo1/dist/apps/support-center/licenses.html">Licenses</a>--}}
        {{--                            </li>--}}
        <!--end::Nav item-->
        <!--begin::Nav item-->

        <!--end::Nav item-->
    </ul>
    <!--end::Nav-->
    <!--begin::Action-->
    {{--                        <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_new_ticket"--}}
    {{--                           class="btn btn-primary fw-bolder fs-8 fs-lg-base">Create Ticket</a>--}}
    <!--end::Action-->
</div>

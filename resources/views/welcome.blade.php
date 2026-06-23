@extends('layouts.admin.admin_layout')

@section('content'	)
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Finance Performance</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
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
                    <li class="breadcrumb-item text-muted">Dashboards</li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->
            <!--begin::Actions-->
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!--begin::Daterangepicker(defined in src/js/layout/app.js)-->
                <div data-kt-daterangepicker="true" data-kt-daterangepicker-opens="left" class="btn btn-sm fw-bold btn-secondary d-flex align-items-center px-4" data-kt-initialized="1">
                    <!--begin::Display range-->
                    <div class="text-gray-600 fw-bold">9 Jul 2025 - 7 Aug 2025</div>
                    <!--end::Display range-->
                    <i class="ki-duotone ki-calendar-8 fs-2 ms-2 me-0">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                        <span class="path6"></span>
                    </i>
                </div>
                <!--end::Daterangepicker-->
                <!--begin::Secondary button-->
                <!--end::Secondary button-->
                <!--begin::Primary button-->
                <a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">Add Target</a>
                <!--end::Primary button-->
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Toolbar container-->
    </div>
    <!--end::Toolbar-->
    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-xxl">
            <!--begin::Row-->
            <div class="row gy-5 gx-xl-10">
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-compass fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">327</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">Projects</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-success fs-base">
                            <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>2.1%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-chart-simple fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">27,5M</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">Stock Qty</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-success fs-base">
                            <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>2.1%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-abstract-39 fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">149M</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">Stock Value</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-danger fs-base">
                            <i class="ki-duotone ki-arrow-down fs-5 text-danger ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>0.47%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-map fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">89M</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">C APEX</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-success fs-base">
                            <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>2.1%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-5 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-abstract-35 fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">72.4%</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">OPEX</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-danger fs-base">
                            <i class="ki-duotone ki-arrow-down fs-5 text-danger ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>0.647%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-sm-6 col-xl-2 mb-5 mb-xl-10">
                    <!--begin::Card widget 2-->
                    <div class="card h-lg-100">
                        <!--begin::Body-->
                        <div class="card-body d-flex justify-content-between align-items-start flex-column">
                            <!--begin::Icon-->
                            <div class="m-0">
                                <i class="ki-duotone ki-abstract-26 fs-2hx text-gray-600">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Section-->
                            <div class="d-flex flex-column my-7">
                                <!--begin::Number-->
                                <span class="fw-semibold fs-3x text-gray-800 lh-1 ls-n2">106M</span>
                                <!--end::Number-->
                                <!--begin::Follower-->
                                <div class="m-0">
                                    <span class="fw-semibold fs-6 text-gray-500">Saving</span>
                                </div>
                                <!--end::Follower-->
                            </div>
                            <!--end::Section-->
                            <!--begin::Badge-->
                            <span class="badge badge-light-success fs-base">
                            <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>2.1%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Card widget 2-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                <!--begin::Col-->
                <div class="col-xl-4">
                    <!--begin::Chart widget 19-->
                    <div class="card card-flush h-100 mb-5 mb-xl-10">
                        <!--begin::Header-->
                        <div class="card-header pt-7">
                            <!--begin::Title-->
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Leading Companies</span>
                                <span class="text-gray-500 pt-2 fw-semibold fs-6">8k social visitors</span>
                            </h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Nav-->
                                <ul class="nav" id="kt_chart_widget_19_tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light active fw-bold px-4 me-1" data-bs-toggle="tab" id="kt_chart_widget_19_tab_1" href="#kt_chart_widget_19_tab_content_1" aria-selected="true" role="tab">2025</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4" data-bs-toggle="tab" id="kt_chart_widget_19_tab_2" href="#kt_chart_widget_19_tab_content_2" aria-selected="false" tabindex="-1" role="tab">Month</a>
                                    </li>
                                </ul>
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body pt-0">
                            <!--begin::Tab Content (ishlamayabdi)-->
                            <div class="tab-content">
                                <!--begin::Tap pane-->
                                <div class="tab-pane fade show active" id="kt_chart_widget_19_tab_content_1" role="tabpanel" aria-labelledby="kt_chart_widget_19_tab_1">
                                    <!--begin::Chart container-->
                                    <div id="kt_charts_widget_19_chart_1" class="w-100 h-400px mb-13 mt-n4"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 314px; height: 400px;"><div><canvas class="am5-layer-0" width="392" height="500" style="position: absolute; top: 0px; left: 0px; width: 314px; height: 400px;"></canvas><canvas class="am5-layer-30" width="392" height="500" style="position: absolute; top: 0px; left: 0px; width: 314px; height: 400px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden; width: 314px; height: 400px;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 314px; height: 400px;"><div role="button" aria-label="Zoom Out" aria-hidden="true" style="position: absolute; pointer-events: none; top: -2px; left: -2px; width: 4px; height: 4px;"></div></div><div class="am5-tooltip-container"><div role="tooltip" aria-hidden="false" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;">Human Resources: 68%</div><div role="tooltip" aria-hidden="false" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;">31%</div></div></div></div>
                                    <!--end::Chart container-->
                                    <!--begin::Items-->
                                    <div class="m-0">
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/atica.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Abstergo Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Community</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">579</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>2.6%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/telegram-2.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Binford Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Media</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">2,588</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-danger fs-base">
                                                    <i class="ki-duotone ki-arrow-down fs-5 text-danger ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>0.4%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/balloon.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Barone LLC.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Messanger</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">794</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>0.2%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/kickstarter.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Abstergo Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Video Channel</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">1,578</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>4.1%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/vimeo.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Biffco Enterprises</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Network</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">3,458</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>8.3%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/plurk.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Big Kahuna Burger</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Network</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">2,047</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>1.9%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                    </div>
                                    <!--end::Items-->
                                </div>
                                <!--end::Tap pane-->
                                <!--begin::Tap pane-->
                                <div class="tab-pane fade" id="kt_chart_widget_19_tab_content_2" role="tabpanel" aria-labelledby="kt_chart_widget_19_tab_2">
                                    <!--begin::Chart container-->
                                    <div id="kt_charts_widget_19_chart_2" class="w-100 h-400px mb-13 mt-n4"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 0px; height: 0px;"><div><canvas class="am5-layer-0" width="0" height="0" style="position: absolute; top: 0px; left: 0px; width: 0px; height: 0px;"></canvas><canvas class="am5-layer-30" width="0" height="0" style="position: absolute; top: 0px; left: 0px; width: 0px; height: 0px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 0px; height: 0px;"><div role="button" aria-label="Zoom Out" aria-hidden="true" style="position: absolute; pointer-events: none; top: -2px; left: -2px; width: 4px; height: 4px;"></div></div><div class="am5-tooltip-container"></div></div></div>
                                    <!--end::Chart container-->
                                    <!--begin::Items-->
                                    <div class="m-0">
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/atica.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Abstergo Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Community</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">579</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>2.6%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/telegram-2.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Binford Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Media</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">2,588</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-danger fs-base">
                                                    <i class="ki-duotone ki-arrow-down fs-5 text-danger ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>0.4%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/balloon.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Barone LLC.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Messanger</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">794</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>0.2%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/kickstarter.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Abstergo Ltd.</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Video Channel</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">1,578</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>4.1%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/vimeo.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Biffco Enterprises</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Network</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">3,458</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>8.3%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                        <!--begin::Separator-->
                                        <div class="separator separator-dashed my-4"></div>
                                        <!--end::Separator-->
                                        <!--begin::Item-->
                                        <div class="d-flex flex-stack">
                                            <!--begin::Section-->
                                            <div class="d-flex align-items-center me-5">
                                                <!--begin::Flag-->
                                                <img src="assets/media/svg/brand-logos/plurk.svg" class="me-4 w-30px" style="border-radius: 4px" alt="">
                                                <!--end::Flag-->
                                                <!--begin::Content-->
                                                <div class="me-5">
                                                    <!--begin::Title-->
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">Big Kahuna Burger</a>
                                                    <!--end::Title-->
                                                    <!--begin::Desc-->
                                                    <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Social Network</span>
                                                    <!--end::Desc-->
                                                </div>
                                                <!--end::Content-->
                                            </div>
                                            <!--end::Section-->
                                            <!--begin::Wrapper-->
                                            <div class="d-flex align-items-center">
                                                <!--begin::Number-->
                                                <span class="text-gray-800 fw-bold fs-4 me-3">2,047</span>
                                                <!--end::Number-->
                                                <!--begin::Info-->
                                                <div class="m-0">
                                                    <!--begin::Label-->
                                                    <span class="badge badge-light-success fs-base">
                                                    <i class="ki-duotone ki-arrow-up fs-5 text-success ms-n1">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>1.9%</span>
                                                    <!--end::Label-->
                                                </div>
                                                <!--end::Info-->
                                            </div>
                                            <!--end::Wrapper-->
                                        </div>
                                        <!--end::Item-->
                                    </div>
                                    <!--end::Items-->
                                </div>
                                <!--end::Tap pane-->
                            </div>
                            <!--end::Tab Content-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Chart widget 19-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-xl-8 mb-xl-10">
                    <!--begin::Chart widget 38-->
                    <div class="card card-flush h-xl-50 mb-5 mb-xl-10">
                        <!--begin::Header-->
                        <div class="card-header pt-7">
                            <!--begin::Title-->
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800">LOI Issued by Departments</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-6">Counted in Millions</span>
                            </h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Daterangepicker(defined in src/js/layout/app.js)-->
                                <div data-kt-daterangepicker="true" data-kt-daterangepicker-opens="left" class="btn btn-sm btn-light d-flex align-items-center px-4" data-kt-initialized="1">
                                    <!--begin::Display range-->
                                    <div class="text-gray-600 fw-bold">9 Jul 2025 - 7 Aug 2025</div>
                                    <!--end::Display range-->
                                    <i class="ki-duotone ki-calendar-8 text-gray-500 lh-0 fs-2 ms-2 me-0">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        <span class="path6"></span>
                                    </i>
                                </div>
                                <!--end::Daterangepicker-->
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                            <!--end::Chart-->
                        </div>
                        <!--end: Card Body-->
                    </div>
                    <!--end::Chart widget 38-->
                    <!--begin::Chart widget 20-->
                    <div class="card card-flush h-xl-50">
                        <!--begin::Header-->
                        <div class="card-header py-5">
                            <!--begin::Title-->
                            <h3 class="card-title fw-bold text-gray-800">Monthly Targets</h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Daterangepicker(defined in src/js/layout/app.js)-->
                                <div data-kt-daterangepicker="true" data-kt-daterangepicker-opens="left" class="btn btn-sm btn-light d-flex align-items-center px-4" data-kt-initialized="1">
                                    <!--begin::Display range-->
                                    <div class="text-gray-600 fw-bold">9 Jul 2025 - 7 Aug 2025</div>
                                    <!--end::Display range-->
                                    <i class="ki-duotone ki-calendar-8 text-gray-500 lh-0 fs-2 ms-2 me-0">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                        <span class="path6"></span>
                                    </i>
                                </div>
                                <!--end::Daterangepicker-->
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body d-flex justify-content-between flex-column pb-0 px-0 pt-1">
                            <!--begin::Items-->
                            <div class="d-flex flex-wrap d-grid gap-5 px-9 mb-5">
                                <!--begin::Item-->
                                <div class="me-md-2">
                                    <!--begin::Statistics-->
                                    <div class="d-flex mb-2">
                                        <span class="fs-4 fw-semibold text-gray-500 me-1">$</span>
                                        <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">12,706</span>
                                    </div>
                                    <!--end::Statistics-->
                                    <!--begin::Description-->
                                    <span class="fs-6 fw-semibold text-gray-500">Targets for April</span>
                                    <!--end::Description-->
                                </div>
                                <!--end::Item-->
                                <!--begin::Item-->
                                <div class="border-start-dashed border-end-dashed border-start border-end border-gray-300 px-5 ps-md-10 pe-md-7 me-md-5">
                                    <!--begin::Statistics-->
                                    <div class="d-flex mb-2">
                                        <span class="fs-4 fw-semibold text-gray-500 me-1">$</span>
                                        <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">8,035</span>
                                    </div>
                                    <!--end::Statistics-->
                                    <!--begin::Description-->
                                    <span class="fs-6 fw-semibold text-gray-500">Actual for April</span>
                                    <!--end::Description-->
                                </div>
                                <!--end::Item-->
                                <!--begin::Item-->
                                <div class="m-0">
                                    <!--begin::Statistics-->
                                    <div class="d-flex align-items-center mb-2">
                                        <!--begin::Currency-->
                                        <span class="fs-4 fw-semibold text-gray-500 align-self-start me-1">$</span>
                                        <!--end::Currency-->
                                        <!--begin::Value-->
                                        <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">4,684</span>
                                        <!--end::Value-->
                                        <!--begin::Label-->
                                        <span class="badge badge-light-success fs-base">
                                        <i class="ki-duotone ki-black-up fs-7 text-success ms-n1"></i>4.5%</span>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Statistics-->
                                    <!--begin::Description-->
                                    <span class="fs-6 fw-semibold text-gray-500">GAP</span>
                                    <!--end::Description-->
                                </div>
                                <!--end::Item-->
                            </div>
                            <!--end::Items-->
                            <!--begin::Chart-->
                            <div id="kt_charts_widget_20" class="min-h-auto ps-4 pe-6" data-kt-chart-info="Revenue" style="height: 300px; min-height: 315px;"><div id="apexchartsxg0y2ywl" class="apexcharts-canvas apexchartsxg0y2ywl apexcharts-theme-" style="width: 746.5px; height: 300px;"><svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" class="apexcharts-svg apexcharts-zoomable" xmlns:data="ApexChartsNS" transform="translate(0, 0)" width="746.5" height="300"><foreignObject x="0" y="0" width="746.5" height="300"><style type="text/css">
.apexcharts-flip-y {
transform: scaleY(-1) translateY(-100%);
transform-origin: top;
transform-box: fill-box;
}
.apexcharts-flip-x {
transform: scaleX(-1);
transform-origin: center;
transform-box: fill-box;
}
.apexcharts-legend {
display: flex;
overflow: auto;
padding: 0 10px;
}
.apexcharts-legend.apexcharts-legend-group-horizontal {
flex-direction: column;
}
.apexcharts-legend-group {
display: flex;
}
.apexcharts-legend-group-vertical {
flex-direction: column-reverse;
}
.apexcharts-legend.apx-legend-position-bottom, .apexcharts-legend.apx-legend-position-top {
flex-wrap: wrap
}
.apexcharts-legend.apx-legend-position-right, .apexcharts-legend.apx-legend-position-left {
flex-direction: column;
bottom: 0;
}
.apexcharts-legend.apx-legend-position-bottom.apexcharts-align-left, .apexcharts-legend.apx-legend-position-top.apexcharts-align-left, .apexcharts-legend.apx-legend-position-right, .apexcharts-legend.apx-legend-position-left {
justify-content: flex-start;
align-items: flex-start;
}
.apexcharts-legend.apx-legend-position-bottom.apexcharts-align-center, .apexcharts-legend.apx-legend-position-top.apexcharts-align-center {
justify-content: center;
align-items: center;
}
.apexcharts-legend.apx-legend-position-bottom.apexcharts-align-right, .apexcharts-legend.apx-legend-position-top.apexcharts-align-right {
justify-content: flex-end;
align-items: flex-end;
}
.apexcharts-legend-series {
cursor: pointer;
line-height: normal;
display: flex;
align-items: center;
}
.apexcharts-legend-text {
position: relative;
font-size: 14px;
}
.apexcharts-legend-text *, .apexcharts-legend-marker * {
pointer-events: none;
}
.apexcharts-legend-marker {
position: relative;
display: flex;
align-items: center;
justify-content: center;
cursor: pointer;
margin-right: 1px;
}

.apexcharts-legend-series.apexcharts-no-click {
cursor: auto;
}
.apexcharts-legend .apexcharts-hidden-zero-series, .apexcharts-legend .apexcharts-hidden-null-series {
display: none !important;
}
.apexcharts-inactive-legend {
opacity: 0.45;
}

</style></foreignObject><g class="apexcharts-datalabels-group" transform="translate(0, 0) scale(1)"></g><g class="apexcharts-datalabels-group" transform="translate(0, 0) scale(1)"></g><rect width="0" height="0" x="0" y="0" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fefefe"></rect><g class="apexcharts-yaxis" rel="0" transform="translate(26.975000381469727, 0)"><g class="apexcharts-yaxis-texts-g"><text x="20" y="34" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$363</tspan><title>$363</title></text><text x="20" y="70.93404447873434" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$357</tspan><title>$357</title></text><text x="20" y="107.86808895746867" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$352</tspan><title>$352</title></text><text x="20" y="144.802133436203" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$346</tspan><title>$346</title></text><text x="20" y="181.73617791493734" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$341</tspan><title>$341</title></text><text x="20" y="218.67022239367168" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$335</tspan><title>$335</title></text><text x="20" y="255.60426687240601" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-yaxis-label " style="font-family: inherit;"><tspan>$330</tspan><title>$330</title></text></g></g><g class="apexcharts-inner apexcharts-graphical" transform="translate(56.97500038146973, 30)"><defs><clipPath id="gridRectMaskxg0y2ywl"><rect width="686.5249996185303" height="228.60426687240601" x="-3.5" y="-3.5" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fff"></rect></clipPath><clipPath id="gridRectBarMaskxg0y2ywl"><rect width="686.5249996185303" height="228.60426687240601" x="-3.5" y="-3.5" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fff"></rect></clipPath><clipPath id="gridRectMarkerMaskxg0y2ywl"><rect width="679.5249996185303" height="221.60426687240601" x="0" y="0" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fff"></rect></clipPath><clipPath id="forecastMaskxg0y2ywl"></clipPath><clipPath id="nonForecastMaskxg0y2ywl"></clipPath><linearGradient x1="0" y1="0" x2="0" y2="1" id="SvgjsLinearGradient1004"><stop stop-opacity="0.4" stop-color="rgba(248,40,90,0.4)" offset="0"></stop><stop stop-opacity="0" stop-color="rgba(255,255,255,0)" offset="0.8"></stop><stop stop-opacity="0" stop-color="rgba(255,255,255,0)" offset="1"></stop></linearGradient></defs><g class="apexcharts-grid"><g class="apexcharts-gridlines-horizontal"><line x1="0" y1="36.934044478734336" x2="679.5249996185303" y2="36.934044478734336" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line><line x1="0" y1="73.86808895746867" x2="679.5249996185303" y2="73.86808895746867" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line><line x1="0" y1="110.80213343620301" x2="679.5249996185303" y2="110.80213343620301" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line><line x1="0" y1="147.73617791493734" x2="679.5249996185303" y2="147.73617791493734" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line><line x1="0" y1="184.67022239367168" x2="679.5249996185303" y2="184.67022239367168" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line></g><g class="apexcharts-gridlines-vertical"></g><line x1="0" y1="221.60426687240601" x2="679.5249996185303" y2="221.60426687240601" stroke="transparent" stroke-dasharray="0" stroke-linecap="butt"></line><line x1="0" y1="1" x2="0" y2="221.60426687240601" stroke="transparent" stroke-dasharray="0" stroke-linecap="butt"></line></g><g class="apexcharts-grid-borders"><line x1="0" y1="0" x2="679.5249996185303" y2="0" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line><line x1="0" y1="221.60426687240601" x2="679.5249996185303" y2="221.60426687240601" stroke="#dbdfe9" stroke-dasharray="4" stroke-linecap="butt" class="apexcharts-gridline"></line></g><g class="apexcharts-area-series apexcharts-plot-series"><g class="apexcharts-series" zIndex="0" seriesName="Revenue" data:longestSeries="true" rel="1" data:realIndex="0"><path d="M 0 120.87505465767617C 118.9168749332428 120.87505465767617 220.84562487602233 120.87505465767617 339.76249980926514 120.87505465767617C 458.67937474250795 120.87505465767617 560.6081246852875 87.29865058609948 679.5249996185303 87.29865058609948C 798.441874551773 87.29865058609948 900.3706244945527 87.29865058609948 1019.2874994277954 87.29865058609948C 1138.2043743610382 87.29865058609948 1240.1331243038178 53.722246514522794 1359.0499992370605 53.722246514522794C 1477.9668741703033 53.722246514522794 1579.895624113083 53.722246514522794 1698.8124990463257 53.722246514522794C 1817.7293739795684 53.722246514522794 1919.658123922348 87.29865058609948 2038.5749988555908 87.29865058609948C 2157.491873788834 87.29865058609948 2259.420623731613 87.29865058609948 2378.337498664856 87.29865058609948C 2497.254373598099 87.29865058609948 2599.183123540878 53.722246514522794 2718.099998474121 53.722246514522794C 2837.016873407364 53.722246514522794 2938.9456233501433 53.722246514522794 3057.8624982833862 53.722246514522794C 3176.779373216629 53.722246514522794 3278.7081231594084 87.29865058609948 3397.6249980926514 87.29865058609948C 3516.5418730258943 87.29865058609948 3618.4706229686735 87.29865058609948 3737.3874979019165 87.29865058609948C 3856.3043728351595 87.29865058609948 3958.2331227779387 120.87505465767617 4077.1499977111816 120.87505465767617C 4196.066872644425 120.87505465767617 4297.995622587204 120.87505465767617 4416.912497520447 120.87505465767617C 4535.82937245369 120.87505465767617 4637.758122396469 87.29865058609948 4756.674997329712 87.29865058609948C 4875.591872262955 87.29865058609948 4977.520622205734 87.29865058609948 5096.437497138977 87.29865058609948C 5215.35437207222 87.29865058609948 5317.283122014999 60.43752732883786 5436.199996948242 60.43752732883786C 5555.116871881485 60.43752732883786 5657.045621824264 60.43752732883786 5775.962496757507 60.43752732883786C 5894.87937169075 60.43752732883786 5996.8081216335295 87.29865058609948 6115.7249965667725 87.29865058609948C 6115.7249965667725 87.29865058609948 6115.7249965667725 87.29865058609948 6115.7249965667725 221.60426687240601 L 0 221.60426687240601z" fill="url(#SvgjsLinearGradient1004)" fill-opacity="1" stroke="none" stroke-opacity="1" stroke-linecap="butt" stroke-width="0" stroke-dasharray="0" class="apexcharts-area" index="0" clip-path="url(#gridRectMaskxg0y2ywl)" pathTo="M 0 120.87505465767617C 118.9168749332428 120.87505465767617 220.84562487602233 120.87505465767617 339.76249980926514 120.87505465767617C 458.67937474250795 120.87505465767617 560.6081246852875 87.29865058609948 679.5249996185303 87.29865058609948C 798.441874551773 87.29865058609948 900.3706244945527 87.29865058609948 1019.2874994277954 87.29865058609948C 1138.2043743610382 87.29865058609948 1240.1331243038178 53.722246514522794 1359.0499992370605 53.722246514522794C 1477.9668741703033 53.722246514522794 1579.895624113083 53.722246514522794 1698.8124990463257 53.722246514522794C 1817.7293739795684 53.722246514522794 1919.658123922348 87.29865058609948 2038.5749988555908 87.29865058609948C 2157.491873788834 87.29865058609948 2259.420623731613 87.29865058609948 2378.337498664856 87.29865058609948C 2497.254373598099 87.29865058609948 2599.183123540878 53.722246514522794 2718.099998474121 53.722246514522794C 2837.016873407364 53.722246514522794 2938.9456233501433 53.722246514522794 3057.8624982833862 53.722246514522794C 3176.779373216629 53.722246514522794 3278.7081231594084 87.29865058609948 3397.6249980926514 87.29865058609948C 3516.5418730258943 87.29865058609948 3618.4706229686735 87.29865058609948 3737.3874979019165 87.29865058609948C 3856.3043728351595 87.29865058609948 3958.2331227779387 120.87505465767617 4077.1499977111816 120.87505465767617C 4196.066872644425 120.87505465767617 4297.995622587204 120.87505465767617 4416.912497520447 120.87505465767617C 4535.82937245369 120.87505465767617 4637.758122396469 87.29865058609948 4756.674997329712 87.29865058609948C 4875.591872262955 87.29865058609948 4977.520622205734 87.29865058609948 5096.437497138977 87.29865058609948C 5215.35437207222 87.29865058609948 5317.283122014999 60.43752732883786 5436.199996948242 60.43752732883786C 5555.116871881485 60.43752732883786 5657.045621824264 60.43752732883786 5775.962496757507 60.43752732883786C 5894.87937169075 60.43752732883786 5996.8081216335295 87.29865058609948 6115.7249965667725 87.29865058609948C 6115.7249965667725 87.29865058609948 6115.7249965667725 87.29865058609948 6115.7249965667725 221.60426687240601 L 0 221.60426687240601z" pathFrom="M 0 120.87505465767617C 39.63895831108093 120.87505465767617 73.61520829200745 120.87505465767617 113.25416660308838 120.87505465767617C 152.8931249141693 120.87505465767617 186.86937489509583 87.29865058609948 226.50833320617676 87.29865058609948C 266.1472915172577 87.29865058609948 300.1235414981842 87.29865058609948 339.76249980926514 87.29865058609948C 379.4014581203461 87.29865058609948 413.37770810127256 53.722246514522794 453.0166664123535 53.722246514522794C 492.65562472343447 53.722246514522794 526.6318747043609 53.722246514522794 566.2708330154419 53.722246514522794C 605.9097913265228 53.722246514522794 639.8860413074493 87.29865058609948 679.5249996185303 87.29865058609948C 719.1639579296112 87.29865058609948 753.1402079105377 87.29865058609948 792.7791662216187 87.29865058609948C 832.4181245326996 87.29865058609948 866.3943745136261 53.722246514522794 906.033332824707 53.722246514522794C 945.672291135788 53.722246514522794 979.6485411167145 53.722246514522794 1019.2874994277954 53.722246514522794C 1058.9264577388763 53.722246514522794 1092.902707719803 87.29865058609948 1132.5416660308838 87.29865058609948C 1172.1806243419646 87.29865058609948 1206.1568743228913 87.29865058609948 1245.7958326339722 87.29865058609948C 1285.434790945053 87.29865058609948 1319.4110409259797 120.87505465767617 1359.0499992370605 120.87505465767617C 1398.6889575481414 120.87505465767617 1432.665207529068 120.87505465767617 1472.304165840149 120.87505465767617C 1511.9431241512298 120.87505465767617 1545.9193741321565 87.29865058609948 1585.5583324432373 87.29865058609948C 1625.1972907543181 87.29865058609948 1659.1735407352448 87.29865058609948 1698.8124990463257 87.29865058609948C 1738.4514573574065 87.29865058609948 1772.4277073383332 60.43752732883786 1812.066665649414 60.43752732883786C 1851.705623960495 60.43752732883786 1885.6818739414216 60.43752732883786 1925.3208322525024 60.43752732883786C 1964.9597905635833 60.43752732883786 1998.93604054451 87.29865058609948 2038.5749988555908 87.29865058609948C 2038.5749988555908 87.29865058609948 2038.5749988555908 87.29865058609948 2038.5749988555908 221.60426687240601 L 0 221.60426687240601zz"></path><path d="M 0 120.87505465767617C 118.9168749332428 120.87505465767617 220.84562487602233 120.87505465767617 339.76249980926514 120.87505465767617C 458.67937474250795 120.87505465767617 560.6081246852875 87.29865058609948 679.5249996185303 87.29865058609948C 798.441874551773 87.29865058609948 900.3706244945527 87.29865058609948 1019.2874994277954 87.29865058609948C 1138.2043743610382 87.29865058609948 1240.1331243038178 53.722246514522794 1359.0499992370605 53.722246514522794C 1477.9668741703033 53.722246514522794 1579.895624113083 53.722246514522794 1698.8124990463257 53.722246514522794C 1817.7293739795684 53.722246514522794 1919.658123922348 87.29865058609948 2038.5749988555908 87.29865058609948C 2157.491873788834 87.29865058609948 2259.420623731613 87.29865058609948 2378.337498664856 87.29865058609948C 2497.254373598099 87.29865058609948 2599.183123540878 53.722246514522794 2718.099998474121 53.722246514522794C 2837.016873407364 53.722246514522794 2938.9456233501433 53.722246514522794 3057.8624982833862 53.722246514522794C 3176.779373216629 53.722246514522794 3278.7081231594084 87.29865058609948 3397.6249980926514 87.29865058609948C 3516.5418730258943 87.29865058609948 3618.4706229686735 87.29865058609948 3737.3874979019165 87.29865058609948C 3856.3043728351595 87.29865058609948 3958.2331227779387 120.87505465767617 4077.1499977111816 120.87505465767617C 4196.066872644425 120.87505465767617 4297.995622587204 120.87505465767617 4416.912497520447 120.87505465767617C 4535.82937245369 120.87505465767617 4637.758122396469 87.29865058609948 4756.674997329712 87.29865058609948C 4875.591872262955 87.29865058609948 4977.520622205734 87.29865058609948 5096.437497138977 87.29865058609948C 5215.35437207222 87.29865058609948 5317.283122014999 60.43752732883786 5436.199996948242 60.43752732883786C 5555.116871881485 60.43752732883786 5657.045621824264 60.43752732883786 5775.962496757507 60.43752732883786C 5894.87937169075 60.43752732883786 5996.8081216335295 87.29865058609948 6115.7249965667725 87.29865058609948" fill="none" fill-opacity="1" stroke="#f8285a" stroke-opacity="1" stroke-linecap="butt" stroke-width="3" stroke-dasharray="0" class="apexcharts-area" index="0" clip-path="url(#gridRectMaskxg0y2ywl)" pathTo="M 0 120.87505465767617C 118.9168749332428 120.87505465767617 220.84562487602233 120.87505465767617 339.76249980926514 120.87505465767617C 458.67937474250795 120.87505465767617 560.6081246852875 87.29865058609948 679.5249996185303 87.29865058609948C 798.441874551773 87.29865058609948 900.3706244945527 87.29865058609948 1019.2874994277954 87.29865058609948C 1138.2043743610382 87.29865058609948 1240.1331243038178 53.722246514522794 1359.0499992370605 53.722246514522794C 1477.9668741703033 53.722246514522794 1579.895624113083 53.722246514522794 1698.8124990463257 53.722246514522794C 1817.7293739795684 53.722246514522794 1919.658123922348 87.29865058609948 2038.5749988555908 87.29865058609948C 2157.491873788834 87.29865058609948 2259.420623731613 87.29865058609948 2378.337498664856 87.29865058609948C 2497.254373598099 87.29865058609948 2599.183123540878 53.722246514522794 2718.099998474121 53.722246514522794C 2837.016873407364 53.722246514522794 2938.9456233501433 53.722246514522794 3057.8624982833862 53.722246514522794C 3176.779373216629 53.722246514522794 3278.7081231594084 87.29865058609948 3397.6249980926514 87.29865058609948C 3516.5418730258943 87.29865058609948 3618.4706229686735 87.29865058609948 3737.3874979019165 87.29865058609948C 3856.3043728351595 87.29865058609948 3958.2331227779387 120.87505465767617 4077.1499977111816 120.87505465767617C 4196.066872644425 120.87505465767617 4297.995622587204 120.87505465767617 4416.912497520447 120.87505465767617C 4535.82937245369 120.87505465767617 4637.758122396469 87.29865058609948 4756.674997329712 87.29865058609948C 4875.591872262955 87.29865058609948 4977.520622205734 87.29865058609948 5096.437497138977 87.29865058609948C 5215.35437207222 87.29865058609948 5317.283122014999 60.43752732883786 5436.199996948242 60.43752732883786C 5555.116871881485 60.43752732883786 5657.045621824264 60.43752732883786 5775.962496757507 60.43752732883786C 5894.87937169075 60.43752732883786 5996.8081216335295 87.29865058609948 6115.7249965667725 87.29865058609948" pathFrom="M 0 120.87505465767617C 39.63895831108093 120.87505465767617 73.61520829200745 120.87505465767617 113.25416660308838 120.87505465767617C 152.8931249141693 120.87505465767617 186.86937489509583 87.29865058609948 226.50833320617676 87.29865058609948C 266.1472915172577 87.29865058609948 300.1235414981842 87.29865058609948 339.76249980926514 87.29865058609948C 379.4014581203461 87.29865058609948 413.37770810127256 53.722246514522794 453.0166664123535 53.722246514522794C 492.65562472343447 53.722246514522794 526.6318747043609 53.722246514522794 566.2708330154419 53.722246514522794C 605.9097913265228 53.722246514522794 639.8860413074493 87.29865058609948 679.5249996185303 87.29865058609948C 719.1639579296112 87.29865058609948 753.1402079105377 87.29865058609948 792.7791662216187 87.29865058609948C 832.4181245326996 87.29865058609948 866.3943745136261 53.722246514522794 906.033332824707 53.722246514522794C 945.672291135788 53.722246514522794 979.6485411167145 53.722246514522794 1019.2874994277954 53.722246514522794C 1058.9264577388763 53.722246514522794 1092.902707719803 87.29865058609948 1132.5416660308838 87.29865058609948C 1172.1806243419646 87.29865058609948 1206.1568743228913 87.29865058609948 1245.7958326339722 87.29865058609948C 1285.434790945053 87.29865058609948 1319.4110409259797 120.87505465767617 1359.0499992370605 120.87505465767617C 1398.6889575481414 120.87505465767617 1432.665207529068 120.87505465767617 1472.304165840149 120.87505465767617C 1511.9431241512298 120.87505465767617 1545.9193741321565 87.29865058609948 1585.5583324432373 87.29865058609948C 1625.1972907543181 87.29865058609948 1659.1735407352448 87.29865058609948 1698.8124990463257 87.29865058609948C 1738.4514573574065 87.29865058609948 1772.4277073383332 60.43752732883786 1812.066665649414 60.43752732883786C 1851.705623960495 60.43752732883786 1885.6818739414216 60.43752732883786 1925.3208322525024 60.43752732883786C 1964.9597905635833 60.43752732883786 1998.93604054451 87.29865058609948 2038.5749988555908 87.29865058609948" fill-rule="evenodd"></path><g class="apexcharts-series-markers-wrap apexcharts-hidden-element-shown" data:realIndex="0"><g class="apexcharts-series-markers"><path d="M 0, 0 
m -0, 0 
a 0,0 0 1,0 0,0 
a 0,0 0 1,0 -0,0" fill="#f8285a" fill-opacity="1" stroke="#f8285a" stroke-opacity="0.9" stroke-linecap="butt" stroke-width="3" stroke-dasharray="0" cx="0" cy="0" shape="circle" class="apexcharts-marker wgn3ulq3 no-pointer-events" default-marker-size="0"></path></g></g></g><g class="apexcharts-datalabels" data:realIndex="0"></g></g><line x1="0" y1="0" x2="0" y2="221.60426687240601" stroke="#f8285a" stroke-dasharray="3" stroke-linecap="butt" class="apexcharts-xcrosshairs" x="0" y="0" width="1" height="221.60426687240601" fill="#b1b9c4" filter="none" fill-opacity="0.9" stroke-width="1"></line><line x1="0" y1="0" x2="679.5249996185303" y2="0" stroke="#b6b6b6" stroke-dasharray="0" stroke-width="1" stroke-linecap="butt" class="apexcharts-ycrosshairs"></line><line x1="0" y1="0" x2="679.5249996185303" y2="0" stroke="#b6b6b6" stroke-dasharray="0" stroke-width="0" stroke-linecap="butt" class="apexcharts-ycrosshairs-hidden"></line><g class="apexcharts-xaxis" transform="translate(0, 0)"><g class="apexcharts-xaxis-texts-g" transform="translate(0, -10)"><text x="0" y="243.60426687240601" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-xaxis-label " transform="rotate(0 1 -1)" style="font-family: inherit;"><tspan></tspan><title></title></text><text x="339.76249980926514" y="243.60426687240601" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-xaxis-label " transform="rotate(0 340.76249504089355 238.2042784690857)" style="font-family: inherit;"><tspan>Apr 02</tspan><title>Apr 02</title></text><text x="679.5249996185303" y="243.60426687240601" text-anchor="end" dominant-baseline="auto" font-size="12px" font-family="inherit" font-weight="400" fill="#99a1b7" class="apexcharts-text apexcharts-xaxis-label " transform="rotate(0 680.5249881744385 238.2042784690857)" style="font-family: inherit;"><tspan>Apr 03</tspan><title>Apr 03</title></text></g></g><g class="apexcharts-yaxis-annotations"></g><g class="apexcharts-xaxis-annotations"></g><g class="apexcharts-point-annotations"></g></g><rect width="0" height="0" x="0" y="0" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fefefe" class="apexcharts-zoom-rect"></rect><rect width="0" height="0" x="0" y="0" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fefefe" class="apexcharts-selection-rect"></rect></svg><div class="apexcharts-legend" style="max-height: 150px;"></div><div class="apexcharts-tooltip apexcharts-theme-light"><div class="apexcharts-tooltip-title" style="font-family: inherit; font-size: 12px;"></div><div class="apexcharts-tooltip-series-group apexcharts-tooltip-series-group-0" style="order: 1;"><span class="apexcharts-tooltip-marker" shape="circle" style="color: rgb(248, 40, 90);"></span><div class="apexcharts-tooltip-text" style="font-family: inherit; font-size: 12px;"><div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div><div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div><div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div></div></div></div><div class="apexcharts-xaxistooltip apexcharts-xaxistooltip-bottom apexcharts-theme-light"><div class="apexcharts-xaxistooltip-text" style="font-family: inherit; font-size: 12px;"></div></div><div class="apexcharts-yaxistooltip apexcharts-yaxistooltip-0 apexcharts-yaxistooltip-left apexcharts-theme-light"><div class="apexcharts-yaxistooltip-text"></div></div></div></div>
                            <!--end::Chart-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Chart widget 20-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                <!--begin::Col-->
                <div class="col-xxl-4">
                    <!--begin::Engage widget 1-->
                    <div class="card h-md-100" dir="ltr">
                        <!--begin::Body-->
                        <div class="card-body d-flex flex-column flex-center">
                            <!--begin::Heading-->
                            <div class="mb-2">
                                <!--begin::Title-->
                                <h1 class="fw-semibold text-gray-800 text-center lh-lg">Try out our 
                                <br>new 
                                <span class="fw-bolder">Invoice Manager</span></h1>
                                <!--end::Title-->
                                <!--begin::Illustration-->
                                <div class="py-10 text-center">
                                    <img src="assets/media/svg/illustrations/easy/2.svg" class="theme-light-show w-200px" alt="">
                                    <img src="assets/media/svg/illustrations/easy/2-dark.svg" class="theme-dark-show w-200px" alt="">
                                </div>
                                <!--end::Illustration-->
                            </div>
                            <!--end::Heading-->
                            <!--begin::Links-->
                            <div class="text-center mb-1">
                                <!--begin::Link-->
                                <a class="btn btn-sm btn-primary me-2" data-bs-target="#kt_modal_new_address" data-bs-toggle="modal">Try Now</a>
                                <!--end::Link-->
                                <!--begin::Link-->
                                <a class="btn btn-sm btn-light" href="apps/user-management/users/view.html">Learn More</a>
                                <!--end::Link-->
                            </div>
                            <!--end::Links-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Engage widget 1-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-xxl-8">
                    <!--begin::Chart widget 23-->
                    <div class="card card-flush overflow-hidden h-md-100">
                        <!--begin::Header-->
                        <div class="card-header py-5">
                            <!--begin::Title-->
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Some Chart with AmCharts</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-6">83 countries in service</span>
                            </h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Menu-->
                                <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                                    <i class="ki-duotone ki-dots-square fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </button>
                                <!--begin::Menu 2-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px" data-kt-menu="true">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Actions</div>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu separator-->
                                    <div class="separator mb-3 opacity-75"></div>
                                    <!--end::Menu separator-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Ticket</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Customer</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                                        <!--begin::Menu item-->
                                        <a href="#" class="menu-link px-3">
                                            <span class="menu-title">New Group</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <!--end::Menu item-->
                                        <!--begin::Menu sub-->
                                        <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Admin Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Staff Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Member Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                        </div>
                                        <!--end::Menu sub-->
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Contact</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu separator-->
                                    <div class="separator mt-3 opacity-75"></div>
                                    <!--end::Menu separator-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <div class="menu-content px-3 py-3">
                                            <a class="btn btn-primary btn-sm px-4" href="#">Generate Reports</a>
                                        </div>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu 2-->
                                <!--end::Menu-->
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-4">
                            <!--begin::Chart-->
                            <div id="kt_charts_widget_23" class="h-400px w-100"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 720px; height: 400px;"><div><canvas class="am5-layer-0" width="900" height="500" style="position: absolute; top: 0px; left: 0px; width: 720px; height: 400px;"></canvas><canvas class="am5-layer-30" width="900" height="500" style="position: absolute; top: 0px; left: 0px; width: 720px; height: 400px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden; width: 720px; height: 400px;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 720px; height: 400px;"><div role="button" aria-label="Zoom Out" style="position: absolute; pointer-events: none; top: 24px; left: 652px; width: 40px; height: 40px;" aria-hidden="true"></div><div role="checkbox" aria-label="Income; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 139.031px; height: 32px;"></div><div role="checkbox" aria-label="Expenses; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 149.812px; height: 32px;"></div></div><div class="am5-tooltip-container"><div role="tooltip" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;"> in :  </div><div role="tooltip" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;"> in :  </div></div></div></div>
                            <!--end::Chart-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Chart widget 23-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row g-5 g-xl-10">
                <!--begin::Col-->
                <div class="col-xxl-4">
                    <!--begin::Chart widget 25-->
                    <div class="card card-flush h-md-100">
                        <!--begin::Header-->
                        <div class="card-header pt-7">
                            <!--begin::Title-->
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Warephase stats</span>
                                <span class="text-gray-500 pt-2 fw-semibold fs-6">8k social visitors</span>
                            </h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Nav-->
                                <ul class="nav" id="kt_chart_widget_19_tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1" data-bs-toggle="tab" id="kt_chart_widget_25_tab_1" href="#kt_chart_widget_25_tab_content_1" aria-selected="false" tabindex="-1" role="tab">2025</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light active fw-bold px-4" data-bs-toggle="tab" id="kt_chart_widget_25_tab_2" href="#kt_chart_widget_25_tab_content_2" aria-selected="true" role="tab">Month</a>
                                    </li>
                                </ul>
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body pt-0">
                            <!--begin::Tab Content (ishlamayabdi)-->
                            <div class="tab-content">
                                <!--begin::Tap pane-->
                                <div class="tab-pane fade" id="kt_chart_widget_25_tab_content_1" role="tabpanel" aria-labelledby="kt_chart_widget_25_tab_1">
                                    <!--begin::Chart-->
                                    <div id="kt_charts_widget_25_chart_1" class="w-100 h-400px"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 0px; height: 0px;"><div><canvas class="am5-layer-0" width="0" height="0" style="position: absolute; top: 0px; left: 0px; width: 0px; height: 0px;"></canvas><canvas class="am5-layer-30" width="0" height="0" style="position: absolute; top: 0px; left: 0px; width: 0px; height: 0px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 0px; height: 0px;"><div role="button" aria-label="Zoom Out" aria-hidden="true" style="position: absolute; pointer-events: none; top: -2px; left: -2px; width: 4px; height: 4px;"></div><div role="checkbox" aria-label="Revenue; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 142.134px; height: 32px;"></div><div role="checkbox" aria-label="Expense; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 140.077px; height: 32px;"></div></div><div class="am5-tooltip-container"><div role="tooltip" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;">Revenue: 
Expense:</div></div></div></div>
                                    <!--end::Chart-->
                                </div>
                                <!--end::Tap pane-->
                                <!--begin::Tap pane-->
                                <div class="tab-pane fade active show" id="kt_chart_widget_25_tab_content_2" role="tabpanel" aria-labelledby="kt_chart_widget_25_tab_2">
                                    <!--begin::Chart-->
                                    <div id="kt_charts_widget_25_chart_2" class="w-100 h-400px"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 314px; height: 400px;"><div><canvas class="am5-layer-0" width="392" height="500" style="position: absolute; top: 0px; left: 0px; width: 314px; height: 400px;"></canvas><canvas class="am5-layer-30" width="392" height="500" style="position: absolute; top: 0px; left: 0px; width: 314px; height: 400px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden; width: 314px; height: 400px;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 314px; height: 400px;"><div role="button" aria-label="Zoom Out" aria-hidden="true" style="position: absolute; pointer-events: none; top: -2px; left: -2px; width: 4px; height: 4px;"></div><div role="checkbox" aria-label="Revenue; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 142.134px; height: 32px;"></div><div role="checkbox" aria-label="Expense; Press ENTER to toggle" aria-checked="true" tabindex="0" style="position: absolute; pointer-events: none; top: -11px; left: -2px; width: 140.077px; height: 32px;"></div></div><div class="am5-tooltip-container"><div role="tooltip" aria-hidden="true" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px); pointer-events: none;">Revenue: 
Expense:</div></div></div></div>
                                    <!--end::Chart-->
                                </div>
                                <!--end::Tap pane-->
                            </div>
                            <!--end::Tab Content-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Chart widget 25-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-xxl-8">
                    <!--begin::Chart widget 24-->
                    <div class="card card-flush overflow-hidden h-md-100">
                        <!--begin::Header-->
                        <div class="card-header py-5">
                            <!--begin::Title-->
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">Human Resources</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-6">Reports by states and ganders</span>
                            </h3>
                            <!--end::Title-->
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Menu-->
                                <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                                    <i class="ki-duotone ki-dots-square fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </button>
                                <!--begin::Menu 2-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px" data-kt-menu="true">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Actions</div>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu separator-->
                                    <div class="separator mb-3 opacity-75"></div>
                                    <!--end::Menu separator-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Ticket</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Customer</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                                        <!--begin::Menu item-->
                                        <a href="#" class="menu-link px-3">
                                            <span class="menu-title">New Group</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <!--end::Menu item-->
                                        <!--begin::Menu sub-->
                                        <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Admin Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Staff Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3">Member Group</a>
                                            </div>
                                            <!--end::Menu item-->
                                        </div>
                                        <!--end::Menu sub-->
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">New Contact</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu separator-->
                                    <div class="separator mt-3 opacity-75"></div>
                                    <!--end::Menu separator-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <div class="menu-content px-3 py-3">
                                            <a class="btn btn-primary btn-sm px-4" href="#">Generate Reports</a>
                                        </div>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu 2-->
                                <!--end::Menu-->
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <!--begin::Chart-->
                            <div id="kt_charts_widget_24" class="min-h-auto" style="height: 400px"><div style="position: relative; width: 100%; height: 100%;"><div aria-hidden="true" style="position: absolute; width: 720px; height: 400px;"><div><canvas class="am5-layer-0" width="900" height="500" style="position: absolute; top: 0px; left: 0px; width: 720px; height: 400px;"></canvas><canvas class="am5-layer-30" width="900" height="500" style="position: absolute; top: 0px; left: 0px; width: 720px; height: 400px;"></canvas></div></div><div class="am5-html-container" style="position: absolute; pointer-events: none; overflow: hidden; width: 720px; height: 400px;"></div><div class="am5-reader-container" role="alert" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(1px, 1px, 1px, 1px);"></div><div class="am5-focus-container" role="graphics-document" style="position: absolute; pointer-events: none; top: 0px; left: 0px; overflow: hidden; width: 720px; height: 400px;"><div role="button" aria-label="Zoom Out" aria-hidden="true" style="position: absolute; pointer-events: none; top: -2px; left: -2px; width: 4px; height: 4px;"></div></div><div class="am5-tooltip-container"></div></div></div>
                            <!--end::Chart-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Chart widget 24-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
</div>
@endsection
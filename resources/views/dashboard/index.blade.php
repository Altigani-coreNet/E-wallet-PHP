@extends('layouts.merchant.merchant_layout')

@section('title', 'Dashboard')

@section('content')
<div id="kt_app_content_container" class="app-container container-xxl">

    <!-- First Row - 3 boxes -->
    <div class="row gy-5 g-xl-10 mb-5">
        <!--begin::Users Count-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-info">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Total Users</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Active users count</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($totalUsers ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Users</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Users Count-->
        
        <!--begin::Terminals Count-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-warning">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Total Terminals</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Active terminals count</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($totalTerminals ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Terminals</span>
                    </div>
                    <div class="mb-2">
                        <span class="fs-6 fw-semibold text-success">{{ number_format($activeTerminals ?? 0) }} Active</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Terminals Count-->
        
        <!--begin::Total Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-primary">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Total Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">All time transactions</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($totalTransactions ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Transactions</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Total Transactions-->
    </div>

    <!-- Second Row - 3 boxes -->
    <div class="row gy-5 g-xl-10">
        <!--begin::Sale Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-success">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Sale Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Approved, Pending, Capture</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($saleTransactions ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Sales</span>
                    </div>
                    <div class="mb-2">
                        <span class="fs-6 fw-semibold text-success">${{ number_format($saleTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Sale Transactions-->
        
        <!--begin::Refund Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-danger">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Refund Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Refunded transactions</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($refundTransactions ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Refunds</span>
                    </div>
                    <div class="mb-2">
                        <span class="fs-6 fw-semibold text-danger">${{ number_format($refundTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Refund Transactions-->
        
        <!--begin::Void Transactions-->
        <div class="col-xl-4 mb-xl-10">
            <div class="card card-flush h-xl-100 bg-light-dark">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Void Transactions</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Voided transactions</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <div class="mb-2">
                        <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($voidTransactions ?? 0) }}</span>
                        <span class="fs-6 fw-semibold text-gray-500">Voids</span>
                    </div>
                    <div class="mb-2">
                        <span class="fs-6 fw-semibold text-dark">${{ number_format($voidTransactionsAmount ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Void Transactions-->
    </div>
    
    <!--begin::Row-->
    <div class="row gy-5 gx-xl-10">
        <!--begin::Col-->
        <div class="col-xl-5 mb-xl-10">
            <!--begin::Engage widget 3-->
            <div class=" h-md-100" data-bs-theme="light">
                <!--begin::Body-->
                <div class="card-body d-flex flex-column pt-13 pb-14">
                    <div class="row gy-5 g-xl-10">
                        <!--begin::Col-->
                        <div class="col-6 ">
                            <!--begin::Card widget 7-->
                            <div class="card card-flush h-xl-100 bg-light-warning">
                                <!--begin::Header-->
                                <div class="card-header pt-5">
                                    <!--begin::Title-->
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-gray-800">Today's Transactions</span>
                                    </h3>
                                    <!--end::Title-->
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-2">
                                    <!--begin::Statistics-->
                                    
                                    <!--end::Statistics-->
                                    <!--begin::Amount-->
                                    <div class="mb-2">
                                        <span class="fs-2hx fw-bold d-block text-success me-2 mb-2 lh-1 ls-n2">${{ number_format($transactionSummary['today']['amount'] ?? 0, 2) }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">Total Amount</span>
                                    </div>
                                    <!--end::Amount-->
                                </div>
                                <!--end::Body-->
                            </div>
                            <!--end::Card widget 7-->
                        </div>
                        <!--end::Col-->
                        
                        <!--begin::Col-->
                        <div class="col-6 ">
                            <!--begin::Card widget 7-->
                            <div class="card card-flush h-xl-100 bg-light-primary">
                                <!--begin::Header-->
                                <div class="card-header pt-5">
                                    <!--begin::Title-->
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-gray-800">This Week</span>
                                    </h3>
                                    <!--end::Title-->
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-2">
                                    <!--begin::Statistics-->
                                    
                                    <!--end::Statistics-->
                                    <!--begin::Amount-->
                                    <div class="mb-2">
                                        <span class="fs-2hx fw-bold d-block text-success me-2 mb-2 lh-1 ls-n2">${{ number_format($transactionSummary['this_week']['amount'] ?? 0, 2) }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">Total Amount</span>
                                    </div>
                                    <!--end::Amount-->
                                </div>
                                <!--end::Body-->
                            </div>
                            <!--end::Card widget 7-->
                        </div>
                        <!--end::Col-->
                        
                        <!--begin::Col-->
                        <div class="col-6 ">
                            <!--begin::Card widget 7-->
                            <div class="card card-flush h-xl-100 bg-light-danger">
                                <!--begin::Header-->
                                <div class="card-header pt-5">
                                    <!--begin::Title-->
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-gray-800">This Month</span>
                                    </h3>
                                    <!--end::Title-->
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-2">
                                    <!--begin::Statistics-->
                                    
                                    <!--end::Statistics-->
                                    <!--begin::Amount-->
                                    <div class="mb-2">
                                        <span class="fs-2hx fw-bold d-block text-success me-2 mb-2 lh-1 ls-n2">${{ number_format($transactionSummary['this_month']['amount'] ?? 0, 2) }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">Total Amount</span>
                                    </div>
                                    <!--end::Amount-->
                                </div>
                                <!--end::Body-->
                            </div>
                            <!--end::Card widget 7-->
                        </div>
                        <!--end::Col-->
                        
                        <!--begin::Col-->
                        <div class="col-6 ">
                            <!--begin::Card widget 7-->
                            <div class="card card-flush h-xl-100 ">
                                <!--begin::Header-->
                                <div class="card-header pt-5">
                                    <!--begin::Title-->
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-gray-800">This Year</span>
                                    </h3>
                                    <!--end::Title-->
                                </div>
                                <!--end::Header-->
                                <!--begin::Body-->
                                <div class="card-body pt-2">
                                    <!--begin::Statistics-->
                                    
                                    <!--end::Statistics-->
                                    <!--begin::Amount-->
                                    <div class="mb-2">
                                        <span class="fs-2hx fw-bold d-block text-success me-2 mb-2 lh-1 ls-n2">${{ number_format($transactionSummary['this_year']['amount'] ?? 0, 2) }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">Total Amount</span>
                                    </div>
                                    <!--end::Amount-->
                                </div>
                                <!--end::Body-->
                            </div>
                            <!--end::Card widget 7-->
                        </div>
                        <!--end::Col-->
                    </div>
                </div>
                <!--end::Body-->
            </div>
            <!--end::Engage widget 3-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-7 mb-5 mb-xl-10">
            <!--begin::Chart widget 11-->
            <x-transaction-chart
                :chartData="$transactionChartData"
                chartId="kt_charts_transactions"
                title="Transaction Analytics"
                subtitle="{{ number_format($todayStats['count'] ?? 0) }} transactions & ${{ number_format($todayStats['amount'] ?? 0, 2) }} today"
            />
        </div>
        
        <!--begin::Latest Transactions Table-->
       
        <!--end::Latest Transactions Table-->
    </div>
    <!--end::Row-->
    
    <!--begin::Row-->
  
    <!--end::Row-->
    
    <!--begin::Row-->
  
    <!--end::Row-->
    
    <!--begin::Row-->
    <div class="row gy-5 g-xl-10">
        <!--begin::Col-->
        <div class="col-xl-4 mb-xl-10">
            <!--begin::List widget 16-->
            <div class="card card-flush h-xl-100">
                <!--begin::Header-->
                <div class="card-header pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Terminal Status</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ $onlineCount ?? 0 }} terminals in use</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <a href="#" class="btn btn-sm btn-light" data-bs-toggle="tooltip" data-bs-dismiss="click" data-bs-custom-class="tooltip-inverse" data-bs-original-title="Terminal Management App is coming soon" data-kt-initialized="1">View All</a>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Header-->
                <!--begin::Body-->
                <div class="card-body pt-4 px-0">
                    <!--begin::Nav-->
                    <ul class="nav nav-pills nav-pills-custom item position-relative mx-9 mb-9" role="tablist">
                        <!--begin::Item-->
                        <li class="nav-item col-4 mx-0 p-0" role="presentation">
                            <!--begin::Link-->
                            <a class="nav-link active d-flex justify-content-center w-100 border-0 h-100" data-bs-toggle="pill" href="#kt_list_widget_16_tab_1" aria-selected="true" role="tab">
                                <!--begin::Subtitle-->
                                <span class="nav-text text-gray-800 fw-bold fs-6 mb-3">Online ({{ $onlineCount ?? 0 }})</span>
                                <!--end::Subtitle-->
                                <!--begin::Bullet-->
                                <span class="bullet-custom position-absolute z-index-2 bottom-0 w-100 h-4px bg-primary rounded"></span>
                                <!--end::Bullet-->
                            </a>
                            <!--end::Link-->
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="nav-item col-4 mx-0 px-0" role="presentation">
                            <!--begin::Link-->
                            <a class="nav-link d-flex justify-content-center w-100 border-0 h-100" data-bs-toggle="pill" href="#kt_list_widget_16_tab_2" aria-selected="false" tabindex="-1" role="tab">
                                <!--begin::Subtitle-->
                                <span class="nav-text text-gray-800 fw-bold fs-6 mb-3">Offline ({{ $offlineCount ?? 0 }})</span>
                                <!--end::Subtitle-->
                                <!--begin::Bullet-->
                                <span class="bullet-custom position-absolute z-index-2 bottom-0 w-100 h-4px bg-primary rounded"></span>
                                <!--end::Bullet-->
                            </a>
                            <!--end::Link-->
                        </li>
                        <!--end::Item-->
                        <!--begin::Item-->
                        <li class="nav-item col-4 mx-0 px-0" role="presentation">
                            <!--begin::Link-->
                            <a class="nav-link d-flex justify-content-center w-100 border-0 h-100" data-bs-toggle="pill" href="#kt_list_widget_16_tab_3" aria-selected="false" tabindex="-1" role="tab">
                                <!--begin::Subtitle-->
                                <span class="nav-text text-gray-800 fw-bold fs-6 mb-3">Testing ({{ $testingCount ?? 0 }})</span>
                                <!--end::Subtitle-->
                                <!--begin::Bullet-->
                                <span class="bullet-custom position-absolute z-index-2 bottom-0 w-100 h-4px bg-primary rounded"></span>
                                <!--end::Bullet-->
                            </a>
                            <!--end::Link-->
                        </li>
                        <!--end::Item-->
                        <!--begin::Bullet-->
                        <span class="position-absolute z-index-1 bottom-0 w-100 h-4px bg-light rounded"></span>
                        <!--end::Bullet-->
                    </ul>
                    <!--end::Nav-->
                    <!--begin::Tab Content-->
                    <div class="tab-content px-9 hover-scroll-overlay-y pe-7 me-3 mb-2" style="height: 454px">
                        <!--begin::Tap pane-->
                        <div class="tab-pane fade show active" id="kt_list_widget_16_tab_1" role="tabpanel">
                            @if(isset($onlineTerminals) && $onlineTerminals->count() > 0)
                                @foreach($onlineTerminals as $terminal)
                                    <!--begin::Item-->
                                    <div class="m-0">
                                        <!--begin::Timeline-->
                                        <div class="timeline timeline-border-dashed">
                                            <!--begin::Timeline item-->
                                            <div class="timeline-item">
                                                <!--begin::Timeline line-->
                                                <div class="timeline-line"></div>
                                                <!--end::Timeline line-->
                                                <!--begin::Timeline icon-->
                                                <div class="timeline-icon">
                                                    <i class="ki-duotone ki-cd fs-2 text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <!--end::Timeline icon-->
                                                <!--begin::Timeline content-->
                                                <div class="timeline-content m-0">
                                                    <!--begin::Label-->
                                                    <span class="fs-8 fw-bolder text-success text-uppercase">Terminal ID</span>
                                                    <!--begin::Label-->
                                                    <!--begin::Title-->
                                                    <a href="#" class="fs-6 text-gray-800 fw-bold d-block text-hover-primary">{{ $terminal->terminal_id }}</a>
                                                    <!--end::Title-->
                                                    <!--begin::Title-->
                                                    <span class="fw-semibold text-gray-500">{{ $terminal->name }}</span>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Timeline content-->
                                            </div>
                                            <!--end::Timeline item-->
                                        </div>
                                        <!--end::Timeline-->
                                    </div>
                                    <!--end::Item-->
                                    @if(!$loop->last)
                                    <!--begin::Separator-->
                                    <div class="separator separator-dashed mt-5 mb-4"></div>
                                    <!--end::Separator-->
                                    @endif
                                @endforeach
                            @else
                                <!--begin::Item-->
                                <div class="m-0 text-center py-10">
                                    <div class="text-gray-500 fs-6">No online terminals found</div>
                                </div>
                                <!--end::Item-->
                            @endif
                        </div>
                        <!--end::Tap pane-->
                        <!--begin::Tap pane-->
                        <div class="tab-pane fade" id="kt_list_widget_16_tab_2" role="tabpanel">
                            @if(isset($offlineTerminals) && $offlineTerminals->count() > 0)
                                @foreach($offlineTerminals as $terminal)
                                    <!--begin::Item-->
                                    <div class="m-0">
                                        <!--begin::Timeline-->
                                        <div class="timeline timeline-border-dashed">
                                            <!--begin::Timeline item-->
                                            <div class="timeline-item">
                                                <!--begin::Timeline line-->
                                                <div class="timeline-line"></div>
                                                <!--end::Timeline line-->
                                                <!--begin::Timeline icon-->
                                                <div class="timeline-icon">
                                                    <i class="ki-duotone ki-cd fs-2 text-danger">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <!--end::Timeline icon-->
                                                <!--begin::Timeline content-->
                                                <div class="timeline-content m-0">
                                                    <!--begin::Label-->
                                                    <span class="fs-8 fw-bolder text-danger text-uppercase">Terminal ID</span>
                                                    <!--begin::Label-->
                                                    <!--begin::Title-->
                                                    <a href="#" class="fs-6 text-gray-800 fw-bold d-block text-hover-primary">{{ $terminal->terminal_id }}</a>
                                                    <!--end::Title-->
                                                    <!--begin::Title-->
                                                    <span class="fw-semibold text-gray-500">{{ $terminal->name }}</span>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Timeline content-->
                                            </div>
                                            <!--end::Timeline item-->
                                        </div>
                                        <!--end::Timeline-->
                                    </div>
                                    <!--end::Item-->
                                    @if(!$loop->last)
                                    <!--begin::Separator-->
                                    <div class="separator separator-dashed mt-5 mb-4"></div>
                                    <!--end::Separator-->
                                    @endif
                                @endforeach
                            @else
                                <!--begin::Item-->
                                <div class="m-0 text-center py-10">
                                    <div class="text-gray-500 fs-6">No available terminals found</div>
                                </div>
                                <!--end::Item-->
                            @endif
                        </div>
                        <!--end::Tap pane-->
                        <!--begin::Tap pane-->
                        <div class="tab-pane fade" id="kt_list_widget_16_tab_3" role="tabpanel">
                            @if(isset($testingTerminals) && $testingTerminals->count() > 0)
                                @foreach($testingTerminals as $terminal)
                                    <!--begin::Item-->
                                    <div class="m-0">
                                        <!--begin::Timeline-->
                                        <div class="timeline timeline-border-dashed">
                                            <!--begin::Timeline item-->
                                            <div class="timeline-item">
                                                <!--begin::Timeline line-->
                                                <div class="timeline-line"></div>
                                                <!--end::Timeline line-->
                                                <!--begin::Timeline icon-->
                                                <div class="timeline-icon">
                                                    <i class="ki-duotone ki-cd fs-2 text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <!--end::Timeline icon-->
                                                <!--begin::Timeline content-->
                                                <div class="timeline-content m-0">
                                                    <!--begin::Label-->
                                                    <span class="fs-8 fw-bolder text-warning text-uppercase">Terminal ID</span>
                                                    <!--begin::Label-->
                                                    <!--begin::Title-->
                                                    <a href="#" class="fs-6 text-gray-800 fw-bold d-block text-hover-primary">{{ $terminal->terminal_id }}</a>
                                                    <!--end::Title-->
                                                    <!--begin::Title-->
                                                    <span class="fw-semibold text-gray-500">{{ $terminal->name }}</span>
                                                    <!--end::Title-->
                                                </div>
                                                <!--end::Timeline content-->
                                            </div>
                                            <!--end::Timeline item-->
                                        </div>
                                        <!--end::Timeline-->
                                    </div>
                                    <!--end::Item-->
                                    @if(!$loop->last)
                                    <!--begin::Separator-->
                                    <div class="separator separator-dashed mt-5 mb-4"></div>
                                    <!--end::Separator-->
                                    @endif
                                @endforeach
                            @else
                                <!--begin::Item-->
                                <div class="m-0 text-center py-10">
                                    <div class="text-gray-500 fs-6">No testing terminals found</div>
                                </div>
                                <!--end::Item-->
                            @endif
                        </div>
                        <!--end::Tap pane-->
                    </div>
                    <!--end::Tab Content-->
                </div>
                <!--end: Card Body-->
            </div>
            <!--end::List widget 16-->
        </div>
        <div class="col-xl-8">
            <div class="col-xl-12 mb-5 mb-xl-10">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-7">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">Latest Transactions</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">Recent activity</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <!--begin::Nav-->
                        <ul class="nav nav-pills nav-pills-custom mb-3" role="tablist">
                            <li class="nav-item mb-3 me-3" role="presentation">
                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-success flex-column overflow-hidden w-80px h-85px pt-5 pb-2 active" data-bs-toggle="pill" href="#latest_sales_tab" aria-selected="true" role="tab">
                                    <div class="nav-icon mb-3">
                                        <i class="ki-duotone ki-dollar fs-1 text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <span class="nav-text text-gray-800 fw-bold fs-6 lh-1">Sales</span>
                                    <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-success"></span>
                                </a>
                            </li>
                            <li class="nav-item mb-3 me-3" role="presentation">
                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-danger flex-column overflow-hidden w-80px h-85px pt-5 pb-2" data-bs-toggle="pill" href="#latest_refunds_tab" aria-selected="false" tabindex="-1" role="tab">
                                    <div class="nav-icon mb-3">
                                        <i class="ki-duotone ki-arrow-uturn-left fs-1 text-danger">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <span class="nav-text text-gray-800 fw-bold fs-6 lh-1">Refunds</span>
                                    <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-danger"></span>
                                </a>
                            </li>
                            <li class="nav-item mb-3" role="presentation">
                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-dark flex-column overflow-hidden w-80px h-85px pt-5 pb-2" data-bs-toggle="pill" href="#latest_voids_tab" aria-selected="false" tabindex="-1" role="tab">
                                    <div class="nav-icon mb-3">
                                        <i class="ki-duotone ki-cross fs-1 text-dark">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                    <span class="nav-text text-gray-800 fw-bold fs-6 lh-1">Voids</span>
                                    <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-dark"></span>
                                </a>
                            </li>
                        </ul>
                        <!--end::Nav-->
                        
                        <!--begin::Tab Content-->
                        <div class="tab-content">
                            <!--begin::Sales Tab-->
                            <div class="tab-pane fade active show" id="latest_sales_tab" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gs-0 gy-4 my-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="p-0 min-w-150px">Terminal</th>
                                                <th class="p-0 min-w-100px">Amount</th>
                                                <th class="p-0 min-w-100px">Status</th>
                                                <th class="p-0 w-50px">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($latestSales ?? [] as $transaction)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px me-3">
                                                            @if($transaction->user && $transaction->user->profile_image)
                                                                <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" class="rounded-circle" alt="Profile">
                                                            @else
                                                                <div class="symbol-label bg-light-primary text-primary fs-6 fw-bold">
                                                                    {{ substr($transaction->user->name ?? 'U', 0, 1) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">{{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                            <span class="text-muted fw-semibold d-block fs-7">Terminal: {{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold d-block mb-1 fs-6">${{ number_format($transaction->amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $transaction->status === 'approved' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'info') }} fs-7 fw-bold">
                                                        {{ ucfirst($transaction->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="ki-duotone ki-eye fs-2 text-gray-500"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <span class="fs-6">No sales transactions found</span>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!--end::Sales Tab-->
                            
                            <!--begin::Refunds Tab-->
                            <div class="tab-pane fade" id="latest_refunds_tab" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gs-0 gy-4 my-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="p-0 min-w-150px">Terminal</th>
                                                <th class="p-0 min-w-100px">Amount</th>
                                                <th class="p-0 min-w-100px">Status</th>
                                                <th class="p-0 w-50px">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($latestRefunds ?? [] as $transaction)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px me-3">
                                                            @if($transaction->user && $transaction->user->profile_image)
                                                                <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" class="rounded-circle" alt="Profile">
                                                            @else
                                                                <div class="symbol-label bg-light-danger text-danger fs-6 fw-bold">
                                                                    {{ substr($transaction->user->name ?? 'U', 0, 1) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">{{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                            <span class="text-muted fw-semibold d-block fs-7">Terminal: {{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold d-block mb-1 fs-6">${{ number_format($transaction->amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-danger fs-7 fw-bold">Refunded</span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="ki-duotone ki-eye fs-2 text-gray-500"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <span class="fs-6">No refund transactions found</span>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!--end::Refunds Tab-->
                            
                            <!--begin::Voids Tab-->
                            <div class="tab-pane fade" id="latest_voids_tab" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gs-0 gy-4 my-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="p-0 min-w-150px">Terminal</th>
                                                <th class="p-0 min-w-100px">Amount</th>
                                                <th class="p-0 min-w-100px">Status</th>
                                                <th class="p-0 w-50px">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($latestVoids ?? [] as $transaction)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px me-3">
                                                            @if($transaction->user && $transaction->user->profile_image)
                                                                <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" class="rounded-circle" alt="Profile">
                                                            @else
                                                                <div class="symbol-label bg-light-dark text-dark fs-6 fw-bold">
                                                                    {{ substr($transaction->user->name ?? 'U', 0, 1) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">{{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                            <span class="text-muted fw-semibold d-block fs-7">Terminal: {{ $transaction->terminal_id ?? 'N/A' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold d-block mb-1 fs-6">${{ number_format($transaction->amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark fs-7 fw-bold">Voided</span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="ki-duotone ki-eye fs-2 text-gray-500"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <span class="fs-6">No void transactions found</span>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!--end::Voids Tab-->
                        </div>
                        <!--end::Tab Content-->
                    </div>
                </div>
            </div>
        </div>

        
    </div>
        <!--end::Col-->
        <!--begin::Col-->
    <!--begin::Row-->
  
   
    <!--end::Row-->
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Transaction Charts Data
    const dailyData = @json($dailyStats ?? []);
    const weeklyData = @json($weeklyStats ?? []);
    const monthlyData = @json($monthlyStats ?? []);

    // Daily Transactions Chart
    const dailyCtx = document.getElementById('kt_charts_widget_11_chart_1');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.labels || [],
                datasets: [{
                    label: 'Transactions',
                    data: dailyData.counts || [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Amount',
                    data: dailyData.amounts || [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Transaction Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Amount'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Transaction Statistics'
                    }
                }
            }
        });
    }

    // Weekly Transactions Chart
    const weeklyCtx = document.getElementById('kt_charts_widget_11_chart_2');
    if (weeklyCtx) {
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: weeklyData.labels || [],
                datasets: [{
                    label: 'Transactions',
                    data: weeklyData.counts || [],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }, {
                    label: 'Amount',
                    data: weeklyData.amounts || [],
                    backgroundColor: 'rgba(255, 159, 64, 0.8)',
                    borderColor: 'rgb(255, 159, 64)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Week'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Transaction Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Amount'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Weekly Transaction Statistics'
                    }
                }
            }
        });
    }

    // Monthly Transactions Chart
    const monthlyCtx = document.getElementById('kt_charts_widget_11_chart_3');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels || [],
                datasets: [{
                    label: 'Transactions',
                    data: monthlyData.counts || [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Amount',
                    data: monthlyData.amounts || [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Transaction Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Amount'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Transaction Statistics'
                    }
                }
            }
        });
    }

    // Terminal Usage Chart
    const terminalUsageCtx = document.getElementById('terminalUsageChart');
    if (terminalUsageCtx) {
        new Chart(terminalUsageCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Active Terminals',
                    data: [12, 19, 15, 17, 22, 25, 20],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Terminal Status Chart
    const terminalStatusCtx = document.getElementById('terminalStatusChart');
    if (terminalStatusCtx) {
        new Chart(terminalStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'Maintenance'],
                datasets: [{
                    data: [70, 20, 10],
                    backgroundColor: [
                        'rgb(75, 192, 192)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 205, 86)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

<!--begin::Custom POS Dashboard Script-->
<script src="{{ asset('assets/js/pos-dashboard-custom.js') }}"></script>
<!--end::Custom POS Dashboard Script-->
@endpush 
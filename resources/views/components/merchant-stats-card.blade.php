<!--begin::Stats Card-->
<div class="card card-custom card-stretch gutter-b">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 text-gray-800">{{ $title ?? __('translation.merchant_statistics') }}</span>
            <span class="text-muted mt-1 fw-semibold fs-7">{{ $subtitle ?? __('translation.overview_of_merchant_performance') }}</span>
        </h3>
        <div class="card-toolbar">
            @if(isset($actionUrl) && isset($actionText))
                <a href="{{ $actionUrl }}" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-arrow-right fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ $actionText }}
                </a>
            @endif
        </div>
    </div>
    <!--end::Header-->

    <!--begin::Body-->
    <div class="card-body d-flex flex-column">
        <!--begin::Stats-->
        <div class="row g-5 g-xl-8">
            <!--begin::Stat-->
            <div class="col-xl-3">
                <div class="bg-body-light rounded px-6 py-8 d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="ki-duotone ki-shop fs-2x text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.total_branches') }}</span>
                        <span class="fs-2 fw-bold text-gray-800">{{ $merchant->branches_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <!--end::Stat-->

            <!--begin::Stat-->
            <div class="col-xl-3">
                <div class="bg-body-light rounded px-6 py-8 d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="ki-duotone ki-abstract-26 fs-2x text-success">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.total_terminals') }}</span>
                        <span class="fs-2 fw-bold text-gray-800">{{ $merchant->terminals_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <!--end::Stat-->

            <!--begin::Stat-->
            <div class="col-xl-3">
                <div class="bg-body-light rounded px-6 py-8 d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="ki-duotone ki-user fs-2x text-info">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.associated_users') }}</span>
                        <span class="fs-2 fw-bold text-gray-800">{{ $merchant->user ? 1 : 0 }}</span>
                    </div>
                </div>
            </div>
            <!--end::Stat-->

            <!--begin::Stat-->
            <div class="col-xl-3">
                <div class="bg-body-light rounded px-6 py-8 d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <div class="symbol-label bg-light-warning">
                            <i class="ki-duotone ki-calendar fs-2x text-warning">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.days_active') }}</span>
                        <span class="fs-2 fw-bold text-gray-800">{{ $merchant->created_at ? $merchant->created_at->diffInDays(now()) : 0 }}</span>
                    </div>
                </div>
            </div>
            <!--end::Stat-->
        </div>
        <!--end::Stats-->

        @if(isset($additionalContent))
            <div class="mt-8">
                {{ $additionalContent }}
            </div>
        @endif
    </div>
    <!--end::Body-->
</div>
<!--end::Stats Card-->

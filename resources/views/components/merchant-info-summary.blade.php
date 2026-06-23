<!--begin::Merchant Info Summary-->
<div class="card card-custom card-stretch gutter-b">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 text-gray-800">{{ $title ?? __('translation.merchant_summary') }}</span>
            <span class="text-muted mt-1 fw-semibold fs-7">{{ $subtitle ?? __('translation.key_information_at_a_glance') }}</span>
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
    <div class="card-body">
        <div class="row g-5 g-xl-8">
            <!--begin::Info Column-->
            <div class="col-xl-6">
                <div class="d-flex flex-column">
                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
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
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.business_name') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->name ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="ki-duotone ki-user fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.owner_name') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->owner_name ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="ki-duotone ki-sms fs-2x text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.email') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->email ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="ki-duotone ki-phone fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.phone') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->phone ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->
                </div>
            </div>
            <!--end::Info Column-->

            <!--begin::Info Column-->
            <div class="col-xl-6">
                <div class="d-flex flex-column">
                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-dark">
                                <i class="ki-duotone ki-abstract-26 fs-2x text-dark">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.merchant_code') }}</span>
                            <span class="badge badge-primary fs-6">{{ $merchant->merchant_code ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-danger">
                                <i class="ki-duotone ki-geolocation fs-2x text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.address') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->address ?? __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-calendar fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.created_date') }}</span>
                            <span class="fs-5 fw-bold text-gray-800">{{ $merchant->created_at ? $merchant->created_at->format('M d, Y') : __('translation.not_available') }}</span>
                        </div>
                    </div>
                    <!--end::Info Item-->

                    <!--begin::Info Item-->
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-40px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="ki-duotone ki-check-circle fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="fs-6 fw-semibold text-gray-600">{{ __('translation.status') }}</span>
                            @if($merchant->is_active)
                                <span class="badge badge-light-success fs-6">{{ __('translation.active') }}</span>
                            @else
                                <span class="badge badge-light-danger fs-6">{{ __('translation.inactive') }}</span>
                            @endif
                        </div>
                    </div>
                    <!--end::Info Item-->
                </div>
            </div>
            <!--end::Info Column-->
        </div>

        @if(isset($additionalContent))
            <div class="mt-8">
                {{ $additionalContent }}
            </div>
        @endif
    </div>
    <!--end::Body-->
</div>
<!--end::Merchant Info Summary-->

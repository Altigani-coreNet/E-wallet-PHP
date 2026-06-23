<!--begin::Activity Timeline-->
<div class="card card-custom card-stretch gutter-b">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 text-gray-800">{{ $title ?? __('translation.recent_activity') }}</span>
            <span class="text-muted mt-1 fw-semibold fs-7">{{ $subtitle ?? __('translation.merchant_activity_timeline') }}</span>
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
        <!--begin::Timeline-->
        <div class="timeline">
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line w-40px"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-success">
                        <i class="ki-duotone ki-shop fs-2x text-success">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                    </div>
                </div>
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <h3 class="fs-5 fw-semibold mb-2">{{ __('translation.merchant_created') }}</h3>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">{{ $merchant->created_at ? $merchant->created_at->format('M d, Y H:i') : __('translation.date_not_available') }}</div>
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->

            @if($merchant->user)
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line w-40px"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon symbol symbol-40px me-4">
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
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <h3 class="fs-5 fw-semibold mb-2">{{ __('translation.user_account_linked') }}</h3>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">{{ $merchant->user->created_at ? $merchant->user->created_at->format('M d, Y H:i') : __('translation.date_not_available') }}</div>
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->
            @endif

            @if($merchant->branches && count($merchant->branches) > 0)
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line w-40px"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-primary">
                        <i class="ki-duotone ki-geolocation fs-2x text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <h3 class="fs-5 fw-semibold mb-2">{{ __('translation.branches_added') }}</h3>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">{{ count($merchant->branches) }} {{ __('translation.branches') }} {{ __('translation.total') }}</div>
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->
            @endif

            @if($merchant->terminals && count($merchant->terminals) > 0)
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line w-40px"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-warning">
                        <i class="ki-duotone ki-abstract-26 fs-2x text-warning">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <h3 class="fs-5 fw-semibold mb-2">{{ __('translation.terminals_assigned') }}</h3>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">{{ count($merchant->terminals) }} {{ __('translation.terminals') }} {{ __('translation.total') }}</div>
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->
            @endif

            @if($merchant->updated_at && $merchant->updated_at != $merchant->created_at)
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line w-40px"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon symbol symbol-40px me-4">
                    <div class="symbol-label bg-light-dark">
                        <i class="ki-duotone ki-pencil fs-2x text-dark">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <h3 class="fs-5 fw-semibold mb-2">{{ __('translation.last_updated') }}</h3>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">{{ $merchant->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->
            @endif

            @if(isset($additionalItems))
                {{ $additionalItems }}
            @endif
        </div>
        <!--end::Timeline-->
    </div>
    <!--end::Body-->
</div>
<!--end::Activity Timeline-->

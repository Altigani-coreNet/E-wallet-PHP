<!--begin::Quick Actions-->
<div class="card card-custom card-stretch gutter-b">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 text-gray-800">{{ $title ?? __('translation.quick_actions') }}</span>
            <span class="text-muted mt-1 fw-semibold fs-7">{{ $subtitle ?? __('translation.common_merchant_actions') }}</span>
        </h3>
    </div>
    <!--end::Header-->

    <!--begin::Body-->
    <div class="card-body">
        <div class="row g-5 g-xl-8">
            <!--begin::Action Card-->
            <div class="col-xl-3 col-md-6">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-body text-center">
                        <div class="symbol symbol-60px symbol-lg-80px symbol-fixed position-relative mb-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-pencil fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('translation.edit_merchant') }}</h4>
                        <p class="text-muted fs-7 mb-4">{{ __('translation.update_merchant_information') }}</p>
                        <a href="{{ route('merchants.edit', $merchant->id) }}" class="btn btn-primary btn-sm">
                            {{ __('translation.edit') }}
                        </a>
                    </div>
                </div>
            </div>
            <!--end::Action Card-->

            <!--begin::Action Card-->
            <div class="col-xl-3 col-md-6">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-body text-center">
                        <div class="symbol symbol-60px symbol-lg-80px symbol-fixed position-relative mb-4">
                            <div class="symbol-label bg-light-success">
                                <i class="ki-duotone ki-geolocation fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('translation.manage_branches') }}</h4>
                        <p class="text-muted fs-7 mb-4">{{ __('translation.add_or_edit_branches') }}</p>
                        <a href="{{ route('branches.index') }}?merchant_id={{ $merchant->id }}" class="btn btn-success btn-sm">
                            {{ __('translation.manage') }}
                        </a>
                    </div>
                </div>
            </div>
            <!--end::Action Card-->

            <!--begin::Action Card-->
            <div class="col-xl-3 col-md-6">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-body text-center">
                        <div class="symbol symbol-60px symbol-lg-80px symbol-fixed position-relative mb-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="ki-duotone ki-abstract-26 fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('translation.manage_terminals') }}</h4>
                        <p class="text-muted fs-7 mb-4">{{ __('translation.assign_or_configure_terminals') }}</p>
                        <a href="{{ route('terminals.index') }}?merchant_id={{ $merchant->id }}" class="btn btn-warning btn-sm">
                            {{ __('translation.manage') }}
                        </a>
                    </div>
                </div>
            </div>
            <!--end::Action Card-->

            <!--begin::Action Card-->
            <div class="col-xl-3 col-md-6">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-body text-center">
                        <div class="symbol symbol-60px symbol-lg-80px symbol-fixed position-relative mb-4">
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
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('translation.user_account') }}</h4>
                        <p class="text-muted fs-7 mb-4">{{ __('translation.manage_associated_user') }}</p>
                        @if($merchant->user)
                            <a href="{{ route('users.edit', $merchant->user->id) }}" class="btn btn-info btn-sm">
                                {{ __('translation.view') }}
                            </a>
                        @else
                            <span class="badge badge-light-warning">{{ __('translation.no_user') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <!--end::Action Card-->

            @if(isset($additionalActions))
                {{ $additionalActions }}
            @endif
        </div>

        @if(isset($bottomContent))
            <div class="mt-8">
                {{ $bottomContent }}
            </div>
        @endif
    </div>
    <!--end::Body-->
</div>
<!--end::Quick Actions-->

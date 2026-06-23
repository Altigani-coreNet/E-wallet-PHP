<!--begin::Profile header-->
                    @php
                        $pendingChangeRequests = \App\Models\ChangeRequest::where('changeable_type', \App\Models\Merchant::class)
                            ->where('changeable_id', $merchant->id)
                            ->where('status', 'pending')
                            ->count();
                    @endphp

<div class="card mb-5  {{ $pendingChangeRequests > 0  ? 'mb-xl-10' : '' }}">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
            <!--begin::Pic-->
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    @if($merchant->logo_url)
                        <img src="{{ $merchant->logo_url }}" alt="Merchant Logo" class="rounded">
                    @else
                        <div class="symbol-label fs-3 bg-light-primary text-primary">
                            {{ strtoupper(substr($merchant->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-white h-20px w-20px"></div>
                </div>
            </div>
            <!--end::Pic-->
            
            <!--begin::Info-->
            <div class="flex-grow-1">
                <!--begin::Title-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::Merchant-->
                    <div class="d-flex flex-column">
                        <!--begin::Name-->
                        <div class="d-flex align-items-center mb-2">
                            <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bolder me-1">
                                {{ $merchant->name ?? __('translation.merchant_name_not_available') }}
                            </a>
                            <a href="#">
                                <!--begin::Svg Icon | path: icons/duotune/general/gen026.svg-->
                                <span class="svg-icon svg-icon-1 svg-icon-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24">
                                        <path d="M10.0813 3.7242C10.8849 2.16438 13.1151 2.16438 13.9187 3.7242V3.7242C14.4016 4.66147 15.4909 5.1127 16.4951 4.79139V4.79139C18.1663 4.25668 19.7433 5.83365 19.2086 7.50485V7.50485C18.8873 8.50905 19.3385 9.59842 20.2758 10.0813V10.0813C21.8356 10.8849 21.8356 13.1151 20.2758 13.9187V13.9187C19.3385 14.4016 18.8873 15.491 19.2086 16.4951V16.4951C19.7433 18.1663 18.1663 19.7433 16.4951 19.2086V19.2086C15.491 18.8873 14.4016 19.3385 13.9187 20.2758V20.2758C13.1151 21.8356 10.8849 21.8356 10.0813 20.2758V20.2758C9.59842 19.3385 8.50905 18.8873 7.50485 19.2086V19.2086C5.83365 19.7433 4.25668 18.1663 4.79139 16.4951V16.4951C5.1127 15.491 4.66147 14.4016 3.7242 13.9187V13.9187C2.16438 13.1151 2.16438 10.8849 3.7242 10.0813V10.0813C4.66147 9.59842 5.1127 8.50905 4.79139 7.50485V7.50485C4.25668 5.83365 5.83365 4.25668 7.50485 4.79139V4.79139C8.50905 5.1127 9.59842 4.66147 10.0813 3.7242V3.7242Z" fill="#00A3FF"></path>
                                        <path class="permanent" d="M14.8563 9.1903C15.0606 8.94984 15.3771 8.9385 15.6175 9.14289C15.858 9.34728 15.8229 9.66433 15.6185 9.9048L11.863 14.6558C11.6554 14.9001 11.2876 14.9258 11.048 14.7128L8.47656 12.4271C8.24068 12.2174 8.21944 11.8563 8.42911 11.6204C8.63877 11.3845 8.99996 11.3633 9.23583 11.5729L11.3706 13.4705L14.8563 9.1903Z" fill="white"></path>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                            </a>
                        </div>
                        <!--end::Name-->
                        
                        <!--begin::Info-->
                        <div class="d-flex flex-wrap fw-bold fs-6 mb-4 pe-2">
                            @if($merchant->address)
                                <a href="#" class="d-flex align-items-center text-gray-400 text-hover-primary me-5 mb-2">
                                    <!--begin::Svg Icon | path: icons/duotune/general/gen018.svg-->
                                    <span class="svg-icon svg-icon-4 me-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path opacity="0.3" d="M18.0624 15.3453L13.1624 20.7453C12.5624 21.4453 11.5624 21.4453 10.9624 20.7453L6.06242 15.3453C4.56242 13.6453 3.76242 11.4453 4.06242 8.94534C4.56242 5.34534 7.46242 2.44534 11.0624 2.04534C15.8624 1.54534 19.9624 5.24534 19.9624 9.94534C20.0624 12.0453 19.2624 13.9453 18.0624 15.3453Z" fill="black"></path>
                                            <path d="M12.0624 13.0453C13.7193 13.0453 15.0624 11.7022 15.0624 10.0453C15.0624 8.38849 13.7193 7.04535 12.0624 7.04535C10.4056 7.04535 9.06241 8.38849 9.06241 10.0453C9.06241 11.7022 10.4056 13.0453 12.0624 13.0453Z" fill="black"></path>
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    {{ $merchant->address }}
                                </a>
                            @endif
                            
                            @if($merchant->business_type_display_name)
                                <a href="#" class="d-flex align-items-center text-gray-400 text-hover-primary me-5 mb-2">
                                    <!--begin::Svg Icon | path: icons/duotune/communication/com011.svg-->
                                    <span class="svg-icon svg-icon-4 me-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path opacity="0.3" d="M21 19H3C2.4 19 2 18.6 2 18V6C2 5.4 2.4 5 3 5H21C21.6 5 22 5.4 22 6V18C22 18.6 21.6 19 21 19Z" fill="black"></path>
                                            <path d="M21 5H2.99999C2.69999 5 2.49999 5.10005 2.29999 5.30005L11.2 13.3C11.7 13.7 12.4 13.7 12.8 13.3L21.7 5.30005C21.5 5.10005 21.3 5 21 5Z" fill="black"></path>
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    {{ $merchant->business_type_display_name }}
                                </a>
                            @endif
                            
                            <a href="#" class="d-flex align-items-center text-gray-400 text-hover-primary mb-2">
                                <!--begin::Svg Icon | path: icons/duotune/communication/com011.svg-->
                                <span class="svg-icon svg-icon-4 me-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path opacity="0.3" d="M21 19H3C2.4 19 2 18.6 2 18V6C2 5.4 2.4 5 3 5H21C21.6 5 22 5.4 22 6V18C22 18.6 21.6 19 21 19Z" fill="black"></path>
                                        <path d="M21 5H2.99999C2.69999 5 2.49999 5.10005 2.29999 5.30005L11.2 13.3C11.7 13.7 12.4 13.7 12.8 13.3L21.7 5.30005C21.5 5.10005 21.3 5 21 5Z" fill="black"></path>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                {{ $merchant->email }}
                            </a>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Merchant-->
                    
                    <!--begin::Actions-->
                    <div class="d-flex my-4">
                        <!--begin::Menu-->
                        <div class="me-0">
                            <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="bi bi-three-dots fs-3"></i>
                            </button>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                @if($merchant->status === 'pending' || $merchant->status === null || $merchant->status === 'viewed')
                                    <div class="menu-item px-3">
                                        <form action="{{ route('merchants.approve', $merchant->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 bg-light-success text-success" style="background: none; border: none; width: 100%; text-align: left;">
                                                Approve
                                            </button>
                                        </form>
                                    </div>
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3 bg-light-danger text-danger reject-merchant" data-merchant-id="{{ $merchant->id }}">
                                            Reject
                                        </a>
                                    </div>
                                @elseif($merchant->status === 'approved' || $merchant->status === 'viewed' || $merchant->status === 'rejected')
                                    <div class="menu-item px-3">
                                        <form action="{{ route('merchants.suspend', $merchant->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 text-warning" style="background: none; border: none; width: 100%; text-align: left;">
                                                Suspend
                                            </button>
                                        </form>
                                    </div>
                                @elseif($merchant->status === 'rejected')
                                    <div class="menu-item px-3">
                                        <form action="{{ route('merchants.approve', $merchant->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                                                Approve
                                            </button>
                                        </form>
                                    </div>
                                @elseif($merchant->status === 'suspended')
                                    <div class="menu-item px-3">
                                        <form action="{{ route('merchants.unsuspend', $merchant->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                                                {{ __('translation.unsuspend') }}
                                            </button>
                                        </form>
                                    </div>
                                    <div class="menu-item px-3">
                                        <form action="{{ route('merchants.approve', $merchant->id) }}" method="post" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 text-success" style="background: none; border: none; width: 100%; text-align: left;">
                                                Approve
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <div class="menu-item px-3">
                                    <a href="{{ route('merchants.edit', $merchant->id) }}" class="menu-link px-3">
                                        Edit
                                    </a>
                                </div>
                                <div class="menu-item px-3">
                                    <form action="{{ route('merchants.destroy', $merchant->id) }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <a href="#" class="menu-link px-3 text-danger" onclick="event.preventDefault(); this.closest('form').submit();">
                                            Delete
                                        </a>
                                    </form>
                                </div>
                            </div>
                            <!--end::Menu-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Title-->
                
                <!--begin::Stats-->
                <div class="d-flex flex-wrap flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <!--begin::Stats-->
                        <div class="d-flex flex-wrap">
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                                <!--begin::Number-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr065.svg-->
                                    <span class="svg-icon svg-icon-3 svg-icon-success me-2">
                                        <span class="svg-icon svg-icon-success svg-icon-2x">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path opacity="0.3" d="M20 15H4C2.9 15 2 14.1 2 13V7C2 6.4 2.4 6 3 6H21C21.6 6 22 6.4 22 7V13C22 14.1 21.1 15 20 15ZM13 12H11C10.5 12 10 12.4 10 13V16C10 16.5 10.4 17 11 17H13C13.6 17 14 16.6 14 16V13C14 12.4 13.6 12 13 12Z" fill="black"></path>
                                                <path d="M14 6V5H10V6H8V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V6H14ZM20 15H14V16C14 16.6 13.5 17 13 17H11C10.5 17 10 16.6 10 16V15H4C3.6 15 3.3 14.9 3 14.7V18C3 19.1 3.9 20 5 20H19C20.1 20 21 19.1 21 18V14.7C20.7 14.9 20.4 15 20 15Z" fill="black"></path>
                                            </svg>
                                        </span>
                                    </span>
                                    <!--end::Svg Icon-->
                                    <div class="fs-2 fw-bolder counted" data-kt-countup="true" data-kt-countup-value="{{ $merchant->branches_count ?? 0 }}">
                                        {{ $merchant->branches_count ?? 0 }}
                                    </div>
                                </div>
                                <!--end::Number-->
                                <!--begin::Label-->
                                <div class="fw-bold fs-6 text-gray-400">Branches</div>
                                <!--end::Label-->
                            </div>
                            <!--end::Stat-->
                            
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                                <!--begin::Number-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr065.svg-->
                                    <span class="svg-icon svg-icon-primary svg-icon-2x">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <title>Stockholm-icons / General / Clipboard</title>
                                            <desc>Created with Sketch.</desc>
                                            <defs/>
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24"/>
                                                <path d="M8,3 L8,3.5 C8,4.32842712 8.67157288,5 9.5,5 L14.5,5 C15.3284271,5 16,4.32842712 16,3.5 L16,3 L18,3 C19.1045695,3 20,3.8954305 20,5 L20,21 C20,22.1045695 19.1045695,23 18,23 L6,23 C4.8954305,23 4,22.1045695 4,21 L4,5 C4,3.8954305 4.8954305,3 6,3 L8,3 Z" fill="#000000" opacity="0.3"/>
                                                <path d="M11,2 C11,1.44771525 11.4477153,1 12,1 C12.5522847,1 13,1.44771525 13,2 L14.5,2 C14.7761424,2 15,2.22385763 15,2.5 L15,3.5 C15,3.77614237 14.7761424,4 14.5,4 L9.5,4 C9.22385763,4 9,3.77614237 9,3.5 L9,2.5 C9,2.22385763 9.22385763,2 9.5,2 L11,2 Z" fill="#000000"/>
                                                <rect fill="#000000" opacity="0.3" x="7" y="10" width="5" height="2" rx="1"/>
                                                <rect fill="#000000" opacity="0.3" x="7" y="14" width="9" height="2" rx="1"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    <div class="fs-2 fw-bolder counted" data-kt-countup="true" data-kt-countup-value="{{ $merchant->terminals_count ?? 0 }}">
                                        {{ $merchant->terminals_count ?? 0 }}
                                    </div>
                                </div>
                                <!--end::Number-->
                                <!--begin::Label-->
                                <div class="fw-bold fs-6 text-gray-400">Terminals</div>
                                <!--end::Label-->
                            </div>
                            <!--end::Stat-->
                            
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                                <!--begin::Number-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr065.svg-->
                                    <span class="svg-icon svg-icon-dark svg-icon-2x mx-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <title>Stockholm-icons / Communication / Group</title>
                                            <desc>Created with Sketch.</desc>
                                            <defs/>
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <polygon points="0 0 24 0 24 24 0 24"/>
                                                <path d="M18,14 C16.3431458,14 15,12.6568542 15,11 C15,9.34314575 16.3431458,8 18,8 C19.6568542,8 21,9.34314575 21,11 C21,12.6568542 19.6568542,14 18,14 Z M9,11 C6.790861,11 5,9.209139 5,7 C5,4.790861 6.790861,3 9,3 C11.209139,3 13,4.790861 13,7 C13,9.209139 11.209139,11 9,11 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                                                <path d="M17.6011961,15.0006174 C21.0077043,15.0378534 23.7891749,16.7601418 23.9984937,20.4 C24.0069246,20.5466056 23.9984937,21 23.4559499,21 L19.6,21 C19.6 18.7490654 18.8562935,16.6718327 17.6011961,15.0006174 Z M0.00065168429,20.1992055 C0.388258525,15.4265159 4.26191235,13 8.98334134,13 C13.7712164,13 17.7048837,15.2931929 17.9979143,20.2 C18.0095879,20.3954741 17.9979143,21 17.2466999,21 C13.541124,21 8.03472472,21 0.727502227,21 C0.476712155,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#000000" fill-rule="nonzero"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    <div class="fs-2 fw-bolder counted" data-kt-countup="true" data-kt-countup-value="{{ $merchant->user ? 1 : 0 }}">
                                        {{ $merchant->user ? 1 : 0 }}
                                    </div>
                                </div>
                                <!--end::Number-->
                                <!--begin::Label-->
                                <div class="fw-bold fs-6 text-gray-400">Users</div>
                                <!--end::Label-->
                            </div>
                            <!--end::Stat-->
                            
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-100px py-3 px-4 me-6 mb-3">
                                <!--begin::Number-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Svg Icon | path: icons/duotune/arrows/arr065.svg-->
                                    <span class="svg-icon svg-icon-danger svg-icon-2x">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <title>Stockholm-icons / General / Fire</title>
                                            <desc>Created with Sketch.</desc>
                                            <defs/>
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24"/>
                                                <path d="M14,7 C13.6666667,10.3333333 12.6666667,12.1167764 11,12.3503292 C11,12.3503292 12.5,6.5 10.5,3.5 C10.5,3.5 10.287918,6.71444735 8.14498739,10.5717225 C7.14049032,12.3798172 6,13.5986793 6,16 C6,19.428689 9.51143904,21.2006583 12.0057195,21.2006583 C14.5,21.2006583 18,20.0006172 18,15.8004732 C18,14.0733981 16.6666667,11.1399071 14,7 Z" fill="#000000"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <!--end::Svg Icon-->
                                    <div class="fs-2 fw-bolder counted" data-kt-countup="true" data-kt-countup-value="{{ $merchant->transactions_count ?? 0 }}">
                                        {{ $merchant->transactions_count ?? 0 }}
                                    </div>
                                </div>
                                <!--end::Number-->
                                <!--begin::Label-->
                                <div class="fw-bold fs-6 text-gray-400">Transactions</div>
                                <!--end::Label-->
                            </div>
                            <!--end::Stat-->
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Wrapper-->
                    
                    <!--begin::Progress-->
                    @if(isset($profileCompletion))
                    <div class="d-flex align-items-center w-200px w-sm-300px flex-column mt-3">
                        <div class="d-flex justify-content-between w-100 mt-auto mb-2" 
                             data-bs-toggle="tooltip" 
                             data-bs-html="true"
                             data-bs-custom-class="tooltip-dark"
                             title="@if(!empty($profileCompletion['missing']))
                                    <div class='text-start'>
                                        <p class='fw-bold mb-2'>{{ __('translation.complete_your_profile') }}</p>
                                        @foreach($profileCompletion['missing'] as $message)
                                            <div class='d-flex align-items-center mb-2'>
                                                <span class='bullet bullet-dot bg-danger me-2'></span>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                   @else
                                    {{ __('translation.profile_complete') }}
                                   @endif">
                            <span class="fw-bold fs-6 text-gray-400">{{ __('translation.profile_completion') }}</span>
                            <span class="fw-bolder fs-6">{{ $profileCompletion['completion'] }}%</span>
                        </div>
                        <div class="h-5px mx-3 w-100 bg-light mb-3">
                            <div class="bg-success rounded h-5px" role="progressbar" 
                                style="width: {{ $profileCompletion['completion'] }}%;" 
                                aria-valuenow="{{ $profileCompletion['completion'] }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                                                 <div class="text-center w-100">
                            @php
                                $statusClasses = [
                                    'pending' => 'badge-light-warning',
                                    'approved' => 'badge-light-success',
                                    'rejected' => 'badge-light-danger',
                                    'suspended' => 'badge-light-danger',
                                    'viewed' => 'badge-light-info',
                                ];
                                $statusClass = $statusClasses[$merchant->status] ?? 'badge-light-warning';
                                
                                $statusLabels = [
                                    'pending' => __('translation.pending'),
                                    'approved' => __('translation.approved'),
                                    'rejected' => __('translation.rejected'),
                                    'suspended' => __('translation.suspended'),
                                    'viewed' => __('translation.viewed'),
                                ];
                                $statusLabel = $statusLabels[$merchant->status] ?? $merchant->status;
                            @endphp
                            <button type="button" class="btn btn-sm {{ $statusClass }} px-9 py-4">
                                <span class="indicator-label">
                                    {{ ucfirst($statusLabel) }}
                                </span>
                                @if($merchant->status === 'pending' || $merchant->status === 'viewed')
                                    <span class="indicator-progress">
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                @endif
                            </button>
                            @if($merchant->status === 'rejected' && $merchant->rejection_reason)
                                <div class="mt-2">
                                    <span class="text-danger fs-8" data-bs-toggle="tooltip" title="{{ $merchant->rejection_reason }}">
                                        <i class="bi bi-info-circle me-1"></i>{{ Str::limit($merchant->rejection_reason, 30) }}
                                    </span>
                                </div>
                            @endif
                         </div>
                      </div>
                      <!--end::Progress-->
                    @endif
                    </div>
                <!--end::Stats-->
            </div>
            <!--end::Info-->
        </div>
        <!--end::Details-->
        
        <!--begin::Pending Change Requests Alert-->
       
        <!--end::Pending Change Requests Alert-->
        
        <!--begin::Navs-->
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-nav-line-tabs mb-5 fs-6">
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'overview' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'overview']) }}">
                    {{ __('translation.overview') }}
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'events' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'events']) }}">
                    {{ __('translation.events') }}
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'transactions' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'transactions']) }}">
                    {{ __('translation.transactions') }}
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'users' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'users']) }}">
                    {{ __('translation.users') }}
                </a>
            </li>
            
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'terminals' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'terminals']) }}">
                    {{ __('translation.terminals') }}
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'branches' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'branches']) }}">
                    {{ __('translation.branches') }}
                </a>
            </li>
            
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'attachments' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'attachments']) }}">
                    {{ __('translation.attachments') }}
                </a>
            </li>
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ $activeTab === 'change-requests' ? 'active' : '' }}" href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'change-requests']) }}">
                    {{ __('translation.change_requests') }}
                    
                    @if($pendingChangeRequests > 0)
                        <span class="badge badge-danger ms-2">{{ $pendingChangeRequests }}</span>
                    @endif
                </a>
            </li>
        </ul>


        <!--end::Navs-->
    </div>

    
</div>

 @php
            $pendingChangeRequests = \App\Models\ChangeRequest::where('changeable_type', \App\Models\Merchant::class)
                ->where('changeable_id', $merchant->id)
                ->where('status', 'pending')
                ->get();
        @endphp
        @if($pendingChangeRequests->count() > 0)
            <div class="alert alert-warning d-flex align-items-center p-5 mb-5">
                <i class="ki-duotone ki-shield-cross fs-2hx text-warning me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-warning">{{ __('translation.pending_change_requests') }}</h4>
                    <span>{{ __('translation.merchant_has_pending_changes', ['count' => $pendingChangeRequests->count()]) }}</span>
                    <div class="mt-2">
                        <a href="{{ route('merchants.sections', ['merchant' => $merchant->id, 'tab' => 'change-requests']) }}" class="btn btn-warning btn-sm">
                            <i class="ki-duotone ki-eye fs-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            {{ __('translation.review_changes') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
<!--end::Profile header-->

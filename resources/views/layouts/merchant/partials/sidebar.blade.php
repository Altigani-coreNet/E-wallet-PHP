@php
use App\Models\ExternalUser;
@endphp
<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="{default: '#kt_app_sidebar_logo, #kt_app_sidebar_footer', lg: '#kt_app_header, #kt_app_sidebar_logo, #kt_app_sidebar_footer'}" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                @if(auth()->user()->merchant->status === 'approved' && auth()->user()->can('view_dashboard'))
                <!--begin:Menu item - POS Parent-->
                <div data-kt-menu-trigger="click" class="py-3 menu-item menu-accordion hover show">
                    <!--begin:Menu link-->
                    <span class="menu-link {{ request()->routeIs('merchant.dashboard') || request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.batches.*') || request()->routeIs('merchant.settlements.*') || request()->routeIs('merchant.payment-links.*') || request()->routeIs('merchant.branches.*') || request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') || request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') || request()->routeIs('merchant.profile') || request()->routeIs('merchant.users.*') || request()->routeIs('merchant.user-groups.*') || request()->routeIs('merchant.roles.*') ? 'active' : '' }}" style="padding: 0.65rem 1rem;">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-shop fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title fs-4 fw-bold">POS</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion show">
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.dashboard') ? 'active' : '' }}" href="{{ route('merchant.dashboard') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                        <!--end:Menu item-->

                        @if(auth()->user()->can('view_transactions') || auth()->user()->can('view_settlements'))
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.batches.*') || request()->routeIs('merchant.settlements.*') ? 'hover show' : '' }}">
                    <!--begin:Menu link-->
                    <span class="menu-link {{ request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.batches.*') || request()->routeIs('merchant.settlements.*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-dollar fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('translation.payments') }}</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.transactions.*') || request()->routeIs('merchant.batches.*') || request()->routeIs('merchant.settlements.*') ? 'show' : '' }}">
                        @if(    Auth::guard('external')->user()->can('view_transactions'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('merchant.transactions.*') && !request()->type ? 'active' : '' }}" href="{{ route('merchant.transactions.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('translation.transactions') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('merchant.transactions.*') && request()->type == 'refunded' ? 'active' : '' }}" href="{{ route('merchant.transactions.index', ['type' => 'refunded']) }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('translation.refunded_transactions') }}</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('merchant.transactions.*') && request()->type == 'voided' ? 'active' : '' }}" href="{{ route('merchant.transactions.index', ['type' => 'voided']) }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('translation.voided_transactions') }}</span>
                            </a>
                        </div>
                        @endif
                        @if(Auth::guard('external')->user()->can('view_transactions'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('merchant.batches.*') ? 'active' : '' }}" href="{{ route('merchant.batches.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('translation.batches') }}</span>
                            </a>
                        </div>
                        @endif
                        @if(Auth::guard('external')->user()->can('view_settlements'))
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.settlements.*') ? 'hover show' : '' }}">
                            <span class="menu-link {{ request()->routeIs('merchant.settlements.*') ? 'active' : '' }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{ __('translation.settlements') }}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.settlements.*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.settlements.index') ? 'active' : '' }}" href="{{ route('merchant.settlements.index') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('translation.settlements') }}</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.settlements.transactions') ? 'active' : '' }}" href="{{ route('merchant.settlements.transactions') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('translation.settlements_transactions') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <!--end:Menu sub-->
                </div>
                        @endif

                        @if(auth()->user()->can('view_link_payments'))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.payment-links.*') ? 'active' : '' }}" href="{{ route('merchant.payment-links.index') }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-disconnect fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </span>
                                <span class="menu-title">{{ __('translation.payment_links') }}</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                @endif

                @if(
                        auth()->user()->can('branches') || auth()->user()->can('view_branches') ||
                        auth()->user()->can('view_terminals') || auth()->user()->can('view_terminal_assignments') ||
                        auth()->user()->can('view_contract_terms') || auth()->user()->can('view_services_fees')
                    )
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.branches.*') || request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') || request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') || request()->routeIs('merchant.profile') ? 'hover show' : '' }}">
                    <!--begin:Menu link-->
                    <span class="menu-link {{ request()->routeIs('merchant.branches.*') || request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') || request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') || request()->routeIs('merchant.profile') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-abstract-28 fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">{{ __('translation.merchant_management') }}</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.branches.*') || request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') || request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') || request()->routeIs('merchant.profile') ? 'show' : '' }}">

                        @if(auth()->user()->can('branches') || auth()->user()->can('view_branches'))
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.branches.*') ? 'hover show' : '' }}">
                            <span class="menu-link {{ request()->routeIs('merchant.branches.*') ? 'active' : '' }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">My Branches</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.branches.*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.branches.index') ? 'active' : '' }}" href="{{ route('merchant.branches.index') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">All Branches</span>
                                    </a>
                                </div>
                                @if(auth()->user()->can('branches') || auth()->user()->can('request_branches'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.branches.create') ? 'active' : '' }}" href="{{ route('merchant.branches.create') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Add New Branch</span>
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(auth()->user()->can('view_terminals') || auth()->user()->can('view_terminal_assignments'))
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') ? 'hover show' : '' }}">
                            <span class="menu-link {{ request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') ? 'active' : '' }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">My Terminals</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.terminals.*') || request()->routeIs('merchant.terminal-groups.*') ? 'show' : '' }}">
                                @if(Auth::guard('external')->user()->can('view_terminals'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.terminals.index') ? 'active' : '' }}" href="{{ route('merchant.terminals.index') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">All Terminals</span>
                                    </a>
                                </div>
                                @endif
                                @if(Auth::guard('external')->user()->can('create_terminals'))
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('merchant.terminals.create') ? 'active' : '' }}" href="{{ route('merchant.terminals.create') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Add Terminal</span>
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        

					@if(auth()->user()->can('view_contract_terms') || auth()->user()->can('view_services_fees'))
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') ? 'hover show' : '' }}">
						<span class="menu-link {{ request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') ? 'active' : '' }}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Contract Management</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.contracts.*') || request()->routeIs('merchant.service-fees.*') ? 'show' : '' }}">
							@if(Auth::guard('external')->user()->can('view_contract_terms'))
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('merchant.contracts.index') ? 'active' : '' }}" href="{{ route('merchant.contracts.index') }}">
									<span class="menu-bullet">
										<span class="bullet bullet-dot"></span>
									</span>
									<span class="menu-title">Contracts</span>
								</a>
							</div>
							@endif
							@if(Auth::guard('external')->user()->can('view_services_fees'))
							<div class="menu-item">
								<a class="menu-link {{ request()->routeIs('merchant.service-fees.index') ? 'active' : '' }}" href="{{ route('merchant.service-fees.index') }}">
									<span class="menu-bullet">
										<span class="bullet bullet-dot"></span>
									</span>
									<span class="menu-title">Service Fees</span>
								</a>
							</div>
							@endif
						</div>
					</div>
					@endif

					<div class="menu-item">
						<a class="menu-link {{ request()->routeIs('merchant.profile') ? 'active' : '' }}" href="{{ route('merchant.profile') }}">
							<span class="menu-bullet">
								<span class="bullet bullet-dot"></span>
							</span>
							<span class="menu-title">Profile</span>
						</a>
					</div>

                    </div>
                    <!--end:Menu sub-->
                </div>
                @endif

                        @if(auth()->user()->can('users') || auth()->user()->can('view_users') || auth()->user()->can('users_groups') || auth()->user()->can('view_users_groups') || auth()->user()->can('view_roles'))
                <!--begin:Menu item-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->routeIs('merchant.users.*') || request()->routeIs('merchant.user-groups.*') || request()->routeIs('merchant.roles.*') ? 'hover show' : '' }}">
                    <!--begin:Menu link-->
                            <span class="menu-link {{ request()->routeIs('merchant.users.*') || request()->routeIs('merchant.user-groups.*') || request()->routeIs('merchant.roles.*') ? 'active' : '' }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-profile-user fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">User Management</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion {{ request()->routeIs('merchant.users.*') || request()->routeIs('merchant.user-groups.*') || request()->routeIs('merchant.roles.*') ? 'show' : '' }}">
                        @if(auth()->user()->can('users') || auth()->user()->can('view_users'))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.users.index') ? 'active' : '' }}" href="{{ route('merchant.users.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">All Users</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                        @endif
                        @if(auth()->user()->can('users') || auth()->user()->can('create_users'))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.users.create') ? 'active' : '' }}" href="{{ route('merchant.users.create') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Add User</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                        @endif
                        @if(auth()->user()->can('users_groups') || auth()->user()->can('view_users_groups'))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.user-groups.*') ? 'active' : '' }}" href="{{ route('merchant.user-groups.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">User Groups</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                        @endif
                        @if(auth()->user()->can('view_roles'))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link {{ request()->routeIs('merchant.roles.*') ? 'active' : '' }}" href="{{ route('merchant.roles.index') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Role Management</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                        @endif
                    </div>
                    <!--end:Menu sub-->
                </div>
                <!--end:Menu item-->
                @endif
                    </div>
                    <!--end:Menu sub-->
                </div>
                <!--end:Menu item - POS Parent-->
                @endif

                @if(auth()->user()->merchant->status === 'approved' && auth()->user()->can('view_customers'))
                <!--begin:Menu item - Sales Parent-->
                <div data-kt-menu-trigger="click" class="py-3 menu-item menu-accordion hover show">
                    <!--begin:Menu link-->
                    <span class="menu-link {{ request()->is('merchant/sales*') ? 'active' : '' }}" style="padding: 0.65rem 1rem;">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-chart-line-up fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title fs-4 fw-bold">Sales</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion show">
                        
                        <!--begin:Menu item - Dashboard-->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('merchant/sales/dashboard') ? 'active' : '' }}" 
                               href="{{ url('merchant/sales/dashboard') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Dashboard</span>
                            </a>
                        </div>
                        <!--end:Menu item-->

                        <!--begin:Menu item - Sale-->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('merchant/sales/sale') ? 'active' : '' }}" 
                               href="{{ url('merchant/sales/sale') }}">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Sale</span>
                            </a>
                        </div>
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->is('merchant/sales/reports*') ? 'hover show' : '' }}">
                            <!--begin:Menu link-->
                            <span class="menu-link {{ request()->is('merchant/sales/reports*') ? 'active' : '' }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-chart-simple fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                               </i>
                                </span>
                                <span class="menu-title">Reports</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion {{ request()->is('merchant/sales/reports*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/reports/sales') ? 'active' : '' }}" href="{{ url('merchant/sales/reports/sales') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Sales Report</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/reports/purchases') ? 'active' : '' }}" href="{{ url('merchant/sales/reports/purchases') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Purchase Report</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/reports/products') ? 'active' : '' }}" href="{{ url('merchant/sales/reports/products') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Product Report</span>
                                    </a>
                                </div>
                                {{-- <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/reports/expenses') ? 'active' : '' }}" href="{{ url('merchant/sales/reports/expenses') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Expense Report</span>
                                    </a>
                                </div> --}}
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end:Menu item-->

                        <!--begin:Menu item - Purchase-->
                        {{-- <div class="menu-item">
                            <a class="menu-link {{ request()->is('merchant/sales/purchase') ? 'active' : '' }}" 
                               href="{{ url('merchant/sales/purchase') }}"
                               data-spa-link="true"
                               data-spa-route="/merchant/sales/purchase">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Purchase</span>
                            </a>
                        </div>
                        <!--end:Menu item-->

                        <!--begin:Menu item - Reports-->
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('merchant/sales/reports') ? 'active' : '' }}" 
                               href="{{ url('merchant/sales/reports') }}"
                               data-spa-link="true"
                               data-spa-route="/merchant/sales/reports">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">Reports</span>
                            </a>
                        </div> --}}
                        <!--end:Menu item-->

                        <!--begin:Menu item - Customer Management-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->is('merchant/sales/customers*') ? 'hover show' : '' }}">
                            <!--begin:Menu link-->
                            <span class="menu-link {{ request()->is('merchant/sales/customers*') ? 'active' : '' }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-people fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Customer Management</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion {{ request()->is('merchant/sales/customers*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/customers') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/customers') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('translation.customers') }}</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/customers/create') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/customers/create') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/customers/create">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{ __('translation.add_customer') }}</span>
                                    </a>
                                </div>
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end:Menu item-->

                        <!--begin:Menu item - User Management-->
                       
                        <!--end:Menu item-->

                        <!--begin:Menu item - Product Management-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->is('merchant/sales/tags*') || request()->is('merchant/sales/categories*') || request()->is('merchant/sales/warehouse*') || request()->is('merchant/sales/products*') || request()->is('merchant/sales/taxes*') ? 'hover show' : '' }}">
                            <!--begin:Menu link-->
                            <span class="menu-link {{ request()->is('merchant/sales/tags*') || request()->is('merchant/sales/categories*') || request()->is('merchant/sales/warehouse*') || request()->is('merchant/sales/products*') || request()->is('merchant/sales/taxes*') ? 'active' : '' }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-package fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Product Management</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion {{ request()->is('merchant/sales/tags*') || request()->is('merchant/sales/categories*') || request()->is('merchant/sales/warehouse*') || request()->is('merchant/sales/products*') || request()->is('merchant/sales/taxes*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/tags*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/tags') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/tags">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Tags</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/taxes*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/taxes') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/taxes">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Taxes</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/categories*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/categories') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/categories">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Categories</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/warehouse*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/warehouse') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/warehouse">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Warehouse</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/products*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/products') }}"
                                       data-spa-link="true"
                                       data-spa-route="/merchant/sales/products">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Products</span>
                                    </a>
                                </div>
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end:Menu item-->

                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion mb-1 {{ request()->is('merchant/sales/users*') || request()->is('merchant/sales/roles*') ? 'hover show' : '' }}">
                            <!--begin:Menu link-->
                            <span class="menu-link {{ request()->is('merchant/sales/users*') || request()->is('merchant/sales/roles*') ? 'active' : '' }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-profile-user fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </span>
                                <span class="menu-title">User Management</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion {{ request()->is('merchant/sales/users*') || request()->is('merchant/sales/roles*') ? 'show' : '' }}">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/users') && !request()->is('merchant/sales/users/create') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/users') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">All Users</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/users/create') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/users/create') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Add User</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('merchant/sales/roles*') ? 'active' : '' }}" 
                                       href="{{ url('merchant/sales/roles') }}">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">Roles & Permissions</span>
                                    </a>
                                </div>
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--begin:Menu item - Reports-->
                        
                        <!--end:Menu item-->

                    </div>
                    <!--end:Menu sub-->
                </div>
                <!--end:Menu item - Sales Parent-->
                @endif

                
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::Sidebar menu-->
</div>
<!--end::Sidebar--> 
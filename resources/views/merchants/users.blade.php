@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.merchant_users'))
@section('page_title', __('translation.merchant_users'))
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.dashboard') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchants.index') }}" class="text-muted text-hover-primary">{{ __('translation.merchants') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchants.show', $merchant->id) }}" class="text-muted text-hover-primary">{{ $merchant->name }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.users') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
    <!--begin::Row-->
    <div class="row gy-5 g-xl-8">
        <!--begin::Col-->
            {{-- <div class="card"> --}}
            <!--begin::Body-->
            {{-- <div class="card-body py-3"> --}}
                @if(isset($merchant))
                    <x:merchant-profile-header :merchant="$merchant" active-tab="users" />
                    
                    <!--begin::Filters Section (Hidden by default)-->
                    <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-section" style="display: none;">
                        <!--begin::Body-->
                        <div class="card-body">
                            <div class="row">
                                <x:text-input class="col-md-3" name='search'
                                              filedname="search" value="{{old('')}}"/>
        
                                <x:select-options class="col-md-3" name="status"
                                                  filed-name='status'
                                                  :options="['active', 'inactive']"
                                                  value="{{old('status')}}"/>
                                                  
                                <x:text-input class="col-md-3" name='from_date'
                                              filedname="from_date" type="date"
                                              value="{{old('from_date')}}"/>
                                              
                                <x:text-input class="col-md-3" name='to_date'
                                              filedname="to_date" type="date"
                                              value="{{old('to_date')}}"/>
                            </div>
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Filters Section-->

                    <!--begin::Users Section-->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_merchant_users">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                                                    <div class="card-title m-0">
                                    <h3 class="fw-bolder m-0">{{ __('translation.merchant_users') }}</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <!--begin::Filter Toggle Button-->
                                    <button type="button" class="btn btn-light-primary me-3" id="toggle-filters-btn">
                                        <i class="ki-duotone ki-filter fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('translation.filters') }}
                                    </button>
                                    <!--end::Filter Toggle Button-->
                                    
                                    <a href="{{ route('users.create') }}?merchant_id={{ $merchant->id }}" class="btn btn-primary align-self-center">
                                        <i class="ki-duotone ki-plus fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('translation.add_user') }}
                                    </a>
                                </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Filters-->
                                   
                                    <!--end::Filters-->

                                    <!--begin::Users Table-->
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="merchant-users-table">
                                            <!--begin::Table head-->
                                            <thead>
                                            <!--begin::Table row-->
                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                               data-kt-check-target="#merchant-users-table .form-check-input" value="1"/>
                                                    </div>
                                                </th>
                                                <th class="text-dark">{{ __('translation.id') }}</th>
                                                <th class="min-w-125px text-dark">{{ __('translation.name') }}</th>
                                                <th class="min-w-125px text-dark">{{ __('translation.image') }}</th>
                                                <th class="text-dark">{{ __('translation.email') }}</th>
                                                <th class="text-dark">{{ __('translation.phone') }}</th>
                                                <th class="text-dark">{{ __('translation.branch') }}</th>
                                                <th class="text-dark">{{ __('translation.roles') }}</th>
                                                <th class="text-dark">{{ __('translation.status') }}</th>
                                                <th class="text-end text-dark">{{ __('translation.action') }}</th>
                                            </tr>
                                            <!--end::Table row-->
                                            </thead>
                                            <!--end::Table head-->
                                            <!--begin::Table body-->
                                            <!--end::Table body-->
                                        </table>
                                    </div>
                                    <!--end::Users Table-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                    </div>
                    <!--end::Users Section-->

                @else
                    <!--begin::Empty State-->
                    <div class="text-center py-10">
                        <div class="mb-7">
                            <i class="ki-duotone ki-shop fs-5x text-gray-500">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </div>
                        <h3 class="fw-bold text-gray-800 mb-3">{{ __('translation.no_merchant_found') }}</h3>
                        <p class="text-gray-500 fs-6">{{ __('translation.merchant_not_found_description') }}</p>
                        <a href="{{ route('merchants.index') }}" class="btn btn-primary">
                            {{ __('translation.back_to_merchants') }}
                        </a>
                    </div>
                    <!--end::Empty State-->
                @endif
            </div>
            
            </div>
        <!--end::Card-->
        </div>
        <!--end::Col-->
    </div>

</div>
</div>
    <!--end::Row-->
@endsection

@push('scripts')
    <script>
        let search, status, from_date, to_date;
        let merchantUsersTable = $('#merchant-users-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("user.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.from_date = from_date;
                    q.to_date = to_date;
                    q.merchant_id = {{ $merchant->id ?? 'null' }};
                }
            }
            , columns: [{
                data: 'record_select'
                , name: 'record_select'
                , searchable: false
                , sortable: false
                , width: '1%'
            },
                {
                    data: 'id'
                    , name: 'id'
                },
                {
                    data: 'name'
                    , name: 'name'
                },
                {
                    data: 'profile_image'
                    , name: 'profile_image'
                },
                {
                    data: 'email'
                    , name: 'email'
                },
                {
                    data: 'phone'
                    , name: 'phone'
                },
                {
                    data: 'branch_id'
                    , name: 'branch_id'
                },
                {
                    data: 'roles'
                    , name: 'roles'
                },
                {
                    data: 'status'
                    , name: 'status'
                },
                {
                    data: 'actions'
                    , name: 'actions'
                    , searchable: false
                    , sortable: false
                    , width: '20%'
                }
            ]
            , order: [
                [1, 'desc']
            ]
            , drawCallback: function (settings) {
                $('.record__select').prop('checked', false);
                $('#record__select-all').prop('checked', false);
                $('#record-ids').val('');
                $('#bulk-delete').attr('disabled', true);

                // ✅ Re-initialize KTMenu dropdowns here
                if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                    KTMenu.createInstances();
                }
            }
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#merchant-users-table').css('width', '100%');
            $('.table-responsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{__('translation.name,_email,_phone')}}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            console.log(search);
            merchantUsersTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            merchantUsersTable.ajax.reload();
        });

        // Date filter handlers
        $("[name='from_date']").on('change', function () {
            from_date = $(this).val();
            merchantUsersTable.ajax.reload();
        });

        $("[name='to_date']").on('change', function () {
            to_date = $(this).val();
            merchantUsersTable.ajax.reload();
        });

        // Filter toggle functionality
        $('#toggle-filters-btn').on('click', function () {
            const filtersSection = $('#filters-section');
            const isVisible = filtersSection.is(':visible');
            
            if (isVisible) {
                filtersSection.slideUp(300);
                $(this).removeClass('btn-primary').addClass('btn-light-primary');
                $(this).html('<i class="ki-duotone ki-filter fs-2"><span class="path1"></span><span class="path2"></span></i> {{ __("translation.filters") }}');
            } else {
                filtersSection.slideDown(300);
                $(this).removeClass('btn-light-primary').addClass('btn-primary');
                $(this).html('<i class="ki-duotone ki-filter fs-2"><span class="path1"></span><span class="path2"></span></i> {{ __("translation.hide_filters") }}');
            }
        });
    </script>
@endpush

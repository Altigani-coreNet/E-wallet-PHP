@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.merchant_branches'))
@section('page_title', __('translation.merchant_branches'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.branches') }}</li>
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
            <!--begin::Body-->
                @if(isset($merchant))
                    <x:merchant-profile-header :merchant="$merchant" active-tab="branches" />
                    
                    <!-- Pending Branches Alert -->
                    <div class="alert alert-warning d-flex align-items-center p-5 mb-5" id="pending-branches-alert" style="display: none !important;">
                        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h5 class="mb-1">{{ __('translation.pending_branch_requests') }}</h5>
                            <span>{{ __('translation.you_have') }} <strong id="pending-count">0</strong> {{ __('translation.branch_requests_waiting') }}</span>
                        </div>
                    </div>

                    <!--begin::Filters Section (Hidden by default)-->
                    <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-section" style="display: none;">
                        <!--begin::Body-->
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label" for="search">{{ __('translation.search') }}</label>
                                    <input type="text" class="form-control" name="search" id="search">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="status">{{ __('translation.status') }}</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="">{{ __('translation.all') }}</option>
                                        <option value="active">{{ __('translation.active') }}</option>
                                        <option value="inactive">{{ __('translation.inactive') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="from_date">{{ __('translation.from_date') }}</label>
                                    <input type="date" class="form-control" name="from_date" id="from_date">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="to_date">{{ __('translation.to_date') }}</label>
                                    <input type="date" class="form-control" name="to_date" id="to_date">
                                </div>
                            </div>
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Filters Section-->

                    <!--begin::Branches Section-->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_merchant_branches">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.merchant_branches') }}</h3>
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
                                        
                                        <a href="{{ route('branches.create') }}?merchant_id={{ $merchant->id }}" class="btn btn-primary align-self-center">
                                            <i class="ki-duotone ki-plus fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ __('translation.add_branch') }}
                                        </a>
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Branches Table-->
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="merchant-branches-table">
                                            <!--begin::Table head-->
                                            <thead>
                                            <!--begin::Table row-->
                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                               data-kt-check-target="#merchant-branches-table .form-check-input" value="1"/>
                                                    </div>
                                                </th>
                                                <th class="text-dark">{{ __('translation.id') }}</th>
                                                <th class="min-w-125px text-dark">{{ __('translation.name') }}</th>
                                                {{-- <th class="text-dark">{{ __('translation.code') }}</th> --}}
                                                {{-- <th class="text-dark">{{ __('translation.phone') }}</th> --}}
                                                <th class="text-dark">{{ __('translation.address') }}</th>
                                                {{-- <th class="text-dark">{{ __('translation.terminals_count') }}</th> --}}
                                                <th class="text-dark">{{ __('translation.status') }}</th>
                                                <th class="text-end text-dark">{{ __('translation.action') }}</th>
                                            </tr>
                                            <!--end::Table row-->
                                            </thead>
                                            <!--end::Table head-->
                                        </table>
                                    </div>
                                    <!--end::Branches Table-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                    </div>
                    <!--end::Branches Section-->

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
    </div>
</div>
@endsection

@push('scripts')
    <script>
        let search, status, from_date, to_date;
        let merchantBranchesTable = $('#merchant-branches-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("branches.data")}}',
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
                // {
                //         data: 'code'
                //     , name: 'code'
                // },
                // {
                //     data: 'phone'
                //     , name: 'phone'
                // },
                {
                    data: 'address'
                    , name: 'address'
                },
                // {
                //     data: 'terminals_count'
                //     , name: 'terminals_count'
                // },
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

                // Re-initialize KTMenu dropdowns
                if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                    KTMenu.createInstances();
                }
            }
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#merchant-branches-table').css('width', '100%');
            $('.table-responsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{__('translation.name,_code,_phone')}}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            merchantBranchesTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            merchantBranchesTable.ajax.reload();
        });

        // Date filter handlers
        $("[name='from_date']").on('change', function () {
            from_date = $(this).val();
            merchantBranchesTable.ajax.reload();
        });

        $("[name='to_date']").on('change', function () {
            to_date = $(this).val();
            merchantBranchesTable.ajax.reload();
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

         // Check for pending branches and show alert
         function checkPendingBranches() {
             $.ajax({
                 url: '{{ route("branches.data") }}',
                 method: 'GET',
                 data: { 
                     status: 'pending',
                     merchant_id: {{ $merchant->id ?? 'null' }}
                 },
                 success: function(response) {
                     if (response.data && response.data.length > 0) {
                         $('#pending-count').text(response.data.length);
                         $('#pending-branches-alert').show();
                     } else {
                         $('#pending-branches-alert').hide();
                     }
                 },
                 error: function() {
                     // Hide alert on error
                     $('#pending-branches-alert').hide();
                 }
             });
         }

         // Check for pending branches on page load
         checkPendingBranches();

         // Check for pending branches after table reload
         merchantBranchesTable.on('draw.dt', function() {
             checkPendingBranches();
         });
     </script>
 @endpush

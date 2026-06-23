@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.merchant_attachments'))
@section('page_title', __('translation.merchant_attachments'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.attachments') }}</li>
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
                    <x:merchant-profile-header :merchant="$merchant" active-tab="attachments" />
                    
                    <!--begin::Filters Section (Hidden by default)-->
                    <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-section" style="display: none;">
                        <!--begin::Body-->
                        <div class="card-body">
                            <div class="row">
                                <x:text-input class="col-md-4" name='search'
                                              filedname="search" value="{{old('')}}"/>
        
                                {{-- <x:select-options class="col-md-3" name="url_type"
                                                  filed-name='url_type'
                                                  :options="['logo', 'document', 'image', 'contract', 'other']"
                                                  value="{{old('url_type')}}"/>
                                                   --}}
                                <div class="col-md-4">
    <label class="form-label" for="from_date">From Date</label>
    <input type="date" class="form-control" name="from_date" id="from_date" value="{{ old('from_date') }}">
</div>

<div class="col-md-4">
    <label class="form-label" for="to_date">To Date</label>
    <input type="date" class="form-control" name="to_date" id="to_date" value="{{ old('to_date') }}">
</div>
                            </div>
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Filters Section-->

                    <!--begin::Attachments Section-->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-5 mb-xl-10" id="kt_merchant_attachments">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.merchant_attachments') }}</h3>
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
                                        
                                        {{-- <a href="{{ route('attachments.create') }}?merchant_id={{ $merchant->id }}" class="btn btn-primary align-self-center">
                                            <i class="ki-duotone ki-plus fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ __('translation.add_attachment') }}
                                        </a> --}}
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Filters-->
                                   
                                    <!--end::Filters-->

                                    <!--begin::Attachments Table-->
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="merchant-attachments-table">
                                            <!--begin::Table head-->
                                            <thead>
                                            <!--begin::Table row-->
                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                               data-kt-check-target="#merchant-attachments-table .form-check-input" value="1"/>
                                                    </div>
                                                </th>
                                                <th class="text-dark">{{ __('translation.attachment') }}</th>
                                                <th class="min-w-125px text-dark">{{ __('translation.file_name') }}</th>
                                                {{-- <th class="min-w-125px text-dark">{{ __('translation.url_type') }}</th> --}}
                                                <th class="text-dark">{{ __('translation.type') }}</th>
                                                <th class="text-dark">{{ __('translation.created_at') }}</th>
                                                <th class="text-end text-dark">{{ __('translation.action') }}</th>
                                            </tr>
                                            <!--end::Table row-->
                                            </thead>
                                            <!--end::Table head-->
                                            <!--begin::Table body-->
                                            <!--end::Table body-->
                                        </table>
                                    </div>
                                    <!--end::Attachments Table-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        </div>
                    </div>
                    <!--end::Attachments Section-->

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
        let search, url_type, from_date, to_date;
        let merchantAttachmentsTable = $('#merchant-attachments-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("admin.attachments.data")}}',
                data: (q) => {
                    q.search = search;
                    // q.url_type = url_type;
                    q.from_date = from_date;
                    q.to_date = to_date;
                    q.attachable_id = {{ $merchant->id ?? 'null' }};
                    q.attachable_type = 'App\\Models\\Merchant';
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
                    data: 'attachment'
                    , name: 'attachment'
                },
                {
                    data: 'url_type'
                    , name: 'url_type'
                },
                
                {
                    data: 'type'
                    , name: 'type'
                },
                {
                    data: 'created_at'
                    , name: 'created_at'
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
                [5, 'desc']
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
            $('#merchant-attachments-table').css('width', '100%');
            $('.table-responsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{__('translation.file_name,_url_type')}}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            console.log(search);
            merchantAttachmentsTable.ajax.reload();
        });

        $("[name='url_type']").on('change', function () {
            url_type = $(this).val();
            merchantAttachmentsTable.ajax.reload();
        });

        // Date filter handlers
        $("[name='from_date']").on('change', function () {
            from_date = $(this).val();
            merchantAttachmentsTable.ajax.reload();
        });

        $("[name='to_date']").on('change', function () {
            to_date = $(this).val();
            merchantAttachmentsTable.ajax.reload();
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

@extends("layouts.admin.admin_layout")
@section('main-head', __('translation.change_requests'))
@section('page_title', __('translation.change_requests'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.change_requests') }}</li>
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
                    <x:merchant-profile-header :merchant="$merchant" active-tab="change-requests" />
                    
                    <!--begin::Filters Section (Hidden by default)-->
                    <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-section" style="display: none;">
                        <!--begin::Body-->
                        <div class="card-body">
                            <div class="row">
                                <x:text-input class="col-md-4" name='search'
                                              filedname="search" value="{{old('')}}"/>
        
                                <x:select-options class="col-md-3" name="status"
                                                  filed-name='status'
                                                  :options="['pending', 'approved', 'rejected']"
                                                  value="{{old('status')}}"/>

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

                    {{-- <!--begin::Change Requests Section-->
                    <div class="row">
                        <div class="col-lg-12"> --}}
                            <div class="card mb-5 mb-xl-10" id="kt_merchant_change_requests">
                                <!--begin::Card header-->
                                <div class="card-header cursor-pointer">
                                    <!--begin::Card title-->
                                    <div class="card-title m-0">
                                        <h3 class="fw-bolder m-0">{{ __('translation.change_requests') }}</h3>
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
                                    </div>
                                </div>
                                <!--end::Card header-->
                                <!--begin::Card body-->
                                <div class="card-body p-9">
                                    <!--begin::Filters-->
                                   
                                    <!--end::Filters-->

                                    <!--begin::Change Requests Table-->
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="merchant-change-requests-table">
                                            <!--begin::Table head-->
                                            <thead>
                                            <!--begin::Table row-->
                                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                                <th class="w-10px pe-2">
                                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                               data-kt-check-target="#merchant-change-requests-table .form-check-input" value="1"/>
                                                    </div>
                                                </th>
                                                <th class="text-dark">{{ __('translation.request_type') }}</th>
                                                <th class="min-w-125px text-dark">{{ __('translation.reason') }}</th>
                                                <th class="text-dark">{{ __('translation.status') }}</th>
                                                <th class="text-dark">{{ __('translation.requester') }}</th>
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
                                    <!--end::Change Requests Table-->
                                </div>
                                <!--end::Card body-->
                            </div>
                        {{-- </div>
                    </div> --}}
                    <!--end::Change Requests Section-->

                    <!--begin::Change Request Details Modal-->
                    <div class="modal fade" id="changeRequestDetailsModal" tabindex="-1" aria-labelledby="changeRequestDetailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeRequestDetailsModalLabel">{{ __('translation.change_request_details') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="changeRequestDetailsContent">
                                        <!-- Content will be loaded here -->
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">{{ __('translation.loading') }}...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-light-success me-2" id="modal-approve-btn" style="display: none;">
                                        <i class="ki-duotone ki-check fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('translation.approve') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light-danger me-2" id="modal-reject-btn" style="display: none;">
                                        <i class="ki-duotone ki-cross fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('translation.reject') }}
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('translation.close') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Change Request Details Modal-->

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

@push('styles')
    <style>
        .bg-warning-light {
            background-color: #fff3cd !important;
        }
        .bg-success-light {
            background-color: #d1edff !important;
        }
        .border-warning {
            border-color: #ffc107 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let search, status, from_date, to_date;
        let merchantChangeRequestsTable = $('#merchant-change-requests-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("admin.change-requests.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.from_date = from_date;
                    q.to_date = to_date;
                    q.changeable_id = {{ $merchant->id ?? 'null' }};
                    q.changeable_type = 'App\\Models\\Merchant';
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
                    data: 'request_type'
                    , name: 'request_type'
                },
                {
                    data: 'reason'
                    , name: 'reason'
                },
                {
                    data: 'status'
                    , name: 'status'
                },
                {
                    data: 'requester'
                    , name: 'requester'
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
            $('#merchant-change-requests-table').css('width', '100%');
            $('.table-responsive').css('width', '100%');
        });

        $("[name='search']").attr("placeholder", "{{__('translation.search_by_reason_or_type')}}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            console.log(search);
            merchantChangeRequestsTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            merchantChangeRequestsTable.ajax.reload();
        });

        // Date filter handlers
        $("[name='from_date']").on('change', function () {
            from_date = $(this).val();
            merchantChangeRequestsTable.ajax.reload();
        });

        $("[name='to_date']").on('change', function () {
            to_date = $(this).val();
            merchantChangeRequestsTable.ajax.reload();
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

        // Approve/Reject functionality
        $(document).on('click', '.approve-change-request', function(e) {
            e.preventDefault();
            const changeRequestId = $(this).data('id');
            const url = $(this).attr('href');
            
            Swal.fire({
                title: '{{ __("translation.approve_change_request") }}',
                text: '{{ __("translation.are_you_sure_approve") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __("translation.approve") }}',
                cancelButtonText: '{{ __("translation.cancel") }}',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '{{ __("translation.success") }}',
                                text: '{{ __("translation.change_request_approved") }}',
                                icon: 'success'
                            }).then(() => {
                                merchantChangeRequestsTable.ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: '{{ __("translation.error") }}',
                                text: xhr.responseJSON?.message || '{{ __("translation.something_went_wrong") }}',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.reject-change-request', function(e) {
            e.preventDefault();
            const changeRequestId = $(this).data('id');
            const url = $(this).attr('href');
            
            Swal.fire({
                title: '{{ __("translation.reject_change_request") }}',
                text: '{{ __("translation.are_you_sure_reject") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("translation.reject") }}',
                cancelButtonText: '{{ __("translation.cancel") }}',
                confirmButtonColor: '#dc3545',
                input: 'textarea',
                inputLabel: '{{ __("translation.rejection_reason") }}',
                inputPlaceholder: '{{ __("translation.enter_rejection_reason") }}',
                inputValidator: (value) => {
                    if (!value) {
                        return '{{ __("translation.rejection_reason_required") }}';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            moderation_note: result.value
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '{{ __("translation.success") }}',
                                text: '{{ __("translation.change_request_rejected") }}',
                                icon: 'success'
                            }).then(() => {
                                merchantChangeRequestsTable.ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: '{{ __("translation.error") }}',
                                text: xhr.responseJSON?.message || '{{ __("translation.something_went_wrong") }}',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Show Change Request Details functionality
        $(document).on('click', '.show-change-request-details', function(e) {
            e.preventDefault();
            const changeRequestId = $(this).data('id');
            
            // Store the change request ID for later use
            $('#modal-approve-btn').data('id', changeRequestId);
            $('#modal-reject-btn').data('id', changeRequestId);
            
            // Show loading state
            $('#changeRequestDetailsContent').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">{{ __('translation.loading') }}...</span>
                    </div>
                </div>
            `);
            
            // Hide action buttons initially
            $('#modal-approve-btn').hide();
            $('#modal-reject-btn').hide();
            
            // Make API call to get rendered HTML
            $.ajax({
                url: '{{ route("admin.change-requests.details", ":id") }}'.replace(':id', changeRequestId),
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Simply display the rendered HTML
                        $('#changeRequestDetailsContent').html(response.html);
                        
                        // Show action buttons only for pending requests
                        if (response.data && response.data.status === 'pending') {
                            $('#modal-approve-btn').show();
                            $('#modal-reject-btn').show();
                        }
                    } else {
                        $('#changeRequestDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="ki-duotone ki-cross-circle fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                ${response.message || '{{ __("translation.error_loading_details") }}'}
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    $('#changeRequestDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="ki-duotone ki-cross-circle fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __("translation.error_loading_details") }}
                        </div>
                    `);
                }
            });
        });

        // Modal Approve functionality
        $(document).on('click', '#modal-approve-btn', function(e) {
            e.preventDefault();
            const changeRequestId = $(this).data('id');
            const url = '{{ route("admin.change-requests.approve", ":id") }}'.replace(':id', changeRequestId);
            
            Swal.fire({
                title: '{{ __("translation.approve_change_request") }}',
                text: '{{ __("translation.are_you_sure_approve") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __("translation.approve") }}',
                cancelButtonText: '{{ __("translation.cancel") }}',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '{{ __("translation.success") }}',
                                text: '{{ __("translation.change_request_approved") }}',
                                icon: 'success'
                            }).then(() => {
                                // Close modal and reload table
                                $('#changeRequestDetailsModal').modal('hide');
                                merchantChangeRequestsTable.ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: '{{ __("translation.error") }}',
                                text: xhr.responseJSON?.message || '{{ __("translation.something_went_wrong") }}',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Modal Reject functionality
        $(document).on('click', '#modal-reject-btn', function(e) {
            e.preventDefault();
            const changeRequestId = $(this).data('id');
            const url = '{{ route("admin.change-requests.reject", ":id") }}'.replace(':id', changeRequestId);
            
            Swal.fire({
                title: '{{ __("translation.reject_change_request") }}',
                text: '{{ __("translation.are_you_sure_reject") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("translation.reject") }}',
                cancelButtonText: '{{ __("translation.cancel") }}',
                confirmButtonColor: '#dc3545',
                input: 'textarea',
                inputLabel: '{{ __("translation.rejection_reason") }}',
                inputPlaceholder: '{{ __("translation.enter_rejection_reason") }}',
                inputValidator: (value) => {
                    if (!value) {
                        return '{{ __("translation.rejection_reason_required") }}';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            moderation_note: result.value
                        },
                        success: function(response) {
                            Swal.fire({
                                title: '{{ __("translation.success") }}',
                                text: '{{ __("translation.change_request_rejected") }}',
                                icon: 'success'
                            }).then(() => {
                                // Close modal and reload table
                                $('#changeRequestDetailsModal').modal('hide');
                                merchantChangeRequestsTable.ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: '{{ __("translation.error") }}',
                                text: xhr.responseJSON?.message || '{{ __("translation.something_went_wrong") }}',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

    </script>
@endpush

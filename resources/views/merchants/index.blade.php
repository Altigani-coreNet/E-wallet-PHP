@extends("layouts.admin.admin_layout")
@section('main-head' , __('translation.merchants_management'))
@section('breadcrumb')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
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
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}</button>
        <!--end::Menu toggle-->
        <!--begin::Menu 1-->
       
        <!--end::Menu 1-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Secondary button-->
    <!--end::Secondary button-->    
    <!--begin::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#importMerchantsModal">
        <i class="ki-duotone ki-file-up fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.import_merchants') }}
    </button>
    <!--end::Import button-->
    <button type="button" class="btn btn-sm fw-bold btn-success" id="export-filtered">
        <i class="ki-duotone ki-download fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export') }}
    </button>
    <!--begin::Primary button-->
    <a href='{{ route('merchants.create')}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>

        {{ __('translation.add_merchant') }}</a>
    <!--end::Primary button-->
</div>
@endsection

@section('content')
    <style>
        #filters-body {
            transition: all 0.3s ease;
            display: none; /* Hidden by default */
        }
        
        #filter-icon {
            transition: transform 0.3s ease;
            transform: rotate(90deg); /* Rotated by default */
        }
        
        #filters_button:hover {
            transform: translateY(-1px);
        }
        
        #filters_button {
            transition: all 0.3s ease;
        }
    </style>
    
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4">
            </div>
            <!-- Hidden input for selected IDs -->
            <input type="hidden" id="record-ids" value="">
            
            <!--begin::Filters Card-->
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8"  id="filters-body">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('translation.filters') }}</h3>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" id="clear-filters">
                            <i class="ki-duotone ki-refresh fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.clear_filters') }}
                        </button>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body" >
                    <div class="row g-4">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                            <input type="text" class="form-control" id="search-input" 
                                   placeholder="{{ __('translation.search_by_name_email_phone_business_type') }}">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">{{ __('translation.status') }}</label>
                            <select class="form-select" id="status-filter">
                                <option value="">{{ __('translation.all_statuses') }}</option>
                                <option value="pending">{{ __('translation.pending') }}</option>
                                <option value="viewed">{{ __('translation.viewed') }}</option>
                                <option value="approved">{{ __('translation.approved') }}</option>
                                <option value="rejected">{{ __('translation.rejected') }}</option>
                                <option value="suspended">{{ __('translation.suspended') }}</option>
                            </select>
                        </div>
                        
                        <!-- Country Filter -->
                        <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                        url="{{route('countries.select')}}" />
                        
                        <!-- Date Range -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('translation.created_date_from') }}</label>
                            <input type="date" class="form-control" id="date-from">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('translation.created_date_to') }}</label>
                            <input type="date" class="form-control" id="date-to">
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    {{-- <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="apply-filters">
                                    <i class="ki-duotone ki-filter fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('translation.apply_filters') }}
                                </button>
                                
                                <button type="button" class="btn btn-success" id="export-filtered">
                                    <i class="ki-duotone ki-download fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('translation.export_filtered') }}
                                </button>
                            </div>
                        </div>
                    </div> --}}
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Filters Card-->

            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        {{-- <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <a href='{{ route('merchants.create')}}'
                               class=" btn btn-primary mx-2">
                                <i class="ki-duotone ki-plus fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.add_merchant') }}
                            </a>
                        </div> --}}
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-customer-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>{{ __('translation.selected') }}
                            </div>
                            <button type="button" class="btn btn-danger" id="bulk-delete">
                                {{ __('translation.delete_selected') }}
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="table-reponsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="merchants-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#merchants-table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="text-dark">{{ __('translation.id') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.logo') }}</th>
                                <th class="min-w-200px text-dark">{{ __('translation.merchant_info') }}</th>
                                <th class="text-dark">{{ __('translation.email') }}</th>
                                <th class="text-dark">{{ __('translation.phone') }}</th>
                                <th class="text-dark">{{ __('translation.business_type') }}</th>
                                <th class="text-dark">{{ __('translation.status') }}</th>
                                <th class="text-dark">{{ __('translation.is_active') }}</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th class="text-dark">{{ __('translation.country') }}</th>
                                @endif
                                <th class="text-end text-dark">{{ __('translation.actions') }}</th>
                            </tr>
                            <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Container-->
    </div>

    <!--begin::Import Merchants Modal-->
    <div class="modal fade" id="importMerchantsModal" tabindex="-1" aria-labelledby="importMerchantsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importMerchantsModalLabel">
                        <i class="ki-duotone ki-file-up fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.import_merchants') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importMerchantsForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">{{ __('translation.select_file') }}</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">{{ __('translation.supported_formats') }}: .xlsx, .xls, .csv</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex">
                                <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="d-flex flex-column">
                                    <h5 class="mb-1">{{ __('translation.import_instructions') }}</h5>
                                    <span>{{ __('translation.import_instructions_text') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <a href="{{ route('merchants.export-template') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ki-duotone ki-download fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('translation.download_template') }}
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('translation.cancel') }}
                        </button>
                        <button type="button" class="btn btn-info" id="previewImportBtn">
                            <i class="ki-duotone ki-eye fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            {{ __('translation.preview') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Import Merchants Modal-->

    <!--begin::Preview Import Modal-->
    <div class="modal fade" id="previewImportModal" tabindex="-1" aria-labelledby="previewImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewImportModalLabel">
                        <i class="ki-duotone ki-eye fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        {{ __('translation.import_preview') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Preview Summary -->
                    <div id="previewSummary" class="mb-5">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card bg-light-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-document fs-2x text-primary me-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <div>
                                                <div class="fs-7 text-muted">{{ __('translation.total_rows') }}</div>
                                                <div class="fs-3 fw-bold text-primary" id="totalRows">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-success">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-check-circle fs-2x text-success me-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <div>
                                                <div class="fs-7 text-muted">{{ __('translation.valid_rows') }}</div>
                                                <div class="fs-3 fw-bold text-success" id="validRows">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-danger">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-cross-circle fs-2x text-danger me-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <div>
                                                <div class="fs-7 text-muted">{{ __('translation.invalid_rows') }}</div>
                                                <div class="fs-3 fw-bold text-danger" id="invalidRows">0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Table -->
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover" id="previewTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 50px;">{{ __('translation.row') }}</th>
                                    <th class="text-center" style="width: 70px;">{{ __('translation.status') }}</th>
                                    <th>{{ __('translation.name') }}</th>
                                    <th>{{ __('translation.email') }}</th>
                                    <th>{{ __('translation.phone') }}</th>
                                    <th>{{ __('translation.business_type') }}</th>
                                    <th>{{ __('translation.country') }}</th>
                                    <th>{{ __('translation.messages') }}</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Preview rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('translation.cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmImportBtn">
                        <i class="ki-duotone ki-check fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.confirm_import') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Preview Import Modal-->

    <!--begin::Reject Merchant Modal-->
    <div class="modal fade" id="rejectMerchantModal" tabindex="-1" aria-labelledby="rejectMerchantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="rejectMerchantForm">
                    @csrf
                    <input type="hidden" id="reject_merchant_id" name="merchant_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectMerchantModalLabel">
                            <i class="ki-duotone ki-cross-circle fs-2 text-danger me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.reject_merchant_application') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ __('translation.select_invalid_fields') }}</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="name" id="reject_name">
                                        <label class="form-check-label" for="reject_name">
                                            {{ __('translation.merchant_name') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="owner_name" id="reject_owner_name">
                                        <label class="form-check-label" for="reject_owner_name">
                                            {{ __('translation.owner_name') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="email" id="reject_email">
                                        <label class="form-check-label" for="reject_email">
                                            {{ __('translation.email') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="phone" id="reject_phone">
                                        <label class="form-check-label" for="reject_phone">
                                            {{ __('translation.phone') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="address" id="reject_address">
                                        <label class="form-check-label" for="reject_address">
                                            {{ __('translation.address') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="business_type" id="reject_business_type">
                                        <label class="form-check-label" for="reject_business_type">
                                            {{ __('translation.business_type') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="trade_license_number" id="reject_trade_license">
                                        <label class="form-check-label" for="reject_trade_license">
                                            {{ __('translation.trade_license_number') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="tax_certified_number" id="reject_tax_number">
                                        <label class="form-check-label" for="reject_tax_number">
                                            {{ __('translation.tax_certified_number') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="country" id="reject_country">
                                        <label class="form-check-label" for="reject_country">
                                            {{ __('translation.country') }}
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input rejection-field" type="checkbox" value="city" id="reject_city">
                                        <label class="form-check-label" for="reject_city">
                                            {{ __('translation.city') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Merchant Attachments Section -->
                            <div class="mt-4">
                                <label class="form-label fw-bold">{{ __('translation.merchant_attachments') }}</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input attachment-field" type="checkbox" value="trade_license_document" id="reject_trade_license_doc">
                                            <label class="form-check-label" for="reject_trade_license_doc">
                                                {{ __('translation.trade_license_document') }}
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input attachment-field" type="checkbox" value="tax_certificate_document" id="reject_tax_certificate_doc">
                                            <label class="form-check-label" for="reject_tax_certificate_doc">
                                                {{ __('translation.tax_certificate_document') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input attachment-field" type="checkbox" value="company_logo_document" id="company_logo_document_doc">
                                            <label class="form-check-label" for="company_logo_document_doc">
                                                {{ __('translation.company_logo_document') }}
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input attachment-field" type="checkbox" value="identity_document" id="reject_identity_doc">
                                            <label class="form-check-label" for="reject_identity_doc">
                                                {{ __('translation.identity_document') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">{{ __('translation.rejection_reason') }}</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('translation.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger" id="rejectSubmitBtn">
                            <i class="ki-duotone ki-cross-circle fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.reject_application') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Reject Merchant Modal-->
@endsection

@push('scripts')
    <script>
        let search = '', status = '', country = '', dateFrom = '', dateTo = '';
        let merchantsTable = $('#merchants-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("merchants.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.country_id = country;
                    q.date_from = dateFrom;
                    q.date_to = dateTo;
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
                    data: 'logo'
                    , name: 'logo'
                    , searchable: false
                    , sortable: false
                },
                {
                    data: 'merchant_info'
                    , name: 'merchant_info'
                    , searchable: true
                    , sortable: true
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
                    data: 'business_type'
                    , name: 'business_type'
                },
                {
                    data: 'status'
                    , name: 'status'
                    , searchable: false
                    , sortable: false
                },

                {
                    data: 'is_active'
                    , name: 'is_active'
                },
                @if(!Auth::guard('admin')->user()->custom_region)
                {
                    data: 'country'
                    , name: 'country'
                },
                @endif
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
                // $('#bulk-delete').attr('disabled', true);

                // Re-initialize KTMenu dropdowns here
                if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                    KTMenu.createInstances();
                }
            }
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#merchants-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        // Filter event handlers
        $('#search-input').on('keyup', function () {
            search = $(this).val();
            merchantsTable.ajax.reload();
        });

        $('#country_id').on('change', function () {
            country = $(this).val();
            merchantsTable.ajax.reload();
        });

        $('#status-filter').on('change', function () {
            status = $(this).val();
            merchantsTable.ajax.reload();
        });

        $('#country-filter').on('change', function () {
            country = $(this).val();
            merchantsTable.ajax.reload();
        });

        $('#date-from').on('change', function () {
            dateFrom = $(this).val();
            merchantsTable.ajax.reload();
        });

        $('#date-to').on('change', function () {
            dateTo = $(this).val();
            merchantsTable.ajax.reload();
        });

        // Apply filters button
        $('#apply-filters').on('click', function () {
            merchantsTable.ajax.reload();
        });

        // Clear filters button
        $('#clear-filters').on('click', function () {
            search = '';
            status = '';
            country = '';
            dateFrom = '';
            dateTo = '';
            
            $('#search-input').val('');
            $('#status-filter').val('');
            $('#country-filter').val('');
            $('#date-from').val('');
            $('#date-to').val('');
            
            merchantsTable.ajax.reload();
        });

        // Export filtered results
        $('#export-filtered').on('click', function () {
            let params = new URLSearchParams({
                search: search || '',
                status: status || '',
                country_id: country || '',
                date_from: dateFrom || '',
                date_to: dateTo || ''
            });

            window.open('{{ route("merchants.export") }}?' + params.toString(), '_blank');
        });

        // Toggle filters section using the filter button
        $('#filters_button').on('click', function () {
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            if (filtersBody.is(':visible')) {
                // Collapse filters
                filtersBody.slideUp(300);
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('merchantFiltersCollapsed', 'true');
            } else {
                // Expand filters
                filtersBody.slideDown(300);
                filterIcon.css('transform', 'rotate(0deg)');
                localStorage.setItem('merchantFiltersCollapsed', 'false');
            }
        });

        // Check if filters should be collapsed on page load
        $(document).ready(function() {
            const isCollapsed = localStorage.getItem('merchantFiltersCollapsed');
            const filtersBody = $('#filters-body');
            const filterIcon = $('#filter-icon');
            
            // Default to collapsed if no preference is set, or if user previously collapsed it
            if (isCollapsed === null || isCollapsed === 'true') {
                filtersBody.hide();
                filterIcon.css('transform', 'rotate(90deg)');
                localStorage.setItem('merchantFiltersCollapsed', 'true');
            }

        });

        // Handle bulk delete
        $(document).on('click', '#bulk-delete', function() {
            let selectedIds = $('#record-ids').val();
            
            if (!selectedIds) {
                toastr.warning('{{ __('translation.please_select_records') }}');
                return;
            }
            
            Swal.fire({
                title: '{{ __('translation.are_you_sure') }}',
                text: "{{ __('translation.you_wont_be_able_to_revert') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('translation.yes_delete_it') }}',
                cancelButtonText: '{{ __('translation.cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    $('#bulk-delete').html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.deleting") }}...');
                    
                    $.ajax({
                        url: '{{ route("merchants.bulk-delete") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ids: selectedIds
                        },
                        success: function(response) {
                            if (response.success) {
                                merchantsTable.ajax.reload();
                                toastr.success('{{ __('translation.merchants_deleted_successfully') }}');
                                // Reset bulk delete button
                                $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                            } else {
                                toastr.error('{{ __('translation.something_went_wrong') }}');
                                // Reset bulk delete button
                                $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                            }
                        },
                        error: function() {
                            toastr.error('{{ __('translation.something_went_wrong') }}');
                            // Reset bulk delete button
                            $('#bulk-delete').html('{{ __("translation.delete_selected") }}');
                        }
                    });
                }
            });
        });

        // Handle checkbox selection
        $(document).on('change', '.record__select', function() {
            let selectedIds = [];
            $('.record__select:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            $('#record-ids').val(selectedIds.join(','));
            
            if (selectedIds.length > 0) {
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(selectedIds.length);
            } else {
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        // Handle select all checkbox
        $(document).on('change', '[data-kt-check-target="#merchants-table .form-check-input"]', function() {
            let isChecked = $(this).is(':checked');
            $('.record__select').prop('checked', isChecked);
            
            if (isChecked) {
                let allIds = [];
                $('.record__select').each(function() {
                    allIds.push($(this).val());
                });
                $('#record-ids').val(allIds.join(','));
                $('[data-kt-customer-table-toolbar="selected"]').removeClass('d-none');
                $('[data-kt-customer-table-select="selected_count"]').text(allIds.length);
            } else {
                $('#record-ids').val('');
                $('[data-kt-customer-table-toolbar="selected"]').addClass('d-none');
            }
        });

        var file = null;
        // Filter card toggle functionality
        $('#filters_button').on('click', function(e) {
            e.preventDefault();
            $('#filters_card').toggleClass('d-none');
        });

        // Preview import functionality
        let previewData = null;
        let storedFormData = null;
        let isTransitioning = false; // Flag to prevent clearing FormData during modal transitions
        
        $('#previewImportBtn').on('click', function(e) {
            e.preventDefault();
            
            // Validate file is selected
            if (!$('#import_file')[0].files.length) {
                toastr.warning('{{ __("translation.please_select_file") }}');
                return;
            }
            
            // Store FormData for later use in confirm import
            storedFormData = new FormData($('#importMerchantsForm')[0]);
            isTransitioning = true; // Set flag before hiding modal
            
            let btn = $(this);
            let originalText = btn.html();

            
            // Show loading state
            btn.html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.loading") }}...').prop('disabled', true);
            
            $.ajax({
                url: '{{ route("merchants.import-preview") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: storedFormData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success && response.data) {
                        previewData = response.data;
                        displayPreview(response.data);
                        $('#importMerchantsModal').modal('hide');
                        $('#previewImportModal').modal('show');
                        // Reset flag after modals have transitioned
                        setTimeout(function() {
                            isTransitioning = false;
                        }, 500);
                    } else {
                        toastr.error(response.message || '{{ __("translation.preview_failed") }}');
                        isTransitioning = false; // Reset flag on error
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    isTransitioning = false; // Reset flag on error
                },
                complete: function() {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // Display preview data
        function displayPreview(data) {
            // Update summary
            $('#totalRows').text(data.total || 0);
            $('#validRows').text(data.valid || 0);
            $('#invalidRows').text(data.invalid || 0);
            
            // Clear and populate table
            let tbody = $('#previewTableBody');
            tbody.empty();
            
            if (data.rows && data.rows.length > 0) {
                data.rows.forEach((row, index) => {
                    let statusBadge = row.valid 
                        ? '<span class="badge badge-light-success"><i class="ki-duotone ki-check-circle fs-2"><span class="path1"></span><span class="path2"></span></i></span>'
                        : '<span class="badge badge-light-danger"><i class="ki-duotone ki-cross-circle fs-2"><span class="path1"></span><span class="path2"></span></i></span>';
                    
                    let messages = [];
                    if (row.errors && row.errors.length > 0) {
                        row.errors.forEach(error => {
                            messages.push('<span class="badge badge-danger me-1 mb-1">' + error + '</span>');
                        });
                    }
                    if (row.warnings && row.warnings.length > 0) {
                        row.warnings.forEach(warning => {
                            messages.push('<span class="badge badge-warning me-1 mb-1">' + warning + '</span>');
                        });
                    }
                    
                    let tr = $('<tr>').addClass(row.valid ? '' : 'table-danger');
                    tr.append($('<td>').addClass('text-center').text(index + 2));
                    tr.append($('<td>').addClass('text-center').html(statusBadge));
                    tr.append($('<td>').text(row.original.name || ''));
                    tr.append($('<td>').text(row.original.email || ''));
                    tr.append($('<td>').text(row.original.phone || ''));
                    tr.append($('<td>').text(row.original.business_type || ''));
                    tr.append($('<td>').text(row.original.country || ''));
                    tr.append($('<td>').html(messages.join('') || '-'));
                    
                    tbody.append(tr);
                });
            } else {
                tbody.append('<tr><td colspan="8" class="text-center">{{ __("translation.no_data_found") }}</td></tr>');
            }
        }
        
        // Confirm import after preview
        $('#confirmImportBtn').on('click', function() {
            if (!previewData) {
                toastr.error('{{ __("translation.no_preview_data") }}');
                return;
            }
            
            // Check if there are any valid rows
            if (previewData.valid === 0) {
                toastr.error('{{ __("translation.no_valid_rows_to_import") }}');
                return;
            }
            
            // Check if we have the stored FormData
            if (!storedFormData) {
                console.error('❌ storedFormData is null!');
                toastr.error('{{ __("translation.no_file_data") }}');
                return;
            }
            
            console.log('✅ storedFormData exists:', storedFormData);
            console.log('✅ File in FormData:', storedFormData.get('import_file'));
            
            // Ensure CSRF token is included
            storedFormData.set('_token', '{{ csrf_token() }}');
            
            let btn = $(this);
            let originalText = btn.html();
            
            // Show loading state
            btn.html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.importing") }}...').prop('disabled', true);
            $.ajax({
                url: '{{ route("merchants.import") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: storedFormData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        let message = response.message;
                        if (response.errors && response.errors.length > 0) {
                            message += '<br><small>{{ __("translation.click_to_see_errors") }}</small>';
                            toastr.warning(message, '', {
                                timeOut: 5000,
                                onclick: function() {
                                    let errorHtml = '<ul class="mb-0">';
                                    response.errors.forEach(error => {
                                        errorHtml += '<li>' + error + '</li>';
                                    });
                                    errorHtml += '</ul>';
                                    
                                    Swal.fire({
                                        title: '{{ __("translation.import_errors") }}',
                                        html: errorHtml,
                                        icon: 'warning',
                                        confirmButtonText: '{{ __("translation.ok") }}'
                                    });
                                }
                            });
                        } else {
                            toastr.success(message);
                        }
                        
                        $('#previewImportModal').modal('hide');
                        $('#importMerchantsForm')[0].reset();
                        merchantsTable.ajax.reload();
                        previewData = null;
                        storedFormData = null;
                        isTransitioning = false;
                    } else {
                        toastr.error(response.message || '{{ __("translation.import_failed") }}');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Reset forms when modals are closed
        $('#importMerchantsModal').on('hidden.bs.modal', function() {
            // Only clear if not transitioning to preview modal
            if (!isTransitioning) {
                $('#importMerchantsForm')[0].reset();
                storedFormData = null;
            }
        });
        
        $('#previewImportModal').on('hidden.bs.modal', function() {
            previewData = null;
            storedFormData = null;
            isTransitioning = false; // Reset flag
            $('#previewTableBody').empty();
            // Don't reopen import modal if user cancelled - they might not want it
        });

        // Handle merchant rejection
        $(document).on('click', '.reject-merchant', function(e) {
            e.preventDefault();
            const merchantId = $(this).data('merchant-id');
            $('#reject_merchant_id').val(merchantId);
            $('#rejectMerchantModal').modal('show');
        });

        // Handle rejection form submission
        $('#rejectMerchantForm').on('submit', function(e) {
            e.preventDefault();
            
            const merchantId = $('#reject_merchant_id').val();
            const rejectionReason = $('#rejection_reason').val();
            const submitBtn = $('#rejectSubmitBtn');
            const originalBtnHtml = submitBtn.html();
            
            // Get selected invalid fields (including both field and attachment checkboxes)
            const invalidFields = [];
            $('.rejection-field:checked, .attachment-field:checked').each(function() {
                invalidFields.push($(this).val());
            });
            
            // Show loading state
            submitBtn.html('<i class="ki-duotone ki-spinner fs-2 rotate"></i> {{ __("translation.rejecting") }}...');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: `/admin/merchants/${merchantId}/reject`,
                method: 'POST',
                headers: {
                    'accept': 'application/json',
                }, 
                data: {
                    _token: '{{ csrf_token() }}',
                    rejection_reason: rejectionReason,
                    invalid_fields: invalidFields
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || '{{ __("translation.merchant_rejected_successfully") }}');
                        $('#rejectMerchantModal').modal('hide');
                        merchantsTable.ajax.reload();
                    } else {
                        toastr.error(response.message || '{{ __("translation.rejection_failed") }}');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ __("translation.something_went_wrong") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });

        // Reset rejection form when modal is closed
        $('#rejectMerchantModal').on('hidden.bs.modal', function() {
            $('#rejectMerchantForm')[0].reset();
            $('#reject_merchant_id').val('');
            // Clear all checkboxes
            $('.rejection-field, .attachment-field').prop('checked', false);
        });

        // Handle rejection field checkboxes
        $('.rejection-field, .attachment-field').on('change', function() {
            updateRejectionReason();
        });

        function updateRejectionReason() {
            const checkedFields = [];
            
            const fieldMessages = {
                'name': '{{ __("translation.merchant_name") }}',
                'owner_name': '{{ __("translation.owner_name") }}',
                'email': '{{ __("translation.email") }}',
                'phone': '{{ __("translation.phone") }}',
                'address': '{{ __("translation.address") }}',
                'business_type': '{{ __("translation.business_type") }}',
                'trade_license_number': '{{ __("translation.trade_license_number") }}',
                'tax_certified_number': '{{ __("translation.tax_certified_number") }}',
                'country': '{{ __("translation.country") }}',
                'city': '{{ __("translation.city") }}',
                'trade_license_document': '{{ __("translation.trade_license_document") }}',
                'tax_certificate_document': '{{ __("translation.tax_certificate_document") }}',
                'company_logo_document': '{{ __("translation.company_logo_document") }}',
                'identity_document': '{{ __("translation.identity_document") }}'
            };

            // Get all checked checkboxes (both field and attachment)
            $('.rejection-field:checked, .attachment-field:checked').each(function() {
                const fieldValue = $(this).val();
                if (fieldMessages[fieldValue]) {
                    checkedFields.push(fieldMessages[fieldValue]);
                }
            });

            let rejectionText = '';
            
            if (checkedFields.length > 0) {
                if (checkedFields.length === 1) {
                    rejectionText = `Your data has invalid ${checkedFields[0]}. Please provide correct information and resubmit your application.`;
                } else if (checkedFields.length === 2) {
                    rejectionText = `Your data has invalid ${checkedFields[0]} and ${checkedFields[1]}. Please provide correct information and resubmit your application.`;
                } else {
                    const lastField = checkedFields.pop();
                    const otherFields = checkedFields.join(', ');
                    rejectionText = `Your data has invalid ${otherFields}, and ${lastField}. Please provide correct information and resubmit your application.`;
                }
            }

            $('#rejection_reason').val(rejectionText);
        }
    </script>
@endpush 
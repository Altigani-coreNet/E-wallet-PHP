@extends('layouts.merchant.merchant_layout')

@section('title', __('translation.settlements_transactions'))

@section('breadcrumb')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('merchant.settlements.index') }}" class="text-muted text-hover-primary">{{ __('translation.settlements') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.settlements_transactions') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter menu-->
    <div class="m-0">
        <!--begin::Menu toggle-->
        <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.filter') }}</button>
        <!--end::Menu toggle-->
    </div>
    <!--end::Filter menu-->
    <!--begin::Refresh button-->
    <button id="refresh-table" class="btn btn-sm btn-flex btn-light fw-bold me-2">
        <i class="ki-duotone ki-arrows-circle fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.refresh') }}
    </button>
    <!--end::Refresh button-->
    @if(auth()->user()->can('transactions') || auth()->user()->can('export_transactions'))
    <!--begin::Export button-->
    <button id="export-transactions" class="btn btn-sm fw-bold btn-success" 
            data-bs-toggle="tooltip" 
            data-bs-placement="top" 
            title="Export settlements transactions with current filters applied">
        <i class="ki-duotone ki-file-down fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export_transactions') }}
    </button>
    <!--end::Export button-->
    @endif
</div>
@endsection

@section('content')
<style>
    #filter-summary {
        transition: all 0.3s ease;
    }
    
    #filter-summary:hover {
        background-color: rgba(0,0,0,0.05);
        border-radius: 4px;
        padding: 4px 8px;
        margin: -4px -8px;
    }
    
    .dataTables_empty {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .dataTables_empty:before {
        content: "📊";
        font-size: 48px;
        display: block;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .is-loading {
        position: relative;
        pointer-events: none;
    }
    
    .is-loading:after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 16px;
        height: 16px;
        margin: -8px 0 0 -8px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <!-- Statistics Cards -->
        @if(auth()->user()->can('statistics'))
        <div class="row g-5 g-xl-8 mt-4">
            <div class="col-md-12">
                <div class="alert alert-info d-flex align-items-center p-5">
                    <i class="ki-duotone ki-information fs-2hx text-info me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1">{{ __('translation.settlements_transactions') }}</h4>
                        <span>{{ __('translation.showing_refunded_and_voided_transactions') }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row gy-5 g-xl-10">
            <!--begin::Refund Transactions-->
            <div class="col-xl-6 mb-xl-10">
                <div class="card card-flush h-xl-100 bg-light-warning">
                    <div class="card-header pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">{{ __('translation.refunded_transactions') }}</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ __('translation.refunded_transactions_description') }}</span>
                        </h3>
                    </div>
                    <div class="card-body pt-2 row">
                        <div class="mb-2 col-6">
                            <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($combinedStatistics['refundTransactions'] ?? 0) }}</span>
                        </div>
                        <div class="mb-2 col-6 d-flex justify-content-center align-items-center">
                            <span class="fs-2x fw-semibold text-warning">${{ number_format($combinedStatistics['refundTransactionsAmount'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Refund Transactions-->
            
            <!--begin::Void Transactions-->
            <div class="col-xl-6 mb-xl-10">
                <div class="card card-flush h-xl-100 bg-light-danger">
                    <div class="card-header pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">{{ __('translation.voided_transactions') }}</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ __('translation.voided_transactions_description') }}</span>
                        </h3>
                    </div>
                    <div class="card-body pt-2 row">
                        <div class="mb-2 col-6">
                            <span class="fs-2hx fw-bold d-block text-gray-800 me-2 mb-2 lh-1 ls-n2">{{ number_format($combinedStatistics['voidTransactions'] ?? 0) }}</span>
                        </div>
                        <div class="mb-2 col-6 d-flex justify-content-center align-items-center">
                            <span class="fs-2x fw-semibold text-danger">${{ number_format($combinedStatistics['voidTransactionsAmount'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Void Transactions-->
        </div>
        @endif

        <!-- Hidden input for selected IDs -->
        <input type="hidden" id="record-ids" value="">
        
        <div class="card bg-white card-xl-stretch mb-5 mb-xl-8 d-none" id="filters_card">
            <!--begin::Body-->
            <div class="card-body">
                <div class="row">
                    <x:text-input class="col-md-3" name='search'
                                  filedname="search" value="{{old('')}}"
                                  label="{{ __('translation.search') }}"/>
                    <x:select-options class="col-md-3" name="status"
                                      filed-name='status'
                                      :options="['APPROVED', 'DECLINED', 'PENDING', 'CAPTURED', 'VOIDED', 'REFUNDED']"
                                      value="{{old('status')}}"
                                      label="{{ __('translation.status') }}"/>
                    <x:select-options class="col-md-3" name="type"
                                      filed-name='type'
                                      :options="['refunded', 'voided']"
                                      value="{{old('type')}}"
                                      label="{{ __('translation.transaction_type') }}"/>
                    <x:select-options class="col-md-3" name="terminal"
                                      filed-name='terminal_id'
                                      :options="[]"
                                      value="{{old('terminal_id')}}"
                                      label="{{ __('translation.terminal') }}"/>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">{{ __('translation.from_date') }}</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               placeholder="{{ __('translation.from_date') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">{{ __('translation.to_date') }}</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               placeholder="{{ __('translation.to_date') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-8">
                        <div id="filter-summary" class="text-muted fs-7 d-none">
                            <i class="ki-duotone ki-filter fs-6 text-muted me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span id="active-filters-count">0</span> {{ __('translation.active_filters') }}
                            <span class="ms-2 badge badge-light-primary fs-8" id="filter-details"></span>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <button type="button" class="btn btn-secondary btn-sm" id="clear-filters">
                            <i class="ki-duotone ki-filter-remove fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('translation.clear_filters') }}
                        </button>
                    </div>
                </div>
            </div>
            <!--end::Body-->
        </div>

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
                    <!--end::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none"
                         data-kt-customer-table-toolbar="selected">
                        <div class="fw-bolder me-5">
                            <span class="me-2" data-kt-customer-table-select="selected_count"></span>{{ __('translation.selected') }}
                        </div>
                        @if(auth()->user()->can('transactions') || auth()->user()->can('delete_transactions'))
                        <button type="button" class="btn btn-danger" id="bulk-delete">
                            {{ __('translation.delete_selected') }}
                        </button>
                        @endif
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="settlements-transactions-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#settlements-transactions-table .form-check-input" value="1" />
                                </div>
                            </th>
                            <th class="min-w-125px">{{ __('translation.transaction_id') }}</th>
                            <th class="min-w-125px">{{ __('translation.batch_number') }}</th>
                            <th class="min-w-125px">{{ __('translation.payment_channel') }}</th>
                            <th class="min-w-100px">{{ __('translation.type') }}</th>
                            <th class="min-w-100px">{{ __('translation.status') }}</th>
                            <th class="min-w-100px">{{ __('translation.amount') }}</th>
                            <th class="min-w-100px">{{ __('translation.created_at') }}</th>
                            <th class="text-end min-w-100px">{{ __('translation.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Container-->
</div>
@endsection

@push('scripts')
<script>
    "use strict";

    // Class definition
    var KTSettlementsTransactionsList = function () {
        // Define shared variables
        var table = document.getElementById('settlements-transactions-table');
        var datatable;
        var filterModal;

        // Private functions
        var initSettlementsTransactionsTable = function () {
            // Set date data
            datatable = $(table).DataTable({
                "searchDelay": 500,
                "processing": true,
                "serverSide": true,
                "order": [[8, "desc"]],
                "stateSave": true,
                "select": {
                    "style": "multi",
                    "selector": 'td:first-child input[type="checkbox"]',
                },
                "ajax": {
                    "url": "{{ route('merchant.settlements.transactions.data') }}",
                    "type": "GET",
                    "data": function (d) {
                        d.search = $('#search').val();
                        d.status = $('#status').val();
                        d.type = $('#type').val();
                        d.terminal_id = $('#terminal').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                "columns": [
                    {"data": "record_select"},
                    {"data": "transaction_id"},
                    {"data": "batch_number"},
                    {"data": "payment_channel"},
                    {"data": "type"},
                    {"data": "status"},
                    {"data": "amount"},
                    {"data": "created_at"},
                    {"data": "actions"}
                ],
                "columnDefs": [
                    {
                        "targets": 0,
                        "orderable": false,
                        "className": "text-center"
                    },
                    {
                        "targets": -1,
                        "data": null,
                        "orderable": false,
                        "className": "text-end"
                    }
                ],
            });

            // Re-init functions on every table re-draw
            datatable.on('draw', function () {
                initToggleToolbar();
                handleDeleteRows();
                KTMenu.createInstances();
            });
        }

        // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
        var handleSearchDatatable = function () {
            const filterSearch = document.querySelector('[data-kt-transactions-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        }

        // Filter Datatable
        var handleFilterDatatable = function () {
            // Select elements
            const filterButton = document.querySelector('#filters_button');
            const filterCard = document.querySelector('#filters_card');
            const clearFiltersButton = document.querySelector('#clear-filters');

            // Filter toggle
            if (filterButton) {
                filterButton.addEventListener('click', function () {
                    filterCard.classList.toggle('d-none');
                });
            }

            // Clear filters
            if (clearFiltersButton) {
                clearFiltersButton.addEventListener('click', function () {
                    // Reset form
                    document.querySelector('#filters_card form').reset();
                    
                    // Clear datatable filters
                    datatable.search('').draw();
                    datatable.ajax.reload();
                    
                    // Hide filter card
                    filterCard.classList.add('d-none');
                    
                    // Reset filter summary
                    updateFilterSummary();
                });
            }

            // Filter datatable on input change
            const filterInputs = ['#search', '#status', '#type', '#terminal', '#start_date', '#end_date'];
            filterInputs.forEach(function (selector) {
                const element = document.querySelector(selector);
                if (element) {
                    element.addEventListener('change', function () {
                        datatable.ajax.reload();
                        updateFilterSummary();
                    });
                }
            });
        }

        // Update filter summary
        var updateFilterSummary = function () {
            const activeFilters = [];
            const filterInputs = [
                { selector: '#search', label: 'Search' },
                { selector: '#status', label: 'Status' },
                { selector: '#type', label: 'Type' },
                { selector: '#terminal', label: 'Terminal' },
                { selector: '#start_date', label: 'Start Date' },
                { selector: '#end_date', label: 'End Date' }
            ];

            filterInputs.forEach(function (filter) {
                const element = document.querySelector(filter.selector);
                if (element && element.value) {
                    activeFilters.push(filter.label + ': ' + element.value);
                }
            });

            const filterSummary = document.querySelector('#filter-summary');
            const activeFiltersCount = document.querySelector('#active-filters-count');
            const filterDetails = document.querySelector('#filter-details');

            if (activeFilters.length > 0) {
                filterSummary.classList.remove('d-none');
                activeFiltersCount.textContent = activeFilters.length;
                filterDetails.textContent = activeFilters.join(', ');
            } else {
                filterSummary.classList.add('d-none');
            }
        }

        // Delete customer
        var handleDeleteRows = function () {
            // Select all delete buttons
            const deleteButtons = table.querySelectorAll('[data-kt-transactions-table-filter="delete_row"]');

            deleteButtons.forEach(d => {
                // Delete button on click
                d.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Select parent row
                    const parent = e.target.closest('tr');

                    // Get customer name
                    const customerName = parent.querySelectorAll('td')[1].innerText;

                    // SweetAlert2 pop up confirmation
                    Swal.fire({
                        text: "Are you sure you want to delete " + customerName + "?",
                        icon: "warning",
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: "Yes, delete!",
                        cancelButtonText: "No, cancel",
                        customClass: {
                            confirmButton: "btn fw-bold btn-danger",
                            cancelButton: "btn fw-bold btn-active-light-primary"
                        }
                    }).then(function (result) {
                        if (result.value) {
                            // Simulate delete request -- replace with actual delete logic
                            Swal.fire({
                                text: "You have deleted " + customerName + "!",
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn fw-bold btn-primary"
                                }
                            }).then(function () {
                                // Remove row from table
                                datatable.row($(parent)).remove().draw();
                            });
                        } else if (result.dismiss === 'cancel') {
                            Swal.fire({
                                text: customerName + " was not deleted.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn fw-bold btn-primary"
                                }
                            });
                        }
                    });
                });
            });
        }

        // Handle bulk delete
        var handleBulkDelete = function () {
            const bulkDeleteButton = document.querySelector('#bulk-delete');
            
            if (bulkDeleteButton) {
                bulkDeleteButton.addEventListener('click', function () {
                    const selectedRows = datatable.rows({ selected: true }).data();
                    
                    if (selectedRows.length === 0) {
                        Swal.fire({
                            text: "Please select at least one transaction to delete.",
                            icon: "warning",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn fw-bold btn-primary"
                            }
                        });
                        return;
                    }

                    Swal.fire({
                        text: "Are you sure you want to delete " + selectedRows.length + " selected transactions?",
                        icon: "warning",
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: "Yes, delete!",
                        cancelButtonText: "No, cancel",
                        customClass: {
                            confirmButton: "btn fw-bold btn-danger",
                            cancelButton: "btn fw-bold btn-active-light-primary"
                        }
                    }).then(function (result) {
                        if (result.value) {
                            // Simulate bulk delete request -- replace with actual delete logic
                            Swal.fire({
                                text: "You have deleted " + selectedRows.length + " transactions!",
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn fw-bold btn-primary"
                                }
                            }).then(function () {
                                // Remove selected rows from table
                                datatable.rows({ selected: true }).remove().draw();
                            });
                        }
                    });
                });
            }
        }

        // Toggle toolbar
        var initToggleToolbar = function () {
            // Toggle selected action toolbar
            const container = document.querySelector('#kt_content_container');
            const checkboxes = table.querySelectorAll('[type="checkbox"]');
            const deleteButton = document.querySelector('#bulk-delete');

            // Select elements
            const checkAll = table.querySelector('[data-kt-check="true"]');

            // Toggle delete selected toolbar
            checkboxes.forEach(c => {
                // Checkbox on click event
                c.addEventListener('click', function () {
                    setTimeout(function () {
                        toggleToolbars();
                    }, 50);
                });
            });

            // Check all on click event
            if (checkAll) {
                checkAll.addEventListener('click', function () {
                    setTimeout(function () {
                        toggleToolbars();
                    }, 50);
                });
            }

            const toggleToolbars = () => {
                // Define variables
                const count = datatable.rows({ selected: true }).count();

                // Toggle toolbar
                if (count > 0) {
                    container.classList.add('table-selected');
                } else {
                    container.classList.remove('table-selected');
                }
            }
        }

        // Refresh table
        var handleRefreshTable = function () {
            const refreshButton = document.querySelector('#refresh-table');
            
            if (refreshButton) {
                refreshButton.addEventListener('click', function () {
                    datatable.ajax.reload();
                });
            }
        }

        // Export table
        var handleExportTable = function () {
            const exportButton = document.querySelector('#export-transactions');
            
            if (exportButton) {
                exportButton.addEventListener('click', function () {
                    // Get current filters
                    const filters = {
                        search: $('#search').val(),
                        status: $('#status').val(),
                        type: $('#type').val(),
                        terminal_id: $('#terminal').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val()
                    };
                    
                    // Create export URL with filters
                    const exportUrl = "{{ route('merchant.transactions.export') }}?" + new URLSearchParams(filters).toString();
                    
                    // Open export URL in new window
                    window.open(exportUrl, '_blank');
                });
            }
        }

        // Public methods
        return {
            init: function () {
                if (!table) {
                    return;
                }

                initSettlementsTransactionsTable();
                handleSearchDatatable();
                handleFilterDatatable();
                handleDeleteRows();
                handleBulkDelete();
                handleRefreshTable();
                handleExportTable();
            }
        };
    }();

    // On document ready
    KTUtil.onDOMContentLoaded(function () {
        KTSettlementsTransactionsList.init();
    });
</script>
@endpush

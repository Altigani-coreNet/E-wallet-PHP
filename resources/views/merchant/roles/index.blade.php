@extends("layouts.merchant.merchant_layout")
@section('main-head' , __('translation.role_and_permisions'))
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
        <li class="breadcrumb-item text-muted">{{ __('translation.role_and_permisions') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-fluid">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" data-kt-roles-table-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="{{ __('translation.search_roles') }}" />
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                            <!--begin::Add role-->
                            <a href='{{ route('merchant.roles.create') }}'
                               class="btn btn-primary">{{ __('translation.add_roles') }}</a>
                            <!--end::Add role-->
                        </div>
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-roles-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-roles-table-select="selected_count">0</span>Selected
                            </div>
                            <button type="button" class="btn btn-danger"
                                    data-kt-roles-table-select="delete_selected">Delete Selected
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    {{-- @include('layouts.merchant.partials.sessions') --}}
                    <!--begin::Table-->
                    <div id="kt_roles_table_wrapper" class="dt-container dt-bootstrap5 dt-empty-footer w-100">
                        <div class="table-responsive w-100">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 dataTable w-100" id="kt_roles_table" style="width: 100% !important;">
                                <colgroup>
                                    <col data-dt-column="0" >
                                    <col data-dt-column="1" >
                                    <col data-dt-column="2" >
                                    <col data-dt-column="3" >
                                </colgroup>
                                <thead>
                                <tr class="text-start text-dark fw-bold fs-7 text-uppercase gs-0">
                                    <th class="w-10px pe-2 dt-orderable-none" data-dt-column="0">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_roles_table .form-check-input" value="1">
                                        </div>
                                    </th>
                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="1">
                                        <span class="dt-column-title text-dark">{{ __('translation.id') }}</span>
                                    </th>
                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="2">
                                        <span class="dt-column-title text-dark">{{ __('translation.name') }}</span>
                                    </th>
                                    <th class="min-w-125px dt-orderable-asc dt-orderable-desc" data-dt-column="3">
                                        <span class="dt-column-title text-dark">{{ __('translation.permission_count') }}</span>
                                    </th>
                                    <th class="text-end min-w-70px dt-orderable-none" data-dt-column="4">
                                        <span class="dt-column-title text-dark">{{ __('translation.action') }}</span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="fw-semibold text-dark">
                                </tbody>
                            </table>
                        </div>
                    </div>
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
        let rolesTable = $('#kt_roles_table').DataTable({
            dom: "tiplr",
            serverSide: true,
            processing: true,
            autoWidth: false,
            scrollX: true,
            "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            },
            ajax: {
                url: '{{ route("merchant.roles.data") }}',
                data: function (d) {
                    // Add any additional data if needed
                }
            },
            columns: [{
                data: 'record_select',
                name: 'record_select',
                searchable: false,
                sortable: false,
                width: '1%'
            },
            {
                data: 'id',
                name: 'id',
            },
            {
                data: 'name',
                name: 'name',
                width: '30%'
            },
            {
                data: 'permission_count',
                name: 'permission_count',
                width: '20%'
            },
            {
                data: 'actions',
                name: 'actions',
                searchable: false,
                sortable: false,
                width: '20%',
                className: 'text-end',
                render: function(data, type, row) {
                    return '<div class="text-end">' + data + '</div>';
                }
            }],
            order: [[1, 'desc']],
            drawCallback: function (settings) {
                $('.record__select').prop('checked', false);
                $('#record__select-all').prop('checked', false);
                $('#record-ids').val();
                $('#bulk-delete').attr('disabled', true);
                
                // Force table to maintain full width
                $('#kt_roles_table').css('width', '100%');
                $('#kt_roles_table_wrapper').css('width', '100%');
            }
        });

        $('#data_search').keyup(function () {
            rolesTable.search(this.value).draw();
        });

        // Ensure table maintains full width on window resize
        $(window).on('resize', function () {
            $('#kt_roles_table').css('width', '100%');
            $('#kt_roles_table_wrapper').css('width', '100%');
        });
    </script>
@endpush

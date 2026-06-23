@extends("layouts.admin.admin_layout")
@section('main-head' , ___('translation.brands_management'))
@section('page_title',  'Brands Management')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1" >
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">Home</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">Brands</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
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
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            <a href='{{ route('brands.create')}}'
                               class="btn btn-primary">{{ ___('translation.add_brand') }}</a>
                        </div>
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-customer-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
                            </div>
                            <button type="button" class="btn btn-danger"
                                    data-kt-customer-table-select="delete_selected">Delete Selected
                            </button>
                        </div>
                        <!--end::Group actions-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    @include('layouts.admin.partials.sessions')
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="brands-table">
                            <!--begin::Table head-->
                            <thead>
                            <!--begin::Table row-->
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true"
                                               data-kt-check-target="#kt_customers_table .form-check-input" value="1"/>
                                    </div>
                                </th>
                                <th class="min-w-125px">{{ ___('translation.id') }}</th>
                                <th class="">{{ ___('translation.image') }}</th>
                                <th class="">{{ ___('translation.name') }}</th>
                                <th class="">{{ ___('translation.status') }}</th>
                                <th class="text-center ">{{ ___('translation.action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#brands-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('brands.data') }}",
                columns: [
                    {data: 'record_select', name: 'record_select', orderable: false, searchable: false},
                    {data: 'id', name: 'id'},
                    {data: 'image', name: 'image', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'status', name: 'status', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                order: [[1, 'desc']],
                pageLength: 10,
                language: {
                        url: "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
                    },
                    drawCallback: function (settings) {
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
        });
    </script>
@endpush 
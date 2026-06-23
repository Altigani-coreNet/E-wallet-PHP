@extends("layouts.merchant.merchant_layout")
@section('main-head' , __('translation.users_managements'))
@section('breadcrumb')
<li class="breadcrumb-item text-gray-600">
    <a href="{{ route('merchant.dashboard') }}" class="text-gray-600">{{ __('translation.dashboard') }}</a>
</li>
<li class="breadcrumb-item text-gray-600">
    <span class="text-gray-600">{{ __('translation.users_managements') }}</span>
</li>
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <!--begin::Filter toggle-->
    <button id="toggle-filters" class="btn btn-sm btn-flex btn-secondary fw-bold" data-kt-menu-trigger="click">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}
    </button>
    <!--end::Filter toggle-->
    
    @if(auth()->user()->can('users') || auth()->user()->can('create_users'))
    <!--begin::Add user button-->
    <a href='{{ route('merchant.users.create')}}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.add_user') }}
    </a>
    <!--end::Add user button-->
    @endif
</div>
@endsection
@section('content')
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <div class="row g-5 g-xl-8 mt-4">

             
            </div>
            <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-card" style="display: none;">
                <!--begin::Body-->
                <div class="card-body">
                    <!--begin::Svg Icon | path: icons/duotune/graphs/gra007.svg-->
                    <!--end::Svg Icon-->
                    <div class="row">
                        <x:text-input class="col-md-6" name='search'
                                      filedname="search" value="{{old('')}}"/>

                        <x:select-options class="col-md-6" name="status"
                                          filed-name='status'
                                          :options="['active', 'inactive']"
                                          value="{{old('')}}"/>

                        {{-- <x:select2-input class="col-md-4" name="city" filed-name="city_id"
                                         url="{{route('city.select')}}"/> --}}


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
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                            <!--begin::Menu 1-->

                            <!--end::Menu 1-->
                            <!--end::Filter-->
                            <!--begin::Export-->

                            <!--end::Export-->
                            <!--begin::Add customer-->
                            <!--end::Add customer-->
                        </div>
                        <!--end::Toolbar-->
                        <!--begin::Group actions-->
                        <div class="d-flex justify-content-end align-items-center d-none"
                             data-kt-customer-table-toolbar="selected">
                            <div class="fw-bolder me-5">
                                <span class="me-2" data-kt-customer-table-select="selected_count"></span>Selected
                            </div>
                            @if(auth()->user()->can('users') || auth()->user()->can('delete_users'))
                            <button type="button" class="btn btn-danger"
                                    data-kt-customer-table-select="delete_selected">Delete Selected
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
                    <div class="table-reponsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="owners-table">
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
                                <th class="text-dark">{{ __('translation.id') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.name') }}</th>
                                <th class="min-w-125px text-dark">{{ __('translation.image') }}</th>
                                <th class="text-dark">{{ __('translation.email') }}</th>
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
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->

            <!-- Include Assign Terminals Modal -->

        </div>
        <!--end::Container-->
    </div>
@endsection

@push('scripts')

    <script>
        let city_id, search, status;
        let rolesTable = $('#owners-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , autoWidth: false
            , scrollX: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("merchant.users.data")}}',
                data: (q) => {
                    q.search = search;
                    q.status = status;
                    q.city_id = city_id;
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
            ,  drawCallback: function (settings) {
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
            $('#owners-table').css('width', '100%');
            $('.table-reponsive').css('width', '100%');
        });

        $('#data_search').keyup(function () {
            rolesTable.search(this.value).draw();
        });

        $("[name='search']").attr("placeholder", "{{__('translation.name,_email,_phone')}}");

        $("[name='search']").on('keyup', function () {
            search = $(this).val();
            console.log(search);
            rolesTable.ajax.reload();
        });

        $("[name='status']").on('change', function () {
            status = $(this).val();
            rolesTable.ajax.reload();
        });

        $("[name='city_id']").on('change', function () {
            city_id = $(this).val();
            console.log(city_id);
            rolesTable.ajax.reload();
        });

        // Toggle filters functionality
        $('#toggle-filters').on('click', function() {
            $('#filters-card').slideToggle();
        });

    </script>
@endpush 
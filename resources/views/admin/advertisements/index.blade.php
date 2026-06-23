@extends('layouts.admin.admin_layout')

@section('title', 'Advertisements Management')

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">Advertisements</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
    <button id="filters_button" type="button" class="btn btn-secondary me-2 btn-sm">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}
    </button>
    
    <a href="{{ route('admin.advertisements.create') }}" class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3"></i>
        Add Advertisement
    </a>
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <!--begin::Filters Card-->
        <div class="card mb-5 mb-xl-8" id="filters-card" style="display: none;">
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
            <div class="card-body">
                <div class="row g-4">
                    <!-- Search -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                        <input type="text" class="form-control" id="search-input" placeholder="{{ __('translation.search') }}">
                    </div>

                    <!-- Country Filter -->
                    <x:select2-input class="col-md-4" name="country" filed-name="country_id" 
                                    url="{{route('countries.select')}}" />

                    <!-- Status Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select" id="status-filter" name="status">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

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
                    <h3 class="fw-bold m-0">Advertisements List</h3>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="advertisements_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-100px">Image</th>
                            <th class="min-w-125px">Name</th>
                            <th class="min-w-125px">Country</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-150px">Start / End Date</th>
                            <th class="text-end min-w-100px">Actions</th>
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
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle filters
        $('#filters_button').click(function() {
            $('#filters-card').slideToggle();
        });

        // Initialize DataTable
        var table = $('#advertisements_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.advertisements.data') }}",
                data: function(d) {
                    d.country_id = $('#country_id').val();
                    d.status = $('#status-filter').val();
                    d.date_from = $('#date-from').val();
                    d.date_to = $('#date-to').val();
                    d.search = $('#search-input').val();
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'image_preview', name: 'image_preview', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'country_name', name: 'country.name' },
                { data: 'status_badge', name: 'status' },
                { data: 'dates', name: 'start_date', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']]
        });

        // Filter change events
        $('#search-input, #status-filter, #date-from, #date-to').on('change keyup', function() {
            table.draw();
        });

        $('#country_id').on('change', function() {
            table.draw();
        });

        // Clear filters
        $('#clear-filters').click(function() {
            $('#search-input').val('');
            $('#status-filter').val('');
            $('#date-from').val('');
            $('#date-to').val('');
            $('#country_id').val(null).trigger('change');
            table.draw();
        });

        // Delete advertisement
        $(document).on('click', '.delete-advertisement', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                table.draw();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'Failed to delete advertisement', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush


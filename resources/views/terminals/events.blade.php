@extends('layouts.admin.admin_layout')
@section('page_title', __('translation.events'))
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
            <a href="{{ route('terminals.index') }}" class="text-muted text-hover-primary">{{ __('translation.terminals') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.events') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
    <!--begin::Row-->
        <!--begin::Navbar-->
        {{-- <x-terminal-profile-header :terminal="$terminal" :activeTab="'events'" /> --}}
        <!--end::Navbar-->

        <!--begin::Basic info-->
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header border-0 cursor-pointer" role="button">
                <!--begin::Card title-->
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">{{ __('translation.events') }}</h3>
                </div>
                <!--end::Card title-->
            </div>
            <!--begin::Card header-->

            <!--begin::Content-->
            <div class="collapse show">
                <!--begin::Card body-->
                <div class="card-body border-top p-9">
                           
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-0">
                            <!--begin::Table wrapper-->
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5" id="events-table">
                                    <!--begin::Table head-->
                                    <thead>
                                        <!--begin::Table row-->
                                        <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                            <th class="min-w-70px">{{ __('translation.id') }}</th>
                                            <th class="min-w-125px">{{ __('translation.time') }}</th>
                                            <th class="min-w-125px">{{ __('translation.events') }}</th>
                                            <th class="min-w-200px">{{ __('translation.event_description') }}</th>
                                            <th class="min-w-200px">{{ __('translation.message') }}</th>
                                            <th class="min-w-70px">{{ __('translation.actions') }}</th>
                                        </tr>
                                        <!--end::Table row-->
                                    </thead>
                                    <!--end::Table head-->
                                </table>
                            </div>
                            <!--end::Table wrapper-->
                        </div>
                        <!--end::Card body-->
                    </div>
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Basic info-->
    </div>
    <!--end::Container-->
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Events DataTable
    let eventsTable = $('#events-table').DataTable({
        dom: "tiplr",
        serverSide: true,
        processing: true,
        language: {
            url: "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        },
        ajax: {
            url: '{{ route("logs.data") }}',
            data: function(d) {
                d.loggable_id = '{{ $terminal->id }}';
                d.loggable_type = '{{ addslashes(get_class($terminal)) }}';
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'time', name: 'time' },
            { 
                data: 'action', 
                name: 'action',
                render: function(data) {
                    return data; // The action column is already formatted with HTML from the server
                }
            },
            { data: 'text', name: 'text' },
            { data: 'message', name: 'message' },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            $('.record__select').prop('checked', false);
            $('#record__select-all').prop('checked', false);
            $('#record-ids').val();
            $('#bulk-delete').attr('disabled', true);
        }
    });

    // Search functionality
    $('#data_search').keyup(function() {
        eventsTable.search(this.value).draw();
    });
});
</script>
@endpush

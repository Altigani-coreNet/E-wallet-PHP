<div class="card card-xxl-stretch mb-5 mb-xl-8">
    <!--begin::Header-->
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bolder fs-3 mb-1">{{___('translation.Latest_tenders')}}</span>
            <span
                class="text-muted mt-1 fw-bold fs-7">{{___('translation.more_than') ." ".  $tender_count . " ".___('translation.tender') }}</span>
        </h3>
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body py-3">
        <div class="tab-content">
            <!--begin::Tap pane-->
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="tenders-table">
                    <!--begin::Table head-->
                    <thead>
                    <!--begin::Table row-->
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">

                        <th class="max-w-125px" style="width: 50px">{{___('translation.id')}}</th>
                        {{--                        <th class="max-w-125px" style="width: 50px">{{ ___('translation.cover_image') }}</th>--}}
                        <th class="min-w-125px">{{ __('translation.subject') }}</th>
                        <th class="min-w-125px">{{ __('translation.budget') }}</th>
                        <th class="">{{ ___('translation.type') }}</th>
                        <th class="">{{ ___('translation.status') }}</th>
                    </tr>
                    <!--end::Table row-->
                    </thead>
                    <!--end::Table head-->
                    <!--begin::Table body-->

                    <!--end::Table body-->
                </table>
            </div>
        </div>
    </div>
    <!--end::Body-->
</div>
@push("scripts")
    <script>
        let rolesTable = $('#tenders-table').DataTable({
            pageLength: 5,
            paging: false,
            info: false,
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("tenders.data", ["limit" => 4])}}'
            }
            , columns: [
                {
                    data: 'id',
                    name: 'id',
                },

                {
                    data: 'details',
                    name: 'details',
                    orderable: false
                },
                {
                    data: 'cost',
                    name: 'cost',
                    orderable: false
                },
                {
                    data: 'type',
                    name: 'type',
                    orderable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false
                }

            ]
            , order: [
                [0, 'desc']
            ]
            , drawCallback: function (settings) {
                $('.record__select').prop('checked', false);
                $('#record__select-all').prop('checked', false);
                $('#record-ids').val();
                $('#bulk-delete').attr('disabled', true);
            }
        });

        $('#data_search').keyup(function () {
            rolesTable.search(this.value).draw();
        });


    </script>
@endpush

<!--begin::Events Card-->
<div class="card pt-4 mb-6 mb-xl-9">
    <!--begin::Card header-->
    <div class="card-header border-0">
        <!--begin::Card title-->
        <div class="card-title">
            <h2>{{ __('translation.events') }}</h2>
        </div>
        <!--end::Card title-->
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body py-0">
        <!--begin::Table wrapper-->
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="events-table">
                <!--begin::Table head-->
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th class="min-w-125px">{{ __('translation.id') }}</th>
                        <th class="min-w-125px">{{ __('translation.time') }}</th>
                        <th class="min-w-125px">{{ __('translation.events') }}</th>
                        <th class="">{{ __('translation.event_description') }}</th>
                    </tr>
                </thead>
                <!--end::Table head-->
            </table>
        </div>
        <!--end::Table wrapper-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Events Card-->

<!--begin::Attachments Card-->
<div class="card pt-4 mb-6 mb-xl-9">
    <!--begin::Card header-->
    <div class="card-header border-0">
        <!--begin::Card title-->
        <div class="card-title">
            <h2>{{ __('translation.attachments') }}</h2>
        </div>
        <!--end::Card title-->
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#add_attachment_modal">
                <i class="bi bi-plus-circle me-2"></i>{{ __('translation.add_attachment') }}
            </button>
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body py-0">
        <!--begin::Table wrapper-->
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="attachments-table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                        <th class="min-w-125px">{{ __('translation.file_name') }}</th>
                        <th class="min-w-125px">{{ __('translation.url_type') }}</th>
                        <th class="min-w-125px">{{ __('translation.type') }}</th>
                        <th class="min-w-100px">{{ __('translation.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
        <!--end::Table wrapper-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Attachments Card-->

<!--begin::Add Attachment Modal-->
<div class="modal fade" id="add_attachment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form class="form" action="{{ route('merchants.attachments.store', $merchant->id) }}" method="POST" enctype="multipart/form-data" id="add_attachment_form">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bolder">{{ __('translation.add_attachment') }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
                        <span class="svg-icon svg-icon-2x">&times;</span>
                    </div>
                </div>

                <div class="modal-body py-10 px-lg-17">
                    <div class="row mb-5">
                        <div class="col-md-12 fv-row">
                            <label class="required fs-6 fw-bold mb-2">{{ __('translation.type') }}</label>
                            <select class="form-select" name="type" required>
                                <option value="company_logo">{{ __('translation.company_logo') }}</option>
                                <option value="user_id">{{ __('translation.user_id_document') }}</option>
                                <option value="tax_certified">{{ __('translation.tax_certification') }}</option>
                                <option value="trade_license">{{ __('translation.trade_license') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-12 fv-row">
                            <label class="required fs-6 fw-bold mb-2">{{ __('translation.file') }}</label>
                            <input type="file" class="form-control" name="file" required accept="image/*,.pdf">
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-center">
                    <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                        {{ __('translation.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="indicator-label">{{ __('translation.submit') }}</span>
                        <span class="indicator-progress">
                            {{ __('translation.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Add Attachment Modal-->

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
            url: '{{ route("logs.userData", ["loggable_id" => $merchant->id]) }}',
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'time', name: 'time' },
            { data: 'action', name: 'action' },
            { data: 'text', name: 'text' }
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            $('.record__select').prop('checked', false);
            $('#record__select-all').prop('checked', false);
            $('#record-ids').val();
            $('#bulk-delete').attr('disabled', true);
        }
    });

    // Initialize Attachments DataTable
    let attachmentsTable = $('#attachments-table').DataTable({
        dom: "tiplr",
        serverSide: true,
        processing: true,
        language: {
            url: "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        },
        ajax: {
            url: '{{ route("merchants.attachments.index", $merchant->id) }}',
        },
        columns: [
            { data: 'file_name', name: 'file_name' },
            { data: 'url_type', name: 'url_type' },
            { data: 'type', name: 'type' },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <a href="${row.url}" class="btn btn-icon btn-light-primary btn-sm me-1" target="_blank">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-icon btn-light-danger btn-sm delete-attachment" data-id="${row.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    // Handle attachment form submission
    $('#add_attachment_form').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let submitButton = form.find('[type="submit"]');

        submitButton.attr('data-kt-indicator', 'on');
        submitButton.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success(response.message);
                $('#add_attachment_modal').modal('hide');
                attachmentsTable.ajax.reload();
                form[0].reset();
            },
            error: function(xhr) {
                let errors = xhr.responseJSON.errors;
                for (let key in errors) {
                    toastr.error(errors[key][0]);
                }
            },
            complete: function() {
                submitButton.attr('data-kt-indicator', 'off');
                submitButton.prop('disabled', false);
            }
        });
    });

    // Handle attachment deletion
    $(document).on('click', '.delete-attachment', function() {
        let id = $(this).data('id');
        Swal.fire({
            text: "{{ __('translation.are_you_sure_delete') }}",
            icon: "warning",
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonText: "{{ __('translation.yes_delete') }}",
            cancelButtonText: "{{ __('translation.no_cancel') }}",
            customClass: {
                confirmButton: "btn fw-bold btn-danger",
                cancelButton: "btn fw-bold btn-active-light-primary"
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('merchants.attachments.destroy', ['merchant' => $merchant->id, 'attachment' => '']) }}/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        toastr.success(response.message);
                        attachmentsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }
        });
    });

    // Search functionality
    $('#data_search').keyup(function() {
        eventsTable.search(this.value).draw();
    });
});
</script>
@endpush

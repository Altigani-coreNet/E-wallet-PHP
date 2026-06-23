@extends('layouts.admin.admin_layout')
@section('main-head', __('translation.payment_links'))

@section('breadcrumbs')
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.payment_links') }}</li>
    </ul>
@endsection

@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <button id="filters_button" class="btn btn-sm btn-flex btn-secondary fw-bold">
        <i class="ki-duotone ki-filter fs-6 text-muted me-1" id="filter-icon">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>{{ __('translation.toggle_filters') }}</button>
    <button type="button" class="btn btn-sm fw-bold btn-success" id="export-filtered">
        <i class="ki-duotone ki-download fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.export_filtered') }}
    </button>
    <a href='{{ route('admin.payment-links.create') }}' class="btn btn-sm fw-bold btn-primary">
        <i class="ki-duotone ki-plus fs-3">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ __('translation.add_payment_link') }}
    </a>
</div>
@endsection

@section('content')
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="row g-5 g-xl-8 mt-4"></div>

        <!--begin::Filters Card-->
        <div class="card bg-white card-xl-stretch mb-5 mb-xl-8" id="filters-body" style="display: none;">
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
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.search') }}</label>
                        <input type="text" class="form-control" id="search-input" placeholder="{{ __('translation.search') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.merchant') }}</label>
                        <select class="form-select" id="merchant-filter" name="merchant_id">
                            <option value="">{{ __('translation.all') }}</option>
                            @foreach(\App\Models\Merchant::select('id','name')->orderBy('name')->get() as $merchant)
                                <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">{{ __('translation.country') }}</label>
                        <select class="form-select" id="country-filter" name="country_id">
                            <option value="">{{ __('translation.all') }}</option>
                            @foreach(\App\Models\Country::select('id','name')->orderBy('name')->get() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('translation.created_date_from') }}</label>
                        <input type="date" class="form-control" id="created-from">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('translation.created_date_to') }}</label>
                        <input type="date" class="form-control" id="created-to">
                    </div>
                   
                </div>
            </div>
        </div>
        <!--end::Filters Card-->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="card-label">{{ __('translation.payment_links_list') }}</h3>
                </div>
                {{-- <div class="card-toolbar">
                    <a href="{{ route('admin.payment-links.create') }}" class="btn btn-primary">
                        <i class="ki-duotone ki-plus fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('translation.add_payment_link') }}
                    </a>
                </div> --}}
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="payment-links-table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th>{{ __('translation.id') }}</th>
                                <th>{{ __('translation.uuid') }}</th>
                                <th>{{ __('translation.merchant') }}</th>
                                <th>{{ __('translation.customer') }}</th>
                                <th>{{ __('translation.amount') }}</th>
                                <th>{{ __('translation.currency') }}</th>
                                <th>{{ __('translation.status') }}</th>
                                <th>{{ __('translation.created_at') }}</th>
                                <th>{{ __('translation.scheduled_date') }}</th>
                                @if(!Auth::guard('admin')->user()->custom_region)
                                <th>{{ __('translation.country') }}</th>
                                @endif
                                <th class="text-end">{{ __('translation.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Payment links will be loaded here via DataTable or backend --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="reschedule-form">
        <div class="modal-header">
          <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Payment Link</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="payment_link_id" id="reschedule-payment-link-id">
          <div class="mb-3">
            <label for="reschedule-date" class="form-label">New Scheduled Date</label>
            <input type="date" class="form-control" id="reschedule-date" name="scheduled_date" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="send-form">
        <div class="modal-header">
          <h5 class="modal-title" id="sendModalLabel">Send Payment Link</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="payment_link_id" id="send-payment-link-id">
          <input type="hidden" name="payment_link_url" id="send-payment-link-url">
          <div class="mb-3">
            <label class="form-label">Send via:</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="send-email" name="send_email" value="1">
              <label class="form-check-label" for="send-email">Email</label>
            </div>
            <div class="form-check form-check mt-1">
              <input class="form-check-input" type="checkbox" id="send-whatsapp" name="send_whatsapp" value="1">
              <label class="form-check-label" for="send-whatsapp">WhatsApp</label>
            </div>

            <div class="form-check form-check mt-1">
                <input class="form-check-input" type="checkbox" id="send-sms" name="send_sms" value="1">
                <label class="form-check-label" for="send-sms">Sms</label>
              </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Send</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    let search = '', merchantId = '', countryId = '', createdFrom = '', createdTo = '', updatedFrom = '', updatedTo = '';
    let paymentLinksTable = $('#payment-links-table').DataTable({
        dom: "tiplr",
        serverSide: true,
        processing: true,
        autoWidth: false,
        scrollX: true,
        language: {
            url: "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        },
        ajax: {
            url: '{{ route('admin.payment-links.data') }}',
            data: function (q) {
                q.search = search;
                q.merchant_id = merchantId;
                q.country_id = countryId;
                q.created_from = createdFrom;
                q.created_to = createdTo;
                q.updated_from = updatedFrom;
                q.updated_to = updatedTo;
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'uuid', name: 'uuid' },
            { data: 'merchant', name: 'merchant' },
            { data: 'customer', name: 'customer' },
            { data: 'amount', name: 'amount' },
            { data: 'currency', name: 'currency' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'scheduled_date', name: 'scheduled_date' },
            @if(!Auth::guard('admin')->user()->custom_region)
            { data: 'country', name: 'country' },
            @endif
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        drawCallback: function (settings) {
            $('.record__select').prop('checked', false);
            $('#record__select-all').prop('checked', false);
            $('#record-ids').val('');
            $('#bulk-delete').attr('disabled', true);

              // ✅ Re-initialize KTMenu dropdowns here
              if (typeof KTMenu !== 'undefined' && typeof KTMenu.createInstances === 'function') {
                KTMenu.createInstances();
            }
        },
        order: [[0, 'desc']]
    });

    // Toggle filters section
    $('#filters_button').on('click', function () {
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        if (filtersBody.is(':visible')) {
            filtersBody.slideUp(300);
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('paymentLinksFiltersCollapsed', 'true');
        } else {
            filtersBody.slideDown(300);
            filterIcon.css('transform', 'rotate(0deg)');
            localStorage.setItem('paymentLinksFiltersCollapsed', 'false');
        }
    });

    $(document).ready(function () {
        const isCollapsed = localStorage.getItem('paymentLinksFiltersCollapsed');
        const filtersBody = $('#filters-body');
        const filterIcon = $('#filter-icon');
        if (isCollapsed === null || isCollapsed === 'true') {
            filtersBody.hide();
            filterIcon.css('transform', 'rotate(90deg)');
            localStorage.setItem('paymentLinksFiltersCollapsed', 'true');
        }
        if ($.fn.select2) {
            $('#merchant-filter').select2({
                placeholder: '{{ __("translation.select_merchant") }}',
                allowClear: true,
                width: '100%'
            });
            $('#country-filter').select2({
                placeholder: '{{ __("translation.select_country") }}',
                allowClear: true,
                width: '100%'
            });
        }
    });

    // Filter handlers
    $('#search-input').on('keyup', function () { search = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#merchant-filter').on('change', function () { merchantId = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#country-filter').on('change', function () { countryId = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#created-from').on('change', function () { createdFrom = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#created-to').on('change', function () { createdTo = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#updated-from').on('change', function () { updatedFrom = $(this).val(); paymentLinksTable.ajax.reload(); });
    $('#updated-to').on('change', function () { updatedTo = $(this).val(); paymentLinksTable.ajax.reload(); });

    // Clear filters
    $('#clear-filters').on('click', function () {
        search = merchantId = countryId = createdFrom = createdTo = updatedFrom = updatedTo = '';
        $('#search-input').val('');
        $('#merchant-filter').val('');
        $('#country-filter').val('');
        if ($.fn.select2) {
            $('#merchant-filter').val('').trigger('change.select2');
            $('#country-filter').val('').trigger('change.select2');
        }
        $('#created-from').val('');
        $('#created-to').val('');
        if ($('#updated-from').length) $('#updated-from').val('');
        if ($('#updated-to').length) $('#updated-to').val('');
        paymentLinksTable.ajax.reload();
    });

    // Export filtered
    $('#export-filtered').on('click', function () {
        const params = new URLSearchParams({
            search: search || '',
            merchant_id: merchantId || '',
            country_id: countryId || '',
            created_from: createdFrom || '',
            created_to: createdTo || '',
            updated_from: updatedFrom || '',
            updated_to: updatedTo || ''
        });
        window.open('{{ route('admin.payment-links.export') }}?' + params.toString(), '_blank');
    });

$(document).on('click', '.reschedule-action', function(e) {
    e.preventDefault();
    var paymentLinkId = $(this).data('id');
    $('#reschedule-payment-link-id').val(paymentLinkId);
    $('#rescheduleModal').modal('show');
});

$('#reschedule-form').on('submit', function(e) {
    e.preventDefault();
    var paymentLinkId = $('#reschedule-payment-link-id').val();
    var scheduledDate = $('#reschedule-date').val();
    $.ajax({
        url: '/admin/payment-links/' + paymentLinkId + '/update-date',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            scheduled_date: scheduledDate
        },
        success: function(response) {
            $('#rescheduleModal').modal('hide');
            paymentLinksTable.ajax.reload();
        },
        error: function(xhr) {
            alert('Failed to reschedule.');
        }
    });
});

$(document).on('click', '.send-action', function(e) {
    e.preventDefault();
    var paymentLinkId = $(this).data('id');
    var paymentLinkUrl = $(this).data('link');
    $('#send-payment-link-id').val(paymentLinkId);
    $('#send-payment-link-url').val(paymentLinkUrl);
    $('#send-email').prop('checked', false);
    $('#send-whatsapp').prop('checked', false);
    $('#sendModal').modal('show');
});

$('#send-form').on('submit', function(e) {
    e.preventDefault();
    var paymentLinkId = $('#send-payment-link-id').val();
    var sendEmail = $('#send-email').is(':checked') ? 1 : 0;
    var sendWhatsapp = $('#send-whatsapp').is(':checked') ? 1 : 0;
    if (!sendEmail && !sendWhatsapp) {
        alert('Please select at least one option.');
        return;
    }
    $.ajax({
        url: '/admin/payment-links/' + paymentLinkId + '/send',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            send_email: sendEmail,
            send_whatsapp: sendWhatsapp
        },
        success: function(response) {
            $('#sendModal').modal('hide');
            swal({
                title: 'Success',
                text: response.message,
                icon: 'success'
            });
        },
        error: function(xhr) {
            let msg = 'Failed to send.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            swal({
                title: 'Error',
                text: msg,
                icon: 'error'
            });
        }
    });
});
</script>
@endpush
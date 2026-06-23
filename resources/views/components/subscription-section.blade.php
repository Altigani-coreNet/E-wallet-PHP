<div class="card mb-5 mb-xl-10">
    {{-- @dd($user->subscripion_record_count) --}}
    @if(count($subscription)  > 0 || $user->subscripion_record_count > 0)
        {{-- @dd($subscription) --}}
        <!--begin::Card body-->
        @if (count($subscription)  > 0)
        <div class="card-body">
            <!--begin::Notice-->
            @if($subscription["on_trails"])
                <div
                    class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-12 p-6">
                    <!--begin::Icon-->
                    <!--begin::Svg Icon | path: icons/duotune/general/gen044.svg-->
                    <span
                        class="svg-icon svg-icon-2tx svg-icon-warning me-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                 viewBox="0 0 24 24" fill="none">
                <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10"
                      fill="black"></rect>
                <rect x="11" y="14" width="7" height="2" rx="1"
                      transform="rotate(-90 11 14)" fill="black"></rect>
                <rect x="11" y="17" width="2" height="2" rx="1"
                      transform="rotate(-90 11 17)" fill="black"></rect>
            </svg>
        </span>
                    <!--end::Svg Icon-->
                    <!--end::Icon-->
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-grow-1">
                        <!--begin::Content-->
                        <div class="fw-bold">
                            <h4 class="text-gray-900 fw-bolder">{{___("translation.this_user_dose_not_have_subscription")}}
                                !</h4>
                            <div
                                class="fs-6 text-gray-700">{{___('translation.You_can_send_a_reminder_email_to_the_company_to_recommend_that_I_subscribe_to_the_paid_plans')}}
                                <a href="{{route('payments.link', $user->id)}}"
                                   data-bs-target="#kt_modal_new_card">{{___('translation.send_reminder_mail')}}</a>.
                            </div>
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
            @endif
            <!--end::Notice-->
            <!--begin::Row-->
            <div class="row">
                <!--begin::Col-->
                <div class="col-lg-7">
                    <!--begin::Heading-->


                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label
                            class="col-lg-4 fw-bold text-muted">{{___('translation.plan_name')}}</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
        <span
            class="fw-bolder fs-6 text-gray-800">{{$subscription["plan"]->name}}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label
                            class="col-lg-4 fw-bold text-muted">{{___('translation.subscription_Status')}}</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            {{--                                                            @dd($subscription["text_status"] )--}}
                            @if($subscription["text_status"] == "active")
                                <span
                                    class="badge badge-success mx-2"> {{___('translation.active_subscription')}} </span>
                            @elseif($subscription["text_status"] == "canceled")
                                <span
                                    class="badge badge-danger mx-2 "> {{___('translation.canceld_subscription')}}</span>
                            @else
                                <span
                                    class="badge badge-danger mx-2 "> {{___('translation.subscription_expired')}}</span>
                            @endif

                            @if($subscription["on_trails"])
                                <span
                                    class="badge badge-light-warning mx-2"> {{___('translation.free_trails_subscription')}}</span>
                            @endif
                        </div>
                        <!--end::Col-->
                    </div>
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label
                            class="col-lg-4 fw-bold text-muted">{{___('translation.active_until')}}</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
        <span
            class="fw-bolder fs-6 text-gray-800">  {{{___('translation.active_until')}}} {{$subscription["end_date"]}} </span>
                        </div>
                        <!--end::Col-->
                    </div>

                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label
                            class="col-lg-4 fw-bold text-muted">{{___('translation.plan_price')}}</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                <span
                    class="badge badge-light-primary">{{$subscription["plan"]->price }} {{___('translation.AED')}} </span> </span>
                            </span>
                        </div>
                        <!--end::Col-->
                    </div>

                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label
                            class="col-lg-4 fw-bold text-muted">{{___('translation.subscription_plan_type')}}</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
        <span
            class="fw-bolder fs-6 text-gray-800">{{$subscription["plan"]->plan_type}}</span>
                        </div>
                        <!--end::Col-->
                    </div>

                    <h3 class="mb-2">

                    </h3>

                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-lg-5">
                    <!--begin::Heading-->
                    <div class="row gx-9 gy-6">
                        @foreach ($user->paymentMethods() as $paymentMethod)
                            <div class="col-xl-12 mb-5">
                                <!--begin::Card-->
                                <div
                                    class="card card-dashed h-xl-100 flex-row flex-stack flex-wrap p-6">
                                    <!--begin::Info-->
                                    <div
                                        class="d-flex flex-column py-2">
                                        <!--begin::Owner-->
                                        <div
                                            class="d-flex align-items-center fs-4 fw-bolder mb-5">
                                            {{ $user->name }} <!-- Display cardholder's name -->
                                            @if ($paymentMethod->id === $user->defaultPaymentMethod()->id)
                                                <span
                                                    class="badge badge-light-success fs-7 ms-2">Primary</span>
                                            @endif
                                        </div>
                                        <!--end::Owner-->
                                        <!--begin::Wrapper-->
                                        <div
                                            class="d-flex align-items-center">
                                            <!--begin::Icon-->
                                            @if ($paymentMethod->card->brand == 'visa')
                                                <img
                                                    src="{{asset('assets/media/svg/card-logos/visa.svg')}}"
                                                    alt="" class="me-4">
                                            @elseif ($paymentMethod->card->brand == 'mastercard')
                                                <img
                                                    src="{{asset('assets/media/svg/card-logos/mastercard.svg')}}"
                                                    alt="" class="me-4">
                                            @elseif ($paymentMethod->card->brand == 'american_express')
                                                <img
                                                    src="{{asset('assets/media/svg/card-logos/american-express.svg')}}"
                                                    alt="" class="me-4">
                                            @endif
                                            <!--end::Icon-->
                                            <!--begin::Details-->
                                            <div>
                                                <div
                                                    class="fs-4 fw-bolder">{{ ucfirst($paymentMethod->card->brand) }}
                                                    **** {{ $paymentMethod->card->last4 }}</div>
                                                <div
                                                    class="fs-6 fw-bold text-gray-400">
                                                    Card
                                                    expires
                                                    at {{ $paymentMethod->card->exp_month }}
                                                    /{{ $paymentMethod->card->exp_year }}</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Info-->
                                    <!--begin::Actions-->
                                    {{--                                                        <div class="d-flex align-items-center py-2">--}}
                                    {{--                                                            <form--}}
                                    {{--                                                                action="{{ "#"  }}"--}}
                                    {{--                                                                method="POST">--}}
                                    {{--                                                                @csrf--}}
                                    {{--                                                                @method('DELETE')--}}
                                    {{--                                                                <button type="submit"--}}
                                    {{--                                                                        class="btn btn-sm btn-light btn-active-light-primary me-3">--}}
                                    {{--                                                                    Delete--}}
                                    {{--                                                                </button>--}}
                                    {{--                                                            </form>--}}
                                    {{--                                                            <button--}}
                                    {{--                                                                class="btn btn-sm btn-light btn-active-light-primary"--}}
                                    {{--                                                                data-bs-toggle="modal"--}}
                                    {{--                                                                data-bs-target="#kt_modal_new_card">Edit--}}
                                    {{--                                                            </button>--}}
                                    {{--                                                        </div>--}}
                                    <!--end::Actions-->
                                </div>
                                <!--end::Card-->
                            </div>
                        @endforeach
                    </div>

                    @if($user->subscriptions()->exists())
                        <!--begin::Action-->
                        <div
                            class="d-flex justify-content-end pb-0 px-0">
                            <form
                                action="{{ route('subscriptions.destroy', $user->subscription()->id) }}"
                                class="my-1 my-xl-0" method="post"
                                style="display: inline-block;">
                                @csrf
                                @method('delete')
                                <button type="submit"
                                        class="btn btn-light-danger  delete  me-1 ">
<span class="svg-icon svg-icon-3">
{{___('translation.cancel_subscription')}}
</span>
                                </button>
                            </form>
                            <a href="{{route('subscriptions.show', $user->subscription()->id)}}"
                               class="btn btn-primary">PDF
                            </a>

                        </div>
                    @endif
                    <!--end::Action-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        @elseif ($user->LatestSubscription && $user->LatestSubscription->type != 'STRIPE')
        @endif
</div>
<!--end::Card body-->
<div class="card pt-4 mb-6 mb-xl-9">
    <!--begin::Card header-->
    <div class="card-header border-0">
        <!--begin::Card title-->
        <div class="card-title">
            <h2>Subscriptions</h2>
        </div>
        <div class="card-toolbar">
            <a href="#" data-bs-toggle="modal"
               data-bs-target="#create_subscription_contract"
               class="btn btn-primary">{{___('translation.Attach_Contract')}}
            </a>
        {{-- <div class="menu-item px-3"> --}}
            <a href="#"
               data-bs-toggle="modal"
               data-bs-target="#create_free_trails"
               class="btn btn-primary mx-2">{{___('translation.add_manwal_payments')}}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5"
                   id="subscription-table">
                <!--begin::Table head-->
                <thead>
                <!--begin::Table row-->
                <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                    <th class="min-w-125px">{{ ___('translation.id') }}</th>
                    <th class="">{{ ___('translation.name') }}</th>
                    <th class="">{{ ___('translation.subscription_plan') }}</th>
                    <th class="">{{ ___('translation.subscription_type') }}</th>
                    <th class="">{{ ___('translation.status') }}</th>
                    <th class="">{{ ___('translation.date') }}</th>
                    <th class="">{{ ___('translation.end_date') }}</th>
                    <th class="text-center ">{{ ___('translation.action') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<div class="card pt-4 mb-6 mb-xl-9">
    <!--begin::Card header-->
    <div class="card-header border-0">
        <!--begin::Card title-->
        <div class="card-title">
            <h2>{{___('translation.attachments_and_contracts')}}</h2>
        </div>

    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5"
                   id="attachments-table">
                <!--begin::Table head-->
                <thead>
                <!--begin::Table row-->
                <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                    <th class="min-w-125px">{{ __('translation.attachment') }}</th>
                    <th class="">{{ ___('translation.attachment_name') }}</th>
                    <th class="min-w-125px">{{ ___('translation.attachment_type') }}</th>
                    <th class="">{{ ___('translation.created_at') }}</th>
                    <th class="text-end ">{{ __('translation.action') }}</th>
                </tr>
                <!--end::Table row-->
                </thead>
                <!--end::Table body-->
            </table>
        </div>
    </div>
</div>
@else
<div class="card">
    <!--begin::Card body-->
    <div class="card-body">
        <!--begin::Heading-->
        <div class="card-px text-center pt-15 pb-15">
            <!--begin::Title-->
            <!--end::Title-->
            <!--begin::Description-->
            @if(auth()->guard('admin')->check())
                <h2 class="fs-2x fw-bolder mb-0">{{___('translation.this_company_dose_not_have_subscription')}}</h2>

                <p class="text-gray-400 fs-4 fw-bold py-7">{{___("translation.click_on_the_button_to_add_payments_for_company")}}</p>
                <!--end::Description-->
                <!--begin::Action-->
                <a href="#"
                   class="btn btn-light-success er fs-6 px-8 py-4"
                   data-bs-toggle="modal"
                   data-bs-target="#create_free_trails">{{___('translation.Add_Manwal_Payments')}}</a>
            @else
                <h2 class="fs-2x fw-bolder mb-0">{{___('translation.you_dont_have_subscription')}}</h2>
            @endif

            <!--end::Action-->
        </div>
        <!--end::Heading-->
        <!--begin::Illustration-->

        <!--end::Illustration-->
    </div>
    <!--end::Card body-->
</div>

@endif

</div>

@push('scripts')
<script>

    const urlParams = new URLSearchParams(window.location.search);
    const typeParam = urlParams.get('type');
    const parentParam = urlParams.get('parent');

    let rolesTable = $('#subscription-table').DataTable({
        dom: "tiplr"
        , serverSide: true
        , processing: true
        , "language": {
            "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
        }
        , ajax: {
            url: '{{ route('subscriptions.data', ["user_id" => $user->id])}}',
            data: function (d) {
                // Check if the 'type' parameter exists in the URL
                if (typeParam) {
                    d.type = typeParam; // Adding the type parameter to the request
                }
                if (parentParam) {
                    d.parent = parentParam; // Adding the type parameter to the request
                }
            }
        }
        , columns: [
            {
                data: 'id'
                , name: 'id'
            },

            {
                data: 'user_name'
                , name: 'user_name'
                , searchable: false
                , sortable: false
            },

            {
                data: 'plan_name'
                , name: 'plan_name'
                , searchable: false
                , sortable: false
            },
            {
                data: 'type'
                , name: 'type'
                , searchable: false
                , sortable: false
            },
            {
                data: 'status'
                , name: 'status'
                , searchable: false
                , sortable: false
            },
            {
                data: 'created_at'
                , name: 'created_at'
                , searchable: false
                , sortable: false
            },
            {
            data: 'end_date',
            name: 'end_date'
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
<script>
    // Get the value of the 'tab' parameter

    // Define the columns array
    let columns = [
        {
            data: 'attachment',
            name: 'attachment'
        },
        {
            data: 'url_type',
            name: 'url_type'
        },
        {
            data: 'type',
            name: 'type'
        },
        {
            data: 'created_at',
            name: 'created_at'
        },

        {
            data: 'actions',
            name: 'actions',
            searchable: false,
            sortable: false,
            width: '20%'
        }
    ];

    // Check if the tab is 'awards' and modify columns if needed

    let rolesTable2 = $('#attachments-table').DataTable({
            dom: "tiplr"
            , serverSide: true
            , processing: true
            , "language": {
                "url": "{{ asset('admin_assets/datatable-lang/' . app()->getLocale() . '.json') }}"
            }
            , ajax: {
                url: '{{ route("attachments.data", ["attachable_id" => $user->Profile->id])}}',
                data: function (query) {
                    // If tab is 'certificate', only append attachable_type
                    query.attachable_type = @json(\App\Models\Profile::class);
                    query.url_type = "subscription_contract";
                }
            }
            ,
            columns: columns
            , order:
                [
                    [1, 'desc']
                ]
            , drawCallback:

                function (settings) {
                    $('.record__select').prop('checked', false);
                    $('#record__select-all').prop('checked', false);
                    $('#record-ids').val();
                    $('#bulk-delete').attr('disabled', true);
                }
        })
    ;

    $('#data_search').keyup(function () {
        rolesTable2.search(this.value).draw();
    });

</script>
@endpush
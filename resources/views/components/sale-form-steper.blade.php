
<div>
    <!-- Simplicity is an acquired taste. - Katharine Gerould -->
    <div class="stepper stepper-pills between" id="kt_stepper_example_basic" data-kt-stepper="true">
        <!--begin::Nav-->
        <div class="stepper-nav flex-center flex-wrap mb-10">
            <!--begin::Step 1-->
            <div class="stepper-item mx-2 my-4 current" data-kt-stepper-element="nav">
                <!--begin::Line-->
                <div class="stepper-line w-40px"></div>
                <!--end::Line-->
                <!--begin::Icon-->
                <div class="stepper-icon w-40px h-40px">
                    <i class="stepper-check fas fa-check"></i>
                    <span class="stepper-number">1</span>
                </div>
                <!--end::Icon-->
                <!--begin::Label-->
                <div class="stepper-label">
                    <h3 class="stepper-title">{{__('translation.realstate_info')}}</h3>
                    <div class="stepper-desc">{{__('translation.insert_real_State_data')}}</div>
                </div>
                <!--end::Label-->
            </div>
            <!--end::Step 1-->
            <!--begin::Step 2-->
            <div class="stepper-item mx-2 my-4 " data-kt-stepper-element="nav">
                <!--begin::Line-->
                <div class="stepper-line w-40px"></div>
                <!--end::Line-->
                <!--begin::Icon-->
                <div class="stepper-icon w-40px h-40px">
                    <i class="stepper-check fas fa-check"></i>
                    <span class="stepper-number">2</span>
                </div>
                <!--begin::Icon-->
                <!--begin::Label-->
                <div class="stepper-label">
                    <h3 class="stepper-title">{{__('translation.realstate_aqsat')}}</h3>
                    <div class="stepper-desc">{{__('translation.realstate_aqsat_desc')}}</div>
                </div>
                <!--end::Label-->
            </div>
        </div>
        <!--end::Nav-->
        <!--begin::Form-->
        <form class="form w-lg-500px mx-auto" novalidate="novalidate">
            <!--begin::Group-->
            <div class="mb-5">
                <!--begin::Step 1-->
                <div class="flex-column current" data-kt-stepper-element="content">
                    <div class="row">
                        <input type='hidden' name='type' value={{request()->type}} />
                        <x:text-input name='title' class='col-md-6' />
                        <x:text-input name='realstate_number' class='col-md-6' />
                        <x:text-input name='address' class='col-md-6' />
                        <x:text-input name='price' class='col-md-6' />
                        <x:select-options name='category_idd' :options='$categories'  class='col-md-6' />
                        <x:select-options name='status' :options='["ready" , "inready"]'  class='col-md-6' />
                        {{-- <x:text-input name='identification_number' class='col-md-6' /> --}}
                        <x:input-file name='attachments[]' class='col-md-6'/>
                        <x:text-area name='description' class='col-md-6' />
                    </div>
                </div>
                <!--begin::Step 1-->
                <!--begin::Step 1-->
                <div class="flex-column " data-kt-stepper-element="content">
                                                            <!--begin::Repeater-->
                    <div class="">

                        <div id="installment">
                            <!--begin::Form group-->
                            <div class="form-group">
                                <div data-repeater-list="installment">
                                    <div data-repeater-item="">
                                        <div class="form-group row mb-5">
                                            <div class="col-md-3">
                                                <label class="form-label">{{__('translation.precentage')}}:</label>
                                                <input type="number" max='100'  name='precentage'  class="form-control mb-2 mb-md-0 precentage_value"
                                                placeholder="Enter full name">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">{{__('translation.aqsat_amount')}}:</label>
                                                <input type="number"  name='amount'  class="form-control mb-2 mb-md-0 precentage_amount" placeholder="Enter contact number">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">{{__('translation.aqsat_date')}}:</label>
                                                <input type="date" name='date' rendonly class="form-control mb-2 mb-md-0" placeholder="Enter contact number">
                                            </div>
                                            <div class="col-md-2">
                                                <a href="javascript:;" data-repeater-delete="" class="btn btn-sm btn-light-danger mt-3 mt-md-9">
                                                <i class="la la-trash-o fs-3"></i>{{ __('translation.delete') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end::Form group-->
                            <!--begin::Form group-->
                            <div class="form-group">
                                <a href="javascript:;" data-repeater-create="" class="btn btn-light-primary">
                                <i class="la la-plus"></i>{{__('translation.add_installment')}}</a>
                            </div>
                            <!--end::Form group-->
                        </div>
                    </div>
<!--end::Repeater-->
                </div>
            </div>
            <!--end::Group-->
            <!--begin::Actions-->
            <div class="d-flex flex-stack">
                <!--begin::Wrapper-->
                <div class="me-2">
                    <a href='#' type="button" class="btn btn-light btn-active-light-primary" data-kt-stepper-action="previous">{{__('translation.back')}}</a>
                </div>
                <!--end::Wrapper-->
                <!--begin::Wrapper-->
                <div>
                    <button type="button"
                    onclick="document.forms.save_realstate.submit();"
                    class="btn btn-primary" data-kt-stepper-action="submit">
                        <span class="indicator-label" type='submit'

                        >{{__('translation.save')}}</span>
                        <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                    <a href="#" type="button" class="btn btn-primary" data-kt-stepper-action="next">{{__('translation.next')}}</a>
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Actions-->
        </form>
        <!--end::Form-->
    </div>
</div>
@push('scripts')
<script src="{{ asset('datatable/jquery.js') }}"></script>
<script src="{{asset('assets/plugins/custom/formrepeater/formrepeater.bundle.js')}}"></script>

<script>
jQuery = $;
var element = document.querySelector("#kt_stepper_example_basic");
var const_price_value;

// Initialize Stepper
var stepper = new KTStepper(element);

// Handle next step
stepper.on("kt.stepper.next", function (stepper) {
    value = stepper.goNext(); // go next step
    console.log(value);
});

// Handle previous step
stepper.on("kt.stepper.previous", function (stepper) {
    stepper.goPrevious(); // go previous step
});


$('#installment').repeater({
    initEmpty: false,
    defaultValues: {
        'text-input': 'foo'
    },
    show: function () {
        $(this).slideDown();
        eventHandler();
    },
    hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
        eventHandler();
    }
});

let eventHandler =() => ( $('.precentage_value').on('change keyup keydown',function(){

$('.precentage_value').each((index , el) =>{
    // console.log(el, index);
    price_value = $('input[name="price"]').val();

    if($(this).val() <= 100){
        let precentage  = price_value * $(this).val() / 100 ;
        $(el).on('keydown' , function(){
            $($('.precentage_amount')[index]).val(precentage);
            console.log('Hello Form Hell', index , $(this).val());
        });
    }
    // console.log('hello');
});

}));
eventHandler();
$('input[name="price"]').change(function(){
    const_price_value = $(this).val();
})
</script>
@endpush

<div>
    <div class="d-flex justify-content-between">
        <h3 class='p-2'>
            {{__('translation.owner_and_real_state_history')}}
        </h3>
        <div class="btn-group">
            <div class="" style='display:inline ;margin:3px'>
                <a href="{{route('realstate.assignOwner' , $realstate->id)}}"
                class="btn btn-primary btn-sm">
                    {{__('translation.assing_new_owner')}}
                </a>
            </div>
            <form style='display:inline ;margin:3px'
            action='{{route('realstate.FinshRent')}}'
            method='post'
            id='FinshForm'
            onsubmit="(e) => e.preventDefault()"
            >
            @csrf
                <input  type='hidden' name='real_state_id' value='{{$realstate->id}}'/>
                <button
                id='myAnchor'
                 class="btn btn-danger btn-sm"
                 >
                 {{__('translation.finsh_the_currnt_rent')}}
                </button>
            </form>
        </div>
    </div>
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="">
        <!--begin::Table head-->

        <thead>
            <!--begin::Table row-->
            <tr class=" text-gray-400 fw-bolder fs-7 text-uppercase ">
                <th class="">{{ __('translation.name') }}</th>
                <th class="">{{ __('translation.phone') }}</th>
                <th class="">{{ __('translation.month_count') }}</th>
                <th class="">{{ __('translation.rent_status') }}</th>
                <th class="">{{ __('translation.from_date') }}</th>
                <th class="">{{ __('translation.to_date') }}</th>
            </tr>
            <!--end::Table row-->
        </thead>
        <!--end::Table head-->
        <!--begin::Table body-->
    <tbody class="fw-bold text-gray-600">
        @forelse ($realstates as $item)
               <tr>

                    <td> {{ $item->name }}</td>
                    <td> {{ $item->phone }}</td>
                    <td> {{ $item->pivot->month_count }}</td>
                    <td>
                        @if ($item->pivot->rent_status)
                        <span class='badge badge-light-success'>{{__('translation.underrent')}} </span>
                        @else
                        <span class='badge badge-light-danger'>{{__('translation.finshed')}} </span>
                            @endif
                    </td>
                    <td> {{$item->created_at->format('y-m-d')}}</td>
                    <td> {{$item->updated_at->format('y-m-d')}}</td>
                </tr>
                @empty
                <tr>
                    <td collspan='12'>
                        {{__('translation.no_data_found')}}
                    </td>
                </tr>
                @endforelse

        </tbody>
    </table>
</div>
@push('scripts')
<script>
document.getElementById("myAnchor").addEventListener("click", function(event){
  event.preventDefault();
  ConfirmApp();
});
    function ConfirmApp(val) {
        Swal.fire({
            title: " @lang('هل أنت واثق؟')",
            text: " @lang('لن تتمكن من التراجع عن هذا!')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: " @lang('نعم')",
            cancelButtonText: " @lang('إلغاء')"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    " @lang('تم العمليه')",
                    " @lang('تم انها العمليه بنجاح.')",
                    " @lang('النجاح')"
                );

                document.getElementById("FinshForm").submit();
            }
        });
    }
</script>

@endpush



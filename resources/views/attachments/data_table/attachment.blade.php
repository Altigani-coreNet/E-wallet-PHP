<div style="text-align: center">

    {{-- Check if the file is an image --}}
    @if($attachment->type == "image")
        {{-- Show image preview --}}
        <img src="{{ asset($attachment->url) }}" alt="Image" style="width: 70px; height: 70px;"/>
        {{-- Show document icon --}}
    @else
        <div class="symbol symbol-60px mb-5">
            <img src="{{asset("assets/media/svg/files/pdf.svg")}}" alt="">
        </div> {{-- FontAwesome icon for document --}}
    @endif
</div>

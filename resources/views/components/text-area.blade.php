<div class='{{ $class }} mb-5' id='kt_dropzonejs_example_1'>
    <label class="required fs-6 fw-bold mb-2" for="{{$filedName}}">{{ ___('translation.' . $name) }}</label>
    <textarea name="{{ $filedName }}" id="" cols="15" rows="5"
              class="form-control form-control-solid">{{ $value ?? (old($filedName) ?? '') }}</textarea>
    @error($filedName)
    <span class="text-danger">
            {{ $message }}
        </span>
    @enderror
</div>

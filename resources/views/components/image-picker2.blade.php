<div class="fv-row mb-7 {{$class}} {{!$hidden ?: 'd-none'}}">
    <label class="required fs-6 fw-bold mb-2">{{ ___('translation.' .$name) }}</label>
    <input type="file"
           class="fileponds form-control form-control-solid"
           name="{{$filedName}}"
           multiple
           id="{{$name}}"
           data-max-file-size="3MB"
           data-max-files="10"
           {{--           @dd(asset($value))--}}
           {{$value ? 'data-value-url=' . rawurlencode(asset($value)) : ''}}
           data-real-file-id="{{$realFiledId}}"
        @disabled($disabled)
    />
    {{--           value="{{ ( $value ?? old($name) )}}" {{!$hidden ?: 'hidden'}}--}}

    @error($filedName)
    <span class="text-danger">
                {{$message}}
            </span>
    @enderror
</div>

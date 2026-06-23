<div class="fv-row mb-7 {{$class}} {{!$hidden ?: 'd-none'}}">
    <label class=" fs-6 fw-bold mb-2" for="{{$filedname}}">{{ ___('translation.' .$name) }}</label>
    <input type="date"
           class="form-control form-control-solid"
           placeholder="" name="{{$filedname}}"
           value="{{ ( $value ?? old($name) )}}" {{!$hidden ?: 'hidden'}}
        @disabled($disabled)
    />
    @error($filedname)
    <span class="text-danger">
                {{$message}}
            </span>
    @enderror
</div>

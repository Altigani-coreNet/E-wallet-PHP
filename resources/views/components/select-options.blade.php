<div class='{{$class}}'>
    <div class="form-group">
        <label for="" class="fs-6 fw-bold mb-2"> {{ __('translation.' . $name) }} </label>
        <select class="form-control" name="{{ $filedName }}" id="{{$filedName}}">
            {{--            @dd()--}}
            @foreach ($options as $option)
                <option value="{{ is_string($option) ? $option : $option->id  }}"

                        @if ($option instanceof stdClass && !empty($value) && $option->name == $value)
                            selected
                        @endif
                        @if (!empty($value) &&  is_string($value) && $option == $value)
                            selected
                    @endif
                >
                    {{is_string($option) ? __('translation.' . $option) : $option->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

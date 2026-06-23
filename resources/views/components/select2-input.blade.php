<div class="{{$class}}">
    <div class="form-group">
        <label class="fs-6 fw-bold mb-2" for="{{$filedName}}"> {{ __('translation.' . $name) }} </label>
        <select class="form-select has_select_2"
                name="{{$filedName}}"
                data-url={{$url}}
            id="{{$filedName}}"
                data-name='{{$nameValue ?? ""}}'
                data-value="{{$value ?? ""}}"
                data-placeholder="Select an option"
                data-selected-url="{{$selectedUrl}}"
        >
            <option value> ----</option>
        </select>
    </div>
</div>


<div class="{{$class}}">
    <div class="form-group">
        <label for="{{$filedName}}" class="fs-6 fw-bold mb-2"> {{ __('translation.' . $name) }} </label>
        <select class="form-select has_select_3" name="{{$filedName}}" data-url="{{$url}}"
                id="{{$filedName}}"
                data-selected-url="{{$selectedUrl}}"
                multiple="multiple"
                data-value="{{$value ?? ""}}" data-placeholder="Select an option">
            <option value> ----</option>
        </select>
    </div>
</div>


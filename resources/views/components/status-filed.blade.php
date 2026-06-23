<div class="form-group {{$class}}">
    <label for="" class="fs-6 fw-bold mb-2 "> {{___('translation.' . $name)}} </label>
    <select class="form-control" name="{{$filedName}}" id="status">
        <option value="1">{{___('translation.active')}}</option>
        <option value="0"> {{___('translation.in_active')}}</option>
    </select>
</div>

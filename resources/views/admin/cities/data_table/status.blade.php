<div class="form-check form-switch form-check-custom form-check-solid">
    <input class="form-check-input status-toggle" 
           type="checkbox" 
           value="{{ $id }}" 
           data-id="{{ $id }}"
           {{ $status ? 'checked' : '' }}>
</div>


@extends('layouts.admin.admin_layout')

@section('title', 'Edit Admin')
@section('breadcrumbs')
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="index.html" class="text-muted text-hover-primary">{{ __('translation.home') }}</a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">{{ __('translation.admins') }}</li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ __('translation.edit_admin') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
    <a href="{{ route('admins.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Admins
    </a>
@endsection
@section('content')
    <form action="{{ route('admins.update', $admin->id) }}" method="POST" enctype="multipart/form-data">

        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3>Edit Admin: {{ $admin->name }}</h3>
                        </div>
                        <div class="card-toolbar">

                        </div>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-information-5 text-danger me-2"></i>
                                    <strong class="me-2">There were some problems with your input:</strong>
                                </div>
                                <ul class="mb-0 mt-2 ps-4">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Name</label>
                                    <div class="col-lg-8">
                                        <input type="text" name="name"
                                            class="form-control form-control-solid @error('name') is-invalid @enderror"
                                            placeholder="Enter admin name" value="{{ old('name', $admin->name) }}"
                                            required />
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Email</label>
                                    <div class="col-lg-8">
                                        <input type="email" name="email"
                                            class="form-control form-control-solid @error('email') is-invalid @enderror"
                                            placeholder="Enter email address" value="{{ old('email', $admin->email) }}"
                                            required />
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Phone</label>
                                    <div class="col-lg-8">
                                        <input type="text" name="phone"
                                            class="form-control form-control-solid @error('phone') is-invalid @enderror"
                                            placeholder="Enter phone number" value="{{ old('phone', $admin->phone) }}" />
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Password</label>
                                    <div class="col-lg-8">
                                        <input type="password" name="password"
                                            class="form-control form-control-solid @error('password') is-invalid @enderror"
                                            placeholder="Leave blank to keep current password" />
                                        <div class="form-text">Leave blank to keep the current password</div>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Confirm Password</label>
                                    <div class="col-lg-8">
                                        <input type="password" name="password_confirmation"
                                            class="form-control form-control-solid" placeholder="Confirm new password" />
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Status</label>
                                    <div class="col-lg-8">
                                        <select name="status"
                                            class="form-select form-select-solid @error('status') is-invalid @enderror"
                                            required>
                                            <option value="">Select Status</option>
                                            <option value="active"
                                                {{ old('status', $admin->status == 1 ? 'active' : 'inactive') == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="inactive"
                                                {{ old('status', $admin->status == 1 ? 'active' : 'inactive') == 'inactive' ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Country</label>
                                    <div class="col-lg-8">
                                        <select id="country_id" name="country_id"
                                            class="form-select has_select_2 @error('country_id') is-invalid @enderror"
                                            data-url="{{ route('admin.countries.select') }}"
                                            data-placeholder="Select a country"
                                            {{-- data-value="{{ old('country_id', $admin->country_id) }}" --}}
                                            data-name="{{ optional(\App\Models\Country::find(old('country_id', $admin->country_id)))->getTranslation('name', app()->getLocale()) }}">
                                            <option value> ----</option>
                                            @if($admin->country_id)
                                            <option value="{{ $admin->country_id }}" selected> {{ $admin->country?->name  }}</option>
                                            @endif

                                        </select>
                                        @error('country_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Roles</label>
                                    <div class="col-lg-8">
                                        <select id="roles" name="roles[]"
                                            class="form-select has_select_3 @error('roles') is-invalid @enderror"
                                            data-url="{{ route('admin.roles.select') }}" multiple="multiple"
                                            data-placeholder="Select roles">
                                            <option value> ----</option>
                                            @php
                                                $oldRoles = old('roles');
                                                if (is_null($oldRoles)) {
                                                    $oldRoles = $admin->roles->pluck('id')->toArray();
                                                }
                                                if (!is_array($oldRoles)) {
                                                    $oldRoles = [$oldRoles];
                                                }
                                                $prefillRoles = count($oldRoles)
                                                    ? \Spatie\Permission\Models\Role::whereIn('id', $oldRoles)->get()
                                                    : collect();
                                            @endphp
                                            @foreach ($prefillRoles as $role)
                                                <option value="{{ $role->id }}" selected>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('roles')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Custom Regions</label>
                                    <div class="col-lg-8 d-flex align-items-center">
                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                            <input type="hidden" name="custom_region" value="0">
                                            <input class="form-check-input" name="custom_region" type="checkbox"
                                                id="custom_region_toggle" value="1" @checked(old('custom_region', $admin->custom_region))>
                                            <label class="form-check-label ms-2" for="custom_region_toggle">Enable custom
                                                regions</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-6 d-none" id="custom_region_container">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Regions</label>
                                    <div class="col-lg-8">
                                        <select id="regions" name="regions[]"
                                            class="form-select has_select_2 @if ($errors->has('regions') || $errors->has('regions.*')) is-invalid @endif"
                                            data-url="{{ route('admin.countries.select') }}"
                                            data-placeholder="Select countries" multiple="multiple">
                                            <option value> ----</option>
                                            @php
                                                $oldRegions = old('regions');
                                                if (is_null($oldRegions)) {
                                                    $oldRegions = $admin->countries()->pluck('countries.id')->toArray();
                                                }
                                                if (!is_array($oldRegions)) {
                                                    $oldRegions = [$oldRegions];
                                                }
                                                $prefillCountries = count($oldRegions)
                                                    ? \App\Models\Country::whereIn('id', $oldRegions)->get()
                                                    : collect();
                                            @endphp
                                            @foreach ($prefillCountries as $country)
                                                <option value="{{ $country->id }}" selected>
                                                    {{ $country->getTranslation('name', app()->getLocale()) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('regions') || $errors->has('regions.*'))
                                            <div class="invalid-feedback d-block">
                                                {{ $errors->first('regions') ?: $errors->first('regions.*') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>


                        </div>

                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="button" class="btn btn-light btn-active-light-primary me-2"
                                onclick="window.location.href='{{ route('admins.index') }}'">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Update Admin</span>
                                <span class="indicator-progress">Please wait... <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
    {{-- </form> --}}
    </div>
    </div>

    </div>
    <div class="col-md-3">
        <div class="card-body p-3">
            <div class="col-md-12">
                <div class="card p-4">
                    <x:image-picker class="col-md-12" name="user_profile_image" filed-name='image2'
                        real-filed-id="another_file"
                        :value="$admin->profile_image ? asset($admin->profile_image) : asset('assets/media/avatars/300-1.jpg')" />
                    <input type="file" name="profile_image" id="another_file" class="d-none">
                </div>
            </div>
        </div>

    </div>
    </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Initialize image input
        // var avatar = new KTImageInput('#kt_image_input');
        // Toggle custom region select visibility
        (function() {
            const toggle = document.getElementById('custom_region_toggle');
            const container = document.getElementById('custom_region_container');
            const select = document.getElementById('regions');

            function resetSelect2(el) {
                if (!el) return;
                if (window.$ && $(el).hasClass('select2-hidden-accessible')) {
                    $(el).val(null).trigger('change');
                } else {
                    el.value = '';
                }
            }

            if (toggle && container) {
                toggle.addEventListener('change', function() {
                    const enabled = this.checked;
                    if (enabled) {
                        container.classList.remove('d-none');
                    } else {
                        container.classList.add('d-none');
                        resetSelect2(select);
                    }
                });
            }

            // If there are prefilled options, we can show the container by default
            if (select && select.querySelectorAll('option[selected]').length > 0 && container) {
                container.classList.remove('d-none');
                if (toggle) toggle.checked = true;
            }
        })();
    </script>
@endpush

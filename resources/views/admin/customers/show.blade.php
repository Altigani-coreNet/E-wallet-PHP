@extends('layouts.admin.admin_layout')

@section('title', 'Customer Details')

@section('content')
<div class="d-flex flex-column flex-xl-row">
    <!--begin::Sidebar-->
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
        <!--begin::Card-->
        <div class="card mb-5 mb-xl-8">
            <!--begin::Card body-->
            <div class="card-body pt-15">
                <!--begin::Summary-->
                <div class="d-flex flex-center flex-column mb-5">
                    <!--begin::Avatar-->
                    <div class="symbol symbol-150px symbol-circle mb-7">
                        <img src="{{ $customer->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($customer->name) }}" alt="image">
                    </div>
                    <!--end::Avatar-->
                    <!--begin::Name-->
                    <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">{{ $customer->name }}</a>
                    <!--end::Name-->
                    <!--begin::Email-->
                    <a href="#" class="fs-5 fw-semibold text-muted text-hover-primary mb-6">{{ $customer->email }}</a>
                    <!--end::Email-->
                </div>
                <!--end::Summary-->
                <!--begin::Details toggle-->
                <div class="d-flex flex-stack fs-4 py-3">
                    <div class="fw-bold">Details</div>
                    <!--begin::Badge-->
                    <div class="badge badge-light-info d-inline">Active Customer</div>
                    <!--begin::Badge-->
                </div>
                <!--end::Details toggle-->
                <div class="separator separator-dashed my-3"></div>
                <!--begin::Details content-->
                <div class="pb-5 fs-6">
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Customer ID</div>
                    <div class="text-gray-600">#{{ $customer->id }}</div>
                    <!--begin::Details item-->
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Email</div>
                    <div class="text-gray-600">
                        <a href="mailto:{{ $customer->email }}" class="text-gray-600 text-hover-primary">{{ $customer->email }}</a>
                    </div>
                    <!--begin::Details item-->
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Phone</div>
                    <div class="text-gray-600">
                        @if($customer->phone)
                            <a href="tel:{{ $customer->phone }}" class="text-gray-600 text-hover-primary">{{ $customer->phone }}</a>
                        @else
                            No phone provided
                        @endif
                    </div>
                    <!--begin::Details item-->
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Address</div>
                    <div class="text-gray-600">
                        @if($customer->address || $customer->city || $customer->state || $customer->zip)
                            @if($customer->address)
                                {{ $customer->address }}<br>
                            @endif
                            @if($customer->city || $customer->state || $customer->zip)
                                {{ collect([$customer->city, $customer->state, $customer->zip])->filter()->implode(', ') }}
                            @endif
                        @else
                            No address provided
                        @endif
                    </div>
                    <!--begin::Details item-->
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Merchant</div>
                    <div class="text-gray-600">
                        @if($customer->merchant)
                            <a href="#" class="text-gray-600 text-hover-primary">{{ $customer->merchant->name }}</a>
                        @else
                            No merchant assigned
                        @endif
                    </div>
                    <!--begin::Details item-->
                    <!--begin::Details item-->
                    <div class="fw-bold mt-5">Created</div>
                    <div class="text-gray-600">
                        {{ $customer->created_at ? $customer->created_at->format('M d, Y') : 'N/A' }}
                    </div>
                    <!--begin::Details item-->
                </div>
                <!--end::Details content-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Sidebar-->
    <!--begin::Content-->
    <div class="flex-lg-row-fluid ms-lg-15">
        <!--begin:::Tabs-->
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8" role="tablist">
            <!--begin:::Tab item-->
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_ecommerce_customer_overview" aria-selected="true" role="tab">Overview</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_ecommerce_customer_general" aria-selected="false" tabindex="-1" role="tab">General Settings</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_ecommerce_customer_advanced" aria-selected="false" tabindex="-1" role="tab">Advanced Settings</a>
            </li>
            <!--end:::Tab item-->
        </ul>
        <!--end:::Tabs-->
        <!--begin:::Tab content-->
        <div class="tab-content" id="myTabContent">
            <!--begin:::Tab pane-->
            <div class="tab-pane fade show active" id="kt_ecommerce_customer_overview" role="tabpanel">
                <div class="row row-cols-1 row-cols-md-2 mb-6 mb-xl-9">
                    <div class="col">
                        <!--begin::Card-->
                        <div class="card pt-4 h-md-100 mb-6 mb-md-0">
                            <!--begin::Card header-->
                            <div class="card-header border-0">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2 class="fw-bold">Account Status</h2>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <div class="fw-bold fs-2">
                                    <div class="d-flex">
                                        <i class="ki-duotone ki-check-circle text-success fs-2x">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <div class="ms-2">Active
                                        <span class="text-muted fs-4 fw-semibold">Customer Account</span></div>
                                    </div>
                                    <div class="fs-7 fw-normal text-muted">Customer account is active and operational.</div>
                                </div>
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Card-->
                    </div>
                    <div class="col">
                        <!--begin::Merchant Info-->
                        <a href="#" class="card bg-primary hoverable h-md-100">
                            <!--begin::Body-->
                            <div class="card-body">
                                <i class="ki-duotone ki-shop text-white fs-3x ms-n1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="text-white fw-bold fs-2 mt-5">
                                    @if($customer->merchant)
                                        {{ $customer->merchant->name }}
                                    @else
                                        No Merchant
                                    @endif
                                </div>
                                <div class="fw-semibold text-white">Associated Merchant</div>
                            </div>
                            <!--end::Body-->
                        </a>
                        <!--end::Merchant Info-->
                    </div>
                </div>
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>Customer Information</h2>
                        </div>
                        <!--end::Card title-->
                        <!--begin::Card toolbar-->
                        <div class="card-toolbar">
                            <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-pencil fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Edit Customer
                            </a>
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <!--begin::Table-->
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-5">
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Customer ID</td>
                                        <td class="text-gray-800">#{{ $customer->id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Name</td>
                                        <td class="text-gray-800">{{ $customer->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Email</td>
                                        <td class="text-gray-800">
                                            <a href="mailto:{{ $customer->email }}" class="text-gray-900 text-hover-primary">{{ $customer->email }}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Phone</td>
                                        <td class="text-gray-800">
                                            @if($customer->phone)
                                                <a href="tel:{{ $customer->phone }}" class="text-gray-900 text-hover-primary">{{ $customer->phone }}</a>
                                            @else
                                                No phone provided
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Address</td>
                                        <td class="text-gray-800">
                                            @if($customer->address || $customer->city || $customer->state || $customer->zip)
                                                @if($customer->address)
                                                    {{ $customer->address }}<br>
                                                @endif
                                                @if($customer->city || $customer->state || $customer->zip)
                                                    {{ collect([$customer->city, $customer->state, $customer->zip])->filter()->implode(', ') }}
                                                @endif
                                            @else
                                                No address provided
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Created</td>
                                        <td class="text-gray-800">{{ $customer->created_at ? $customer->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Last Updated</td>
                                        <td class="text-gray-800">{{ $customer->updated_at ? $customer->updated_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!--end::Table-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_ecommerce_customer_general" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>Profile Information</h2>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <!--begin::Form-->
                        <form class="form fv-plugins-bootstrap5 fv-plugins-framework" action="{{ route('admin.customers.update', $customer->id) }}" method="POST" id="kt_ecommerce_customer_profile">
                            @csrf
                            @method('PUT')
                            <!--begin::Input group-->
                            <div class="fv-row mb-7 fv-plugins-icon-container">
                                <!--begin::Label-->
                                <label class="fs-6 fw-semibold mb-2 required">Name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" class="form-control form-control-solid" placeholder="" name="name" value="{{ $customer->name }}">
                                <!--end::Input-->
                            <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div></div>
                            <!--end::Input group-->
                            <!--begin::Row-->
                            <div class="row row-cols-1 row-cols-md-2">
                                <!--begin::Col-->
                                <div class="col">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-7 fv-plugins-icon-container">
                                        <!--begin::Label-->
                                        <label class="fs-6 fw-semibold mb-2">
                                            <span class="required">Email</span>
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="email" class="form-control form-control-solid" placeholder="" name="email" value="{{ $customer->email }}">
                                        <!--end::Input-->
                                    <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div></div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fs-6 fw-semibold mb-2">
                                            <span>Phone</span>
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" class="form-control form-control-solid" placeholder="" name="phone" value="{{ $customer->phone }}">
                                        <!--end::Input-->
                                    </div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <div class="d-flex justify-content-end">
                                <!--begin::Button-->
                                <button type="submit" id="kt_ecommerce_customer_profile_submit" class="btn btn-light-primary">
                                    <span class="indicator-label">Save Changes</span>
                                    <span class="indicator-progress">Please wait... 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                                <!--end::Button-->
                            </div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_ecommerce_customer_advanced" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>Merchant Information</h2>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        @if($customer->merchant)
                        <!--begin::Table wrapper-->
                        <div class="table-responsive">
                            <!--begin::Table-->
                            <table class="table align-middle table-row-dashed gy-5">
                                <!--begin::Table body-->
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Merchant ID</td>
                                        <td class="text-gray-800">#{{ $customer->merchant->id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Merchant Name</td>
                                        <td class="text-gray-800">{{ $customer->merchant->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Business Type</td>
                                        <td class="text-gray-800">
                                            @if($customer->merchant->business_type)
                                                <span class="badge badge-info">{{ $customer->merchant->business_type->value }}</span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Status</td>
                                        <td class="text-gray-800">
                                            @if($customer->merchant->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($customer->merchant->owner_name)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Owner</td>
                                        <td class="text-gray-800">{{ $customer->merchant->owner_name }}</td>
                                    </tr>
                                    @endif
                                    @if($customer->merchant->email)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Email</td>
                                        <td class="text-gray-800">
                                            <a href="mailto:{{ $customer->merchant->email }}" class="text-gray-900 text-hover-primary">{{ $customer->merchant->email }}</a>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($customer->merchant->phone)
                                    <tr>
                                        <td class="text-muted min-w-125px w-125px">Phone</td>
                                        <td class="text-gray-800">
                                            <a href="tel:{{ $customer->merchant->phone }}" class="text-gray-900 text-hover-primary">{{ $customer->merchant->phone }}</a>
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                                <!--end::Table body-->
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Table wrapper-->
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">No merchant assigned to this customer.</div>
                        </div>
                        @endif
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2 class="fw-bold mb-0">Quick Actions</h2>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-light-primary">
                                <i class="ki-duotone ki-pencil fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Edit Customer
                            </a>
                            <button type="button" class="btn btn-light-danger delete-customer" 
                                    data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">
                                <i class="ki-duotone ki-trash fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>Delete Customer
                            </button>
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-light-secondary">
                                <i class="ki-duotone ki-arrow-left fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>Back to Customers
                            </a>
                        </div>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
        </div>
        <!--end:::Tab content-->
    </div>
    <!--end::Content-->
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('delete-customer')) {
        var id = e.target.getAttribute('data-id');
        var name = e.target.getAttribute('data-name');
        if (confirm('Are you sure you want to delete customer "' + name + '"? This action cannot be undone.')) {
            fetch('/admin/customers/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    window.location.href = '{{ route("admin.customers.index") }}';
                } else {
                    alert(data.message || 'Error');
                }
            }).catch(() => alert('Error'));
        }
    }
});
</script>
@endpush

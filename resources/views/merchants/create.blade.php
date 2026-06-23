@extends('layouts.admin.admin_layout')

@section('main-head' , __('translation.add_merchant'))

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
        <li class="breadcrumb-item text-muted">{{ __('translation.users') }}</li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
@endsection
@section('toolbar_actions')
<div class="d-flex align-items-center gap-2 gap-lg-3">
    <div class="m-0">
        <a href="{{ route('merchants.index') }}" class="btn btn-light-danger btn-sm me-3">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('translation.back') }}
        </a>
    </div>

</div>
@endsection
@section('content')

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <!--begin::Container-->
        <div id="kt_content_container" class="container-xxl">
            <form action="{{route('merchants.store')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="card mb-5">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>{{ __('translation.merchant_information') }}</h2>
                            </div>
                        </div>
                        
                        <div class="card-body p-3">
                            <div class="col-md-12">
                                <div class="">
                                    <!-- General Validation Errors -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <div class="d-flex">
                                                <i class="ki-duotone ki-cross-circle fs-2hx text-danger me-4">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="d-flex flex-column">
                                                    <h4 class="mb-1">{{ __('translation.validation_errors') }}</h4>
                                                    <ul class="mb-0">
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('translation.close') }}"></button>
                                        </div>
                                    @endif
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">{{ __('translation.business_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('business_name') is-invalid @enderror" 
                                                   id="business_name" name="business_name" value="{{old('business_name')}}" 
                                                   placeholder="{{ __('translation.business_name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="business_type" class="form-label">{{ __('translation.business_type') }} <span class="text-danger">*</span></label>
                                            <select class="form-select @error('business_type') is-invalid @enderror" 
                                                    id="business_type" name="business_type" required>
                                                <option value="">{{ __('translation.select_business_type') }}</option>
                                                @foreach(\App\Enums\BusinessType::toArray() as $value => $label)
                                                    <option value="{{ $value }}" {{old('business_type') == $value ? 'selected' : ''}}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('business_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="tax_file" class="form-label">{{ __('translation.tax_certificate') }} <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control @error('tax_file') is-invalid @enderror" 
                                                   id="tax_file" name="tax_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <small class="form-text text-muted">{{ __('translation.accepted_formats') }}: {{ __('translation.pdf_format') }}, {{ __('translation.jpg_format') }}, {{ __('translation.jpeg_format') }}, {{ __('translation.png_format') }}</small>
                                            @error('tax_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="trade_license_file" class="form-label">{{ __('translation.trade_license') }} <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control @error('trade_license_file') is-invalid @enderror" 
                                                   id="trade_license_file" name="trade_license_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <small class="form-text text-muted">{{ __('translation.accepted_formats') }}: {{ __('translation.pdf_format') }}, {{ __('translation.jpg_format') }}, {{ __('translation.jpeg_format') }}, {{ __('translation.png_format') }}</small>
                                            @error('trade_license_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="is_active" class="form-label">{{ __('translation.status') }} <span class="text-danger">*</span></label>
                                            <select class="form-select @error('is_active') is-invalid @enderror" 
                                                    id="is_active" name="is_active" required>
                                                <option value="1" {{old('is_active', '1') == '1' ? 'selected' : ''}}>{{ __('translation.active') }}</option>
                                                <option value="0" {{old('is_active', '1') == '0' ? 'selected' : ''}}>{{ __('translation.inactive') }}</option>
                                            </select>
                                            @error('is_active')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="owner_name" class="form-label">{{ __('translation.owner_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                                                   id="owner_name" name="owner_name" value="{{old('owner_name')}}" 
                                                   placeholder="{{ __('translation.owner_name') }}" required>
                                            @error('owner_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label for="address" class="form-label">{{ __('translation.business_address') }} <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                                      id="address" name="address" rows="3" 
                                                      placeholder="{{ __('translation.business_address') }}" required>{{old('address')}}</textarea>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="country_id" class="form-label">{{ __('translation.country') }} <span class="text-danger">*</span></label>
                                            <select class="form-select @error('country_id') is-invalid @enderror" 
                                                    id="country_id" name="country_id" data-placeholder="{{ __('translation.select_country') }}" required>
                                                @if(old('country_id'))
                                                    <option value="{{ old('country_id') }}" selected>{{ optional(\App\Models\Country::find(old('country_id')))?->name }}</option>
                                                @else
                                                    <option value="">{{ __('translation.select_country') }}</option>
                                                @endif
                                            </select>
                                            @error('country_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="city_id" class="form-label">{{ __('translation.city') }} <span class="text-danger">*</span></label>
                                            <select class="form-select @error('city_id') is-invalid @enderror" 
                                                    id="city_id" name="city_id" data-placeholder="{{ __('translation.select_city') }}" required disabled>
                                                <option value="">{{ __('translation.select_city') }}</option>
                                            </select>
                                            @error('city_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="trade_license_number" class="form-label">{{ __('translation.trade_license_number') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('trade_license_number') is-invalid @enderror" 
                                                   id="trade_license_number" name="trade_license_number" value="{{old('trade_license_number')}}" 
                                                   placeholder="{{ __('translation.trade_license_number') }}" required>
                                            @error('trade_license_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="tax_certified_number" class="form-label">{{ __('translation.tax_certified_number') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('tax_certified_number') is-invalid @enderror" 
                                                   id="tax_certified_number" name="tax_certified_number" value="{{old('tax_certified_number')}}" 
                                                   placeholder="{{ __('translation.tax_certified_number') }}" required>
                                            @error('tax_certified_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="trade_license_start_date" class="form-label">{{ __('translation.trade_license_start_date') }} <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('trade_license_start_date') is-invalid @enderror" 
                                                   id="trade_license_start_date" name="trade_license_start_date" value="{{old('trade_license_start_date')}}" required>
                                            @error('trade_license_start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="trade_license_expired_date" class="form-label">{{ __('translation.trade_license_expired_date') }} <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('trade_license_expired_date') is-invalid @enderror" 
                                                   id="trade_license_expired_date" name="trade_license_expired_date" value="{{old('trade_license_expired_date')}}" required>
                                            @error('trade_license_expired_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Location from Map Checkbox -->
                                        <div class="col-12 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="get_location_from_map" id="get_location_from_map" value="1" {{old('get_location_from_map') ? 'checked' : ''}}>
                                                <label class="form-check-label" for="get_location_from_map">
                                                    {{ __('translation.get_location_from_map') }}
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Hidden fields for coordinates -->
                                        <input type="hidden" name="lat" id="latitude" value="{{old('lat')}}">
                                        <input type="hidden" name="long" id="longitude" value="{{old('long')}}">
                                        
                                        <!-- Google Maps Component -->
                                        <div id="map_container" class="col-md-12" style="display: none;">
                                            <x:google-map />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Card -->
                    <div class="card mb-5">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>{{ __('translation.personal_information') }}</h2>
                            </div>
                        </div>
                        
                        <div class="card-body p-3">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">{{ __('translation.first_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{old('name')}}" 
                                               placeholder="{{ __('translation.first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">{{ __('translation.last_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                               id="last_name" name="last_name" value="{{old('last_name')}}" 
                                               placeholder="{{ __('translation.last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                        
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">{{ __('translation.email') }} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{old('email')}}" 
                                               placeholder="{{ __('translation.email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                        
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">{{ __('translation.phone_number') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" name="phone" value="{{old('phone')}}" 
                                               placeholder="{{ __('translation.phone_number') }}" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                        
                                    <!-- Personal ID File Upload -->
                                    <div class="col-md-6 mb-3">
                                        <label for="personal_id_file" class="form-label">{{ __('translation.personal_id') }} <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control @error('personal_id_file') is-invalid @enderror" 
                                               id="personal_id_file" name="personal_id_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="form-text text-muted">{{ __('translation.accepted_formats') }}: {{ __('translation.pdf_format') }}, {{ __('translation.jpg_format') }}, {{ __('translation.jpeg_format') }}, {{ __('translation.png_format') }}</small>
                                        @error('personal_id_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-end gap-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('translation.save_merchant') }}
                                </button>
                                <a href="#" onclick="window.history.back()" class="btn btn-light-danger">
                                    {{ __('translation.cancel') }}
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </div>

@push('scripts')
<script>
    $(function() {
        const countrySelect = $('#country_id');
        const citySelect = $('#city_id');

        if (countrySelect.length) {
            countrySelect.select2({
                ajax: {
                    url: '{{ route("countries.select") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search: params.term };
                    },
                    processResults: function (data) {
                        const items = (data.data || data) || [];
                        return { results: items.map(c => ({ id: c.id, text: c.text })) };
                    },
                    cache: true
                },
                placeholder: countrySelect.data('placeholder') || '{{ __('translation.select_country') }}',
                allowClear: true,
                width: '100%'
            });
        }

        function initCities(countryId) {
            citySelect.prop('disabled', !countryId);
            citySelect.val(null).trigger('change');
            if (!countryId) return;
            citySelect.select2({
                ajax: {
                    url: '{{ route("city.select") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search: params.term, country_id: countryId };
                    },
                    processResults: function (data) {
                        const items = (data.data || data) || [];
                        return { results: items.map(c => ({ id: c.id, text: c.text })) };
                    },
                    cache: true
                },
                placeholder: citySelect.data('placeholder') || '{{ __('translation.select_city') }}',
                allowClear: true,
                width: '100%'
            });
        }

        countrySelect.on('change', function(){
            initCities($(this).val());
        });

        // If old country selected, initialize cities for it
        const initialCountryId = '{{ old('country_id') }}';
        const initialCityId = '{{ old('city_id') }}';
        if (initialCountryId) {
            // Preload country option already present in select
            initCities(initialCountryId);
            if (initialCityId) {
                const initialCityText = @json(optional(\App\Models\City::find(old('city_id')))?->name);
                if (initialCityText) {
                    citySelect.append(new Option(initialCityText, initialCityId, true, true)).trigger('change');
                }
            }
        }
    });
</script>
@endpush

@endsection

{{-- @push("scripts")
    <script>
        $(document).ready(function() {
            
            // Toggle map visibility
            $('#get_location_from_map').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#map_container').show();
                    // Initialize map if not already done
                    if (typeof google !== 'undefined' && typeof initAutocomplete === 'function') {
                        initAutocomplete();
                    }
                } else {
                    $('#map_container').hide();
                    // Clear coordinates when unchecked
                    $('#latitude').val('');
                    $('#longitude').val('');
                }
            });

            // Auto-hide validation errors after 5 seconds
            setTimeout(function() {
                $('.alert-danger').fadeOut('slow');
            }, 5000);

            // Clear validation errors when user starts typing
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            });

            // Form validation on submit
            $('form').on('submit', function() {
                let hasErrors = false;
                
                // Check required fields
                $(this).find('input[required], select[required], textarea[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        hasErrors = true;
                    }
                });

                if (hasErrors) {
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                    return false;
                }
            });
        });
        
        // Google Maps functionality
        $("#pac-input").focusin(function() {
            $(this).val('');
        });
        
        // This example adds a search box to a map, using the Google Place Autocomplete
        // feature. People can enter geographical searches. The search box will return a
        // pick list containing a mix of places and predicted search terms.
        // This example requires the Places library. Include the libraries=places
        // parameter when you first load the API. For example:
        // <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">
        function initAutocomplete() {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 24.740691, lng: 46.6528521 },
                zoom: 13,
                mapTypeId: 'roadmap'
            });
            // move pin and current location
            infoWindow = new google.maps.InfoWindow;
            geocoder = new google.maps.Geocoder();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    map.setCenter(pos);
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(pos),
                        map: map,
                        title: '{{ __("translation.your_current_location") }}'
                    });
                    markers.push(marker);
                    marker.addListener('click', function() {
                        geocodeLatLng(geocoder, map, infoWindow,marker);
                    });
                    // to get current position address on load
                    google.maps.event.trigger(marker, 'click');
                }, function() {
                    handleLocationError(true, infoWindow, map.getCenter());
                });
            } else {
                // Browser doesn't support Geolocation
                console.log('dsdsdsdsddsd');
                handleLocationError(false, infoWindow, map.getCenter());
            }
            var geocoder = new google.maps.Geocoder();
            google.maps.event.addListener(map, 'click', function(event) {
                SelectedLatLng = event.latLng;
                geocoder.geocode({
                    'latLng': event.latLng
                }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            deleteMarkers();
                            addMarkerRunTime(event.latLng);
                            SelectedLocation = results[0].formatted_address;
                            console.log( results[0].formatted_address);
                            splitLatLng(String(event.latLng));
                            $("#pac-input").val(results[0].formatted_address);
                        }
                    }
                });
            });
            function geocodeLatLng(geocoder, map, infowindow,markerCurrent) {
                var latlng = {lat: markerCurrent.position.lat(), lng: markerCurrent.position.lng()};
                /* $('#branch-latLng').val("("+markerCurrent.position.lat() +","+markerCurrent.position.lng()+")");*/
                $('#latitude').val(markerCurrent.position.lat());
                $('#longitude').val(markerCurrent.position.lng());
                geocoder.geocode({'location': latlng}, function(results, status) {
                    if (status === 'OK') {
                        if (results[0]) {
                            map.setZoom(22);
                            var marker = new google.maps.Marker({
                                position: latlng,
                                map: map
                            });
                            markers.push(marker);
                            infowindow.setContent(results[0].formatted_address);
                            SelectedLocation = results[0].formatted_address;
                            $("#pac-input").val(results[0].formatted_address);
                            infowindow.open(map, marker);
                        } else {
                            window.alert('{{ __("translation.no_results_found") }}');
                        }
                    } else {
                        window.alert('{{ __("translation.geocoder_failed") }}: ' + status);
                    }
                });
                SelectedLatLng =(markerCurrent.position.lat(),markerCurrent.position.lng());
            }
            function addMarkerRunTime(location) {
                var marker = new google.maps.Marker({
                    position: location,
                    map: map
                });
                markers.push(marker);
            }
            function setMapOnAll(map) {
                for (var i = 0; i < markers.length; i++) {
                    markers[i].setMap(map);
                }
            }
            function clearMarkers() {
                setMapOnAll(null);
            }
            function deleteMarkers() {
                clearMarkers();
                markers = [];
            }
            // Create the search box and link it to the UI element.
            var input = document.getElementById('pac-input');
            $("#pac-input").val("{{ __('translation.search_here') }}");
            var searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_RIGHT].push(input);
            // Bias the SearchBox results towards current map's viewport.
            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });
            var markers = [];
            // Listen for the event fired when the user selects a prediction and retrieve
            // more details for that place.
            searchBox.addListener('places_changed', function() {
                var places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach(function(marker) {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                var bounds = new google.maps.LatLngBounds();
                places.forEach(function(place) {
                    if (!place.geometry) {
                        console.log("{{ __('translation.place_no_geometry') }}");
                        return;
                    }
                    var icon = {
                        url: place.icon,
                        size: new google.maps.Size(100, 100),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25)
                    };
                    // Create a marker for each place.
                    markers.push(new google.maps.Marker({
                        map: map,
                        icon: icon,
                        title: place.name,
                        position: place.geometry.location
                    }));
                    $('#latitude').val(place.geometry.location.lat());
                    $('#longitude').val(place.geometry.location.lng());
                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
        }
        function handleLocationError(browserHasGeolocation, infoWindow, pos) {
            infoWindow.setPosition(pos);
            infoWindow.setContent(browserHasGeolocation ?
                '{{ __("translation.geolocation_service_failed") }}' :
                '{{ __("translation.browser_geolocation_not_supported") }}');
            infoWindow.open(map);
        }
        function splitLatLng(latLng){
            var newString = latLng.substring(0, latLng.length-1);
            var newString2 = newString.substring(1);
            var trainindIdArray = newString2.split(',');
            var lat = trainindIdArray[0];
            var Lng  = trainindIdArray[1];
            $('[name="lat"]').val(lat);
            $('[name="long"]').val(Lng);
        }
    </script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyClrFqfOqOGTSGWpiZby6POa-AEFjGmJoM&libraries=places&callback=initAutocomplete&language=ar&region=EG" async defer></script>
@endpush  --}}
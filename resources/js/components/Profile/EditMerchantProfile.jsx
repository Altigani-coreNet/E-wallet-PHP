import React, { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';
import { AUTH_SERVICE_BASE_URL } from '../../services/authService';

// const { getAuthToken } = useAuth();
/**
 * EditMerchantProfile Component
 * 
 * Full merchant profile editing form (for approved merchants or regular updates)
 * Creates a change request if merchant is not rejected
 */
const EditMerchantProfile = ({ merchant, onSuccess, onCancel }) => {
    const [loading, setLoading] = useState({
        countries: false,
        cities: false,
        businessTypes: false
    });
    const [submitting, setSubmitting] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        owner_name: '',
        email: '',
        phone: '',
        business_type: '',
        address: '',
        trade_license_number: '',
        tax_certified_number: '',
        country_id: '',
        city_id: '',
    });
    const [errors, setErrors] = useState({});
    const [countries, setCountries] = useState([]);
    const [filteredCountries, setFilteredCountries] = useState([]);
    const [cities, setCities] = useState([]);
    const [filteredCities, setFilteredCities] = useState([]);
    const [businessTypes, setBusinessTypes] = useState([]);
    const [countrySearchTerm, setCountrySearchTerm] = useState('');
    const [citySearchTerm, setCitySearchTerm] = useState('');
    const [showCountryList, setShowCountryList] = useState(false);
    const [showCityList, setShowCityList] = useState(false);
    const [selectedCountry, setSelectedCountry] = useState(null);
    const [selectedCity, setSelectedCity] = useState(null);

    // Debounce function
    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(null, args), delay);
        };
    };

    // Fetch countries
    const fetchCountries = async (searchTerm = '') => {
        setLoading(prev => ({ ...prev, countries: true }));
        try {
            const url = searchTerm 
                ? `/api/softpos/countries/select?search=${encodeURIComponent(searchTerm)}`
                : '/api/softpos/countries/select';
            
            const response = await axios.get(url);
            if (response.data.status) {
                setCountries(response.data.data);
                setFilteredCountries(response.data.data);
            }
        } catch (error) {
            console.error('Error fetching countries:', error);
        } finally {
            setLoading(prev => ({ ...prev, countries: false }));
        }
    };

    // Debounced country search
    const debouncedCountrySearch = useCallback(
        debounce((searchTerm) => {
            if (searchTerm.length >= 1) {
                fetchCountries(searchTerm);
            } else {
                fetchCountries();
            }
        }, 500),
        []
    );

    const handleCountrySearch = (searchTerm) => {
        setCountrySearchTerm(searchTerm);
        debouncedCountrySearch(searchTerm);
        setShowCountryList(true);
    };

    const handleCountryDropdownToggle = () => {
        if (!showCountryList) {
            setCountrySearchTerm('');
            fetchCountries();
        }
        setShowCountryList(!showCountryList);
    };

    const handleCountrySelect = (country) => {
        setSelectedCountry(country);
        setCountrySearchTerm(country.text);
        setFormData(prev => ({ ...prev, country_id: country.id, city_id: '' }));
        setShowCountryList(false);
        setSelectedCity(null);
        setCitySearchTerm('');
        fetchCities(country.id);
    };

    const handleRemoveCountry = () => {
        setSelectedCountry(null);
        setCountrySearchTerm('');
        setFormData(prev => ({ ...prev, country_id: '', city_id: '' }));
        setShowCountryList(false);
        setCities([]);
        setSelectedCity(null);
        setCitySearchTerm('');
    };

    // Fetch cities
    const fetchCities = async (countryId) => {
        if (!countryId) {
            setCities([]);
            setFilteredCities([]);
            return;
        }
        
        setLoading(prev => ({ ...prev, cities: true }));
        try {
            const response = await axios.get(`/api/softpos/cities/select?country_id=${countryId}`);
            if (response.data.status) {
                setCities(response.data.data);
                setFilteredCities(response.data.data);
            }
        } catch (error) {
            console.error('Error fetching cities:', error);
        } finally {
            setLoading(prev => ({ ...prev, cities: false }));
        }
    };

    // Debounced city search
    const debouncedCitySearch = useCallback(
        debounce((searchTerm) => {
            if (searchTerm.length >= 1) {
                const filtered = cities.filter(city =>
                    city.text.toLowerCase().includes(searchTerm.toLowerCase())
                );
                setFilteredCities(filtered);
            } else {
                setFilteredCities(cities);
            }
        }, 300),
        [cities]
    );

    const handleCitySearch = (searchTerm) => {
        setCitySearchTerm(searchTerm);
        debouncedCitySearch(searchTerm);
        setShowCityList(true);
    };

    const handleCitySelect = (city) => {
        setSelectedCity(city);
        setCitySearchTerm(city.text);
        setFormData(prev => ({ ...prev, city_id: city.id }));
        setShowCityList(false);
    };

    const handleRemoveCity = () => {
        setSelectedCity(null);
        setCitySearchTerm('');
        setFormData(prev => ({ ...prev, city_id: '' }));
        setShowCityList(false);
    };

    // Fetch business types
    const fetchBusinessTypes = async () => {
        setLoading(prev => ({ ...prev, businessTypes: true }));
        try {
            const response = await axios.get('/api/softpos/business-types/select');
            if (response.data.status) {
                setBusinessTypes(response.data.data);
            }
        } catch (error) {
            console.error('Error fetching business types:', error);
        } finally {
            setLoading(prev => ({ ...prev, businessTypes: false }));
        }
    };

    // Initialize form with merchant data
    useEffect(() => {
        if (merchant) {
            console.log('✅ Setting form data from merchant:', merchant);
            
            setFormData({
                name: merchant.name || '',
                owner_name: merchant.owner_name || '',
                email: merchant.email || '',
                phone: merchant.phone || '',
                business_type: merchant.business_type || '',
                address: merchant.address || '',
                trade_license_number: merchant.trade_license_number || '',
                tax_certified_number: merchant.tax_certified_number || merchant.tax_number || '',
                country_id: merchant.country_id || '',
                city_id: merchant.city_id || '',
            });
        }
    }, [merchant]);

    // Load initial data
    useEffect(() => {
        fetchCountries();
        fetchBusinessTypes();
    }, []);

    // Load country when formData changes
    useEffect(() => {
        if (formData.country_id && countries.length > 0) {
            const country = countries.find(c => c.id === formData.country_id);
            if (country) {
                setSelectedCountry(country);
                setCountrySearchTerm(country.text);
                fetchCities(formData.country_id);
            }
        }
    }, [formData.country_id, countries]);

    // Load city when formData changes
    useEffect(() => {
        if (formData.city_id && cities.length > 0) {
            const city = cities.find(c => c.id === formData.city_id);
            if (city) {
                setSelectedCity(city);
                setCitySearchTerm(city.text);
            }
        }
    }, [formData.city_id, cities]);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        setErrors(prev => ({ ...prev, [name]: '' }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            const response = await axios.post(
                `${AUTH_SERVICE_BASE_URL}/merchant-profile/update`,
                formData
            );

            if (response.data.success) {
                alert(response.data.message || 'Profile updated successfully!');
                if (onSuccess) onSuccess(response.data.data);
            }
        } catch (error) {
            console.error('Failed to update profile:', error);
            
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                alert(error.response?.data?.message || 'Failed to update profile. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="row">
            <div className="col-lg-12">
                <div className="card mb-5 mb-xl-10">
                    <div className="card-header border-0 cursor-pointer">
                        <div className="card-title m-0">
                            <h3 className="fw-bolder m-0">Edit Merchant Profile</h3>
                        </div>
                        <div className="card-toolbar">
                            <button
                                type="button"
                                onClick={onCancel}
                                className="btn btn-sm btn-light me-2"
                                disabled={submitting}
                            >
                                <i className="ki-duotone ki-cross fs-2">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                </i>
                                Cancel
                            </button>
                        </div>
                    </div>

                    <div className="card-body border-top p-9">
                        {merchant?.status === 'requesting_updated' && (
                            <div className="alert alert-warning d-flex align-items-center p-5 mb-10">
                                <i className="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                                    <span className="path1"></span>
                                    <span className="path2"></span>
                                    <span className="path3"></span>
                                </i>
                                <div className="d-flex flex-column">
                                    <h4 className="mb-1 text-warning">Change Request Pending</h4>
                                    <span>You have a pending change request. Submitting this form will create a new request.</span>
                                </div>
                            </div>
                        )}

                        <form onSubmit={handleSubmit}>
                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Business Name</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="text"
                                        name="name"
                                        className={`form-control form-control-lg form-control-solid ${errors.name ? 'is-invalid' : ''}`}
                                        placeholder="Business Name"
                                        value={formData.name}
                                        onChange={handleInputChange}
                                        required
                                    />
                                    {errors.name && (
                                        <div className="invalid-feedback">{errors.name[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Owner Name</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="text"
                                        name="owner_name"
                                        className={`form-control form-control-lg form-control-solid ${errors.owner_name ? 'is-invalid' : ''}`}
                                        placeholder="Owner Name"
                                        value={formData.owner_name}
                                        onChange={handleInputChange}
                                        required
                                    />
                                    {errors.owner_name && (
                                        <div className="invalid-feedback">{errors.owner_name[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Email</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="email"
                                        name="email"
                                        className={`form-control form-control-lg form-control-solid ${errors.email ? 'is-invalid' : ''}`}
                                        placeholder="Email"
                                        value={formData.email}
                                        onChange={handleInputChange}
                                        required
                                    />
                                    {errors.email && (
                                        <div className="invalid-feedback">{errors.email[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Phone</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="text"
                                        name="phone"
                                        className={`form-control form-control-lg form-control-solid ${errors.phone ? 'is-invalid' : ''}`}
                                        placeholder="Phone"
                                        value={formData.phone}
                                        onChange={handleInputChange}
                                        required
                                    />
                                    {errors.phone && (
                                        <div className="invalid-feedback">{errors.phone[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Business Type</label>
                                <div className="col-lg-9 fv-row">
                                    <select
                                        name="business_type"
                                        className={`form-select form-select-solid form-select-lg ${errors.business_type ? 'is-invalid' : ''}`}
                                        value={formData.business_type}
                                        onChange={handleInputChange}
                                        required
                                        disabled={loading.businessTypes}
                                    >
                                        <option value="">{loading.businessTypes ? 'Loading...' : 'Select Business Type'}</option>
                                        {businessTypes.map(type => (
                                            <option key={type.id} value={type.value}>
                                                {type.text}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.business_type && (
                                        <div className="invalid-feedback">{errors.business_type[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label required fw-bold fs-6">Address</label>
                                <div className="col-lg-9 fv-row">
                                    <textarea
                                        name="address"
                                        className={`form-control form-control-lg form-control-solid ${errors.address ? 'is-invalid' : ''}`}
                                        rows="3"
                                        placeholder="Address"
                                        value={formData.address}
                                        onChange={handleInputChange}
                                        required
                                    ></textarea>
                                    {errors.address && (
                                        <div className="invalid-feedback">{errors.address[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label fw-bold fs-6">Trade License Number</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="text"
                                        name="trade_license_number"
                                        className={`form-control form-control-lg form-control-solid ${errors.trade_license_number ? 'is-invalid' : ''}`}
                                        placeholder="Trade License Number"
                                        value={formData.trade_license_number}
                                        onChange={handleInputChange}
                                    />
                                    {errors.trade_license_number && (
                                        <div className="invalid-feedback">{errors.trade_license_number[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label fw-bold fs-6">Tax Number</label>
                                <div className="col-lg-9 fv-row">
                                    <input
                                        type="text"
                                        name="tax_certified_number"
                                        className={`form-control form-control-lg form-control-solid ${errors.tax_certified_number ? 'is-invalid' : ''}`}
                                        placeholder="Tax Number"
                                        value={formData.tax_certified_number}
                                        onChange={handleInputChange}
                                    />
                                    {errors.tax_certified_number && (
                                        <div className="invalid-feedback">{errors.tax_certified_number[0]}</div>
                                    )}
                                </div>
                            </div>

                            {/* Country Dropdown */}
                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label fw-bold fs-6">Country</label>
                                <div className="col-lg-9 fv-row">
                                    <div className="position-relative">
                                        <div 
                                            className={`form-control form-control-lg form-control-solid d-flex align-items-center justify-content-between cursor-pointer ${errors.country_id ? 'is-invalid' : ''}`}
                                            onClick={handleCountryDropdownToggle}
                                            style={{ cursor: 'pointer', minHeight: '50px' }}
                                        >
                                            <div className="d-flex align-items-center">
                                                {selectedCountry ? (
                                                    <>
                                                        <img 
                                                            src={`/flags/${selectedCountry.code?.toLowerCase() || 'placeholder'}.png`} 
                                                            alt={selectedCountry.text}
                                                            className="me-3"
                                                            style={{ width: '20px', height: '15px', objectFit: 'cover' }}
                                                            onError={(e) => {
                                                                e.target.src = '/flags/placeholder.png';
                                                            }}
                                                        />
                                                        <span className="fw-bold text-gray-800">{selectedCountry.text}</span>
                                                    </>
                                                ) : (
                                                    <span className="text-muted">Select Country</span>
                                                )}
                                            </div>
                                            <div className="d-flex align-items-center">
                                                {selectedCountry && (
                                                    <button 
                                                        type="button"
                                                        className="btn btn-icon btn-sm btn-light-danger me-2"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handleRemoveCountry();
                                                        }}
                                                    >
                                                        <i className="ki-duotone ki-cross fs-2">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                    </button>
                                                )}
                                                <i className={`ki-duotone ki-down fs-2 ${showCountryList ? 'rotate-180' : ''}`}>
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                </i>
                                            </div>
                                        </div>
                                        
                                        {/* Country Dropdown List */}
                                        {showCountryList && (
                                            <div className="position-absolute top-100 start-0 w-100 bg-white border rounded-3 shadow-sm mt-1" style={{ zIndex: 1000, maxHeight: '300px', overflowY: 'auto' }}>
                                                <div className="p-2">
                                                    <input 
                                                        type="text" 
                                                        className="form-control form-control-sm mb-2" 
                                                        placeholder="Search countries..."
                                                        value={countrySearchTerm}
                                                        onChange={(e) => handleCountrySearch(e.target.value)}
                                                        onClick={(e) => e.stopPropagation()}
                                                    />
                                                </div>
                                                {loading.countries ? (
                                                    <div className="p-3 text-center">
                                                        <div className="spinner-border spinner-border-sm me-2" role="status">
                                                            <span className="visually-hidden">Loading...</span>
                                                        </div>
                                                        <span className="text-muted">Loading...</span>
                                                    </div>
                                                ) : filteredCountries.length > 0 ? (
                                                    filteredCountries.map((country) => (
                                                        <div 
                                                            key={country.id}
                                                            className="p-3 border-bottom cursor-pointer hover-bg-light d-flex align-items-center"
                                                            onMouseDown={(e) => {
                                                                e.preventDefault();
                                                                handleCountrySelect(country);
                                                            }}
                                                            style={{ cursor: 'pointer' }}
                                                        >
                                                            <img 
                                                                src={`/flags/${country.code?.toLowerCase() || 'placeholder'}.png`} 
                                                                alt={country.text}
                                                                className="me-3"
                                                                style={{ width: '20px', height: '15px', objectFit: 'cover' }}
                                                                onError={(e) => {
                                                                    e.target.src = '/flags/placeholder.png';
                                                                }}
                                                            />
                                                            <div className="fw-bold text-gray-800">{country.text}</div>
                                                        </div>
                                                    ))
                                                ) : (
                                                    <div className="p-3 text-muted text-center">No countries found</div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    {errors.country_id && (
                                        <div className="invalid-feedback d-block">{errors.country_id[0]}</div>
                                    )}
                                </div>
                            </div>

                            {/* City Dropdown */}
                            <div className="row mb-6">
                                <label className="col-lg-3 col-form-label fw-bold fs-6">City</label>
                                <div className="col-lg-9 fv-row">
                                    <div className="position-relative">
                                        <div 
                                            className={`form-control form-control-lg form-control-solid d-flex align-items-center justify-content-between cursor-pointer ${errors.city_id ? 'is-invalid' : ''}`}
                                            onClick={() => {
                                                if (selectedCountry || formData.country_id) {
                                                    setShowCityList(!showCityList);
                                                }
                                            }}
                                            style={{ 
                                                cursor: (selectedCountry || formData.country_id) ? 'pointer' : 'not-allowed',
                                                opacity: (selectedCountry || formData.country_id) ? 1 : 0.6,
                                                minHeight: '50px'
                                            }}
                                        >
                                            <div className="d-flex align-items-center">
                                                {selectedCity ? (
                                                    <span className="fw-bold text-gray-800">{selectedCity.text}</span>
                                                ) : (
                                                    <span className="text-muted">
                                                        {!(selectedCountry || formData.country_id) ? 'Please select a country first' : 'Select City'}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="d-flex align-items-center">
                                                {selectedCity && (
                                                    <button 
                                                        type="button"
                                                        className="btn btn-icon btn-sm btn-light-danger me-2"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handleRemoveCity();
                                                        }}
                                                    >
                                                        <i className="ki-duotone ki-cross fs-2">
                                                            <span className="path1"></span>
                                                            <span className="path2"></span>
                                                        </i>
                                                    </button>
                                                )}
                                                <i className={`ki-duotone ki-down fs-2 ${showCityList ? 'rotate-180' : ''}`}>
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                </i>
                                            </div>
                                        </div>
                                        
                                        {/* City Dropdown List */}
                                        {showCityList && (selectedCountry || formData.country_id) && (
                                            <div className="position-absolute top-100 start-0 w-100 bg-white border rounded-3 shadow-sm mt-1" style={{ zIndex: 1000, maxHeight: '300px', overflowY: 'auto' }}>
                                                <div className="p-2">
                                                    <input 
                                                        type="text" 
                                                        className="form-control form-control-sm mb-2" 
                                                        placeholder="Search cities..."
                                                        value={citySearchTerm}
                                                        onChange={(e) => handleCitySearch(e.target.value)}
                                                        onClick={(e) => e.stopPropagation()}
                                                    />
                                                </div>
                                                {filteredCities.length > 0 ? (
                                                    filteredCities.map((city) => (
                                                        <div 
                                                            key={city.id}
                                                            className="p-3 border-bottom cursor-pointer hover-bg-light d-flex align-items-center"
                                                            onMouseDown={(e) => {
                                                                e.preventDefault();
                                                                handleCitySelect(city);
                                                            }}
                                                            style={{ cursor: 'pointer' }}
                                                        >
                                                            <div className="fw-bold text-gray-800">{city.text}</div>
                                                        </div>
                                                    ))
                                                ) : (
                                                    <div className="p-3 text-muted text-center">No cities found</div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    {errors.city_id && (
                                        <div className="invalid-feedback d-block">{errors.city_id[0]}</div>
                                    )}
                                </div>
                            </div>

                            <div className="card-footer d-flex justify-content-end py-6 px-9">
                                <button
                                    type="button"
                                    onClick={onCancel}
                                    className="btn btn-light btn-active-light-primary me-2"
                                    disabled={submitting}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="btn btn-primary"
                                    disabled={submitting}
                                >
                                    {submitting ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Submitting...
                                        </>
                                    ) : (
                                        'Submit Changes'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default EditMerchantProfile;

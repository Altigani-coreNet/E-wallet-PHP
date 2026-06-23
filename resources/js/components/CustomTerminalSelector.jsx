import { useState, useEffect } from 'react';
import axios from 'axios';

const CustomTerminalSelector = ({ 
    selectedTerminals, 
    onTerminalChange, 
    isMerchantContext = false,
    className = '' 
}) => {
    const [terminals, setTerminals] = useState([]);
    const [filteredTerminals, setFilteredTerminals] = useState([]);
    const [brands, setBrands] = useState([]);
    const [models, setModels] = useState([]);
    const [manufacturers, setManufacturers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [apiLoading, setApiLoading] = useState(false);
    const [brandsLoading, setBrandsLoading] = useState(false);
    const [modelsLoading, setModelsLoading] = useState(false);
    const [manufacturersLoading, setManufacturersLoading] = useState(false);
    const [brandsError, setBrandsError] = useState(null);
    const [modelsError, setModelsError] = useState(null);
    const [manufacturersError, setManufacturersError] = useState(null);
    
    // Filter states
    const [selectedBrands, setSelectedBrands] = useState([]);
    const [selectedModels, setSelectedModels] = useState([]);
    const [selectedManufacturers, setSelectedManufacturers] = useState([]);
    
    // UI states
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [activeTab, setActiveTab] = useState('all');
    const [showBrandDropdown, setShowBrandDropdown] = useState(false);
    const [showModelDropdown, setShowModelDropdown] = useState(false);
    const [showManufacturerDropdown, setShowManufacturerDropdown] = useState(false);

    // Load terminals and initial brands
    useEffect(() => {
        loadTerminals();
        loadBrands();
    }, []);

    // Load models when brands selection changes
    useEffect(() => {
        if (selectedBrands.length > 0) {
            loadModelsForBrands(selectedBrands);
        } else {
            setModels([]);
            setSelectedModels([]);
        }
    }, [selectedBrands]);

    // Load manufacturers when models selection changes
    useEffect(() => {
        if (selectedModels.length > 0) {
            loadManufacturersForModels(selectedModels);
        } else {
            setManufacturers([]);
            setSelectedManufacturers([]);
        }
    }, [selectedModels]);

    // Apply filters when selections change
    useEffect(() => {
        applyFilters();
    }, [selectedBrands, selectedModels, selectedManufacturers, searchTerm, terminals]);

    // Fetch filtered terminals from API when filters change
    useEffect(() => {
        if (selectedBrands.length > 0 || selectedModels.length > 0 || selectedManufacturers.length > 0 || searchTerm) {
            fetchFilteredTerminals();
        } else {
            // If no filters, load all terminals
            loadTerminals();
        }
    }, [selectedBrands, selectedModels, selectedManufacturers, searchTerm]);

    const loadTerminals = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/api/softpos/admin/terminals/filters', {
                headers: {
                    'Authorization': 'Bearer test_token'
                }
            });
            
            let terminalsData = [];
            if (response.data && response.data.data) {
                terminalsData = response.data.data;
            } else if (response.data) {
                terminalsData = response.data;
            }
            
            setTerminals(terminalsData);
        } catch (error) {
            console.error('Error loading terminals:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadBrands = async () => {
        setBrandsLoading(true);
        setBrandsError(null);
        try {
            const response = await axios.get('/api/softpos/admin/brands', {
                headers: {
                    'Authorization': 'Bearer test_token'
                }
            });
            
            if (response.data && response.data.success && response.data.data) {
                const brandsData = response.data.data;
                setBrands(brandsData);
            } else {
                // Fallback: extract brands from terminals
                const terminalsResponse = await axios.get('/api/softpos/admin/terminals/filters', {
                    headers: {
                        'Authorization': 'Bearer test_token'
                    }
                });
                
                if (terminalsResponse.data && terminalsResponse.data.data) {
                    const terminalsData = terminalsResponse.data.data;
                    const uniqueBrands = [...new Set(terminalsData.map(t => t.brand).filter(Boolean))];
                    setBrands(uniqueBrands);
                }
            }
        } catch (error) {
            setBrandsError(error);
            console.error('Error loading brands:', error);
        } finally {
            setBrandsLoading(false);
        }
    };

    const loadModelsForBrands = async (selectedBrands) => {
        setModelsLoading(true);
        setModelsError(null);
        try {
            // Send selected brands to API to get related models
            const response = await axios.post('/api/softpos/admin/terminals/models-by-brands', {
                brands: selectedBrands
            }, {
                headers: {
                    'Authorization': 'Bearer test_token',
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.data && response.data.success && response.data.data) {
                setModels(response.data.data);
            } else {
                // Fallback: filter models from terminals based on selected brands
                const filteredTerminals = terminals.filter(t => selectedBrands.includes(t.brand));
                const uniqueModels = [...new Set(filteredTerminals.map(t => t.model).filter(Boolean))];
                setModels(uniqueModels);
            }
        } catch (error) {
            setModelsError(error);
            console.error('Error loading models for brands:', error);
            // Fallback: filter models from terminals based on selected brands
            const filteredTerminals = terminals.filter(t => selectedBrands.includes(t.brand));
            const uniqueModels = [...new Set(filteredTerminals.map(t => t.model).filter(Boolean))];
            setModels(uniqueModels);
        } finally {
            setModelsLoading(false);
        }
    };

    const loadManufacturersForModels = async (selectedModels) => {
        setManufacturersLoading(true);
        setManufacturersError(null);
        try {
            // Send selected models to API to get related manufacturers
            const response = await axios.post('/api/softpos/admin/terminals/manufacturers-by-models', {
                models: selectedModels
            }, {
                headers: {
                    'Authorization': 'Bearer test_token',
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.data && response.data.success && response.data.data) {
                setManufacturers(response.data.data);
            } else {
                // Fallback: filter manufacturers from terminals based on selected models
                const filteredTerminals = terminals.filter(t => selectedModels.includes(t.model));
                const uniqueManufacturers = [...new Set(filteredTerminals.map(t => t.manufacturer).filter(Boolean))];
                setManufacturers(uniqueManufacturers);
            }
        } catch (error) {
            setManufacturersError(error);
            console.error('Error loading manufacturers for models:', error);
            // Fallback: filter manufacturers from terminals based on selected models
            const filteredTerminals = terminals.filter(t => selectedModels.includes(t.model));
            const uniqueManufacturers = [...new Set(filteredTerminals.map(t => t.manufacturer).filter(Boolean))];
            setManufacturers(uniqueManufacturers);
        } finally {
            setManufacturersLoading(false);
        }
    };

    const fetchFilteredTerminals = async () => {
        setApiLoading(true);
        try {
            // Prepare filter parameters
            const filterParams = new URLSearchParams();
            
            if (selectedBrands.length > 0) {
                filterParams.append('brand', selectedBrands.join(','));
            }
            
            if (selectedModels.length > 0) {
                filterParams.append('model', selectedModels.join(','));
            }
            
            if (selectedManufacturers.length > 0) {
                filterParams.append('manufacturer', selectedManufacturers.join(','));
            }
            
            if (searchTerm) {
                filterParams.append('search', searchTerm);
            }

            // Make API call to get filtered terminals
            const response = await axios.get(`/api/softpos/admin/terminals/filters?${filterParams.toString()}`, {
                headers: {
                    'Authorization': 'Bearer test_token'
                }
            });
            
            let terminalsData = [];
            if (response.data && response.data.data) {
                terminalsData = response.data.data;
            } else if (response.data) {
                terminalsData = response.data;
            }
            
            setTerminals(terminalsData);
            
        } catch (error) {
            console.error('Error fetching filtered terminals:', error);
            // Fallback to local filtering if API fails
            applyFilters();
        } finally {
            setApiLoading(false);
        }
    };

    const applyFilters = () => {
        let filtered = [...terminals];

        // Apply brand filters
        if (selectedBrands.length > 0) {
            filtered = filtered.filter(terminal => selectedBrands.includes(terminal.brand));
        }

        // Apply model filters
        if (selectedModels.length > 0) {
            filtered = filtered.filter(terminal => selectedModels.includes(terminal.model));
        }

        // Apply manufacturer filters
        if (selectedManufacturers.length > 0) {
            filtered = filtered.filter(terminal => selectedManufacturers.includes(terminal.manufacturer));
        }

        // Apply search filter
        if (searchTerm) {
            filtered = filtered.filter(terminal => 
                terminal.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                terminal.terminal_id.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }

        setFilteredTerminals(filtered);
    };

    const handleFilterChange = (filterType, value) => {
        // Close all dropdowns
        setShowBrandDropdown(false);
        setShowModelDropdown(false);
        setShowManufacturerDropdown(false);
        
        switch (filterType) {
            case 'brand':
                const newSelectedBrands = selectedBrands.includes(value) 
                    ? selectedBrands.filter(brand => brand !== value)
                    : [...selectedBrands, value];
                
                setSelectedBrands(newSelectedBrands);
                
                // Clear dependent selections when brands change
                if (newSelectedBrands.length === 0) {
                    setSelectedModels([]);
                    setSelectedManufacturers([]);
                } else if (!newSelectedBrands.includes(value)) {
                    // If removing a brand, clear models and manufacturers that depend on it
                    setSelectedModels([]);
                    setSelectedManufacturers([]);
                }
                break;
                
            case 'model':
                const newSelectedModels = selectedModels.includes(value) 
                    ? selectedModels.filter(model => model !== value)
                    : [...selectedModels, value];
                
                setSelectedModels(newSelectedModels);
                
                // Clear dependent selections when models change
                if (newSelectedModels.length === 0) {
                    setSelectedManufacturers([]);
                } else if (!newSelectedModels.includes(value)) {
                    // If removing a model, clear manufacturers that depend on it
                    setSelectedManufacturers([]);
                }
                break;
                
            case 'manufacturer':
                setSelectedManufacturers(prev => 
                    prev.includes(value) 
                        ? prev.filter(manufacturer => manufacturer !== value)
                        : [...prev, value]
                );
                break;
                
            default:
                break;
        }
    };

    const clearAllFilters = () => {
        setSelectedBrands([]);
        setSelectedModels([]);
        setSelectedManufacturers([]);
        setSearchTerm('');
        setShowBrandDropdown(false);
        setShowModelDropdown(false);
        setShowManufacturerDropdown(false);
        
        // Reset models and manufacturers to empty arrays
        setModels([]);
        setManufacturers([]);
        
        // Reload all terminals when filters are cleared
        loadTerminals();
    };

    const handleTerminalToggle = (terminalId) => {
        const newSelection = selectedTerminals.includes(terminalId)
            ? selectedTerminals.filter(id => id !== terminalId)
            : [...selectedTerminals, terminalId];
        
        onTerminalChange(newSelection);
    };

    const handleSelectAll = () => {
        if (selectedTerminals.length === filteredTerminals.length) {
            // If all are selected, deselect all
            onTerminalChange([]);
        } else {
            // If not all are selected, select all filtered terminals
            const allFilteredTerminalIds = filteredTerminals.map(terminal => terminal.id);
            onTerminalChange(allFilteredTerminalIds);
        }
    };

    const getFilteredOptions = (filterType) => {
        let options = [];
        
        switch (filterType) {
            case 'brand':
                options = brands;
                break;
            case 'model':
                options = models;
                break;
            case 'manufacturer':
                options = manufacturers;
                break;
        }
        
        return options;
    };

    const getActiveFiltersCount = () => {
        return selectedBrands.length + selectedModels.length + selectedManufacturers.length;
    };

    return (
        <div className={`custom-terminal-selector ${className}`}>
            {/* Custom CSS for better tab styling */}
            <style jsx>{`
                .custom-terminal-selector .nav-tabs .nav-link {
                    border-radius: 0.5rem;
                    margin-bottom: 0.5rem;
                    transition: all 0.2s ease;
                }
                .custom-terminal-selector .nav-tabs .nav-link:hover {
                    background-color: #f8f9fa;
                }
                .custom-terminal-selector .nav-tabs .nav-link.active {
                    background-color: #0d6efd;
                    color: white;
                    border-color: #0d6efd;
                }
                .custom-terminal-selector .filter-dropdown {
                    position: relative;
                    z-index: 10;
                }
                .custom-terminal-selector .w-md-200px {
                    width: 200px;
                }
                .custom-terminal-selector .brand-model-badge {
                    cursor: pointer;
                    transition: all 0.2s ease;
                    margin: 0.25rem;
                }
                .custom-terminal-selector .brand-model-badge:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .custom-terminal-selector .brand-model-badge.active {
                    background-color: #0d6efd !important;
                    border-color: #0d6efd !important;
                }
                @media (max-width: 767.98px) {
                    .custom-terminal-selector .w-md-200px {
                        width: auto;
                    }
                }
            `}</style>

            {/* Brands and Models Header */}
            <div className="brands-models-header mb-4 p-4 bg-white border rounded shadow-sm">
                <h5 className="mb-3 fw-bold text-primary">
                    <i className="ki-duotone ki-tag fs-4 me-2">
                        <span className="path1"></span>
                        <span className="path2"></span>
                    </i>
                    Available Brands & Models
                </h5>
                
                {brandsLoading ? (
                    <div className="text-center py-3">
                        <div className="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                        <span className="text-muted">Loading brands...</span>
                    </div>
                ) : brandsError ? (
                    <div className="alert alert-warning py-2">
                        <i className="ki-duotone ki-warning fs-5 me-2"></i>
                        <small>Unable to load brands. Using fallback data.</small>
                    </div>
                ) : (
                    <>
                        {/* Brands Section - Always Visible */}
                        <div className="mb-4">
                            <h6 className="mb-2 fw-semibold text-dark">
                                <i className="ki-duotone ki-star fs-5 me-2 text-warning"></i>
                                Brands ({brands.length})
                            </h6>
                            <div className="d-flex flex-wrap gap-2">
                                {brands.length > 0 ? (
                                    brands.map(brand => (
                                        <span
                                            key={brand}
                                            className={`brand-model-badge badge ${selectedBrands.includes(brand) ? 'active' : 'bg-light text-dark border'}`}
                                            onClick={() => handleFilterChange('brand', brand)}
                                            title={`Filter by ${brand}`}
                                        >
                                            {brand}
                                        </span>
                                    ))
                                ) : (
                                    <span className="text-muted small">No brands available</span>
                                )}
                            </div>
                        </div>

                        {/* Models Section - Only visible when brands are selected */}
                        {selectedBrands.length > 0 && (
                            <div className="mb-3">
                                <h6 className="mb-2 fw-semibold text-dark">
                                    <i className="ki-duotone ki-gear fs-5 me-2 text-info"></i>
                                    Models for Selected Brands ({models.length})
                                </h6>
                                {modelsLoading ? (
                                    <div className="text-center py-2">
                                        <div className="spinner-border spinner-border-sm text-info me-2" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                        <span className="text-muted small">Loading models...</span>
                                    </div>
                                ) : modelsError ? (
                                    <div className="alert alert-warning py-2">
                                        <i className="ki-duotone ki-warning fs-5 me-2"></i>
                                        <small>Unable to load models. Using fallback data.</small>
                                    </div>
                                ) : (
                                    <div className="d-flex flex-wrap gap-2">
                                        {models.length > 0 ? (
                                            models.map(model => (
                                                <span
                                                    key={model}
                                                    className={`brand-model-badge badge ${selectedModels.includes(model) ? 'active' : 'bg-light text-dark border'}`}
                                                    onClick={() => handleFilterChange('model', model)}
                                                    title={`Filter by ${model}`}
                                                >
                                                    {model}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-muted small">No models available for selected brands</span>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Manufacturers Section - Only visible when models are selected */}
                        {selectedModels.length > 0 && (
                            <div className="mb-3">
                                <h6 className="mb-2 fw-semibold text-dark">
                                    <i className="ki-duotone ki-factory fs-5 me-2 text-success"></i>
                                    Manufacturers for Selected Models ({manufacturers.length})
                                </h6>
                                {manufacturersLoading ? (
                                    <div className="text-center py-2">
                                        <div className="spinner-border spinner-border-sm text-success me-2" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                        <span className="text-muted small">Loading manufacturers...</span>
                                    </div>
                                ) : manufacturersError ? (
                                    <div className="alert alert-warning py-2">
                                        <i className="ki-duotone ki-warning fs-5 me-2"></i>
                                        <small>Unable to load manufacturers. Using fallback data.</small>
                                    </div>
                                ) : (
                                    <div className="d-flex flex-wrap gap-2">
                                        {manufacturers.length > 0 ? (
                                            manufacturers.map(manufacturer => (
                                                <span
                                                    key={manufacturer}
                                                    className={`brand-model-badge badge ${selectedManufacturers.includes(manufacturer) ? 'active' : 'bg-light text-dark border'}`}
                                                    onClick={() => handleFilterChange('manufacturer', manufacturer)}
                                                    title={`Filter by ${manufacturer}`}
                                                >
                                                    {manufacturer}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-muted small">No manufacturers available for selected models</span>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                    </>
                )}

                {/* Quick Actions */}
                <div className="d-flex gap-2 mt-3">
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => {
                            if (selectedBrands.length > 0 || selectedModels.length > 0 || selectedManufacturers.length > 0) {
                                clearAllFilters();
                            }
                        }}
                                                        disabled={selectedBrands.length === 0 && selectedModels.length === 0 && selectedManufacturers.length === 0}
                    >
                        <i className="ki-duotone ki-refresh fs-5 me-1"></i>
                        Clear Filters
                    </button>
                    <span className="text-muted small align-self-center">
                        Click on any brand or model to filter terminals
                    </span>
                </div>
            </div>

            {/* Filter Top Bar - Shows selected filters */}
            <div className="filter-top-bar mb-3 p-3 bg-light rounded">
                <div className="d-flex justify-content-between align-items-center">
                    <div className="d-flex align-items-center gap-2">
                        <span className="fw-bold">Active Filters:</span>
                        {getActiveFiltersCount() === 0 ? (
                            <span className="text-muted">None selected</span>
                        ) : (
                            <>
                                {selectedBrands.length > 0 && (
                                    <span className="badge bg-primary me-2">
                                        Brands: {selectedBrands.join(', ')}
                                        <button 
                                            className="btn-close btn-close-white ms-2" 
                                            onClick={() => setSelectedBrands([])}
                                            style={{ fontSize: '0.5rem' }}
                                        />
                                    </span>
                                )}
                                {selectedModels.length > 0 && (
                                    <span className="badge bg-success me-2">
                                        Models: {selectedModels.join(', ')}
                                        <button 
                                            className="btn-close btn-close-white ms-2" 
                                            onClick={() => setSelectedModels([])}
                                            style={{ fontSize: '0.5rem' }}
                                        />
                                    </span>
                                )}
                                {selectedManufacturers.length > 0 && (
                                    <span className="badge bg-info me-2">
                                        Manufacturers: {selectedManufacturers.join(', ')}
                                        <button 
                                            className="btn-close btn-close-white ms-2" 
                                            onClick={() => setSelectedManufacturers([])}
                                            style={{ fontSize: '0.5rem' }}
                                        />
                                    </span>
                                )}
                            </>
                        )}
                    </div>
                    {getActiveFiltersCount() > 0 && (
                        <button 
                            className="btn btn-sm btn-outline-secondary"
                            onClick={clearAllFilters}
                        >
                            Clear All
                        </button>
                    )}
                </div>
            </div>

            {/* Main Selector */}
            <div className="terminal-selector-container">
                {/* Search Bar */}
                <div className="search-container mb-3">
                    <div className="input-group">
                        <span className="input-group-text">
                            <i className="ki-duotone ki-magnifier fs-3">
                                <span className="path1"></span>
                                <span className="path2"></span>
                            </i>
                        </span>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Search terminals by name or ID..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                </div>

                <div className="row">
                    {/* Sidebar Filters */}
                    {/* <div className="col-md-3">
                        <div className="filter-sidebar">
                            <h6 className="mb-3 fw-bold">Filters</h6>
                            
                            {/* Tab-based Filters */}
                            {/* <ul className="nav nav-tabs nav-pills border-0 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6">
                                <li className="nav-item w-md-200px me-0">
                                    <a 
                                        className={`nav-link ${activeTab === 'all' ? 'active' : ''}`} 
                                        data-bs-toggle="tab" 
                                        href="#all-terminals"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setActiveTab('all');
                                            clearAllFilters();
                                            setShowBrandDropdown(false);
                                            setShowModelDropdown(false);
                                            setShowManufacturerDropdown(false);
                                        }}
                                    >
                                        All Terminals
                                    </a>
                                </li>
                                <li className="nav-item w-md-200px me-0">
                                    <a 
                                        className={`nav-link ${activeTab === 'brand' ? 'active' : ''}`} 
                                        data-bs-toggle="tab" 
                                        href="#brand-filter"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setActiveTab('brand');
                                            if (selectedBrands.length === 0) {
                                                setShowBrandDropdown(!showBrandDropdown);
                                                setShowModelDropdown(false);
                                                setShowManufacturerDropdown(false);
                                            }
                                        }}
                                    >
                                        By Brand {selectedBrands.length > 0 && `(${selectedBrands.join(', ')})`}
                                    </a>
                                </li>
                                <li className="nav-item w-md-200px me-0">
                                    <a 
                                        className={`nav-link ${activeTab === 'model' ? 'active' : ''}`} 
                                        data-bs-toggle="tab" 
                                        href="#model-filter"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setActiveTab('model');
                                            if (selectedModels.length === 0) {
                                                setShowModelDropdown(!showModelDropdown);
                                                setShowBrandDropdown(false);
                                                setShowManufacturerDropdown(false);
                                            }
                                        }}
                                    >
                                        By Model {selectedModels.length > 0 && `(${selectedModels.join(', ')})`}
                                    </a>
                                </li>
                                <li className="nav-item w-md-200px">
                                    <a 
                                        className={`nav-link ${activeTab === 'manufacturer' ? 'active' : ''}`} 
                                        data-bs-toggle="tab" 
                                        href="#manufacturer-filter"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setActiveTab('manufacturer');
                                            if (selectedManufacturers.length === 0) {
                                                setShowManufacturerDropdown(!showManufacturerDropdown);
                                                setShowBrandDropdown(false);
                                                setShowModelDropdown(false);
                                            }
                                        }}
                                    >
                                        By Manufacturer {selectedManufacturers.length > 0 && `(${selectedManufacturers.join(', ')})`}
                                    </a>
                                </li>
                            </ul> */}

                            {/* Dropdown Selections - Commented out for now */}
                            {/* {showBrandDropdown && (
                                <div className="filter-dropdown mb-3 p-3 border rounded bg-white shadow-sm">
                                    <label className="form-label fw-semibold mb-2">Select Brand</label>
                                    <div className="d-flex flex-column gap-1">
                                        {getFilteredOptions('brand').map(brand => (
                                            <button
                                                key={brand}
                                                className="btn btn-sm btn-outline-primary text-start"
                                                onClick={() => {
                                                    handleFilterChange('brand', brand);
                                                    setShowBrandDropdown(false);
                                                }}
                                            >
                                                {brand}
                                            </button>
                                        ))}
                                    </div>
                                    <button
                                        className="btn btn-sm btn-outline-secondary mt-2"
                                        onClick={() => setShowBrandDropdown(false)}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            )}

                            {showModelDropdown && (
                                <div className="filter-dropdown mb-3 p-3 border rounded bg-white shadow-sm">
                                    <label className="form-label fw-semibold mb-2">Select Model</label>
                                    <div className="d-flex flex-column gap-1">
                                        {getFilteredOptions('model').map(model => (
                                            <button
                                                key={model}
                                                className="btn btn-sm btn-outline-primary text-start"
                                                onClick={() => {
                                                    handleFilterChange('model', model);
                                                    setShowModelDropdown(false);
                                                }}
                                            >
                                                {model}
                                            </button>
                                        ))}
                                    </div>
                                    <button
                                        className="btn btn-sm btn-outline-secondary mt-2"
                                        onClick={() => setShowModelDropdown(false)}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            )}

                            {showManufacturerDropdown && (
                                <div className="filter-dropdown mb-3 p-3 border rounded bg-white shadow-sm">
                                    <label className="form-label fw-semibold mb-2">Select Manufacturer</label>
                                    <div className="d-flex flex-column gap-1">
                                        {getFilteredOptions('manufacturer').map(manufacturer => (
                                            <button
                                                key={manufacturer}
                                                className="btn btn-sm btn-outline-primary text-start"
                                                onClick={() => {
                                                    handleFilterChange('manufacturer', manufacturer);
                                                    setShowManufacturerDropdown(false);
                                                }}
                                            >
                                                {manufacturer}
                                            </button>
                                        ))}
                                    </div>
                                    <button
                                        className="btn btn-sm btn-outline-secondary mt-2"
                                        onClick={() => setShowManufacturerDropdown(false)}
                                    >
                                        Cancel
                                    </button>
                                </div>
                            )} 


                        </div>
                    </div> */}

                    {/* Terminal List */}
                    <div className="p-3">
                                                {/* Filter Navigation with Badges */}
                       

                        {/* Results Count */}
                        <div className="results-count mb-3 p-2 bg-light rounded">
                            <small className="text-muted">
                                Showing {filteredTerminals.length} of {terminals.length} terminals
                            </small>
                        </div>

                        <div className="terminal-list">
                            {/* Filter Loading Indicator */}
                            {apiLoading && (selectedBrands.length > 0 || selectedModels.length > 0 || selectedManufacturers.length > 0 || searchTerm) && (
                                <div className="alert alert-info py-2 mb-3">
                                    <div className="d-flex align-items-center">
                                        <div className="spinner-border spinner-border-sm text-info me-2" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                        <span className="small">Fetching filtered terminals from server...</span>
                                    </div>
                                </div>
                            )}
                            
                            {/* Active Filters Display */}
                            {(selectedBrands.length > 0 || selectedModels.length > 0 || selectedManufacturers.length > 0 || searchTerm) && (
                                <div className="active-filters mb-3 p-2 bg-light border rounded">
                                    <small className="text-muted me-2">Active filters:</small>
                                    {selectedBrands.length > 0 && (
                                        <span className="badge bg-primary me-1">Brands: {selectedBrands.join(', ')}</span>
                                    )}
                                    {selectedModels.length > 0 && (
                                        <span className="badge bg-info me-1">Models: {selectedModels.join(', ')}</span>
                                    )}
                                    {selectedManufacturers.length > 0 && (
                                        <span className="badge bg-success me-1">Manufacturers: {selectedManufacturers.join(', ')}</span>
                                    )}
                                    {searchTerm && (
                                        <span className="badge bg-warning me-1">Search: "{searchTerm}"</span>
                                    )}
                                </div>
                            )}
                            
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <div className="d-flex align-items-center gap-3">
                                    <h6 className="mb-0 fw-bold">
                                        Terminals ({filteredTerminals.length})
                                    </h6>
                                    {filteredTerminals.length > 0 && (
                                        <div className="d-flex align-items-center gap-2">
                                            <button
                                                className={`btn btn-sm ${selectedTerminals.length === filteredTerminals.length ? 'btn-success' : 'btn-outline-primary'}`}
                                                onClick={() => handleSelectAll()}
                                                title={selectedTerminals.length === filteredTerminals.length ? 'Deselect All' : 'Select All'}
                                            >
                                                <i className={`ki-duotone ${selectedTerminals.length === filteredTerminals.length ? 'ki-check' : 'ki-plus'} fs-5 me-1`}>
                                                    <span className="path1"></span>
                                                    <span className="path2"></span>
                                                </i>
                                                {selectedTerminals.length === filteredTerminals.length ? 'Deselect All' : 'Select All'}
                                            </button>
                                            {selectedTerminals.length > 0 && selectedTerminals.length < filteredTerminals.length && (
                                                <small className="text-muted">
                                                    ({selectedTerminals.length} of {filteredTerminals.length})
                                                </small>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <small className="text-muted">
                                    {selectedTerminals.length} selected
                                </small>
                            </div>

                            {loading ? (
                                <div className="text-center py-4">
                                    <div className="spinner-border text-primary" role="status">
                                        <span className="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            ) : filteredTerminals.length === 0 ? (
                                <div className="text-center py-4 text-muted">
                                    <i className="ki-duotone ki-search fs-2x mb-2"></i>
                                    <p>No terminals found matching your criteria</p>
                                </div>
                            ) : (
                                <div className="terminal-grid">
                                    {/* Select All Header */}
                                    <div className="select-all-header p-2 mb-2 bg-light border rounded">
                                        <div className="form-check d-flex align-items-center">
                                            <input
                                                className="form-check-input me-2"
                                                type="checkbox"
                                                checked={selectedTerminals.length === filteredTerminals.length && filteredTerminals.length > 0}
                                                onChange={handleSelectAll}
                                                id="select-all-terminals"
                                            />
                                            <label className="form-check-label fw-semibold mb-0" htmlFor="select-all-terminals">
                                                {selectedTerminals.length === filteredTerminals.length && filteredTerminals.length > 0 
                                                    ? 'Deselect All' 
                                                    : 'Select All'
                                                } Terminals
                                                {selectedTerminals.length > 0 && (
                                                    <span className="text-muted ms-2">
                                                        ({selectedTerminals.length} of {filteredTerminals.length})
                                                    </span>
                                                )}
                                            </label>
                                        </div>
                                    </div>
                                    
                                    {filteredTerminals.map(terminal => (
                                        <div 
                                            key={terminal.id} 
                                            className={`terminal-item p-3 border rounded mb-2 cursor-pointer ${
                                                selectedTerminals.includes(terminal.id) 
                                                    ? 'border-primary bg-primary bg-opacity-10' 
                                                    : 'border-light'
                                            }`}
                                            onClick={() => handleTerminalToggle(terminal.id)}
                                        >
                                            <div className="d-flex align-items-center">
                                                <div className="form-check me-3">
                                                    <input
                                                        className="form-check-input"
                                                        type="checkbox"
                                                        checked={selectedTerminals.includes(terminal.id)}
                                                        onChange={() => handleTerminalToggle(terminal.id)}
                                                        onClick={(e) => e.stopPropagation()}
                                                    />
                                                </div>
                                                <div className="flex-grow-1">
                                                    <div className="fw-semibold">{terminal.name}</div>
                                                    <div className="row">
                                                    <div className="text-muted small col-md-6">
                                                        ID: {terminal.terminal_id}
                                                    </div>
                                                    {terminal.brand && (
                                                        <div className="text-muted small col-md-6">
                                                            Brand: {terminal.brand}
                                                        </div>
                                                    )}
                                                    {terminal.model && (
                                                        <div className="text-muted small col-md-6">
                                                            Model: {terminal.model}
                                                        </div>
                                                    )}
                                                    {terminal.manufacturer && (
                                                        <div className="text-muted small col-md-6">
                                                            Manufacturer: {terminal.manufacturer}
                                                        </div>
                                                    )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Selected Terminals Summary */}
            {selectedTerminals.length > 0 && (
                <div className="selected-summary mt-3 p-3 bg-primary bg-opacity-10 rounded">
                    <h6 className="mb-2 fw-bold">
                        Selected Terminals ({selectedTerminals.length})
                    </h6>
                    <div className="selected-list">
                        {terminals
                            .filter(t => selectedTerminals.includes(t.id))
                            .map(terminal => (
                                <span key={terminal.id} className="badge bg-primary me-2 mb-1">
                                    {terminal.name} ({terminal.terminal_id})
                                </span>
                            ))
                        }
                    </div>
                </div>
            )}
        </div>
    );
};

export default CustomTerminalSelector; 
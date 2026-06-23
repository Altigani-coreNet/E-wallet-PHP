<!-- Assign Terminals Modal -->
<div class="modal fade" id="assignTerminalsModal" tabindex="-1" aria-labelledby="assignTerminalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTerminalsModalLabel">Assign Terminals</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="alert alert-info">
                            <strong>User:</strong> <span id="selectedUserName"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Terminal Assignment Options -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">Terminal Assignment Options</h6>
                        
                        <!--begin::Radio group-->
                        <div class="card">
                            <div class="card-body">
                                <div data-kt-buttons="true">
                                    <!--begin::Radio button - User Groups-->
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 mb-5">
                                        <div class="d-flex align-items-center me-2">
                                            <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                <input class="form-check-input" type="radio" name="assignmentType" value="user_groups" checked/>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                    User Groups
                                                </h2>
                                                <div class="fw-semibold opacity-50">
                                                    Select user groups for assignment
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-5">
                                            <i class="ki-duotone ki-users fs-2x text-primary"></i>
                                        </div>
                                    </label>
                                    <!--end::Radio button-->
        
                                    <!--begin::Radio button - Terminal Groups-->
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 mb-5">
                                        <div class="d-flex align-items-center me-2">
                                            <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                <input class="form-check-input" type="radio" name="assignmentType" value="terminal_groups"/>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                    Terminal Groups
                                                </h2>
                                                <div class="fw-semibold opacity-50">
                                                    Select terminal groups for assignment
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-5">
                                            <i class="ki-duotone ki-group fs-2x text-primary"></i>
                                        </div>
                                    </label>
                                    <!--end::Radio button-->
        
                                    <!--begin::Radio button - Terminals Directly-->
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6 mb-5">
                                        <div class="d-flex align-items-center me-2">
                                            <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                <input class="form-check-input" type="radio" name="assignmentType" value="terminals_directly"/>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                    Terminals Directly
                                                </h2>
                                                <div class="fw-semibold opacity-50">
                                                    Select individual terminals for assignment
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-5">
                                            <i class="ki-duotone ki-terminal fs-2x text-primary"></i>
                                        </div>
                                    </label>
                                    <!--end::Radio button-->
        
                                    <!--begin::Radio button - All Terminals Assignment-->
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex flex-stack text-start p-6">
                                        <div class="d-flex align-items-center me-2">
                                            <div class="form-check form-check-custom form-check-solid form-check-primary me-6">
                                                <input class="form-check-input" type="radio" name="assignmentType" value="all_terminals"/>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h2 class="d-flex align-items-center fs-3 fw-bold flex-wrap">
                                                    All Terminals Assignment
                                                </h2>
                                                <div class="fw-semibold opacity-50">
                                                    Assign all available terminals
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-5">
                                            <i class="ki-duotone ki-grid fs-2x text-primary"></i>
                                        </div>
                                    </label>
                                    <!--end::Radio button-->
                                </div>
                            </div>
                        </div>
                        <!--end::Radio group-->
                    </div>
                
                <div class="col-md-6">
                    <!-- Single Form Card -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title" id="formTitle">Select Assignment Options</h6>
                        </div>
                        <div class="card-body">
                            <!-- Branch Selection (for user_groups, terminal_groups, terminals_directly) -->
                            <div id="branchSelectionRow" class="row">
                                <div class="col-md-12">
                                    <label for="branchSelect" class="form-label">Branch</label>
                                    <select class="form-select" id="branchSelect" onchange="loadOptionsByBranch()">
                                        <option value="">Select Branch</option>
                                    </select>
                                </div>
                            </div>

                            <!-- User Groups Selection -->
                            <div id="userGroupsRow" class="row mt-3">
                                <div class="col-md-12">
                                    <label for="userGroupsSelect" class="form-label">User Groups</label>
                                    <select class="form-select select2-multiple" id="userGroupsSelect" multiple="multiple" data-placeholder="Select User Groups">
                                        <option value="">Select User Groups</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Terminal Groups Selection -->
                            <div id="terminalGroupsRow" class="row mt-3" style="display: none;">
                                <div class="col-md-12">
                                    <label for="terminalGroupsSelect" class="form-label">Terminal Groups</label>
                                    <select class="form-select select2-multiple" id="terminalGroupsSelect" multiple="multiple" data-placeholder="Select Terminal Groups">
                                        <option value="">Select Terminal Groups</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Terminals Selection -->
                            <div id="terminalsRow" class="row mt-3" style="display: none;">
                                <div class="col-md-12">
                                    <label for="terminalsSelect" class="form-label">Terminals</label>
                                    <select class="form-select select2-multiple" id="terminalsSelect" multiple="multiple" data-placeholder="Select Terminals">
                                        <option value="">Select Terminals</option>
                                    </select>
                                </div>
                            </div>

                            <!-- All Terminals Warning -->
                            <div id="allTerminalsWarning" class="mt-3" style="display: none;">
                                <div class="alert alert-warning">
                                    <i class="ki-duotone ki-information-5 fs-2x text-warning me-4"></i>
                                    <div class="d-flex flex-column">
                                        <h4 class="mb-1">Warning</h4>
                                        <span>This will assign ALL available terminals to the selected user. This action cannot be undone easily.</span>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="ki-duotone ki-information-5 fs-2x text-info me-4"></i>
                                    <div class="d-flex flex-column">
                                        <h4 class="mb-1">Information</h4>
                                        <span>The user will have access to all devices across all branches.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignTerminals()">Assign Terminals</button>
            </div>
        </div>
    </div>
</div>

<script>
// Handle radio button changes
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="assignmentType"]');
    
    // Add event listeners to radio buttons
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            showFormFields(this.value);
        });
    });

    // Show default form fields (user_groups)
    showFormFields('user_groups');
});

// Show/hide form fields based on radio button selection
function showFormFields(selectedValue) {
    const formTitle = document.getElementById('formTitle');
    const branchRow = document.getElementById('branchSelectionRow');
    const userGroupsRow = document.getElementById('userGroupsRow');
    const terminalGroupsRow = document.getElementById('terminalGroupsRow');
    const terminalsRow = document.getElementById('terminalsRow');
    const allTerminalsWarning = document.getElementById('allTerminalsWarning');

    // Reset all fields
    document.getElementById('branchSelect').value = '';
    document.getElementById('userGroupsSelect').innerHTML = '<option value="">Select User Groups</option>';
    document.getElementById('terminalGroupsSelect').innerHTML = '<option value="">Select Terminal Groups</option>';
    document.getElementById('terminalsSelect').innerHTML = '<option value="">Select Terminals</option>';

    // Hide all rows first
    branchRow.style.display = 'none';
    userGroupsRow.style.display = 'none';
    terminalGroupsRow.style.display = 'none';
    terminalsRow.style.display = 'none';
    allTerminalsWarning.style.display = 'none';

    // Show appropriate fields based on selection
    switch(selectedValue) {
        case 'user_groups':
            formTitle.textContent = 'Select User Groups';
            branchRow.style.display = 'block';
            userGroupsRow.style.display = 'block';
            // Re-initialize select2 for user groups
            setTimeout(() => {
                reinitializeSelect2('userGroupsSelect');
            }, 100);
            break;
            
        case 'terminal_groups':
            formTitle.textContent = 'Select Terminal Groups';
            branchRow.style.display = 'block';
            terminalGroupsRow.style.display = 'block';
            // Re-initialize select2 for terminal groups
            setTimeout(() => {
                reinitializeSelect2('terminalGroupsSelect');
            }, 100);
            break;
            
        case 'terminals_directly':
            formTitle.textContent = 'Select Terminals Directly';
            branchRow.style.display = 'block';
            terminalsRow.style.display = 'block';
            // Re-initialize select2 for terminals
            setTimeout(() => {
                reinitializeSelect2('terminalsSelect');
            }, 100);
            break;
            
        case 'all_terminals':
            formTitle.textContent = 'Assign All Terminals';
            allTerminalsWarning.style.display = 'block';
            break;
    }
}

// Load options by branch based on current selection
function loadOptionsByBranch() {
    const assignmentType = document.querySelector('input[name="assignmentType"]:checked').value;
    const branchId = document.getElementById('branchSelect').value;
    
    if (!branchId) return;

    switch(assignmentType) {
        case 'user_groups':
            loadUserGroupsByBranch(branchId);
            break;
        case 'terminal_groups':
            loadTerminalGroupsByBranch(branchId);
            break;
        case 'terminals_directly':
            loadTerminalsByBranch(branchId);
            break;
    }
}

// Load user groups by branch
function loadUserGroupsByBranch(branchId) {
    fetch(`/api/branches/${branchId}/user-groups`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('userGroupsSelect');
            select.innerHTML = '<option value="">Select User Groups</option>';
            data.forEach(group => {
                select.innerHTML += `<option value="${group.id}">${group.name}</option>`;
            });
            // Update select2 with new data
            $('#userGroupsSelect').trigger('change');
        })
        .catch(error => console.error('Error loading user groups:', error));
}

// Load terminal groups by branch
function loadTerminalGroupsByBranch(branchId) {
    fetch(`/api/branches/${branchId}/terminal-groups`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('terminalGroupsSelect');
            select.innerHTML = '<option value="">Select Terminal Groups</option>';
            data.forEach(group => {
                select.innerHTML += `<option value="${group.id}">${group.name}</option>`;
            });
            // Update select2 with new data
            $('#terminalGroupsSelect').trigger('change');
        })
        .catch(error => console.error('Error loading terminal groups:', error));
}

// Load terminals by branch
function loadTerminalsByBranch(branchId) {
    fetch(`/api/branches/${branchId}/terminals`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('terminalsSelect');
            select.innerHTML = '<option value="">Select Terminals</option>';
            data.forEach(terminal => {
                select.innerHTML += `<option value="${terminal.id}">${terminal.name}</option>`;
            });
            // Update select2 with new data
            $('#terminalsSelect').trigger('change');
        })
        .catch(error => console.error('Error loading terminals:', error));
}

// Assign terminals function
function assignTerminals() {
    const assignmentType = document.querySelector('input[name="assignmentType"]:checked').value;
    const userId = document.getElementById('selectedUserName').getAttribute('data-user-id');
    
    let data = {
        user_id: userId,
        assignment_type: assignmentType
    };

    switch(assignmentType) {
        case 'user_groups':
            const userGroups = Array.from(document.getElementById('userGroupsSelect').selectedOptions).map(option => option.value);
            data.user_groups = userGroups;
            data.branch_id = document.getElementById('branchSelect').value;
            break;
            
        case 'terminal_groups':
            const terminalGroups = Array.from(document.getElementById('terminalGroupsSelect').selectedOptions).map(option => option.value);
            data.terminal_groups = terminalGroups;
            data.branch_id = document.getElementById('branchSelect').value;
            break;
            
        case 'terminals_directly':
            const terminals = Array.from(document.getElementById('terminalsSelect').selectedOptions).map(option => option.value);
            data.terminals = terminals;
            data.branch_id = document.getElementById('branchSelect').value;
            break;
            
        case 'all_terminals':
            // Show confirmation alert
            if (confirm('Are you sure you want to assign ALL terminals to this user? This will give them access to all devices across all branches.')) {
                // Proceed with assignment
                data.all_terminals = true;
            } else {
                return;
            }
            break;
    }

    // Send assignment request
    fetch('/api/assign-terminals', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Terminals assigned successfully!');
            $('#assignTerminalsModal').modal('hide');
            // Refresh the page or update the UI as needed
            location.reload();
        } else {
            alert('Error assigning terminals: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning terminals.');
    });
}

// Load branches when modal opens
function loadBranches() {
    fetch('/api/branches')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('branchSelect');
            select.innerHTML = '<option value="">Select Branch</option>';
            data.forEach(branch => {
                select.innerHTML += `<option value="${branch.id}">${branch.name}</option>`;
            });
        })
        .catch(error => console.error('Error loading branches:', error));
}

// Initialize when modal is shown
$('#assignTerminalsModal').on('shown.bs.modal', function() {
    loadBranches();
    // Delay select2 initialization to ensure DOM is ready
    setTimeout(function() {
        initializeSelect2();
    }, 200);
});

// Also initialize when modal is opened
$('#assignTerminalsModal').on('show.bs.modal', function() {
    // Destroy any existing select2 instances
    try {
        $('#userGroupsSelect').select2('destroy');
        $('#terminalGroupsSelect').select2('destroy');
        $('#terminalsSelect').select2('destroy');
    } catch (e) {
        // Ignore errors if select2 wasn't initialized
    }
});

// Initialize Select2 for multiple select fields
function initializeSelect2() {
    console.log('Initializing Select2...');
    
    // Check if select2 is available
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 is not loaded!');
        // Fallback: add multiple attribute and basic styling
        addMultipleSelectFallback();
        return;
    }
    
    try {
        // Initialize all select2 elements with class select2-multiple
        $('.select2-multiple').each(function() {
            const $select = $(this);
            const placeholder = $select.data('placeholder') || 'Select options';
            
            $select.select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                dropdownParent: $('#assignTerminalsModal')
            });
            
            console.log('Select2 initialized for:', $select.attr('id'));
        });
        
    } catch (error) {
        console.error('Error initializing Select2:', error);
        // Fallback if select2 fails
        addMultipleSelectFallback();
    }
}

// Fallback function for when select2 is not available
function addMultipleSelectFallback() {
    console.log('Using fallback multiple select styling');
    
    const selects = ['#userGroupsSelect', '#terminalGroupsSelect', '#terminalsSelect'];
    
    selects.forEach(selector => {
        const select = $(selector);
        if (select.length) {
            // Ensure multiple attribute is set
            select.attr('multiple', 'multiple');
            // Add some basic styling
            select.css({
                'min-height': '100px',
                'padding': '8px',
                'border': '1px solid #ddd',
                'border-radius': '4px'
            });
        }
    });
}

// Re-initialize Select2 when fields are shown
function reinitializeSelect2(fieldId) {
    console.log('Re-initializing Select2 for:', fieldId);
    
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 is not loaded!');
        return;
    }
    
    if ($('#' + fieldId).length) {
        try {
            $('#' + fieldId).select2('destroy');
            $('#' + fieldId).select2({
                placeholder: 'Select ' + fieldId.replace('Select', '').replace(/([A-Z])/g, ' $1').trim(),
                allowClear: true,
                width: '100%',
                dropdownParent: $('#assignTerminalsModal')
            });
            console.log('Select2 re-initialized for:', fieldId);
        } catch (error) {
            console.error('Error re-initializing Select2 for', fieldId, ':', error);
        }
    } else {
        console.warn('Element not found:', fieldId);
    }
}
</script> 
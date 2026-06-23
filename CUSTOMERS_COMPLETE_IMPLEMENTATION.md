# Customers Component - Complete Implementation 🎉

Complete customer management with breadcrumbs, toolbar actions, filters, import/export functionality!

---

## ✅ Features Implemented

### 1. **Breadcrumbs Navigation**
- Home → Sales → Customers
- Clickable navigation path
- Current page highlighted

### 2. **Toolbar Actions**
- ✅ **Filter** - Show/hide filter panel
- ✅ **Export** - Export customers to CSV
- ✅ **Import** - Import customers from CSV/Excel
- ✅ **Add Customer** - Create new customer

### 3. **Filter Panel**
- Date From / Date To filters
- Country filter
- Apply / Reset buttons
- Collapsible filter section

### 4. **Import Flow** (Same as SoftPos MerchantCustomerController)

#### Step 1: Open Import Modal
- Click "Import" button in toolbar
- Modal opens with instructions

#### Step 2: Download Sample Template
- Click "Download Sample Template" button
- Gets CSV template from: `/api/v2/sales/customers/export-template`
- Template includes: Name, Email, Phone, Company Name, Address, City, State, Postal Code, Country, Tax No

#### Step 3: Upload File
- Select CSV or Excel file (.csv, .xlsx, .xls)
- File is automatically previewed

#### Step 4: Preview Data
- Shows table with all rows from file
- ✅ Green checkmark = Valid row
- ❌ Red X = Invalid row (with error message)
- Displays validation errors at the top
- Shows first 5 errors (if more, shows count)

#### Step 5: Confirm Import
- Click "Confirm Import" button
- Imports valid rows
- Shows success message with counts
- Refreshes customer list

### 5. **Export Function**
- Exports all customers to CSV
- Applies current filters to export
- Includes search term if present
- Downloads file: `customers_export_YYYY-MM-DD_HHMMSS.csv`

### 6. **Search & Display**
- Real-time search by name, email, or phone
- Customer avatar with first letter
- Customer ID display
- Company name column
- Edit and Delete actions

---

## 🔗 API Endpoints Used

| Feature | Method | Endpoint |
|---------|--------|----------|
| **List Customers** | GET | `/api/v2/sales/customers` |
| **Delete Customer** | DELETE | `/api/v2/sales/customers/{id}` |
| **Export Customers** | GET | `/api/v2/sales/customers/export` |
| **Download Template** | GET | `/api/v2/sales/customers/export-template` |
| **Import Preview** | POST | `/api/v2/sales/customers/import-preview` |
| **Import Customers** | POST | `/api/v2/sales/customers/import` |

---

## 📸 Component Structure

```
<>
  {/* Toolbar with Breadcrumbs */}
  <div id="kt_app_toolbar">
    <Breadcrumbs />
    <ToolbarActions>
      - Filter Button
      - Export Button
      - Import Button
      - Add Customer Button
    </ToolbarActions>
  </div>

  {/* Content Area */}
  <div id="kt_app_content">
    
    {/* Filter Panel (collapsible) */}
    {showFilters && <FilterPanel />}
    
    {/* Customers Table Card */}
    <Card>
      <CardHeader>
        <Search Input />
      </CardHeader>
      <CardBody>
        <CustomersTable />
      </CardBody>
    </Card>
  </div>

  {/* Import Modal */}
  {showImportModal && <ImportModal />}
</>
```

---

## 🎯 Import Modal Flow

### Modal Sections:

1. **Header**
   - Title: "Import Customers"
   - Close button (X)

2. **Instructions Alert**
   - Icon + Step-by-step guide
   - Professional styling

3. **Download Template Button**
   - Full-width button
   - Downloads sample CSV

4. **File Upload Input**
   - Accepts: .csv, .xlsx, .xls
   - Auto-triggers preview on select

5. **Preview Section**
   - Loading spinner during processing
   - Table with validation status
   - Row count display
   - Error list (first 5 errors)
   - Scrollable table (max 300px height)

6. **Footer Actions**
   - Cancel button
   - Confirm Import button (disabled until preview loaded)

---

## 📋 State Management

```javascript
// Main states
const [customers, setCustomers] = useState([]);
const [loading, setLoading] = useState(true);
const [error, setError] = useState(null);
const [searchTerm, setSearchTerm] = useState('');

// Filter states
const [showFilters, setShowFilters] = useState(false);
const [filters, setFilters] = useState({
    country_id: '',
    date_from: '',
    date_to: '',
});

// Import states
const [showImportModal, setShowImportModal] = useState(false);
const [importFile, setImportFile] = useState(null);
const [importPreviewData, setImportPreviewData] = useState(null);
const [importing, setImporting] = useState(false);
```

---

## 🔄 Data Flow

### 1. List Customers
```
Component Mount → fetchCustomers()
  ↓
GET /api/v2/sales/customers?per_page=100
  ↓
Response: { data: { customers: [...] } }
  ↓
setCustomers(customers)
  ↓
Render Table
```

### 2. Export Flow
```
Click Export Button
  ↓
Build query params (filters + search)
  ↓
window.location.href = /customers/export?params
  ↓
Browser downloads CSV file
```

### 3. Import Flow
```
Click Import Button → Open Modal
  ↓
Click Download Template → Download CSV
  ↓
Select File → handleFileSelect()
  ↓
POST /customers/import-preview (FormData)
  ↓
Response: { data: { data: [...], errors: [...] } }
  ↓
Display Preview Table
  ↓
Click Confirm → handleConfirmImport()
  ↓
POST /customers/import (FormData)
  ↓
Response: { imported_count, skipped_count, errors }
  ↓
Show Alert + Refresh List
```

---

## 💾 Sample Template Structure

**CSV Headers:**
```
Name,Email,Phone,Company Name,Address,City,State,Postal Code,Country,Tax No
```

**Sample Row 1:**
```
John Doe,john.doe@example.com,+1234567890,Doe Enterprises,123 Main Street,New York,NY,10001,United States,TAX123
```

**Sample Row 2:**
```
Jane Smith,jane.smith@example.com,+1234567891,Smith Inc,456 Oak Avenue,Los Angeles,CA,90001,United States,TAX456
```

---

## ✨ Validation Rules

### Required Fields:
- ✅ **Name** - Must not be empty
- ✅ **Email** - Must be valid email format
- ✅ **Email** - Must be unique per shop

### Optional Fields:
- Phone, Company Name, Address, City, State, Postal Code, Country, Tax No

### Validation Errors Shown:
- "Missing name"
- "Missing email"
- "Invalid email format"
- "Duplicate email for this shop"

---

## 🎨 UI Components Used

### Bootstrap/Metronic Classes:
- `app-toolbar` - Toolbar container
- `breadcrumb` - Breadcrumb navigation
- `btn-sm btn-flex` - Toolbar buttons
- `form-control-solid` - Search input
- `table-responsive` - Scrollable table
- `modal fade show` - Import modal
- `alert alert-primary` - Instructions box
- `spinner-border` - Loading spinner
- `ki-duotone` - Icon system

---

## 🔧 Helper Functions

### fetchCustomers()
- Fetches customer list with filters
- Handles token validation
- Sets loading and error states

### handleDelete(customerId)
- Confirms deletion
- Calls DELETE endpoint
- Refreshes list on success

### handleExport()
- Builds query string with filters
- Triggers file download

### handleDownloadTemplate()
- Downloads CSV template

### handleFileSelect(e)
- Gets selected file
- Triggers preview

### handleImportPreview(file)
- Uploads file for validation
- Shows preview table with errors

### handleConfirmImport()
- Confirms import
- Shows results
- Refreshes list

### applyFilters()
- Fetches customers with filters
- Closes filter panel

### resetFilters()
- Clears all filter values
- Resets search term

---

## 📱 Responsive Design

- ✅ Mobile-friendly layout
- ✅ Responsive table with scrolling
- ✅ Collapsible filter panel
- ✅ Toolbar wraps on small screens
- ✅ Modal adapts to screen size

---

## 🚀 Usage Example

### Export Customers:
1. Click "Export" in toolbar
2. File downloads automatically with current filters applied

### Import Customers:
1. Click "Import" in toolbar
2. Modal opens with instructions
3. Click "Download Sample Template"
4. Fill template with customer data
5. Click file input and select filled template
6. Preview table shows (with validation)
7. Check for errors (red rows)
8. Click "Confirm Import"
9. Success message shows imported/skipped counts
10. List refreshes automatically

### Filter Customers:
1. Click "Filter" in toolbar
2. Set date range, country, etc.
3. Click "Apply"
4. List refreshes with filters

### Search Customers:
1. Type in search box (top of table)
2. Filters in real-time
3. Searches: name, email, phone

---

## 🎯 Key Features

### ✅ Import/Export
- Full import/export cycle
- Preview before import
- Validation with error display
- Sample template download

### ✅ Filtering
- Date range filter
- Country filter
- Applied to list and export

### ✅ Search
- Real-time client-side search
- Multiple field search

### ✅ Professional UI
- Breadcrumbs navigation
- Toolbar with actions
- Clean modal design
- Loading states
- Error handling

---

## 📊 Import Response Format

### Preview Response:
```json
{
  "success": true,
  "message": "Import preview generated",
  "data": {
    "data": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890",
        "company_name": "Doe Enterprises",
        "address": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "United States",
        "tax_no": "TAX123",
        "is_valid": true,
        "errors": ""
      },
      {
        "name": "",
        "email": "invalid-email",
        "phone": "+1234567891",
        "company_name": "Invalid Co",
        "address": "",
        "city": "",
        "state": "",
        "postal_code": "",
        "country": "",
        "tax_no": "",
        "is_valid": false,
        "errors": "Missing name, Invalid email format"
      }
    ],
    "errors": [
      "Row 3: Missing name, Invalid email format"
    ]
  }
}
```

### Import Response:
```json
{
  "success": true,
  "message": "Import completed. 48 customers imported successfully, 2 skipped",
  "data": {
    "imported_count": 48,
    "skipped_count": 2,
    "errors": [
      "Row 15: Duplicate email for this shop",
      "Row 23: Invalid email format"
    ]
  }
}
```

---

## 🎉 Summary

### What's Included:
✅ Breadcrumbs navigation
✅ Toolbar with 4 action buttons
✅ Collapsible filter panel
✅ Export to CSV functionality
✅ Import modal with full flow
✅ Download sample template
✅ Preview imported data with validation
✅ Confirm import with results
✅ Real-time search
✅ Professional UI/UX
✅ Loading states
✅ Error handling
✅ Token authentication
✅ Responsive design

### Files Modified:
1. `SoftPos/resources/js/components/Sales/Customers.jsx` - Complete component
2. `SoftPos/resources/js/utils/constants.js` - Added import/export endpoints
3. `Pos/app/Http/Controllers/Api/ApiCustomerController.php` - Added endpoints
4. `Pos/app/Repositories/CustomerRepository.php` - Added import/export methods
5. `Pos/routes/api.php` - Added routes

### Lines of Code: ~700 lines
### Features: 10+ major features
### API Endpoints: 6 endpoints

---

**The customer management system is now complete with all professional features!** 🚀


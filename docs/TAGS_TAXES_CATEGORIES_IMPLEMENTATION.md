# Tags, Taxes, and Categories CRUD Implementation

## Overview
This document describes the complete CRUD implementation for Tags, Taxes, and Categories in the SoftPos application, with APIs served from the Pos project.

## Implementation Date
October 22, 2025

## Architecture

### Backend (Pos Project)
- **API Location**: `Pos/app/Http/Controllers/Api/`
- **Models**: `Pos/app/Models/`
- **Routes**: `Pos/routes/api.php`
- **Resources**: `Pos/app/Http/Resources/`

### Frontend (SoftPos Project)
- **Components**: `SoftPos/resources/js/components/Sales/`
- **Routes**: Configured in `SoftPos/resources/js/sales-app.jsx`
- **SPA Navigation**: Uses React Router for client-side routing

## Files Created/Modified

### Backend Files (Pos Project)

#### 1. API Controllers
- **File**: `Pos/app/Http/Controllers/Api/ApiTagController.php`
  - Full CRUD operations for Tags
  - Endpoints: GET, POST (create/update), DELETE
  - Search and pagination support

- **File**: `Pos/app/Http/Controllers/Api/ApiTaxController.php`
  - Full CRUD operations for Taxes
  - Uses TaxService for business logic
  - Supports translatable names
  - Endpoints: GET, POST (create/update), DELETE

#### 2. Resources
- **File**: `Pos/app/Http/Resources/TagResource.php`
  - Transforms Tag model data for API responses
  - Fields: id, name, slug, created_at, updated_at

- **File**: `Pos/app/Http/Resources/TaxResource.php` (Already existed)
  - Transforms Tax model data for API responses
  - Fields: id, name, rate

- **File**: `Pos/app/Http/Resources/CategoryResource.php` (Already existed)
  - Transforms Category model data for API responses
  - Fields: id, name, thumbnail, parent_category info, total_products

#### 3. Routes
- **File**: `Pos/routes/api.php`
  - Added Tag routes under `/api/v1/tags`
  - Added Tax routes under `/api/v1/taxes`
  - Categories routes already existed under `/api/v1/categories`

### Frontend Files (SoftPos Project)

#### 1. React Components
- **File**: `SoftPos/resources/js/components/Sales/Tags.jsx`
  - Full CRUD interface for Tags
  - Features:
    - List view with search
    - Pagination
    - Create/Edit modal
    - Delete with confirmation
    - Real-time API integration

- **File**: `SoftPos/resources/js/components/Sales/Taxes.jsx`
  - Full CRUD interface for Taxes
  - Features:
    - List view with search
    - Pagination
    - Create/Edit modal with tax type selection
    - Rate input (percentage)
    - Status toggle
    - Delete with confirmation

- **File**: `SoftPos/resources/js/components/Sales/Categories.jsx`
  - Full CRUD interface for Categories
  - Features:
    - List view with search
    - Pagination
    - Image upload support
    - Parent category selection
    - Create/Edit modal
    - Delete with confirmation

#### 2. Routes Configuration
- **File**: `SoftPos/resources/js/sales-app.jsx`
  - Added routes for:
    - `/merchant/sales/tags` → Tags component
    - `/merchant/sales/taxes` → Taxes component
    - `/merchant/sales/categories` → Categories component

#### 3. Sidebar Menu
- **File**: `SoftPos/resources/views/layouts/merchant/partials/sidebar.blade.php`
  - Fixed Taxes link (was pointing to tags URL)
  - Links properly configured for all three sections

## API Endpoints

### Tags API
```
Base URL: /api/v1/tags

GET    /tags                     # List all tags (with search & pagination)
GET    /tags/{tag}               # Get single tag
POST   /tags/store               # Create new tag
POST   /tags/update/{tag}        # Update existing tag
DELETE /tags/delete/{tag}        # Delete tag
```

### Taxes API
```
Base URL: /api/v1/taxes

GET    /taxes                    # List all taxes (with search & pagination)
GET    /taxes/{tax}              # Get single tax
POST   /taxes/store              # Create new tax
POST   /taxes/update/{tax}       # Update existing tax
DELETE /taxes/delete/{tax}       # Delete tax
```

### Categories API
```
Base URL: /api/v1/categories

GET    /categories                      # List all categories (with search & pagination)
GET    /categories/parent-category      # Get parent categories only
GET    /categories/{category}           # Get single category
POST   /categories/store                # Create new category
POST   /categories/update/{category}    # Update existing category
DELETE /categories/delete/{category}    # Delete category
```

## Request/Response Examples

### Tags

#### Create Tag Request
```json
POST /api/v1/tags/store
{
    "name": "New Arrival"
}
```

#### Tag Response
```json
{
    "message": "Tag Created Successfully",
    "data": {
        "tag": {
            "id": 1,
            "name": "New Arrival",
            "slug": "new-arrival",
            "created_at": "2025-10-22 10:00:00",
            "updated_at": "2025-10-22 10:00:00"
        }
    }
}
```

### Taxes

#### Create Tax Request
```json
POST /api/v1/taxes/store
{
    "name": {
        "en": "VAT"
    },
    "rate": 15.5,
    "type": "STANDARD",
    "status": 1
}
```

#### Tax Response
```json
{
    "message": "Tax Created Successfully",
    "data": {
        "tax": {
            "id": 1,
            "name": "VAT",
            "rate": 15.5
        }
    }
}
```

### Categories

#### Create Category Request
```json
POST /api/v1/categories/store
Content-Type: multipart/form-data

name[en]: "Electronics"
parent_id: 1 (optional)
status: 1
image: [file] (optional)
```

#### Category Response
```json
{
    "message": "Category Created Successfully",
    "data": {
        "category": {
            "id": 1,
            "name": "Electronics",
            "thumbnail": "http://example.com/path/to/image.jpg",
            "parent_category_id": null,
            "parent_category_name": null,
            "total_products": 0
        }
    }
}
```

## Features

### Common Features (All Three Components)
1. ✅ Search functionality
2. ✅ Pagination
3. ✅ Create new records
4. ✅ Edit existing records
5. ✅ Delete with confirmation
6. ✅ Loading states
7. ✅ Error handling with SweetAlert2
8. ✅ Responsive design
9. ✅ Real-time API integration

### Tags Specific Features
- Automatic slug generation
- Simple name-based management

### Taxes Specific Features
- Tax type selection (STANDARD, EXEMPTED, ZERO_RATED, RCM)
- Rate input with decimal support
- Status toggle (Active/Inactive)
- Translatable names

### Categories Specific Features
- Image upload and preview
- Parent category selection (hierarchical structure)
- Status toggle
- Product count display
- Translatable names

## Authentication
All API endpoints are protected by JWT authentication middleware.
- Token is automatically passed from the SPA component
- Stored in localStorage
- Included in all API requests via axios interceptors

## Navigation
The sidebar menu includes links to:
- **Tags**: `/merchant/sales/tags`
- **Taxes**: `/merchant/sales/taxes`
- **Categories**: `/merchant/sales/categories`

All navigation is handled via React Router within the Sales SPA, providing a seamless single-page application experience.

## Database Schema

### Tags Table
```sql
- id (bigint, primary key)
- name (varchar)
- slug (varchar)
- shop_id (bigint)
- created_at (timestamp)
- updated_at (timestamp)
```

### Taxes Table
```sql
- id (bigint, primary key)
- name (json, translatable)
- rate (decimal)
- type (enum: STANDARD, EXEMPTED, ZERO_RATED, RCM)
- status (tinyint)
- shop_id (bigint)
- created_by (bigint)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)
```

### Categories Table
```sql
- id (bigint, primary key)
- name (json, translatable)
- image (varchar, nullable)
- parent_id (bigint, nullable)
- type (varchar)
- status (tinyint)
- shop_id (bigint)
- created_at (timestamp)
- updated_at (timestamp)
```

## Testing Instructions

### 1. Backend Setup (Pos Project)
```bash
cd Pos
# Ensure all dependencies are installed
composer install

# Make sure database is set up
php artisan migrate

# Test API endpoints using Postman or similar
```

### 2. Frontend Setup (SoftPos Project)
```bash
cd SoftPos
# Install dependencies
npm install

# Compile assets
npm run dev  # for development
# OR
npm run build  # for production

# Ensure Laravel is running
php artisan serve
```

### 3. Manual Testing Steps
1. Log in to the merchant dashboard
2. Navigate to Sales section
3. Click on "Product Management" submenu
4. Test each section:
   - **Tags**: Create, edit, delete tags
   - **Taxes**: Create taxes with different rates and types
   - **Categories**: Create categories with images and parent categories

### 4. API Testing
Use Postman or similar tool to test API endpoints:
- Import the Pos project's Postman collection
- Test CRUD operations for each resource
- Verify authentication is working
- Check search and pagination

## Dependencies

### Backend (Pos)
- Laravel 10.x
- Laravel Passport (for API authentication)
- Spatie Translatable (for multilingual support)

### Frontend (SoftPos)
- React 18.x
- React Router DOM
- Axios
- SweetAlert2
- Bootstrap 5

## Known Issues
None at this time.

## Future Enhancements
1. Bulk operations (delete multiple records)
2. Export functionality (CSV, Excel)
3. Advanced filtering options
4. Import functionality
5. Audit logs for tracking changes

## Support
For issues or questions, refer to:
- Backend documentation in Pos project
- Frontend component documentation in SoftPos project
- API documentation in Pos project

## Changelog

### Version 1.0.0 (October 22, 2025)
- Initial implementation of Tags CRUD
- Initial implementation of Taxes CRUD
- Initial implementation of Categories CRUD
- API endpoints created in Pos project
- React components created in SoftPos project
- Routes configured in both projects
- Sidebar menu fixed and updated



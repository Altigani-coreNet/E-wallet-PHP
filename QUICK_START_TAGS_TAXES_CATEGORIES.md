# Quick Start Guide: Tags, Taxes & Categories

## 🚀 Getting Started

### Step 1: Compile Frontend Assets
```bash
cd SoftPos
npm run dev
```

### Step 2: Start the Server
```bash
# In SoftPos directory
php artisan serve
```

### Step 3: Access the Features
1. Log in to your merchant account
2. Navigate to **Sales** → **Product Management**
3. You'll see three new menu items:
   - **Tags**
   - **Taxes**
   - **Categories**

## 📋 Quick Reference

### URLs
- Tags: `http://localhost:8000/merchant/sales/tags`
- Taxes: `http://localhost:8000/merchant/sales/taxes`
- Categories: `http://localhost:8000/merchant/sales/categories`

### API Endpoints (from Pos Project)
All endpoints are prefixed with `/api/v1/` and require JWT authentication.

#### Tags
- `GET /tags` - List all
- `GET /tags/{id}` - Get one
- `POST /tags/store` - Create
- `POST /tags/update/{id}` - Update
- `DELETE /tags/delete/{id}` - Delete

#### Taxes
- `GET /taxes` - List all
- `GET /taxes/{id}` - Get one
- `POST /taxes/store` - Create
- `POST /taxes/update/{id}` - Update
- `DELETE /taxes/delete/{id}` - Delete

#### Categories
- `GET /categories` - List all
- `GET /categories/parent-category` - Get parents only
- `GET /categories/{id}` - Get one
- `POST /categories/store` - Create
- `POST /categories/update/{id}` - Update
- `DELETE /categories/delete/{id}` - Delete

## ✅ Features Available

### All Three Components Include:
- ✨ Create new records
- ✏️ Edit existing records
- 🗑️ Delete with confirmation
- 🔍 Search functionality
- 📄 Pagination
- ⚡ Real-time updates
- 🎨 Modern UI with Bootstrap 5
- 🔔 SweetAlert notifications

### Tags Features
- Simple name input
- Automatic slug generation
- Lightweight management

### Taxes Features
- Tax rate (percentage) input
- Tax type selection:
  - STANDARD
  - EXEMPTED
  - ZERO_RATED
  - RCM
- Active/Inactive status toggle
- Multilingual name support

### Categories Features
- Image upload
- Parent category selection (hierarchical)
- Product count display
- Active/Inactive status toggle
- Multilingual name support

## 🔧 Troubleshooting

### Assets Not Loading?
```bash
cd SoftPos
npm run build
php artisan optimize:clear
```

### API Errors?
1. Check if Pos API is running
2. Verify JWT token is valid
3. Check browser console for errors
4. Verify API base URL in axios config

### Navigation Not Working?
1. Clear browser cache
2. Check if React Router is properly configured
3. Verify `sales-app.jsx` is compiled
4. Check browser console for JavaScript errors

## 📝 Sample Data

### Creating a Tag
```json
{
    "name": "New Arrival"
}
```

### Creating a Tax
```json
{
    "name": {
        "en": "VAT 15%"
    },
    "rate": 15.0,
    "type": "STANDARD",
    "status": 1
}
```

### Creating a Category
Form Data:
- name[en]: "Electronics"
- parent_id: (optional)
- image: (file upload, optional)
- status: 1

## 🎯 Common Tasks

### Add a New Tag
1. Click "Add Tag" button
2. Enter tag name
3. Click "Create"

### Add a New Tax
1. Click "Add Tax" button
2. Enter tax name
3. Enter tax rate (e.g., 15.5)
4. Select tax type
5. Toggle status if needed
6. Click "Create"

### Add a New Category
1. Click "Add Category" button
2. Enter category name
3. (Optional) Select parent category
4. (Optional) Upload image
5. Toggle status if needed
6. Click "Create"

### Edit a Record
1. Click the edit (pencil) icon on any row
2. Modify the fields
3. Click "Update"

### Delete a Record
1. Click the delete (trash) icon on any row
2. Confirm the deletion in the popup
3. Record will be deleted

### Search Records
1. Type in the search box at the top
2. Results update automatically

## 📚 File Locations

### Backend (Pos Project)
```
Pos/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── ApiTagController.php
│   │   │   ├── ApiTaxController.php
│   │   │   └── ApiCategoryController.php
│   │   └── Resources/
│   │       ├── TagResource.php
│   │       ├── TaxResource.php
│   │       └── CategoryResource.php
│   └── Models/
│       ├── Tag.php
│       ├── Tax.php
│       └── Category.php
└── routes/
    └── api.php
```

### Frontend (SoftPos Project)
```
SoftPos/
├── resources/
│   ├── js/
│   │   ├── components/Sales/
│   │   │   ├── Tags.jsx
│   │   │   ├── Taxes.jsx
│   │   │   └── Categories.jsx
│   │   └── sales-app.jsx
│   └── views/
│       └── layouts/merchant/partials/
│           └── sidebar.blade.php
└── routes/
    └── web.php
```

## 🆘 Need Help?

Refer to the detailed documentation:
- `TAGS_TAXES_CATEGORIES_IMPLEMENTATION.md` - Complete implementation details
- Pos API Documentation
- SoftPos component documentation

## ✨ What's Next?

After setting up Tags, Taxes, and Categories, you can:
1. Use tags to organize products
2. Apply taxes to products automatically
3. Organize products into categories
4. Create product hierarchies with parent categories
5. Upload category images for better visual organization

Enjoy your new CRUD features! 🎉



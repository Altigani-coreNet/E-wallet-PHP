# Sales SPA (Single Page Application) Setup

This document explains the Sales React SPA setup for the SoftPos application.

## Overview

The Sales module is built as a **Single Page Application (SPA)** using React and React Router. This allows for a seamless user experience without page reloads when navigating between different sales-related pages.

## Architecture

### Laravel Side

1. **Route**: `merchant/sales/{any?}` 
   - Defined in `routes/web.php`
   - Catches all routes under `merchant/sales/*`
   - Uses `SalesController@index` to return the Blade view

2. **Controller**: `App\Http\Controllers\SalesController`
   - Simple controller that returns the `sales.index` view

3. **Blade View**: `resources/views/sales/index.blade.php`
   - Contains a div with id `sales-app-root`
   - Loads the React SPA via `@vite(['resources/js/sales-app.jsx'])`

### React Side

1. **Entry Point**: `resources/js/sales-app.jsx`
   - Main entry file for the Sales SPA
   - Sets up React Router with `BrowserRouter`
   - Defines all routes and their corresponding components

2. **Components** (in `resources/js/components/Sales/`):
   - `Dashboard.jsx` - Sales dashboard with statistics
   - `Reports.jsx` - Sales reports and analytics
   - `Orders.jsx` - Orders management
   - `Products.jsx` - Products inventory

## How It Works

1. **User clicks on a Sales menu item** (e.g., "Dashboard", "Reports", "Orders", "Products")

2. **Laravel catches the route** `merchant/sales/{any}`
   - Returns the same Blade view for all Sales routes

3. **React Router takes over**
   - The React app reads the current URL path
   - Routes like `/merchant/sales/dashboard` are matched to their React components
   - Only the React component changes, no page reload

## Adding New Routes

To add a new route to the Sales SPA:

### 1. Create the React Component

```jsx
// resources/js/components/Sales/YourNewComponent.jsx
import React from 'react';

export default function YourNewComponent() {
    return (
        <div className="container-fluid">
            <h1>Your New Component</h1>
        </div>
    );
}
```

### 2. Add the Route to sales-app.jsx

```jsx
// Import the component
import YourNewComponent from './components/Sales/YourNewComponent';

// Add the route in the Routes section
<Route path="/merchant/sales/your-route" element={<YourNewComponent />} />
```

### 3. Add to Sidebar (Optional)

In `resources/views/layouts/merchant/partials/sidebar.blade.php`:

```html
<div class="menu-item">
    <a class="menu-link {{ request()->is('merchant/sales/your-route') ? 'active' : '' }}" 
       href="{{ url('merchant/sales/your-route') }}">
        <span class="menu-bullet">
            <span class="bullet bullet-dot"></span>
        </span>
        <span class="menu-title">Your Route Name</span>
    </a>
</div>
```

### 4. Rebuild Assets

```bash
npm run build
# or for development
npm run dev
```

## Available Routes

Currently available routes in the Sales SPA:

| Route | Component | Description |
|-------|-----------|-------------|
| `/merchant/sales/dashboard` | Dashboard | Sales dashboard with statistics |
| `/merchant/sales/reports` | Reports | Sales reports and analytics |
| `/merchant/sales/orders` | Orders | Orders management |
| `/merchant/sales/products` | Products | Products inventory |

## Development

### Running in Development Mode

```bash
npm run dev
```

This will start Vite in development mode with hot module replacement (HMR).

### Building for Production

```bash
npm run build
```

This will compile and minify the assets for production.

## Benefits of This Approach

1. **Fast Navigation** - No page reloads when switching between Sales pages
2. **Better UX** - Smooth transitions between pages
3. **Component Reusability** - Share components across different Sales pages
4. **Modern Stack** - Uses React Router for client-side routing
5. **Easy to Extend** - Simply add new routes and components

## Technical Details

- **React Router**: `react-router-dom` v6
- **Vite**: Build tool for fast development
- **Laravel Vite Plugin**: Seamless integration with Laravel
- **Entry Point**: Mounted on `DOMContentLoaded` event

## Troubleshooting

### Routes not working?

1. Make sure you've run `npm run build` after making changes
2. Clear Laravel cache: `php artisan cache:clear`
3. Check browser console for errors

### Component not rendering?

1. Verify the component is imported in `sales-app.jsx`
2. Check that the route path matches exactly
3. Ensure the div with id `sales-app-root` exists in the Blade view

### Sidebar links not working?

Make sure the URLs in sidebar match the React Router paths:
- Sidebar: `{{ url('merchant/sales/dashboard') }}`
- React Router: `<Route path="/merchant/sales/dashboard" ... />`


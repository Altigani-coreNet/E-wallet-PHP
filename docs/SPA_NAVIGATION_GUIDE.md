# SPA Navigation Guide

## Overview

The Sales menu now uses **SPA (Single Page Application) navigation** without page refresh. When you click on Sales menu items, the page doesn't reload - instead, it smoothly transitions using React Router.

## How It Works

### 1. **Sidebar Links with Data Attributes**
All Sales menu links have special data attributes:
```html
<a href="/merchant/sales/dashboard" 
   data-spa-link="true"
   data-spa-route="/merchant/sales/dashboard">
   Dashboard
</a>
```

### 2. **JavaScript Event Handler** (`spaNavigation.js`)
- Intercepts clicks on links with `data-spa-link="true"`
- Prevents default page reload
- Updates browser URL using `window.history.pushState()`
- Dispatches custom `spa-navigate` event
- Updates active states in sidebar

### 3. **React App Listener** (`sales-app.jsx`)
- Listens for `spa-navigate` events
- Uses React Router's `navigate()` to change routes
- Renders the appropriate component without page refresh

## Flow Diagram

```
User clicks menu link
    ↓
spaNavigation.js intercepts click
    ↓
Prevents page reload
    ↓
Updates browser URL (no refresh)
    ↓
Dispatches 'spa-navigate' event
    ↓
React app receives event
    ↓
React Router navigates to route
    ↓
Component renders (no page reload!)
```

## Benefits

✅ **No Page Refresh** - Instant navigation  
✅ **Preserves State** - React state stays intact  
✅ **Better UX** - Smooth transitions  
✅ **Browser History** - Back/forward buttons work  
✅ **URL Updates** - URL changes correctly  

## Adding New Routes

### Step 1: Add to Sidebar
```php
<a class="menu-link" 
   href="{{ url('merchant/sales/newpage') }}"
   data-spa-link="true"
   data-spa-route="/merchant/sales/newpage">
   New Page
</a>
```

### Step 2: Add to React Router
In `sales-app.jsx`:
```jsx
<Route path="/merchant/sales/newpage" element={<NewPage />} />
```

That's it! No other configuration needed.

## Browser Back/Forward Support

The system automatically handles:
- Browser back button → triggers `popstate` event → navigates back
- Browser forward button → triggers `popstate` event → navigates forward
- Active states update automatically

## Testing

1. Click any Sales menu item
2. Notice: **No page flash or reload**
3. Check URL: **Updates correctly**
4. Click browser back button: **Works!**
5. Check sidebar: **Active state updates**

## Current Routes

- `/merchant/sales/dashboard` → Dashboard
- `/merchant/sales/sale` → Sale (placeholder)
- `/merchant/sales/purchase` → Purchase (placeholder)
- `/merchant/sales/reports` → Reports
- `/merchant/sales/orders` → Orders
- `/merchant/sales/products` → Products

## Files Modified

1. ✅ `sidebar.blade.php` - Added data attributes
2. ✅ `spaNavigation.js` - Event handler
3. ✅ `sales-app.jsx` - React listener & routes

## Next Steps

Create your Sale and Purchase components:
- `resources/js/components/Sales/Sale.jsx`
- `resources/js/components/Sales/Purchase.jsx`

Then import them in `sales-app.jsx` and you're done! 🚀



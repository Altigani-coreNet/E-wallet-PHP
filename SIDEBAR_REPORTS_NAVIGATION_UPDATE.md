# Sidebar Reports Navigation Update

## Summary
Updated the sidebar navigation and Reports component to enable proper route-based navigation for report tabs using React Router.

## Changes Made

### 1. Sales App Routes (`SoftPos/resources/js/sales-app.jsx`)
**Added Four New Report Routes:**
```jsx
<Route path="/merchant/sales/reports/sales" element={<Reports />} />
<Route path="/merchant/sales/reports/purchases" element={<Reports />} />
<Route path="/merchant/sales/reports/products" element={<Reports />} />
<Route path="/merchant/sales/reports/expenses" element={<Reports />} />
```

All routes render the same `<Reports />` component, which determines which tab to show based on the URL path.

### 2. Reports Component (`SoftPos/resources/js/components/Sales/Reports.jsx`)
**Added URL Path-based Tab Navigation:**
- Import `useLocation` and `useNavigate` from `react-router-dom`
- Tab state now syncs with URL path (e.g., `/merchant/sales/reports/sales`)
- Users can bookmark specific report tabs
- Browser back/forward buttons work correctly
- Added `getInitialTab()` function to extract report type from URL path
- Added `useEffect` to listen for path changes
- Updated `handleTabChange()` to navigate to proper routes

**Key Logic:**
```javascript
const getInitialTab = () => {
    const path = location.pathname;
    const pathParts = path.split('/');
    const lastPart = pathParts[pathParts.length - 1];
    return validTabs.includes(lastPart) ? lastPart : 'sales';
};

const handleTabChange = (tabId) => {
    navigate(`/merchant/sales/reports/${tabId}`);
};
```

### 3. Sidebar (`SoftPos/resources/views/layouts/merchant/partials/sidebar.blade.php`)
**Updated Report Menu Links:**
- Changed to proper routes without hash fragments
- Added active state highlighting based on current route
- **Sales Report**: `/merchant/sales/reports/sales`
- **Purchase Report**: `/merchant/sales/reports/purchases`
- **Product Report**: `/merchant/sales/reports/products`
- **Expense Report**: `/merchant/sales/reports/expenses`

**Active State Logic:**
```blade
{{ request()->is('merchant/sales/reports/sales') ? 'active' : '' }}
```

### URLs Structure

| Menu Item | URL Route | Component |
|-----------|-----------|-----------|
| Sales Report | `/merchant/sales/reports/sales` | `<Reports />` (sales tab) |
| Purchase Report | `/merchant/sales/reports/purchases` | `<Reports />` (purchases tab) |
| Product Report | `/merchant/sales/reports/products` | `<Reports />` (products tab) |
| Expense Report | `/merchant/sales/reports/expenses` | `<Reports />` (expenses tab) |

## How It Works

1. **User clicks on a report menu item** in the sidebar (e.g., "Purchase Report")
2. **Browser navigates** to `/merchant/sales/reports/purchases`
3. **React Router matches the route** and renders the `<Reports />` component
4. **Reports component reads the URL path** and extracts "purchases"
5. **Correct tab activates** (Purchase Report)
6. **Component renders** the appropriate report (PurchaseReport component)

## Benefits

✅ **Clean URLs** - Proper REST-style routes instead of hash fragments
✅ **Bookmarkable Reports** - Users can bookmark specific report tabs
✅ **Browser Navigation** - Back/forward buttons work as expected
✅ **Shareable Links** - Users can share direct links to specific reports
✅ **Active States** - Sidebar highlights the current report
✅ **SEO Friendly** - If needed in the future, proper routes are better for SEO
✅ **Better UX** - Smooth SPA transitions without full page reloads

## Navigation Flow

```
Sidebar Menu                       URL Route                           Active Tab
────────────────────────────────────────────────────────────────────────────────────
Sales Report        →    /merchant/sales/reports/sales        →    Sales Report
Purchase Report     →    /merchant/sales/reports/purchases    →    Purchase Report
Product Report      →    /merchant/sales/reports/products     →    Product Report
Expense Report      →    /merchant/sales/reports/expenses     →    Expense Report
```

## Testing

To test the functionality:

1. ✅ **Click each menu item** in the sidebar under "Reports"
2. ✅ **Verify the URL** changes to the correct route (e.g., `/merchant/sales/reports/sales`)
3. ✅ **Verify the correct report** tab is displayed
4. ✅ **Verify sidebar active state** - Current report should be highlighted
5. ✅ **Test browser back button** - should navigate between report routes
6. ✅ **Test bookmarking** - bookmark a specific report URL and reopen it
7. ✅ **Test direct URL access** - manually type `/merchant/sales/reports/products` in browser
8. ✅ **Test tab clicking** - Click tabs within the report page to switch between them

## Technical Details

- **Route-based navigation** using React Router's `useNavigate` hook
- **Path extraction** using `location.pathname.split('/')`
- **Dynamic tab selection** based on the last segment of the URL path
- **Valid tabs array** prevents invalid paths from breaking the component
- **Default tab** is "sales" if path doesn't match any valid report type
- **Active state in sidebar** using Laravel's `request()->is()` helper

## Files Modified

1. ✅ `SoftPos/resources/js/sales-app.jsx` - Added 4 new report routes
2. ✅ `SoftPos/resources/js/components/Sales/Reports.jsx` - Updated to use path-based navigation
3. ✅ `SoftPos/resources/views/layouts/merchant/partials/sidebar.blade.php` - Updated links and active states

## Route Registration

All routes are registered in `sales-app.jsx`:

```jsx
// Base reports route (defaults to sales tab)
<Route path="/merchant/sales/reports" element={<Reports />} />

// Specific report routes
<Route path="/merchant/sales/reports/sales" element={<Reports />} />
<Route path="/merchant/sales/reports/purchases" element={<Reports />} />
<Route path="/merchant/sales/reports/products" element={<Reports />} />
<Route path="/merchant/sales/reports/expenses" element={<Reports />} />
```

## Notes

- The base route `/merchant/sales/reports` (without a specific report type) defaults to the "sales" tab
- All four routes render the same `<Reports />` component for efficiency
- The component intelligently determines which tab to show based on the URL
- Sidebar active states are automatically managed by Laravel's routing helpers
- Clean, RESTful URL structure that's easy to understand and maintain

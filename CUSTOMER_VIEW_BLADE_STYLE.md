# Customer View - Blade Template Style ✅

CustomerView component now matches the Blade template design exactly!

---

## ✅ Design Features (Matching show.blade.php)

### 1. **Sidebar (Left Column)**
- 350px wide sidebar
- Large circular avatar (150px) with customer initial
- Customer name (large heading)
- Customer email (clickable mailto link)
- "Details" section with badge
- Detailed information list:
  - Customer ID
  - Email (clickable)
  - Phone (clickable tel: link)
  - Address (formatted)
  - Customer Group
  - Created date
  - Last updated date

### 2. **Main Content (Right Column)**
- Tabs navigation (3 tabs)
- Tab content area

### 3. **Three Tabs**

#### Tab 1: **Overview**
- Account Status Card (with green checkmark)
- Shop Info Card (blue background)
- Customer Information Table
- Quick Actions buttons

#### Tab 2: **General Settings**
- Profile Information table with:
  - Full Name
  - Company Name
  - Email Address
  - Phone Number
  - Country
  - City
  - State
  - Postal Code

#### Tab 3: **Advanced Settings**
- Shop Information table
- Shows shop details if assigned
- "No shop assigned" message if not

---

## 🎨 UI Components

### Sidebar Structure:
```
┌─────────────────────────┐
│                         │
│         [Avatar]        │
│           (J)           │
│                         │
│       John Doe          │
│   john@example.com      │
│                         │
├─────────────────────────┤
│ Details    [Active]     │
├─────────────────────────┤
│ Customer ID             │
│ #1                      │
│                         │
│ Email                   │
│ john@example.com        │
│                         │
│ Phone                   │
│ +1234567890            │
│                         │
│ Address                 │
│ 123 Main St            │
│ New York, NY, 10001    │
│                         │
│ Customer Group          │
│ VIP                     │
│                         │
│ Created                 │
│ Jan 20, 2024           │
│                         │
│ Last Updated            │
│ Jan 20, 2024 10:30:00  │
└─────────────────────────┘
```

### Main Content Structure:
```
┌──────────────────────────────────────────────┐
│ [Overview] [General Settings] [Advanced]     │
├──────────────────────────────────────────────┤
│                                               │
│  ┌──────────────┐  ┌──────────────┐         │
│  │ Account      │  │ Shop Info    │         │
│  │ Status       │  │ (Blue Card)  │         │
│  │ ✅ Active    │  │              │         │
│  └──────────────┘  └──────────────┘         │
│                                               │
│  ┌────────────────────────────────┐         │
│  │ Customer Information            │         │
│  │                                 │         │
│  │ ID: #1                          │         │
│  │ Name: John Doe                  │         │
│  │ Email: john@example.com         │         │
│  │ Phone: +1234567890             │         │
│  │ Address: 123 Main St           │         │
│  └────────────────────────────────┘         │
│                                               │
│  ┌────────────────────────────────┐         │
│  │ Quick Actions                   │         │
│  │                                 │         │
│  │ [Edit] [Delete] [Back]         │         │
│  └────────────────────────────────┘         │
└──────────────────────────────────────────────┘
```

---

## 📋 Tab Content

### Overview Tab:
```
┌─ Account Status Card ──┐ ┌─ Shop Info Card ───┐
│ ✅ Active               │ │ 🏪 Shop Name       │
│ Customer Account        │ │ Associated Shop    │
└─────────────────────────┘ └────────────────────┘

┌─ Customer Information ─────────────────────────┐
│ Customer ID    | #1                            │
│ Name           | John Doe                      │
│ Email          | john@example.com             │
│ Phone          | +1234567890                   │
│ Address        | 123 Main St, NY, 10001       │
│ Created        | Jan 20, 2024 10:30:00        │
│ Last Updated   | Jan 20, 2024 10:30:00        │
└───────────────────────────────────────────────┘

┌─ Quick Actions ────────────────────────────────┐
│ [Edit Customer] [Delete Customer] [Back]      │
└───────────────────────────────────────────────┘
```

### General Settings Tab:
```
┌─ Profile Information ──────────────────────────┐
│ Full Name      | John Doe                      │
│ Company Name   | Doe Enterprises               │
│ Email Address  | john@example.com             │
│ Phone Number   | +1234567890                   │
│ Country        | United States                 │
│ City           | New York                      │
│ State          | NY                            │
│ Postal Code    | 10001                         │
└───────────────────────────────────────────────┘
```

### Advanced Settings Tab:
```
┌─ Shop Information ─────────────────────────────┐
│ Shop ID    | #770e8400                         │
│ Shop Name  | Main Shop                         │
│ Status     | [Active]                          │
└───────────────────────────────────────────────┘

OR (if no shop):

┌───────────────────────────────────────────────┐
│   No shop assigned to this customer.          │
└───────────────────────────────────────────────┘
```

---

## 🎯 Key Features

### ✅ Sidebar Features:
- Large circular avatar with initial
- Customer name and email
- Active customer badge
- All key information at a glance
- Clickable email/phone links

### ✅ Tab Features:
- 3 tabs: Overview, General Settings, Advanced Settings
- Active tab highlighted
- Smooth tab switching (no API calls)
- Clean, professional layout

### ✅ Overview Tab:
- Account Status card (green checkmark)
- Shop Info card (blue, prominent)
- Full customer information table
- Quick action buttons:
  - Edit Customer
  - Delete Customer
  - Back to Customers

### ✅ General Settings Tab:
- Complete profile information
- All customer fields displayed
- Clean table layout

### ✅ Advanced Settings Tab:
- Shop information
- Shop ID, name, status
- Empty state if no shop

---

## 🔧 Technical Implementation

### Tab Switching:
```javascript
const [activeTab, setActiveTab] = useState('overview');

// Tab navigation
<a 
    className={`nav-link ${activeTab === 'overview' ? 'active' : ''}`}
    onClick={() => setActiveTab('overview')}
>
    Overview
</a>

// Tab content
{activeTab === 'overview' && (
    <div className="tab-pane fade show active">
        {/* Overview content */}
    </div>
)}
```

### Date Formatting:
```javascript
// Sidebar dates (short format)
{customer.created_at ? new Date(customer.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
}) : 'N/A'}
// Result: "Jan 20, 2024"

// Table dates (long format with time)
{customer.created_at ? new Date(customer.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
}) : 'N/A'}
// Result: "Jan 20, 2024, 10:30:00 AM"
```

### Address Formatting:
```javascript
{customer.address || customer.city || customer.state || customer.postal_code || customer.zip ? (
    <>
        {customer.address && <>{customer.address}<br /></>}
        {(customer.city || customer.state || customer.postal_code || customer.zip) && (
            <>{[customer.city, customer.state, customer.postal_code || customer.zip].filter(Boolean).join(', ')}</>
        )}
    </>
) : 'No address provided'}

// Result:
// 123 Main Street
// New York, NY, 10001
```

---

## 📱 Responsive Design

### Desktop View:
```
┌─────────────────┬──────────────────────────────┐
│    Sidebar      │     Main Content             │
│    (350px)      │     (Flex remaining)         │
│                 │                              │
│  [Avatar]       │  [Tabs]                      │
│   Details       │  [Tab Content]               │
│                 │   - Cards                    │
│                 │   - Tables                   │
│                 │   - Actions                  │
└─────────────────┴──────────────────────────────┘
```

### Mobile View:
```
┌──────────────────┐
│    Sidebar       │
│                  │
│  [Avatar]        │
│   Details        │
└──────────────────┘
┌──────────────────┐
│  Main Content    │
│                  │
│  [Tabs]          │
│  [Tab Content]   │
└──────────────────┘
```

Classes used:
- `flex-column flex-xl-row` - Stack on mobile, side-by-side on XL screens
- `w-100 w-xl-350px` - Full width on mobile, 350px on XL
- `ms-lg-15` - Margin start on large screens

---

## 🎨 Card Styles

### Account Status Card:
```javascript
<div className="card pt-4 h-md-100 mb-6 mb-md-0">
    <div className="card-body pt-0">
        <div className="d-flex">
            <i className="ki-duotone ki-check-circle text-success fs-2x"></i>
            <div className="ms-2">
                Active
                <span className="text-muted fs-4 fw-semibold d-block">Customer Account</span>
            </div>
        </div>
    </div>
</div>
```

### Shop Info Card (Blue):
```javascript
<div className="card bg-primary hoverable h-md-100">
    <div className="card-body">
        <i className="ki-duotone ki-shop text-white fs-3x ms-n1"></i>
        <div className="text-white fw-bold fs-2 mt-5">
            {customer.shop?.name || 'No Shop'}
        </div>
        <div className="fw-semibold text-white">Associated Shop</div>
    </div>
</div>
```

---

## ✅ Summary

### What Was Created:
✅ Sidebar with avatar and details
✅ 3-tab layout (Overview, General, Advanced)
✅ Account Status card with checkmark
✅ Shop Info card with blue background
✅ Customer Information table
✅ Quick Actions buttons
✅ Profile Information table
✅ Shop Information table
✅ Responsive layout
✅ Professional styling

### Matches Blade Template:
✅ Same layout structure
✅ Same card designs
✅ Same tab structure
✅ Same information display
✅ Same color scheme
✅ Same icons and badges

### Files Updated:
1. `SoftPos/resources/js/components/Sales/CustomerView.jsx` (392 lines)

---

**Customer View now looks exactly like the Blade template!** 🎨✅


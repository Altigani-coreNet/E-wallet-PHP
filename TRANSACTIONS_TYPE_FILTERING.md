# Transaction Type Filtering - Refunded & Voided

## ✅ How It Works

### Sidebar Navigation Support

The component now fully supports type filtering from sidebar links:

```blade
<!-- In sidebar -->
<a href="{{ route('merchant.transactions.index', ['type' => 'refunded']) }}">
    Refunded Transactions
</a>

<a href="{{ route('merchant.transactions.index', ['type' => 'voided']) }}">
    Voided Transactions
</a>
```

### URL Structure

```
/merchant/transactions                  → All transactions (shows statistics)
/merchant/transactions?type=refunded    → Refunded transactions only
/merchant/transactions?type=voided      → Voided transactions only
```

## 🎯 Component Behavior

### When Type is Selected:

1. **Info Alert Appears:**
```
┌─────────────────────────────────────────────┐
│ ℹ️ Refunded Transactions                    │
│ Showing transactions with type: refunded    │
└─────────────────────────────────────────────┘
```

2. **Statistics Cards Hidden:**
   - Sale/Refund/Void cards don't show
   - Only the filtered table appears

3. **Filter Applied to API:**
   - Backend filters by type automatically
   - Only matching transactions returned

4. **Toolbar Shows Count:**
   - "Transactions (X total)" shows filtered count

### When No Type (All Transactions):

1. **No Alert**
2. **Statistics Cards Show:**
   - Sale Transactions
   - Refund Transactions  
   - Void Transactions
3. **All transactions loaded**

## 🔧 Implementation

### Reading URL Parameters:
```javascript
import { useSearchParams } from 'react-router-dom';

const [searchParams] = useSearchParams();
const urlType = searchParams.get('type') || '';
```

### Applying to Filters:
```javascript
const [filters, setFilters] = useState({
    search: '',
    status: '',
    // ... other filters
    type: urlType  // From URL
});

// Update when URL changes
useEffect(() => {
    const newType = searchParams.get('type') || '';
    setFilters(prev => ({ ...prev, type: newType }));
}, [searchParams]);
```

### Sending to API:
```javascript
const response = await axios.get('/api/v1/merchant/transactions/data', {
    params: {
        merchant_id: merchantId,
        page: page,
        per_page: size,
        ...filters  // Includes type filter
    }
});
```

## 📋 Supported Types

| Type | Status Filter | Description |
|------|--------------|-------------|
| `refunded` | REFUNDED | Shows only refunded transactions |
| `voided` | VOIDED | Shows only voided transactions |
| (none) | All | Shows all transactions with statistics |

## 🎨 UI Variations

### All Transactions:
```
Toolbar
Statistics Cards (3)
Table (all transactions)
```

### Refunded Transactions:
```
Toolbar
Alert: "Refunded Transactions"
Table (only refunded)
```

### Voided Transactions:
```
Toolbar
Alert: "Voided Transactions"
Table (only voided)
```

## 🔄 Navigation Flow

### From Sidebar:

1. User clicks "Refunded Transactions" in sidebar
   → URL: `/merchant/transactions?type=refunded`
   → React Router updates
   → Component reads `type=refunded` from URL
   → Applies filter
   → Shows alert: "Refunded Transactions"
   → Hides statistics
   → Loads only refunded transactions

2. User clicks "All Transactions" in sidebar
   → URL: `/merchant/transactions`
   → Component reads no type
   → Shows all transactions
   → Shows statistics cards

### From Filter Clear:

When user clicks "Clear Filters":
```javascript
const clearFilters = () => {
    setFilters({
        search: '',
        status: '',
        payment_type: '',
        terminal_id: '',
        start_date: '',
        end_date: '',
        type: urlType  // Preserves URL type!
    });
};
```

The URL type is preserved even when clearing filters!

## 🧪 Testing

Test these sidebar links:

```
✓ All Transactions       → /merchant/transactions
✓ Refunded Transactions  → /merchant/transactions?type=refunded
✓ Voided Transactions    → /merchant/transactions?type=voided
```

For each:
- [ ] Alert shows correct type
- [ ] Statistics hidden (except "All")
- [ ] Only matching transactions appear
- [ ] Pagination works
- [ ] Export works (only filtered data)
- [ ] Filters still work

## 💡 Additional Types

You can easily add more types by adding sidebar links:

```blade
<!-- Sale transactions -->
<a href="{{ route('merchant.transactions.index', ['type' => 'sale']) }}">
    Sale Transactions
</a>

<!-- Pending transactions -->
<a href="{{ route('merchant.transactions.index', ['type' => 'pending']) }}">
    Pending Transactions
</a>
```

The component automatically handles any type parameter!

---

**Status:** ✅ Complete  
**Supported Types:** refunded, voided, (any custom type)  
**Integration:** Sidebar navigation ready  
**Statistics:** Hidden when type is active  


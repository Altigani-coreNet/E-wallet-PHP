# Transaction Lifecycle & Dashboard Statistics Audit

## Executive summary

Dashboard statistics in `MerchantRepov2` assume **lowercase** transaction statuses and model columns (`refunded_amount`, `voided_amount`, `reference_transaction_id`). The merchant UI refund/void endpoints (`MerchantTransactionController`) historically used a **separate implementation** with uppercase statuses and non-existent columns. That mismatch caused stat bars and charts to disagree with what merchants see in the transaction list.

**Recommended source of truth:** `Transaction::refund()`, `Transaction::void()`, and `Transaction::cancel()` in `app/Models/Transaction.php`, called from all controllers.

---

## Path A — Model methods (canonical)

| Entry points | Model method | Original row after action | Child row |
|--------------|--------------|---------------------------|-----------|
| `Api\TransactionController` refund/partial-refund | `refund()` | `status` stays `approved`; `amount` reduced; `refunded_amount` increased | New row `status = refunded`, `amount` = refund slice, `reference_transaction_id` = original id |
| `Api\TransactionController` void | `void()` | `status = voided`, `amount = 0`, `voided_amount` set | None |
| `Api\TransactionController` cancel | `cancel()` | `status = cancelled`, `amount = 0`, `cancelled_amount` set | None |
| `AdminTransactionController` (V2) refund/void | Same model methods | Same | Same |
| `Admin\TransactionController` refund/void/cancel | Same model methods | Same | Same |

### Refund rules (`Transaction::refund`)

1. Validates amount > 0 and <= `getAvailableAmountForRefund()`.
2. Sets `original_amount` on first refund if missing.
3. Updates original: `refunded_amount += amount`, `amount = original_amount - refunded_amount`.
4. Creates child transaction with `status = refunded`, `type = refund`.
5. Original **status is not changed** (typically remains `approved`).

### Void rules (`Transaction::void`)

1. Fails if already voided or has refunds.
2. Sets `voided_amount = amount`, `amount = 0`, `status = voided`.

### Net sales formula (`calculateNetSalesTotal`)

```
total_amount = pending_amount + approved_amount - voided_amount
```

Refunds are **not** subtracted again — they are already reflected in the reduced `amount` on the original sale row. Separate `refunded` status rows are informational.

---

## Path B — Merchant UI (legacy, fixed in Phase 6)

| Endpoint | Previous behavior | Problem |
|----------|-------------------|---------|
| `POST /v1/merchant/transactions/{id}/refund` | Created row with `status = REFUNDED`, wrote `refundable_amount`, `original_transaction_id` | Columns not in schema / not fillable; uppercase status invisible to stats |
| `POST /v1/merchant/transactions/{id}/void` | Set `status = VOIDED`, `void_reason`, `voided_at` | Same |

**Fix applied:** Delegate to model `refund()` / `void()`; expose `refundable_amount` and `original_transaction_id` via API resources from `getAvailableAmountForRefund()` and `reference_transaction_id`.

---

## Status / column matrix vs `MerchantRepov2`

| Status in DB | Counted in `pending_amount` | Counted in `approved_amount` | `refunded_amount` field | `voided_amount` field | In `total_amount` (net) |
|--------------|----------------------------|------------------------------|-------------------------|----------------------|-------------------------|
| `pending` | Yes (`amount`) | — | — | — | Yes |
| `captured` | Yes (merged with pending) | — | — | — | Yes |
| `approved` | — | Yes (`amount`) | — | — | Yes |
| `refunded` | — | — | Yes (child row `amount`) | — | No (not net sales) |
| `voided` | — | — | — | Yes (`voided_amount`) | Subtracted from net |
| `cancelled` | — | — | — | — | No |
| `failed` | — | — | — | — | No |
| `REFUNDED` / `VOIDED` (legacy uppercase) | **No** | **No** | **No** | **No** | **Miscounted** |

---

## Known statistics bugs (addressed)

| Issue | Location | Fix |
|-------|----------|-----|
| `total_amount` = 0 while `pending_amount` > 0 | `getDailyStatistics` used `SUM(refunded_amount)` on NULL rows | Use `calculateNetSalesTotal()` after loop |
| `capture` typo vs `captured` | `getTransactionStatisticsByStatus` line ~756 | Changed to `captured` |
| Weekly/monthly `*WithStatus` return daily buckets | Method names vs implementation | Documented; tests assert current behavior |
| Voided amount uses `original_amount` in some charts | `generateHourlyTransactionChartWithStatus` | Documented inconsistency |

---

## Historical data migration note

If production contains uppercase `REFUNDED` / `VOIDED` rows from Path B:

```sql
UPDATE transactions SET status = LOWER(status) WHERE status IN ('REFUNDED', 'VOIDED', 'CANCELLED', 'PENDING', 'APPROVED');
```

Reconcile rows that have `reference_transaction_id` set but missing `refunded_amount` on originals by running a one-off reconciliation script (out of scope for automated tests).

---

## API routes reference

| Purpose | Route | Auth |
|---------|-------|------|
| Daily stats (v1) | `GET /dashboard/statistics/daily` | `auth:api` |
| Daily stats (v2) | `GET /v1/merchant/dashboard/statistics/daily` | `auth:api` |
| Refund (generic) | `POST /transactions/refund` | `auth:api` |
| Void (generic) | `POST /transactions/void` | `auth:api` |
| Refund (merchant UI) | `POST /v1/merchant/transactions/{id}/refund` | `auth:api` |
| Void (merchant UI) | `POST /v1/merchant/transactions/{id}/void` | `auth:api` |

---

## Gate for production fixes

Phase 6 controller alignment and status normalization should be deployed together with the test suite in this repo. All new tests assume Path A behavior.

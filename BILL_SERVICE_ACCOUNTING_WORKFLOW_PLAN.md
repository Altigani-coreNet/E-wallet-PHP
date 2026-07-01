# Bill & Service Payment — Accounting Workflow Plan

> Status: **Implemented** (WalletService billPay, ProviderSettlementService, customer bill-payment API, admin settlement)
> Parent doc: [`ACCOUNTING_WORKFLOW_PLAN.md`](ACCOUNTING_WORKFLOW_PLAN.md) (sections 5.7–5.9, 11)
> Audience: backend engineers and accounting reviewers validating debit/credit entries.

---

## 1. What we are building

Customers pay **bills and third-party services** from their wallet (electricity,
mobile top-up, utility fees, etc.). Services are offered by **external partners** —
not by other wallets on the platform.

Accounting must answer two questions:

1. **When the customer pays** — how do we record that their e-money was used for
   an external obligation?
2. **When we pay the partner** — how do we clear that obligation from bank cash?

We use a **two-step model**: accrue a **provider-specific payable** first, settle
from bank later. Each partner has **its own** chart-of-account liability row.

---

## 2. Design principles

| Principle | Rule |
|-----------|------|
| One account per provider | Each partner → unique `21xx` Liability account; never one shared payable for all |
| Two-step settlement | Step 1: `DR Customer / CR Provider Payable`. Step 2: `DR Provider Payable / CR Bank` |
| Bank moves on settlement only | Customer bill pay does **not** touch `1000 Bank` |
| Fees to Income | Platform fee credits `4000 Fee Income`, same as wallet transfer |
| Post on API success | Ledger + wallet debit only after partner confirms the bill/service |
| Aggregate customer ledger | Customer side still posts to aggregate `2000` (per-wallet detail in `wallet_transactions`) |

---

## 3. Chart of accounts (bill / service extension)

Add to the existing chart (see parent doc section 3):

| Code | Account | Type | Purpose |
|------|---------|------|---------|
| 2100 | Third Party Payables (control) | Liability | Optional summary parent — not used for postings |
| 2110 | Provider Payable — Electricity Co | Liability | Owed to partner 1 |
| 2120 | Provider Payable — Telecom Co | Liability | Owed to partner 2 |
| 2130 | Provider Payable — Water Utility | Liability | Owed to partner 3 |
| 2140–2199 | Provider Payable — … | Liability | Next partners |

**Code range:** `2110–2199` reserved for per-partner payables.

**Partner link:** `partners.account_id` → `chart_of_accounts.id`. Bill payment is
rejected if the partner has no linked account.

**Naming convention:** `Provider Payable — {Partner Name}`

**Account flags:** `type` = Liabilities, `created_by = 0`, `is_enabled = 1`.

---

## 4. Updated golden invariant

During the window between customer bill pay and provider settlement:

```
Bank (1000)  ==  Master (2050)
             +  Customer wallets (2000)
             +  Σ Provider payables (2110–2199)
```

Whole-system check (unchanged):

```
Assets  ==  Liabilities + Equity + (Income − COGS − Expenses)
```

When provider payable is settled to zero, bank and total liabilities both drop by
the settlement amount — customer e-money has fully left the closed loop.

---

## 5. Transaction workflows

### 5.1 Customer bill / service payment (accrual)

**Trigger:** Customer confirms payment; partner API returns success.

```
Entry: BILL-001 (Electricity bill 100)
  DR  2000 Customer Wallet Liability              100.00
  CR  2110 Provider Payable — Electricity Co      100.00
```

**With platform fee:**

```
Entry: BILL-002 (bill 100 + 2 fee)
  DR  2000 Customer Wallet Liability              102.00
  CR  2110 Provider Payable — Electricity Co      100.00
  CR  4000 Fee Income (Income)                      2.00
```

**Guards:**

- Customer `balance ≥ amount + fee` before API call.
- Partner `account_id` must be set.
- Idempotent client reference — no double post on retry.
- Wallet row locked (`lockForUpdate`) during post.

**Wallet effect:**

- Debit customer `Wallet.balance` by `amount + fee`.
- `wallet_transactions`: `type = bill_payment`, `direction = debit`.

**Ledger reference (proposed):** `BILL_PAYMENT` in `LedgerService`.

---

### 5.2 Provider settlement (bank payout)

**Trigger:** Operator (or batch job) pays the partner from company bank.

```
Entry: SETTLE-001 (pay Electricity Co 100)
  DR  2110 Provider Payable — Electricity Co      100.00
  CR  1000 Bank (Asset)                         100.00
```

**Guards:**

- Provider payable signed balance ≥ settlement amount.
- Settlement posts only to **that** partner's `21xx` account.
- Partial settlements allowed (multiple SETTLE entries until payable is zero).

**Ledger reference (proposed):** `PROVIDER_SETTLEMENT` in `LedgerService`.

**No wallet movement** — settlement is operator ↔ provider, not customer-facing.

---

### 5.3 Partner onboarding

When a new bill/service partner is created:

1. Allocate next free code in `2110–2199`.
2. Create `ChartOfAccount` (Liability).
3. Set `partners.account_id`.
4. Enable partner for bill payments only after step 3 is complete.

---

## 6. End-to-end example

**Setup:** Bank 2000, Master 800, Customer 200, Equity 1000 (after section 5.3
cash-in in parent doc).

| Step | Event | DR | CR | Bank | Master | Customer | Elec. Payable | Income |
|------|-------|----|----|-----:|-------:|---------:|--------------:|-------:|
| 0 | Starting | | | 2000 | 800 | 200 | 0 | 0 |
| 1 | BILL-001 (100) | 2000 | 2110 | 2000 | 800 | 100 | 100 | 0 |
| 2 | SETTLE-001 (100) | 2110 | 1000 | 1900 | 800 | 100 | 0 | 0 |

After step 1: `A 2000 = L 1000 + E 1000` ✅ (customer → provider payable)

After step 2: `A 1900 = L 900 + E 1000` ✅ (bank paid provider; loop closed)

**With fee (BILL-002: 102 total, 100 to provider, 2 fee):**

| Step | Customer | Elec. Payable | Income | Bank |
|------|----------|---------------|--------|-----:|
| After BILL-002 | 98 | 100 | 2 | 2000 |
| After SETTLE | 98 | 0 | 2 | 1900 |

Fee stays in Income → rolls to Retained Earnings on P&L close.

---

## 7. Comparison with other flows

| | Transfer | Cash-out | Bill pay | Settlement |
|---|----------|----------|----------|------------|
| DR | 2000 | 2000 | 2000 | 21xx |
| CR | 2000 (+4000 fee) | 2050 | 21xx (+4000 fee) | 1000 |
| Bank moves? | No | No | No | **Yes** |
| Per-entity COA? | No (aggregate) | No | **Yes (per partner)** | **Yes** |
| External party? | No | No | **Yes** | **Yes** |

---

## 8. Engineering checklist

| # | Requirement |
|---|-------------|
| 1 | `AccountCode` constants or dynamic lookup for `21xx` via `partners.account_id` |
| 2 | `LedgerService::REF_BILL_PAYMENT`, `REF_PROVIDER_SETTLEMENT` |
| 3 | `WalletService::billPay(Wallet, Partner, amount, fee?, ...)` |
| 4 | `ProviderSettlementService::settle(Partner, amount, ...)` (admin) |
| 5 | Post ledger only after partner API success |
| 6 | Idempotency key on bill payment API |
| 7 | Reconciliation report: payable balance per partner vs unpaid bills |
| 8 | Cypress / PHPUnit: bill accrual + settlement keeps `isSystemBalanced()` true |

---

## 9. Validation checklist (full lifecycle)

```
Entry: BILL-002 + SETTLE-001 (customer pays Electricity 100 + 2 fee; operator settles 100)

Step 1 — BILL-002:
  DR 2000 Customer Wallet Liability       102.00
  CR 2110 Provider Payable — Electricity  100.00
  CR 4000 Fee Income                        2.00

Step 2 — SETTLE-001:
  DR 2110 Provider Payable — Electricity  100.00
  CR 1000 Bank                            100.00

- [x] 1. Each entry balances
- [x] 2. No line has both debit and credit
- [x] 3. No negative or zero/zero lines
- [x] 4. Partner 2110 exists; partners.account_id linked
- [x] 5. Customer DR = spend; provider CR = accrue obligation ✅
- [x] 6. Fee → Income (4000), not provider payable ✅
- [x] 7. Distinct references: BILL_PAYMENT vs PROVIDER_SETTLEMENT
- [x] 8. After both steps: customer −102, bank −100, payable 0, income +2; system balanced ✅

VERDICT: ✅ Correct

Why: Two-step model separates customer e-money consumption from bank payout;
each provider has an isolated payable for reconciliation.

Report impact:
  - Balance sheet: customer liabilities ↓, provider payable ↑ then ↓, bank ↓ on settle
  - P&L: fee → Income
  - Trial balance: total DR == total CR after each entry
```

---

## 10. Verdict

```
VERDICT: ✅ Valid accounting design — ready for implementation.

Why: Per-provider payables (21xx) with accrual-then-settlement preserves the
master-float model, keeps bank movement on operator payout only, and allows
clean reconciliation per third-party partner. Fees follow the same Income rule
as wallet transfers.
```

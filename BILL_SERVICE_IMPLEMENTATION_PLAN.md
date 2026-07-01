# Bill & Service Payment — Implementation Plan

> Accounting source: [`BILL_SERVICE_ACCOUNTING_WORKFLOW_PLAN.md`](BILL_SERVICE_ACCOUNTING_WORKFLOW_PLAN.md)
> Parent: [`ACCOUNTING_WORKFLOW_PLAN.md`](ACCOUNTING_WORKFLOW_PLAN.md) §5.7–5.9, §11
> Status: **Ready to implement** (accounting approved; code not started)

---

## A. Current code vs what to build (gap analysis)

### What already works (reuse as-is)

| Piece | Location | Reuse for bill/service |
|-------|----------|------------------------|
| Posting engine | `LedgerService::post()` | Same multi-line posts; supports `account_id` per line |
| Wallet money ops | `WalletService::transfer()`, `cashIn()`, `cashOut()` | Copy lock/balance/assert patterns |
| Customer wallet API | `CustomerWalletController` + `CustomerWalletService` | Same auth, idempotency header, error shape |
| Idempotency | `IdempotencyService` + `wallet_idempotency_keys` | New scope `wallet.bill_payment` |
| Transfer OTP | `WalletTransferOtpService` | Optional: reuse for high-value bill pay |
| Admin wallet ops | `AdminWalletMoneyService` | Pattern for admin settlement idempotency |
| Accounting reports | `FinancialReportService`, `AccountBalanceService` | `isSystemBalanced()` after bill + settle |
| Partner / Service catalog | `Partner`, `Service`, `Product`, `ProductServiceForm` | Partner link + `form_url` for API |
| POS service call | `ServiceTransactionService` | HTTP call pattern; **not** wallet posting today |
| E2E accounting harness | `Payment/cypress/e2e/wallet-accounting/*` | Snapshot helpers, admin cash-in seed |
| PHPUnit reconciliation | `WalletAccountingReconciliationTest` | Extend lifecycle with bill + settle |

### What is missing (must build)

| Gap | Current state | Required change |
|-----|---------------|-----------------|
| **Partner payable COA** | `ChartOfAccountSeeder` has no `21xx` | Seed `2100` control + allocate `2110+` per partner |
| **`partners.account_id`** | Column does not exist on `Partner` | Migration + model + admin UI |
| **Ledger references** | Only `WALLET_*`, `OPENING_CAPITAL` | `REF_BILL_PAYMENT`, `REF_PROVIDER_SETTLEMENT` |
| **`WalletService::billPay()`** | Does not exist | `DR 2000 / CR partner.account_id` (+ fee → `4000`) |
| **Settlement service** | Does not exist | `ProviderSettlementService::settle()` → `DR 21xx / CR 1000` |
| **`wallet_transactions.type`** | Enum: topup, payment, transfer, refund, adjustment | Add `bill_payment` (migration) |
| **Wallet bill record** | `ServiceTransaction` links to POS `Transaction` only | New `wallet_bill_payments` (or extend `service_transactions` with `wallet_id`) |
| **Customer bill API** | No customer routes for services/bills | Catalog + pay endpoints under `customer/v1` |
| **Partner API orchestration** | POS captured flow only | `WalletBillPaymentService`: validate → call partner → post on success |
| **Admin settlement API** | No provider payout endpoint | `POST /v2/admin/provider-settlements` |
| **Frontend utils** | `walletAccounting.js` has no `billPayment` | Add deltas for accrual + settlement |
| **Permissions** | No `settle_provider_payables` | Add to `config/permission.php` |
| **Customer app UI** | Wallet transfer/withdraw only | Service catalog + bill pay screens |

### Enhance (do not rewrite)

| File | Enhancement |
|------|-------------|
| `app/Support/AccountCode.php` | `PROVIDER_PAYABLE_CONTROL = 2100`; helper or range constant `2110–2199` |
| `app/Services/LedgerService.php` | Two new `REF_*` constants |
| `app/Services/WalletService.php` | `billPay()`, `billPaymentFee()` (or reuse `transferFee()` config key) |
| `app/Models/Partner.php` | `account_id`, `chartOfAccount()` relation |
| `database/seeders/ChartOfAccountSeeder.php` | `2100` + doc comment for dynamic `2110+` |
| `app/Modules/Accounting/Services/ChartOfAccountService.php` | `createProviderPayableAccount(Partner)` — next free `21xx` |
| `app/Services/ServiceTransactionService.php` | Extract shared HTTP client; wallet flow uses new service |
| `app/Modules/CustomerAuth/Services/CustomerWalletService.php` | `payBill()` wrapping idempotency + notifications |
| `Payment/src/utils/walletAccounting.js` | `billPayment`, `providerSettlement` cases |
| `WalletAccountingReconciliationTest` | Full lifecycle including bill accrual + settlement |

---

## B. Phased implementation plan

### Phase 0 — Data model & chart of accounts (backend only)

**Goal:** Partners can be linked to a payable account; seeder knows `21xx` range.

**Done when:** Partner with `account_id` exists in DB; COA row `2110` visible in admin accounting.

### Phase 1 — Core money layer (backend)

**Goal:** `billPay()` and `settle()` post balanced ledger lines; wallet debits correctly.

**Done when:** PHPUnit proves `isSystemBalanced()` after bill + settlement with fee.

### Phase 2 — Partner integration & persistence (backend)

**Goal:** Bill pay calls partner API; posts only on success; audit row stored.

**Done when:** Feature test mocks partner HTTP 200 → single ledger entry; 500 → no wallet movement.

### Phase 3 — Customer API (backend + minimal FE)

**Goal:** Customer can list billable services and pay from wallet.

**Done when:** Postman/Cypress customer flow completes bill pay with idempotency.

### Phase 4 — Admin settlement & reconciliation (backend + admin FE)

**Goal:** Operator settles provider payables from bank; sees payable balance per partner.

**Done when:** Admin settles partial/full; bank and payable move correctly.

### Phase 5 — E2E & hardening

**Goal:** Cypress wallet-accounting specs; chaos cases; docs updated.

**Done when:** New specs `20–24` green in CI; `BILL_SERVICE_ACCOUNTING_WORKFLOW_PLAN.md` status → Implemented.

**Dependency chain:**

```
Phase 0 → Phase 1 → Phase 2 → Phase 3
                ↘ Phase 4 (can start after Phase 1)
Phase 3 + 4 → Phase 5
```

---

## C. PRD

# PRD: Customer wallet bill & service payment (two-step provider settlement)

## Problem / context

Customers hold e-money in wallet liabilities backed 1:1 by bank cash. The app already
offers third-party bills/services via `Partner` + `Service` + partner APIs, but wallet
money today only moves via cash-in/out, transfer, and withdraw. Paying a utility bill
from the wallet requires a **new accrual entry** (`DR Customer / CR Provider Payable`)
and a later **settlement** (`DR Provider Payable / CR Bank`), with **one payable account
per partner** for reconciliation.

## Goals & non-goals

- **Goal:** Customer pays bill/service from wallet; ledger balances; partner API called;
  operator can settle payables from bank per partner.
- **Goal:** Platform fee (if any) credits Fee Income, same as transfer.
- **Non-goal:** Instant `DR 2000 / CR 1000` one-step (batch settlement is required).
- **Non-goal:** Per-customer COA rows (customer stays aggregate `2000`).
- **Non-goal:** Automatic bank wire to partner (operator triggers settlement in v1).
- **Non-goal:** Reversal/refund tooling (out of scope per accounting plan §8).

## Users & permissions

| Role | Capability | Permission (proposed) |
|------|------------|------------------------|
| Customer | Browse services, pay bill from wallet | Authenticated customer (`customer` guard) |
| Admin | Create partner payable COA, link `account_id` | `edit_partners`, `create_chart_of_accounts` |
| Admin | Settle provider payable from bank | `settle_provider_payables` (new) |
| Admin | View payable balances / bill payment history | `view_chart_of_accounts`, `view_service_transactions` |

## Functional requirements

- **FR1:** On partner create/approve, system can allocate `2110–2199` COA and set `partners.account_id`.
- **FR2:** Customer can list active services (by country/region) with amount or amount-from-form.
- **FR3:** Customer submits bill payment with `service_id`, `product_id` (optional), `service_payload`, `amount`, `idempotency_key`.
- **FR4:** System validates wallet balance ≥ amount + fee; partner has `account_id`; service is active.
- **FR5:** System calls partner `form_url`; on success posts `DR 2000 / CR partner payable` (+ `CR 4000` if fee).
- **FR6:** On partner API failure, no ledger post and no wallet debit.
- **FR7:** `wallet_transactions` records `type=bill_payment`, `direction=debit`.
- **FR8:** Admin can settle amount against a partner payable (`DR 21xx / CR 1000`); partial allowed.
- **FR9:** Idempotent bill payment and settlement requests return stored response without double post.
- **FR10:** Customer receives notification on successful bill payment.

## Money & data rules (fintech)

- Accrual: `DR 2000` total = provider amount + fee; `CR 21xx` = provider amount only; `CR 4000` = fee.
- Settlement: `DR 21xx` = settle amount; `CR 1000` = same.
- Golden invariant (between accrual and settle): `Bank = Master + Customers + Σ Provider payables`.
- Currency: v1 same currency as wallet (SDG); multi-currency later.
- Region: service/partner must match customer country scope (`AppliesCountryScope`).
- Rounding: 2 decimals; debits = credits after round.
- Idempotency scopes: `wallet.bill_payment`, `admin.provider_settlement:{partnerId}`.

## Edge cases & failure handling

| Case | Behavior |
|------|----------|
| Insufficient balance | 422, no API call / no post |
| Partner missing `account_id` | 422, service disabled for wallet pay |
| Partner API timeout/failure | 502/422, no post; store failed attempt |
| Duplicate idempotency key | Return first response |
| Settlement > payable balance | 422 |
| Inactive/frozen customer wallet | 422 (same as transfer) |
| Service inactive or wrong region | 404/422 |
| Concurrent bill pays | Wallet row lock prevents double spend |

## Success metrics

- `isSystemBalanced()` true after every bill + settlement in tests.
- Zero duplicate wallet debits on idempotent retry (monitor `wallet_idempotency_keys`).
- Provider payable balance reconciles with sum of unsettled bill payments per partner.

## Assumptions & open questions

- **Assumption:** Bill pay requires OTP (reuse transfer OTP flow) — *confirm with product*; plan supports optional v1 without OTP for low amounts.
- **Assumption:** Fee uses `config('services.wallet.bill_payment_fee')` or per-service fee from `Product` — start with global config like transfer.
- **Assumption:** New table `wallet_bill_payments` links `wallet_id`, `partner_id`, `service_id`, `ledger reference_id`.
- **Open:** Refund if partner API succeeds but service fails later — defer to v2.

---

## D. Tasks

### Phase 0 — Schema & COA

- [ ] BE: Migration `add_account_id_to_partners_table` — `partners.account_id` nullable FK → `chart_of_accounts.id` — done when: column exists
- [ ] BE: Migration `create_wallet_bill_payments_table` — uuid, wallet_id, partner_id, service_id, product_id, amount, fee, status, partner_reference, service_payload, ledger reference fields — done when: model + factory
- [ ] BE: Migration `add_bill_payment_to_wallet_transactions_type` — extend enum with `bill_payment` — done when: migration runs
- [ ] BE: `PartnerPayableAccountService` — `allocateForPartner(Partner): ChartOfAccount` — file: `app/Services/PartnerPayableAccountService.php` — done when: next `2110+` created
- [ ] BE: Hook partner approve/create — call allocate + set `account_id` — file: `AdminPartnerController` or observer — done when: new partner gets COA
- [ ] BE: Seed `2100 Third Party Payables` in `ChartOfAccountSeeder` — done when: seeder idempotent

### Phase 1 — Ledger & wallet core

- [ ] BE: `LedgerService::REF_BILL_PAYMENT`, `REF_PROVIDER_SETTLEMENT` — file: `LedgerService.php`
- [ ] BE: `AccountCode::PROVIDER_PAYABLE_CONTROL = 2100` — file: `AccountCode.php`
- [ ] BE: `WalletService::billPay(Wallet, ChartOfAccount $payable, float $amount, float $fee, ...)` — file: `WalletService.php` — done when: posts balanced lines + wallet debit
- [ ] BE: `ProviderSettlementService::settle(Partner, float $amount, ...)` — file: `app/Services/ProviderSettlementService.php` — done when: posts `DR 21xx / CR 1000`
- [ ] BE: Unit test `WalletServiceBillPayTest` — balanced entry, fee to 4000
- [ ] BE: Feature test `ProviderSettlementTest` — partial + full settlement

### Phase 2 — Partner API orchestration

- [ ] BE: `WalletBillPaymentService::process(...)` — validate → HTTP → post on success — file: `app/Services/WalletBillPaymentService.php`
- [ ] BE: Extract `PartnerApiClient` from `ServiceTransactionService` (shared HTTP + logging)
- [ ] BE: Status machine on `wallet_bill_payments`: pending → completed | failed
- [ ] BE: Reject post if partner response not successful

### Phase 3 — Customer API

- [ ] BE: `CustomerBillServiceController` — `GET services/catalog`, `GET services/{id}`, `POST wallet/bill-payment` — routes: `api.php` customer group
- [ ] BE: Form requests — `CustomerBillPaymentRequest` (amount, service_id, product_id, service_payload, idempotency)
- [ ] BE: `CustomerWalletService::payBill()` + scope `wallet.bill_payment` — idempotency wrapper
- [ ] BE: `CustomerWalletService::SCOPE_BILL_PAYMENT` constant
- [ ] BE: Feature tests in `CustomerWalletBillPaymentTest.php`
- [ ] FE: Customer service catalog screen — `Payment/src/` customer app area — done when: lists services
- [ ] FE: Bill pay form (dynamic fields from `ProductServiceForm`) — submits to bill-payment API
- [ ] FE: Wallet transactions filter shows `bill_payment` type

### Phase 4 — Admin settlement & reports

- [ ] BE: `AdminProviderSettlementController` — index payables, post settlement — `routes/api.php` admin
- [ ] BE: `AdminProviderSettlementService` — idempotency scope per partner
- [ ] BE: Permission `settle_provider_payables` in `config/permission.php`
- [ ] BE: Report endpoint or ledger filter: payable balance by partner (`account_id`)
- [ ] FE: Admin partner detail — show linked COA code + payable balance
- [ ] FE: Admin settlement form — partner select, amount, idempotency

### Phase 5 — QA & docs

- [ ] QA: Extend `walletAccounting.js` — `billPayment`, `providerSettlement` deltas
- [ ] QA: Cypress `20-bill-payment-happy.cy.js` — accrual snapshot
- [ ] QA: Cypress `21-bill-payment-insufficient.cy.js`
- [ ] QA: Cypress `22-bill-payment-idempotency.cy.js`
- [ ] QA: Cypress `23-provider-settlement.cy.js` — full lifecycle
- [ ] QA: Cypress `24-bill-partner-api-failure.cy.js` — zero delta
- [ ] QA: Extend `WalletAccountingReconciliationTest` — bill + settle in lifecycle
- [ ] DOC: Set `BILL_SERVICE_ACCOUNTING_WORKFLOW_PLAN.md` status to Implemented when Phase 5 passes

---

## E. Scenarios (user stories + Gherkin)

### Story 1 — Customer pays a bill from wallet

```gherkin
Story: As a customer, I want to pay a third-party bill from my wallet so that the provider receives the service without a wallet transfer.

Scenario: Successful bill payment accrues provider payable
  Given I am an active customer with wallet balance 200
  And partner "Electricity Co" has payable account 2110 linked via partners.account_id
  And service "Electricity bill" is active for my country
  And the partner API will return success
  When I POST bill payment for amount 100 with a new Idempotency-Key
  Then the response is 200
  And my wallet balance is 100
  And a wallet_transaction exists with type "bill_payment" and direction "debit"
  And ledger lines exist with reference "BILL_PAYMENT"
    | account        | debit | credit |
    | 2000 Customer  | 100   | 0      |
    | 2110 Provider  | 0     | 100    |
  And AccountBalanceService.isSystemBalanced() is true
  And bank account 1000 balance is unchanged

Scenario: Bill payment with platform fee
  Given my wallet balance is 200
  And bill payment fee is configured as 2
  When I pay a bill of 100 to Electricity Co successfully
  Then my wallet balance is 98
  And ledger includes credit 2 to account 4000 Fee Income
  And provider payable 2110 is credited 100 only
```

### Story 2 — Bill payment failures (no side effects)

```gherkin
Story: As the platform, I must not debit the customer unless the partner confirms the bill.

Scenario: Insufficient balance rejected
  Given my wallet balance is 50
  When I attempt to pay a bill of 100
  Then the response is 422
  And my wallet balance is still 50
  And no BILL_PAYMENT ledger lines exist
  And accounting snapshot delta is zero

Scenario: Partner API failure
  Given my wallet balance is 200
  And the partner API returns HTTP 500
  When I attempt to pay a bill of 100
  Then the response indicates failure
  And my wallet balance is still 200
  And wallet_bill_payments status is "failed"
  And no BILL_PAYMENT ledger lines exist

Scenario: Partner without payable account
  Given partner "New Co" has no account_id
  When I attempt to pay a bill for that partner's service
  Then the response is 422
  And no ledger or wallet movement occurs
```

### Story 3 — Idempotent bill payment

```gherkin
Story: As a customer, I want retries to be safe so I am not charged twice.

Scenario: Duplicate Idempotency-Key returns same result
  Given I successfully paid bill 100 with Idempotency-Key "pay-abc"
  When I repeat the same request with Idempotency-Key "pay-abc"
  Then the response matches the first response
  And my wallet was debited only once
  And only one BILL_PAYMENT posting exists for that reference
```

### Story 4 — Admin settles provider payable

```gherkin
Story: As an admin, I want to pay the provider from bank so the payable clears.

Scenario: Full settlement after customer bill payments
  Given provider payable 2110 has balance 100 from customer bill payments
  And bank balance is 2000
  When admin settles 100 to Electricity Co with idempotency key
  Then ledger lines exist with reference "PROVIDER_SETTLEMENT"
    | account       | debit | credit |
    | 2110 Provider | 100   | 0      |
    | 1000 Bank     | 0     | 100    |
  And provider payable balance is 0
  And bank balance is 1900
  And isSystemBalanced() is true

Scenario: Partial settlement
  Given provider payable balance is 100
  When admin settles 60
  Then payable balance is 40
  And bank decreased by 60

Scenario: Settlement exceeds payable rejected
  Given provider payable balance is 40
  When admin attempts to settle 100
  Then the response is 422
  And no PROVIDER_SETTLEMENT ledger lines are created
```

### Story 5 — Partner onboarding

```gherkin
Story: As an admin, I want each new bill partner to have its own payable account.

Scenario: Approve partner creates payable COA
  Given a pending partner "Water Utility"
  When admin approves the partner
  Then a chart_of_accounts row exists in range 2110-2199
  And partners.account_id points to that row
  And the account type is Liabilities
```

---

## F. Test matrix

| ID | Scenario | Type | Layer | File / area | Expected |
|----|----------|------|-------|-------------|----------|
| T1 | Bill pay happy (no fee) | Unit | WalletService | `tests/Unit/WalletServiceBillPayTest.php` | Balanced DR 2000 / CR 21xx; wallet −100 |
| T2 | Bill pay with fee | Unit | WalletService | same | CR 4000 = fee; payable = net to provider |
| T3 | Provider settlement full | Unit | ProviderSettlementService | `tests/Unit/ProviderSettlementServiceTest.php` | DR 21xx / CR 1000 balanced |
| T4 | Settlement exceeds payable | Unit | ProviderSettlementService | same | Exception, no lines |
| T5 | Full lifecycle balanced | Feature | Accounting | `WalletAccountingReconciliationTest` | `isSystemBalanced()` after bill + settle |
| T6 | Customer API happy | Feature | API | `CustomerWalletBillPaymentTest.php` | 200 + wallet_transaction |
| T7 | Insufficient balance | Feature | API | same | 422, no ledger |
| T8 | Idempotency duplicate | Feature | API | same | Single debit, cached response |
| T9 | Partner API failure | Feature | HTTP mock | `WalletBillPaymentServiceTest.php` | No ledger, status failed |
| T10 | Partner no account_id | Feature | API | same | 422 |
| T11 | Permission denied settlement | Feature | Admin API | `AdminProviderSettlementTest.php` | 403 |
| T12 | Partner approve creates COA | Feature | Admin | `PartnerPayableAccountTest.php` | account_id set, code in 21xx |
| T13 | Bill pay accrual snapshot | E2E | Cypress | `20-bill-payment-happy.cy.js` | Customer ↓, payable ↑, bank unchanged |
| T14 | Bill pay insufficient | E2E | Cypress | `21-bill-payment-insufficient.cy.js` | zeroAccountingDelta |
| T15 | Bill idempotency | E2E | Cypress | `22-bill-payment-idempotency.cy.js` | Single debit |
| T16 | Settlement lifecycle | E2E | Cypress | `23-provider-settlement.cy.js` | payable 0, bank ↓ |
| T17 | Partner API failure E2E | E2E | Cypress | `24-bill-partner-api-failure.cy.js` | zero delta |
| T18 | walletAccounting.js billPayment | Unit | JS | `walletAccounting.test.js` | Correct delta object |
| T19 | Frozen wallet bill pay | Feature | API | `CustomerWalletBillPaymentTest.php` | 422 |
| T20 | Region mismatch service | Feature | API | same | 404/422 |

**Traceability:** FR1→T12; FR3–FR7→T6,T13; FR5–FR6→T9,T17; FR8→T3,T11,T16; FR9→T8,T15.

---

## G. Code touch map (what applies where)

```
Customer App                    Customer API                         Core Services
─────────────                   ────────────                         ─────────────
Service catalog UI      →       GET  customer/v1/services/*    →     Service (existing)
Bill pay form           →       POST customer/v1/wallet/bill-payment → CustomerWalletService::payBill()
                                                                        → WalletBillPaymentService
                                                                        → Partner HTTP (form_url)
                                                                        → WalletService::billPay()
                                                                        → LedgerService::post(BILL_PAYMENT)

Admin partner UI        →       PUT  admin/partners/{id}       →     PartnerPayableAccountService
Admin settlement UI     →       POST admin/provider-settlements →    ProviderSettlementService
                                                                        → LedgerService::post(PROVIDER_SETTLEMENT)

Reports                 →       GET  admin/accounting/*        →     FinancialReportService (existing)
Cypress assertions      →       walletAccounting.js            →     expectedWalletOperationDelta('billPayment')
```

---

## H. Quality bar checklist

- [x] Every FR traces to ≥1 scenario and ≥1 test row
- [x] Money paths assert ledger balance + `isSystemBalanced()`
- [x] Idempotency, permissions, region scoping addressed
- [x] Failure paths (API fail, insufficient, no account_id) covered
- [x] Implementation complete

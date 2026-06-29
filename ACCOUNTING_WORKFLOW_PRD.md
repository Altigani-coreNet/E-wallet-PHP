# PRD & Implementation Spec: E-Wallet Accounting Workflow

> Companion to [`ACCOUNTING_WORKFLOW_PLAN.md`](./ACCOUNTING_WORKFLOW_PLAN.md) (the accounting model).
> This document turns that model into a buildable spec: gap analysis vs. current
> code, PRD, use cases (operator / customer), API design, task
> breakdown, Gherkin scenarios, a test matrix, and an edge-case catalog.
>
> Model: pre-funded **master float** + aggregate customer liability. **No merchant.**
>
> Stack: Laravel backend (`Fast_Pay_Soft_Pos/`), React admin (`Payment/src/`),
> double-entry ledger (`LedgerService` / `TransactionLine`), wallet balance is a
> **liability**. Cross-cutting: idempotency, permissions, currency, region scope,
> auditability, and failure paths.

---

## 0. Glossary

| Term | Meaning |
|------|---------|
| Master wallet | System-owned **pre-funded float** (`WalletService::MASTER_WALLET_ID = 'WAL-MASTER'`), posts to `2050`. |
| Customer wallet | A customer's wallet; balance is a liability owed to the customer, aggregated under `2000`. |
| Master fund / defund | Operator deposits cash into / withdraws cash from the master float (`DR Bank / CR 2050` and reverse). |
| Buy / cash-in | Master issues e-money to a customer (`DR 2050 / CR 2000`); master must hold float. |
| Withdraw / cash-out | Customer redeems e-money back to the master (`DR 2000 / CR 2050`). |
| Fee | Platform charge on a transaction; credited to **Fee Income (4000)**. |
| Posting | A balanced set of ledger lines written via `LedgerService::post()`. |

---

## 1. Gap analysis — current code vs. target model

What exists today (read from the repo):

- `app/Services/LedgerService.php` — `post()` already enforces the **five invariants**
  (≥1 line, non-negative, not both DR+CR, not zero/zero, debits==credits). ✅ Reuse as-is.
- `app/Services/WalletService.php` — constants `BANK_ACCOUNT_CODE = 1000`,
  `WALLET_LIABILITY_ACCOUNT_CODE = 2000`, `MASTER_WALLET_ID`. Has `topUpFromBank()`,
  `transfer()` (with `lockForUpdate` + insufficient-balance guard), `createMasterWallet()`,
  `createForCustomer()`.
- `app/Models/Wallet.php` — `TYPE_MASTER` / `TYPE_USER`, `isMaster()`, `account_id` link.
- `app/Models/WalletTransaction.php` — types `topup|payment|transfer|refund|adjustment`,
  directions `credit|debit`.
- `app/Models/WalletIdempotencyKey.php` — model exists (scope + key + cached response).
- `app/Modules/Accounting/Services/AccountBalanceService.php` — `isSystemBalanced()`,
  signed balances, opening/closing balances. ✅ Reuse.
- `app/Modules/Accounting/Services/FinancialReportService.php` — ledger / balance sheet / P&L.

### Gaps to close (this is the work)

| # | Gap | Status | Implementation |
|---|-----|--------|----------------|
| G1 | **Customer cash-in (master issues e-money)** | Done | `cashIn()` for a customer posts `DR 2050 Master / CR 2000 Customer`, guarded by master float balance; updates master + customer balances |
| G2 | **Customer cash-out (redeem to master)** | Done | `cashOut()` for a customer posts `DR 2000 Customer / CR 2050 Master`, overdraft-guarded |
| G2b | **Master fund / defund** | Done | `cashIn(master)` posts `DR Bank / CR 2050`; `cashOut(master)` posts `DR 2050 / CR Bank`, float-guarded |
| G3 | **Fees on transfer** | Done | `transfer()` posts `DR 2000 / CR 2000 / CR 4000 Fee Income` (fee optional) |
| G4 | **Ledger granularity** | Done | Aggregate `2000` for customers + dedicated `2050` for master; per-wallet detail in `wallet_transactions` |
| G5 | **Idempotency wired in** | Done | Admin money endpoints wrap cash-in/out via `IdempotencyService` keyed by scope+key |
| G6 | **Opening capital / Equity** | Done | `recordOpeningCapital()` posts `DR Bank / CR 3000 Owner's Capital` |
| G7 | **Immutability** | Done | Ledger lines append-only; no correction/reversal flow |
| G8 | **Master = pre-funded float** | Done | Master holds a real balance; funded/defunded by operator |
| G9 | **Overdraft guard everywhere** | Done | `assertSufficientBalance()` on every debit path (transfer, cash-out, customer cash-in vs master float) |
| G10 | **Fee/tax + COGS accounts** | Done | Seeded `2000, 2050, 2900, 3000, 3900, 4000` (+ optional `5000`) |

---

## 2. Decisions (locked)

**D1 — Wallet ledger granularity: aggregate.** All customer wallets post to a single
`2000 Customer Wallet Liability`; the master posts to `2050 Master Wallet Liability`.
Per-wallet history lives in `wallet_transactions` keyed by `wallet_id`. No per-wallet
chart-of-accounts rows.

**D2 — Master wallet: pre-funded float.** The master holds a real running balance.
The operator funds it (`DR Bank / CR 2050`) and defunds it (`DR 2050 / CR Bank`).
Customer cash-in/out shift e-money between `2050` and `2000` only; the bank moves
only on master fund/defund. All ops lock the affected rows; insufficient float is
rejected before posting.

**D3 — No merchant.** There is no merchant account or merchant wallet type. All
customer wallets are equivalent and aggregate to `2000`.

---

## 3. PRD

```markdown
# PRD: E-Wallet Accounting Workflow (cash-in, transfer, cash-out, fees)

## Problem / context
The wallet system must move money between a pre-funded master float and customer
wallets while keeping a correct, always-balanced double-entry ledger. The operator
funds the master with cash, sells e-money to customers (cash-in), customers transact
with fees, redeem to cash (cash-out), and the operator can withdraw float. Affected:
operators (fund/settle), customers (buy/spend/withdraw), finance/admin (reports must
balance).

## Goals & non-goals
- Goal: Customer buys balance (cash-in), transacts (with fees), withdraws (cash-out).
- Goal: Every operation posts a balanced ledger entry; system stays balanced.
- Goal: Idempotent, atomic, overdraft-safe money movements.
- Goal: Operator opening capital recorded as Equity.
- Non-goal: Interest, lending, FX conversion between currencies (single currency per wallet).
- Non-goal: Card/PSP integration (cash-in trust handled operationally).

## Users & permissions
- Operator / Senior Admin — fund opening capital, fund/defund the master float,
  approve cash-in/out, view reports.
  Permissions (see `Payment/src/utils/permissions.js`, `config/permission.php`):
  `wallets.manage`, `wallets.cashin`, `wallets.cashout`, `accounting.view`.
- Customer — buy, transfer, withdraw within own wallet (`wallets.self`).

## Functional requirements
- FR1: Operator can record opening capital (DR Bank / CR Owner's Capital).
- FR2a: Operator funds/defunds the master float (cash-in/out targeting the master):
  `DR Bank / CR 2050` and `DR 2050 / CR Bank`.
- FR2b: Customer cash-in: master issues e-money to the customer (`DR 2050 / CR 2000`),
  rejected if the master float is insufficient.
- FR3: Recipient lookup — resolve a single identifier (wallet_id OR user_number OR
  phone) to recipient wallet data before transferring.
- FR4: One unified transfer endpoint moves balance between customer wallets with an
  optional fee to Fee Income: `DR 2000 / CR 2000 (amount − fee) / CR 4000 (fee)`.
- FR5: Cash-out: customer redeems balance back to the master (`DR 2000 / CR 2050`).
- FR6: Every money op is idempotent (client key) and atomic (single DB txn).
- FR7: Reports (trial balance, balance sheet, P&L) reflect all of the above and balance.
- FR8: Ledger lines are append-only (immutable); no destructive edits.

## Money & data rules (fintech)
- All wallet balances are liabilities; bank cash backs them 1:1.
- Debits == credits on every posting (`LedgerService::post`).
- Fees credit Fee Income (4000) only — never a liability.
- No negative wallet balance: check `available_balance >= amount + fee` before posting.
- Amounts rounded to 2 decimals; rounding must preserve balance.
- Single currency per wallet; reject cross-currency transfers (out of scope).
- Region scope (X-Regions / CountryScope) respected on listing/reporting.

## Edge cases & failure handling
- Insufficient balance → 422, no ledger movement.
- Customer cash-in when master float is insufficient → 422, no movement.
- Duplicate request (same idempotency key) → return original result, single posting.
- Concurrent transfers on same wallet → serialized via lockForUpdate.
- Transfer to self / to frozen/closed wallet → rejected.
- Recipient identifier not found / ambiguous → 404 / clear error before transfer.
- Zero/negative amount → rejected.

## Success metrics
- 100% of postings balanced; `isSystemBalanced()` true after every operation.
- 0 duplicate postings under retry/load tests.
- Reconciliation: `Bank == Master float (2050) + Σ customer liabilities (2000) (+ fees swept)` holds daily.

## Assumptions & open questions
- D1 (aggregate ledger), D2 (pre-funded master float), and D3 (no merchant) per section 2.
- Cash-in trust (proof of cash receipt) is an operational control, not modeled here.
```

---

## 4. Use cases & actors

### 4.1 Operator / Senior Admin
- **UC-O1 Opening capital:** seed the business — `DR 1000 Bank / CR 3000 Owner's Capital`.
- **UC-O2 Fund master float:** deposit cash into the master — `DR 1000 Bank / CR 2050 Master`.
- **UC-O3 Approve cash-in:** confirm the customer paid cash, then master issues e-money.
- **UC-O4 Approve cash-out:** confirm cash handed to customer, then redeem balance.
- **UC-O5 Defund master float:** withdraw cash from the master — `DR 2050 Master / CR 1000 Bank`.
- **UC-O6 Reconcile & report:** view trial balance, balance sheet, P&L; verify
  `isSystemBalanced()`.

### 4.2 Customer
- **UC-C1 Buy balance (cash-in):** pay cash → wallet credited from the master float.
- **UC-C2 Resolve recipient:** enter one identifier (wallet_id / user_number / phone)
  → get recipient wallet data back to confirm.
- **UC-C3 Transfer:** send balance to the resolved recipient (another customer) via
  the single transfer endpoint (optional fee).
- **UC-C4 Withdraw (cash-out):** redeem balance back to cash via the master.
- **UC-C5 View history & balance.**

### Customer transfer journal entry (with 2 fee on a 100 transfer)

```
Entry: PAY-001 (Customer A pays Customer B 100, platform fee 2)
  DR  2000 Customer Wallet (Liability)   100.00
  CR  2000 Customer Wallet (Liability)    98.00
  CR  4000 Fee Income (Income)             2.00
```
`100 == 98 + 2` ✅ — fee lands in Income, customer liabilities net −2, system stays balanced.

---

## 5. API design (proposed)

All money endpoints require an `Idempotency-Key` header and respect `X-Regions`.

| Method | Endpoint | Use case | Permission |
|--------|----------|----------|------------|
| POST | `/api/admin/accounting/opening-capital` | UC-O1 | `accounting.manage` |
| POST | `/api/wallets/{wallet}/cash-in` | UC-C1 / UC-O2 / UC-O3 (customer or master target) | `wallets.cashin` |
| GET | `/api/wallets/resolve-recipient?identifier=` | UC-C2 (lookup) | `wallets.self` |
| POST | `/api/wallets/{wallet}/transfer` | UC-C3 (customer to customer) | `wallets.self` |
| POST | `/api/wallets/{wallet}/cash-out` | UC-C4 / UC-O5 (customer or master target) | `wallets.cashout` |
| GET | `/api/wallets/{wallet}/transactions` | history | `wallets.view` |
| GET | `/api/admin/accounting/balance-sheet` | UC-O6 | `accounting.view` |

There is exactly **one** transfer endpoint. The frontend always resolves the
recipient first (`resolve-recipient`), then posts to `transfer` with the resolved
`recipient_wallet_id` — no separate by-phone / by-wallet-id / pay endpoints. The
cash-in/cash-out endpoints target a customer wallet (issue/redeem) or the master
wallet (fund/defund) depending on the `{wallet}` id.

- Lookup response: `{ recipient_wallet_id, owner_name, wallet_id, status, currency }`.
- Transfer body: `{ recipient_wallet_id, amount, fee?, description? }` + `Idempotency-Key` header.
- Money-op response: `{ transaction_id, balance_after, posting_reference, status }`.

---

## 6. Tasks (implementation breakdown)

Dependency order: **migrations/seed → service methods → form requests → controllers/routes → resources → React → tests.**

### Backend

```
- [x] BE: Seed chart of accounts 2000 (Customer), 2050 (Master), 2900 (Fees/Tax),
      3000 (Owner's Capital), 3900 (Retained Earnings), 4000 (Fee Income)
      — area: database/seeders + ChartOfAccountService — done when: accounts exist with correct type/sub_type.
- [x] BE: Add account-code constants — area: AccountCode (BANK, CUSTOMER_LIABILITY,
      MASTER_LIABILITY, FEES_TAX_PAYABLE, OWNER_EQUITY, RETAINED_EARNINGS, FEE_INCOME).
- [x] BE: cashIn(Wallet, amount, …) — master target funds float (DR Bank / CR 2050);
      customer target issues e-money (DR 2050 / CR 2000), guarded by master float,
      dual-leg WalletTransactions written, atomic + locked.
- [x] BE: cashOut(Wallet, amount, …) — master target defunds (DR 2050 / CR Bank);
      customer target redeems (DR 2000 / CR 2050), overdraft guarded.
- [x] BE: Single transfer() supporting optional fee → CR 4000, customer to customer
      (both legs `2000`) — done when: balanced posting, fee → Fee Income; no merchant.
- [ ] BE: resolveRecipient(identifier) — area: WalletService/CustomerWalletController
      — done when: matches wallet_id OR user_number OR phone, returns recipient data,
      404 on miss.
- [ ] BE: Idempotency wrapper (lookup WalletIdempotencyKey by scope+key; replay cached
      response; store on success) — area: WalletService or a IdempotencyService
      — done when: duplicate key returns original result, single posting.
- [ ] BE: recordOpeningCapital(amount, createdBy) — area: new AccountingService
      — done when: DR 1000 / CR 3000 posted; balance sheet equity increases.
- [ ] BE: Overdraft guard helper assertSufficient(wallet, needed) used by all debit paths.
- [ ] BE: FormRequests (CashInRequest, CashOutRequest, TransferRequest,
      OpeningCapitalRequest) with amount>0, fee>=0, currency match, wallet status active.
- [ ] BE: Controllers + routes per section 5; remove old transferByWalletId/transferByPhone
      routes + methods; API Resources for transaction responses.
- [ ] BE: Permissions in config/permission.php (wallets.cashin/cashout, accounting.manage).
```

### Frontend (React, `Payment/src/`)

```
- [ ] FE: Cash-in / cash-out modals on admin wallet view — area: components/admin/wallets/
      — done when: calls endpoints with Idempotency-Key, shows balance_after.
- [ ] FE: Transfer flow = resolve-recipient search (identifier input → show recipient
      card) then single transfer call with fee preview (net to recipient).
- [ ] FE: Opening-capital form under accounting — area: components/admin/accounting/
- [ ] FE: Permission gating via utils/permissions.js for each new action.
- [ ] FE: Show master float vs customer liability in wallet dashboard.
```

### Cross-cutting / QA

```
- [ ] QA: Unit tests for WalletService (cashIn/cashOut/transfer+fee/resolveRecipient/idempotency).
- [ ] QA: Feature tests for each endpoint (happy, 422, 403, duplicate-key, frozen wallet).
- [ ] QA: Property/assertion: isSystemBalanced() true after every test op.
- [ ] QA: Concurrency test: parallel transfers on one wallet → no overdraft, no double post.
- [ ] QA: React component tests for modals (Payment/src/**/__tests__/).
```

---

## 7. Scenarios (Gherkin)

```gherkin
Story: As an operator, I want to fund the master float so I can sell e-money.

Scenario: Fund the master float (happy path)
  Given the master wallet exists with balance 0
  And the bank account 1000 and master account 2050 are configured
  When the operator records a cash-in of 1000 for the master wallet
  Then the master wallet balance is 1000.00
  And a balanced posting exists: DR Bank 1000 / CR Master 1000
  And isSystemBalanced() is true

Scenario: Defund the master float
  Given the master wallet balance is 1000
  When the operator records a cash-out of 300 for the master wallet
  Then the master wallet balance is 700.00
  And a balanced posting exists: DR Master 300 / CR Bank 300

Scenario: Defund rejected when float is insufficient
  Given the master wallet balance is 100
  When a cash-out of 300 for the master wallet is attempted
  Then it is rejected with 422 and no ledger movement
```

```gherkin
Story: As a customer, I want to buy wallet balance with cash so that I can transact.

Scenario: Successful cash-in (happy path, money correctness)
  Given a customer wallet "W1" with balance 0 and the master float holds 1000
  And the customer account 2000 and master account 2050 are configured
  When the operator records a cash-in of 200 for "W1" with idempotency key "k1"
  Then "W1" balance is 200.00
  And the master float balance is 800.00
  And a balanced posting exists: DR Master 200 / CR Customer 200
  And isSystemBalanced() is true

Scenario: Cash-in rejected when master float is insufficient
  Given a customer wallet "W1" and the master float holds 100
  When a cash-in of 200 for "W1" is attempted
  Then it is rejected with 422 and no ledger movement

Scenario: Duplicate cash-in is idempotent
  Given a successful cash-in of 200 for "W1" with key "k1"
  When the same request with key "k1" is replayed
  Then the original result is returned
  And "W1" balance is still 200.00
  And only one set of ledger lines exists for "k1"
```

```gherkin
Story: As a customer, I want to transfer balance to another user with a fee.

Scenario: Transfer with fee (money correctness)
  # Fee model: "amount" is what leaves the sender; recipient gets amount - fee
  Given "WA" balance 150 and "WB" balance 0
  When "WA" transfers 50 to "WB" with a fee of 2
  Then "WA" balance is 100.00 (50 deducted from 150)
  And "WB" balance is 48.00 (50 - 2 fee)
  And a 3-line balanced posting exists: DR WA 50 / CR WB 48 / CR Fee Income 2
  And Fee Income increased by 2
  And isSystemBalanced() is true

Scenario: Transfer rejected for insufficient balance (failure, no side effects)
  Given "WA" balance 40
  When "WA" attempts to transfer 50
  Then the request is rejected with 422
  And no ledger lines are created
  And "WA" balance is unchanged at 40.00

Scenario: Transfer to frozen wallet is rejected
  Given "WB" status is "frozen"
  When "WA" transfers 10 to "WB"
  Then the request is rejected
  And no balances change
```

```gherkin
Story: As a customer, I want to withdraw balance back to cash.

Scenario: Successful cash-out
  Given "W1" balance 48 and the master float holds 800
  When the operator records a cash-out of 48 for "W1"
  Then "W1" balance is 0.00
  And the master float balance is 848.00
  And a balanced posting exists: DR Customer 48 / CR Master 48
  And isSystemBalanced() is true

Scenario: Cash-out exceeding balance is rejected
  Given "W1" balance 10
  When a cash-out of 48 is attempted
  Then it is rejected with 422 and no ledger movement
```

```gherkin
Story: As an operator, I want to record opening capital.

Scenario: Opening capital recorded as equity
  When the operator records opening capital of 1000
  Then a balanced posting exists: DR Bank 1000 / CR Owner's Capital 1000
  And the balance sheet shows Equity 1000 and Assets 1000
  And isSystemBalanced() is true
```

```gherkin
Story: As a customer, I want to find a recipient before sending money.

Scenario: Resolve recipient by identifier (happy path)
  Given a customer wallet "WB" exists for a user with phone "0900000000"
  When I call resolve-recipient with identifier "0900000000"
  Then I receive "WB" recipient data (owner name, wallet_id, status, currency)

Scenario: Resolve recipient not found
  When I call resolve-recipient with identifier "does-not-exist"
  Then I get a 404 and no transfer is possible
```

> **Fee-model decision (D3):** is `amount` the gross debit from the sender (fee
> included) or added on top? Lock this in the FormRequest. The scenarios above use
> the recommended model: `amount` is what leaves the sender; recipient gets
> `amount − fee`; posting is `DR amount / CR (amount − fee) / CR fee`.

---

## 8. Test matrix

| ID | Scenario | Type | Layer | Expected |
|----|----------|------|-------|----------|
| T1 | Happy customer cash-in (from master float) | Feature | Laravel (PHPUnit) | 200, DR 2050 / CR 2000, balances updated, system balanced |
| T2 | Duplicate idempotency key | Unit/Feature | Idempotency + WalletService | single posting, cached response replayed |
| T3 | Insufficient balance (transfer/cash-out) | Feature | API | 422, no ledger movement |
| T4 | Permission denied | Feature | API | 403 |
| T5 | Transfer with fee | Unit | WalletService | 3-line balanced, Fee Income +fee |
| T6 | Master fund / defund (+ insufficient float) | Feature | API | DR Bank/CR 2050 and reverse; 422 when float low |
| T7 | Cash-out happy | Feature | API | balance 0, DR 2000 / CR 2050 |
| T8 | Opening capital | Feature | AccountingService | DR 1000 / CR 3000, equity up |
| T9 | Resolve recipient (found / not found) | Feature | CustomerWalletController | recipient data on match, 404 on miss |
| T10 | Concurrency (parallel transfers) | Feature | DB locks | serialized, no overdraft, no double post |
| T11 | Frozen/closed wallet | Feature | API | rejected, no movement |
| T12 | Zero/negative amount | Unit | FormRequest/Service | rejected |
| T13 | System balanced invariant | Feature | AccountBalanceService | isSystemBalanced() true after each op |
| T14 | React cash-in/out modal | Component | React (Vitest/RTL) | sends Idempotency-Key, renders balance_after |

Reference patterns: `tests/Feature/AdminWalletApiTest.php`, `tests/Unit/Admin/AdminWalletServiceTest.php`,
`tests/Support/AdminWalletTestHelpers.php`; frontend `Payment/src/**/__tests__/`.

---

## 9. Edge-case catalog

| Area | Edge case | Required handling |
|------|-----------|-------------------|
| Amounts | 0, negative, > balance, > 2 decimals | Reject (FormRequest + service); round to 2dp |
| Fee | fee > amount, negative fee, fee with no income account | Reject; ensure 4000 exists |
| Idempotency | replay, same key different body, concurrent same key | Replay cached; reject mismatched body; lock on key |
| Concurrency | two debits draining one wallet | `lockForUpdate` both wallets in deterministic order (avoid deadlock) |
| Master float | customer cash-in / defund exceeding float | Lock master row; reject if float insufficient before posting |
| Wallet status | frozen / closed source or destination | Reject |
| Self-transfer | from == to | Reject (already in `transfer()`) |
| Currency | cross-currency transfer | Reject (out of scope) |
| Region | wallet outside caller's X-Regions scope | 403 / not found per CountryScope |
| Lookup | identifier not found, ambiguous, or matches caller's own wallet | 404 / clear error; block self-transfer |
| Reporting | posting dated in a closed period | Block or flag per accounting policy |
| Rounding | split that doesn't sum after rounding | Force debits==credits; assign rounding remainder deterministically |
| Cash-in trust | issued before cash actually received | Operational approval step (UC-O2) before issuance |

---

## 10. Definition of done (quality bar)

- [ ] Every FR traces to ≥1 scenario and ≥1 test (sections 6–8).
- [ ] Every money path asserts the posting balances and `isSystemBalanced()` stays true.
- [ ] Idempotency, permissions, currency, and region scoping covered.
- [ ] Failure paths tested, not just happy paths.
- [ ] `transaction_lines` are append-only (no destructive edits; corrections out of scope).
- [x] D1 (aggregate ledger), D2 (pre-funded master float), and D3 (no merchant) recorded in section 2.

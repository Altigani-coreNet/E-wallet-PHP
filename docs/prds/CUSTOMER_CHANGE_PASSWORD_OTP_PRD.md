# PRD: Customer Change Password — OTP Two-Step Flow

> **Status:** Implemented  
> **Last updated:** 2026-07-02  
> **Related:** `docs/testing/change-password/`, `CustomerAuthService`, `CustomerActionOtpService`

---

## Problem / context

Authenticated customers can change their password via a single API call after supplying the current password. That updates credentials immediately with no step-up verification beyond knowledge of the current password. For a fintech wallet, sensitive account changes should require **OTP confirmation** on the customer's verified phone before the new password is applied.

Affected: **customers** (mobile/app clients), **support/ops** (fewer unauthorized password changes), **audit** (clear two-step trail).

---

## Goals & non-goals

### Goals

- Two-step flow: **request** (validate + store pending intent + send SMS OTP) → **confirm** (verify OTP + apply password).
- Never store plaintext new password in OTP payload; use HMAC fingerprint only.
- Invalidate prior unconsumed password-change OTP rows when a new request is issued.
- On success: update password hash, issue new JWT, send `PasswordChanged` notification + customer event log.
- Automated coverage: PHPUnit feature tests + Cypress E2E.

### Non-goals

- Merchant/user `POST /profile/change-password` (stays single-step).
- Email OTP channel for password change in v1 (SMS only).
- Customer React settings UI in v1 (API-only; clients consume new endpoints).
- Ledger / wallet balance changes (no money movement).

---

## Users & permissions

| Role | Capability | Auth |
|------|------------|------|
| Customer | Request password change OTP | Customer JWT (`customer.jwt`) |
| Customer | Confirm password change with OTP | Customer JWT |

---

## Functional requirements

| ID | Requirement |
|----|-------------|
| FR1 | `POST /api/v1/customer/password/change/request` — validate `current_password`, password rules, new ≠ current; issue OTP; return `{ otp_token, expires_at }` |
| FR2 | `POST /api/v1/customer/password/change/confirm` — validate same fields + `otp_token` + `otp`; verify OTP + payload fingerprint; update password |
| FR3 | Step 1 must **not** update `customers.password` |
| FR4 | Step 1 deletes prior unconsumed `password_change` OTP rows for same customer |
| FR5 | Wrong/expired OTP or payload mismatch → 422, password unchanged |
| FR6 | Success returns new `token` + `refresh_token` (same as legacy single-step) |
| FR7 | Send `PasswordChanged` notification and `password_changed` audit event **only after confirm** |
| FR8 | Remove legacy `POST /api/v1/customer/password/change` |

---

## Money & data rules (fintech)

- No ledger postings; wallet balance unchanged.
- OTP payload stores `password_fingerprint` (HMAC-SHA256), never plaintext password.
- OTP expiry: 10 minutes; single-use via `consumed_at`.
- Storage: `customer_action_otps` table with `purpose = password_change` (not `customers_otp`).

---

## Edge cases & failure handling

| Case | Expected |
|------|----------|
| Wrong current password at request | 400; no OTP row |
| New password same as current | 400 at request |
| Weak password | 422 validation at request |
| Wrong OTP at confirm | 422; password unchanged |
| Expired `otp_token` | 422; password unchanged |
| Different password on confirm vs request | 422 payload mismatch |
| Unauthenticated | 401 |
| Re-request before confirm | Previous OTP invalidated; new token issued |
| Confirm after password already changed elsewhere | Current password check fails at confirm |

---

## Success metrics

- 100% of password changes go through OTP confirm step.
- Zero plaintext passwords in `customer_action_otps.payload`.
- PHPUnit + Cypress green for happy path and failure cases.

---

## Assumptions & open questions

- SMS OTP uses mock code `111111` in non-production (same as wallet transfer OTP).
- Customer must have verified phone on file for SMS delivery.
- Legacy single-step endpoint removed (breaking change for API clients).

---

## Tasks

### Backend

- [x] BE: Create `customer_action_otps` migration — `database/migrations/` — table exists with purpose enum
- [x] BE: Add `CustomerActionOtp` model — `app/Models/CustomerActionOtp.php`
- [x] BE: Add `CustomerActionOtpService` — `app/Modules/CustomerAuth/Services/` — issue/verify for `password_change`
- [x] BE: Refactor `CustomerAuthService` — `requestPasswordChange` + `confirmPasswordChange`
- [x] BE: Form requests + controller methods + routes — replace `password/change`

### Frontend / clients

- [ ] FE: Customer settings UI two-step flow — future when customer profile app exists
- [x] QA: Cypress commands + E2E spec — `Payment/cypress/`

### QA

- [x] QA: PHPUnit feature tests — `tests/Feature/CustomerAuth/ChangePassword/`
- [x] QA: Test matrix + scenarios docs — `docs/testing/change-password/`
- [x] QA: Update Postman collection

**Dependency order:** migration → service → API → PHPUnit → Cypress

---

## Scenarios (Gherkin)

### Story 1 — Happy path

```gherkin
Story: As a customer, I want to change my password with OTP confirmation so that unauthorized changes are harder.

Scenario: Successful two-step password change
  Given I am logged in as an active customer with phone verified
  And my current password is "Password1!"
  When I POST password/change/request with current_password and new password "NewSecure1!"
  Then I receive otp_token and expires_at
  And my password hash is unchanged
  When I POST password/change/confirm with otp_token, otp, and the same password fields
  Then my password is updated
  And I receive a new JWT
  And I cannot log in with "Password1!"
  And I can log in with "NewSecure1!"
  And a PasswordChanged notification is sent
```

### Story 2 — Wrong current password

```gherkin
Scenario: Request rejected with wrong current password
  Given I am logged in as a customer
  When I POST password/change/request with wrong current_password
  Then I receive 400 with "Current password is incorrect"
  And no customer_action_otps row is created
```

### Story 3 — OTP failure (no side effects)

```gherkin
Scenario: Confirm rejected with wrong OTP
  Given I have a valid password change OTP request
  When I POST password/change/confirm with wrong otp
  Then I receive 422
  And my password hash is unchanged
  And the OTP row is not consumed
```

### Story 4 — Payload tamper

```gherkin
Scenario: Confirm rejected when new password differs from request
  Given I requested password change for "NewSecure1!"
  When I POST password/change/confirm with password "OtherPass1!"
  Then I receive 422
  And my password hash is unchanged
```

### Story 5 — Re-request invalidates prior OTP

```gherkin
Scenario: New request invalidates previous OTP
  Given I requested password change and received otp_token A
  When I request again and receive otp_token B
  And I confirm with otp_token A
  Then I receive 422
  And my password is unchanged
```

---

## Tests

| ID | Scenario | Type | Layer | Expected |
|----|----------|------|-------|----------|
| T1 | Happy two-step change | Feature | PHPUnit | 201 request + 200 confirm; old password fails login |
| T2 | Wrong current password at request | Feature | API | 400; no OTP row |
| T3 | Wrong OTP at confirm | Feature | API | 422; password unchanged |
| T4 | Expired otp_token | Feature | API | 422; password unchanged |
| T5 | Payload tamper | Feature | API | 422; password unchanged |
| T6 | Unauthenticated | Feature | API | 401 |
| T7 | Weak password validation | Feature | API | 422 on request |
| T8 | Same password as current | Feature | API | 400 on request |
| T9 | Notification + audit event | Feature | PHPUnit | PasswordChanged after confirm only |
| T10 | E2E two-step | E2E | Cypress | full flow with mock OTP 111111 |
| T11 | Idempotent re-request | Feature | API | new OTP invalidates previous unconsumed row |

See also: [`docs/testing/change-password/TEST_MATRIX.md`](../testing/change-password/TEST_MATRIX.md)

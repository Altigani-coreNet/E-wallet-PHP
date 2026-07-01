# PRD: Customer KYC — Rejection, Change Requests & Admin Audit Trail

> **Status:** Implemented (v1 complete)  
> **Last updated:** 2026-07-01  
> **Related:** `CUSTOMER_REJECTION_WORKFLOW_PLAN.md`, `Payment/cypress/e2e/kyc/`

---

## Problem / context

Self-registered customers complete their profile and enter `pending` status awaiting admin KYC review. Before this feature, admins could only toggle generic statuses with no structured rejection reason, field-level guidance, resubmission path, change-request review for active customers, or a readable audit trail of what happened.

Admins need to see **who did what, when, and on which fields** — not raw JSON — when reviewing a customer file.

---

## Goals & non-goals

### Goals

- Admin reject/approve with reason + flagged fields; customer notified (in-app + email).
- Customer fixes flagged fields and resubmits; active customers use change-request review for profile edits.
- Admin customer detail view: **Events** timeline + **Change Requests** tab with approve/reject and field diffs.
- **Customer audit log** on every meaningful lifecycle action (register, KYC submit, reject, resubmit, approve, change request, password).
- Human-readable event messages with field-level detail (e.g. “Name (Ahmed → Corrected Name)”).
- Semantic badge colors in Events UI (green = success, red = reject, amber = change request).
- Automated coverage: PHPUnit feature tests + Cypress KYC E2E suite.

### Non-goals

- Ledger postings on reject/approve (wallet balance unchanged).
- Merchant-portal customer management in v1.
- Backfilling audit logs for customers created before this release.

---

## Users & permissions

| Role | Capability | Permission / auth |
|------|------------|-------------------|
| Admin | Approve pending/rejected customers | `approve_customers` |
| Admin | Reject pending/requesting_updated customers | `reject_customers` |
| Admin | List customer event logs | `view_customers` |
| Admin | List / approve / reject customer change requests | `view_customers`, `approve_customers` |
| Customer | View rejection, fix fields, resubmit | Customer JWT |
| Customer | Request profile update when active | Customer JWT |
| Customer | Change / reset password | Customer JWT |

---

## Functional requirements

### Core KYC & rejection (FR1–FR12) — done

| ID | Requirement | Status |
|----|-------------|--------|
| FR1 | Statuses `rejected`, `requesting_updated` on Customer model and admin UI | Done |
| FR2 | `customer_rejections` table mirroring merchant rejections | Done |
| FR3 | `CustomerService::approve()` with audit log, notification, email | Done |
| FR4 | `CustomerService::reject()` with reason, invalid fields, missing attachments | Done |
| FR5 | Admin API `POST .../approve`, `POST .../reject` | Done |
| FR6 | Customer API rejected-fields + update-rejected-fields; profile includes rejection + pending flag | Done |
| FR7 | Change-request review for active customer profile updates | Done |
| FR8–FR9 | In-app notifications + rejection/approval emails | Done |
| FR10–FR12 | Wallet guards, password-setup guard, admin React UI | Done |

### Admin audit & change requests (FR13–FR22) — done

| ID | Requirement | Status |
|----|-------------|--------|
| FR13 | `GET /api/v2/admin/customers/{id}/logs` — paginated, searchable | Done |
| FR14 | `GET /api/v2/admin/customers/{id}/change-requests` — formatted summaries | Done |
| FR15 | `GET /api/v2/admin/customers/{id}/change-requests/{id}` — field diff detail | Done |
| FR16 | Approve/reject change request writes customer event log | Done |
| FR17 | Admin UI: **Events** tab — timeline (not table), search, pagination | Done |
| FR18 | Admin UI: **Change Requests** tab — list, expand diff, approve/reject | Done |
| FR19 | Pending change-request banner on customer detail when status = `requesting_updated` | Done |
| FR20 | `change_requests` / `change_histories` UUID morph columns (fresh migrate) | Done |
| FR21 | Detailed event messages via `CustomerEventMessageBuilder` | Done |
| FR22 | Event badge colors by action type (success / danger / warning / primary) | Done |

### Customer lifecycle logging (FR23–FR28) — done

| ID | Action | When logged | Example message |
|----|--------|-------------|-----------------|
| FR23 | `registered` | OTP signup | Registered account with phone number +249… |
| FR24 | `profile_completed` | First KYC profile submit | Submitted KYC profile for review: Name (…), Email (…), … |
| FR25 | `profile_resubmitted` | Customer fixes rejected fields | Customer corrected rejected fields…: Name (Old → New) |
| FR26 | `change_request_submitted` | Active customer profile update | Customer requested to update profile: Name (A → B), … |
| FR27 | `password_changed` | Authenticated password change | Ahmed changed account password. |
| FR28 | `password_reset` | OTP password reset | Password reset completed for +249… |

Existing moderation actions (`approved`, `rejected`, `change_request_approved`, `change_request_rejected`) use the same message builder for field-level detail.

---

## State machine

```
registered → profile_completed (customer)
pending → active                 (admin approve)
pending → rejected               (admin reject)
rejected → pending               (customer fix + resubmit → profile_resubmitted log)
active → requesting_updated      (customer profile change → change_request_submitted log)
requesting_updated → active        (admin approve change request → change_request_approved log)
requesting_updated → active        (admin reject change request → change_request_rejected log; fields unchanged)
```

---

## API map

### Admin (`/api/v2/admin/customers`)

| Method | Path | Notes |
|--------|------|-------|
| POST | `{id}/approve` | `status=active`, log + notification + email |
| POST | `{id}/reject` | Body: `rejection_reason`, `invalid_fields[]`, `missing_attachments[]` |
| GET | `{id}/logs` | Query: `page`, `per_page`, `search` — returns `label`, `text`, `time` |
| GET | `{id}/change-requests` | Formatted list (`ChangeRequestFormatter`) |
| GET | `{id}/change-requests/{cr}` | Field diff detail |
| POST | `{id}/change-requests/{cr}/approve` | Applies payload; logs `change_request_approved` |
| POST | `{id}/change-requests/{cr}/reject` | Reverts status; logs `change_request_rejected` |

### Customer (`/api/v1/customer`, JWT)

| Method | Path | Notes |
|--------|------|-------|
| POST | `auth/register` | Logs `registered` |
| POST | `profile/complete` | Logs `profile_completed` (first time) |
| POST | `profile/update-rejected-fields` | Logs `profile_resubmitted` |
| POST | `profile/update` | Rejected → direct update; Active → change request + `change_request_submitted` |
| POST | `password/change` | Logs `password_changed` |
| POST | `password/reset` | Logs `password_reset` |

---

## Admin UI

### Customer detail (`AdminCustomerView`)

| Tab | Component | Behaviour |
|-----|-----------|-----------|
| Overview | existing | Profile, status, wallet summary |
| Change Requests | `CustomerChangeRequestsTab` | Pending/approved/rejected rows; expand field diff; approve/reject |
| Events | `CustomerEventsTab` | Timeline via `EventsTimelineList`; search; pagination; semantic badges |

**Banner:** Shown when `status === requesting_updated` — links admin to Change Requests tab.

**i18n:** English + Arabic keys under `customers.eventsTab`, `customers.changeRequestsTab`.

---

## Event catalog (audit log)

Logs stored in polymorphic `logs` table (`loggable_type = Customer`). Ordered **newest first** in API and UI.

### Badge colors

| Tone | Actions |
|------|---------|
| Green (`success`) | `registered`, `approved`, `profile_completed`, `activated`, … |
| Red (`danger`) | `rejected`, `change_request_rejected`, `suspended`, … |
| Amber (`warning`) | `change_request_submitted`, `change_request_approved`, `password_reset`, … |
| Blue (`primary`) | `profile_resubmitted`, `updated` |
| Gray (`info`) | `viewed`, `logged_out`, … |

### Expected event sequences (E2E)

Defined in `Payment/cypress/support/kycFlowHelpers.js` as `KYC_EVENT_SEQUENCES` (newest first):

| Scenario | Sequence |
|----------|----------|
| KYC reject → resubmit → approve | `approved`, `profile_resubmitted`, `rejected`, `profile_completed`, `registered` |
| Active change request approved | `change_request_approved`, `change_request_submitted`, `approved`, `profile_completed`, `registered` |
| Reject only (no resubmit) | `rejected`, `profile_completed`, `registered` |

---

## Money & data rules

- Status-only transitions; **no ledger movement** on reject/approve/change request.
- Reject idempotency: already-rejected → 422.
- Approve only from `pending` or `rejected`.
- Only `active` customers may transfer/receive; `rejected` and `requesting_updated` blocked.
- Region scoping via `Customer::scopeWithCountry`.
- Change-request morph IDs are UUID-compatible (`uuidMorphs` on create migrations).

---

## Scenarios (user stories + Gherkin)

### US-1 — Admin rejects a KYC field

**As** an admin  
**I want** to reject specific profile fields with a reason  
**So that** the customer knows exactly what to fix  

```gherkin
Feature: Admin KYC rejection

  Scenario: Reject customer name field
    Given a customer has registered and completed their profile
    And the customer status is "pending"
    When the admin rejects the customer with invalid field "name" and a reason
    Then the customer status is "rejected"
    And a customer event "rejected" is logged with incorrect fields and reason in the message
    And the customer receives an in-app notification and rejection email
```

### US-2 — Customer resubmits after rejection

```gherkin
  Scenario: Customer fixes rejected name and resubmits
    Given a customer is "rejected" for field "name"
    When the customer submits corrected name via update-rejected-fields
    Then the customer status is "pending"
    And a customer event "profile_resubmitted" is logged with "Name (old → new)" in the message
```

### US-3 — Admin approves KYC

```gherkin
  Scenario: Admin approves pending customer
    Given a customer is "pending" after resubmit
    When the admin approves the customer
    Then the customer status is "active"
    And a customer event "approved" is logged
    And the customer can use wallet transfer endpoints
```

### US-4 — Active customer change request

```gherkin
  Scenario: Active customer requests profile update
    Given a customer is "active"
    When the customer updates profile fields via profile/update
    Then the customer status is "requesting_updated"
    And a pending change request exists
    And a customer event "change_request_submitted" describes the requested field changes

  Scenario: Admin approves change request
    Given a pending change request for an active customer
    When the admin approves the change request
    Then the customer status is "active"
    And profile fields reflect the requested values
    And a customer event "change_request_approved" describes applied changes
```

### US-5 — Admin views audit trail

```gherkin
  Scenario: Admin views customer events timeline
    Given a customer has completed the full KYC reject-resubmit-approve flow
    When the admin opens the customer Events tab
    Then events appear in a timeline newest-first
    And each event shows a human-readable message without raw JSON metadata
    And badge colors reflect event type (green approve, red reject, amber change request)
```

### US-6 — Rejected customer cannot transfer

```gherkin
  Scenario: Wallet blocked for rejected customer
    Given a customer is "rejected"
    When the customer attempts a wallet transfer
    Then the request is rejected with an authorization error
```

---

## Test coverage

### PHPUnit (`Fast_Pay_Soft_Pos/tests`)

| Test file | What it covers |
|-----------|----------------|
| `AdminCustomerRejectionTest.php` | Admin reject/approve API, validation, permissions |
| `CustomerProfileRejectionTest.php` | Rejected-fields API, resubmit, active change-request creation |
| `AdminCustomerLogsAndChangeRequestsTest.php` | Logs endpoint, formatted change requests, approve writes log |
| `CustomerAuthApiTest.php` | Register + profile complete write `registered` / `profile_completed` logs |
| `CustomerNotificationWorkflowE2eTest.php` | End-to-end notification flows |
| `CustomerSystemNotificationTest.php` | In-app notification types |
| `AdminCustomerApiIntegrationTest.php` | Registration lifecycle, login guards by status |
| `Unit/CustomerEventMessageBuilderTest.php` | Message text for reject, change request, password |

**Run (examples):**

```bash
cd Fast_Pay_Soft_Pos
php artisan test --filter=AdminCustomerRejectionTest
php artisan test --filter=CustomerProfileRejectionTest
php artisan test --filter=AdminCustomerLogsAndChangeRequestsTest
php artisan test --filter=CustomerEventMessageBuilderTest
```

### Cypress KYC E2E (`Payment/cypress/e2e/kyc/`)

**Run full suite:**

```bash
cd Payment
npm run cy:run:kyc
```

| Spec | Scenario |
|------|----------|
| `01-reject-name.cy.js` | Reject name → resubmit → approve; active change request (name) |
| `02-reject-email.cy.js` | Same pattern for email |
| `03-reject-phone.cy.js` | Phone |
| `04-reject-national-id.cy.js` | National ID |
| `05-reject-birth-date.cy.js` | Birth date |
| `06-reject-gender.cy.js` | Gender |
| `07-reject-country.cy.js` | Country |
| `08-reject-city.cy.js` | City |
| `09-reject-profile-image.cy.js` | Profile photo attachment |
| `10-reject-passport-document.cy.js` | Passport attachment |
| `11-reject-all-fields-and-attachments.cy.js` | All fields + attachments |
| `20-active-customer-change-request.cy.js` | Combined field change request; wallet block when rejected |
| `21-customer-admin-events.cy.js` | Events API + Events tab UI; change-request events + Change Requests tab UI |

**Shared commands** (`Payment/cypress/support/commands.js`):

- `kycRegisterAndCompleteProfile`, `kycTestFieldRejection`, `kycTestActiveCustomerChangeRequest`
- `assertAdminCustomerEvents`, `adminVerifyCustomerEventsTab`
- `assertAdminCustomerChangeRequests`, `adminVerifyCustomerChangeRequestsTab`
- `adminApproveChangeRequestViaUi` (customer Change Requests tab)

**Prerequisites:** Laravel on `apiUrl`, Payment dev server, `OTP_MOCK_CODE=111111`, admin credentials in Cypress env, migrations applied (`customer_rejections`, UUID `change_requests` / `change_histories`).

---

## Key implementation files

| Area | Path |
|------|------|
| Event messages | `app/Support/CustomerEventMessageBuilder.php` |
| Customer moderation | `app/Services/CustomerService.php` |
| Change requests | `app/Services/ChangeRequestService.php`, `ChangeRequestFormatter.php` |
| Customer auth / profile | `app/Modules/CustomerAuth/Services/CustomerAuthService.php`, `CustomerProfileService.php` |
| Admin API | `app/Http/Controllers/Api/Cashier/AdminCustomerApiController.php` |
| Log model (label, text) | `app/Models/Log.php` |
| Migrations | `2025_09_08_000000_create_change_requests_table.php`, `2025_09_08_000001_create_change_histories_table.php`, `2026_07_01_000001_create_customer_rejections_table.php` |
| Admin UI | `Payment/src/components/admin/customers/AdminCustomerView.jsx`, `CustomerEventsTab.jsx`, `CustomerChangeRequestsTab.jsx`, `CustomerRejectModal.jsx` |
| Timeline UI | `Payment/src/components/common/EventsTimelineList.jsx`, `utils/eventLabelUtils.js` |
| Cypress helpers | `Payment/cypress/support/kycFlowHelpers.js` |

---

## Notification matrix

| Event | Type | Channel |
|-------|------|---------|
| Profile completed | `profile_completed` | In-app |
| Admin reject | `profile_rejected` | In-app + email |
| Admin approve | `profile_approved` | In-app + email |
| Change request approved | `profile_change_request_approved` | In-app |
| Change request rejected | `profile_change_request_rejected` | In-app |
| Password changed | `password_changed` | In-app |

---

## Success metrics

- Resubmit rate within 7 days of rejection.
- Median time pending → approved after resubmit.
- Zero wallet operations for non-`active` customers.
- Admin can resolve 100% of KYC cases without leaving customer detail (Events + Change Requests tabs).
- Cypress KYC suite green on CI / pre-release.

---

## Assumptions & open questions

- **Assumption:** Event log retention follows general `logs` table policy (no separate archival in v1).
- **Assumption:** Existing customers before deploy will not have `registered` / `profile_completed` logs unless they repeat those flows.
- **Postman:** Import `Customer_Auth_API_Postman_Collection.json` and use the **KYC** folder (`Customer Flow` + `Admin Review` subfolders). Collection vars: `customer_id`, `change_request_id`, `customer_national_id`.
- **Open:** Export customer audit log to PDF/CSV for compliance (future).
- **Open:** Merchant-portal read-only view of linked customer KYC status (future).

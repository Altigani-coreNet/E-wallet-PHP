# Customer Rejection — Workflow Plan

> Status: **Implemented** (see `CUSTOMER_REJECTION_WORKFLOW_PRD.md` for full PRD, scenarios, and test matrix)  
> Mirror of merchant rejection/change-request flow for customer KYC.

## State machine

```
pending → active        (admin approve)
pending → rejected      (admin reject)
rejected → pending      (customer fix + resubmit)
active → requesting_updated   (customer profile change request)
requesting_updated → active   (admin approve change request)
requesting_updated → active   (admin reject change request — revert)
```

## API map

### Admin (`/api/v2/admin/customers`)

| Method | Path | Body | Result |
|--------|------|------|--------|
| POST | `{id}/approve` | — | `status=active`, notification + email |
| POST | `{id}/reject` | `rejection_reason`, `invalid_fields[]`, `missing_attachments[]` | `status=rejected`, rejection row |
| GET | `{id}/logs` | `page`, `per_page`, `search?` | Paginated event audit trail |
| GET | `{id}/change-requests` | — | Formatted change-request list |
| POST | `{id}/change-requests/{cr}/approve` | `moderation_note?` | Apply payload, `status=active` |
| POST | `{id}/change-requests/{cr}/reject` | `moderation_note?` | Revert status, notify customer |

Generic `POST {id}/status` cannot set `rejected` — use dedicated reject endpoint.

### Customer (`/api/v1/customer`, JWT)

| Method | Path | Notes |
|--------|------|-------|
| GET | `profile` | Includes `rejection`, `has_pending_change` |
| GET | `profile/rejected-fields` | When `status=rejected` |
| POST | `profile/update-rejected-fields` | Fixes flagged fields → `pending` |
| POST | `profile/update` | Rejected: direct update → `pending`; Active: change request → `requesting_updated` |

## Notification matrix

| Event | Type | Channel |
|-------|------|---------|
| Profile completed (existing) | `profile_completed` | In-app |
| Admin reject | `profile_rejected` | In-app + email |
| Admin approve | `profile_approved` | In-app + email |
| Change request approved | `profile_change_request_approved` | In-app |
| Change request rejected | `profile_change_request_rejected` | In-app |

## Rejectable fields (v1)

`name`, `email`, `phone`, `national_id`, `birth_date`, `gender`, `country`, `city`, attachment `profile_image`.

## Wallet rules

Only `active` customers may transfer/receive. `rejected` and `requesting_updated` blocked via `walletLoginBlockReason()`.

## Key files

- Model: `app/Models/CustomerRejection.php`
- Service: `app/Services/CustomerService.php` (`approve`, `reject`)
- Customer profile: `app/Modules/CustomerAuth/Services/CustomerProfileService.php`
- Admin API: `app/Http/Controllers/Api/Cashier/AdminCustomerApiController.php`
- Admin UI: `Payment/src/components/admin/customers/CustomerRejectModal.jsx`, `AdminCustomerView.jsx`, `CustomerEventsTab.jsx`, `CustomerChangeRequestsTab.jsx`
- Event messages: `app/Support/CustomerEventMessageBuilder.php`
- Tests (PHPUnit): `AdminCustomerRejectionTest.php`, `CustomerProfileRejectionTest.php`, `AdminCustomerLogsAndChangeRequestsTest.php`, `CustomerEventMessageBuilderTest.php`
- Tests (Cypress): `Payment/cypress/e2e/kyc/*.cy.js` — run `npm run cy:run:kyc`

## Test checklist

See **Test coverage** section in `CUSTOMER_REJECTION_WORKFLOW_PRD.md` for the full PHPUnit + Cypress matrix, Gherkin scenarios, and expected event sequences.

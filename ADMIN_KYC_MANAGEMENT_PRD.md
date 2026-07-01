# PRD: Admin KYC Management Hub & Notifications

> **Status:** Implemented (v1)  
> **Companion:** [CUSTOMER_REJECTION_WORKFLOW_PRD.md](CUSTOMER_REJECTION_WORKFLOW_PRD.md)

## Summary

Admin-facing KYC operations hub with sidebar navigation (Pending Customers, Change Requests, Change History), WebSocket queue badges, and configurable Laravel notifications (database + broadcast) to the primary admin.

## Key endpoints

| Method | Path |
|--------|------|
| GET | `/api/v2/admin/customers/kyc/pending` |
| GET | `/api/v2/admin/customers/kyc/events` |
| GET | `/api/v2/admin/customers/kyc/events/{logId}` |
| GET | `/api/v2/admin/customers/kyc/queue-counts` |
| GET | `/api/v2/admin/change-requests?type=customer` |
| GET | `/api/v2/admin/inbox/notifications` (AuthService) |

## Admin UI routes

- `/admin/kyc/pending`
- `/admin/kyc/change-requests`
- `/admin/kyc/change-requests/:id`
- `/admin/kyc/change-history`
- `/admin/kyc/change-history/:logId`

## Notifications

Primary admin (`created_at ASC`, active) receives KYC notifications on:

- `customer_profile_completed`
- `customer_profile_resubmitted`
- `customer_change_request_submitted`

Channels: `database` + `broadcast` (configurable via `ADMIN_KYC_NOTIFICATION_CHANNELS`).

WebSocket channels: `admin-notifications.{adminId}`, `admin-kyc-queue`.

## Tests

```bash
cd Fast_Pay_Soft_Pos && php artisan test --filter=AdminKyc
cd AuthenticationService && npm run test:e2e -- communication.e2e-spec
cd Payment && npm run cy:run:kyc  # includes 30-admin-kyc-hub.cy.js
```

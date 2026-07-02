# Test Matrix: Customer Change Password OTP

> Maps to PRD: [`docs/prds/CUSTOMER_CHANGE_PASSWORD_OTP_PRD.md`](../../prds/CUSTOMER_CHANGE_PASSWORD_OTP_PRD.md)  
> Scenarios: [`SCENARIOS.md`](./SCENARIOS.md)

| ID | Scenario | Type | Layer | Test file | Expected |
|----|----------|------|-------|-----------|----------|
| T1 | Happy two-step change | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_can_change_password_with_otp` | 201 request + 200 confirm; old password login 401 |
| T2 | Wrong current password at request | Feature | PHPUnit | `ChangePasswordOtpRequestTest::test_request_fails_with_wrong_current_password` | 400; no OTP row |
| T3 | Wrong OTP at confirm | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_confirm_fails_with_wrong_otp` | 422; password unchanged |
| T4 | Expired otp_token | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_confirm_fails_with_expired_token` | 422; password unchanged |
| T5 | Payload tamper | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_confirm_fails_when_password_differs` | 422; password unchanged |
| T6 | Unauthenticated request | Feature | PHPUnit | `ChangePasswordOtpRequestTest::test_request_requires_authentication` | 401 |
| T6b | Unauthenticated confirm | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_confirm_requires_authentication` | 401 |
| T7 | Weak password validation | Feature | PHPUnit | `ChangePasswordOtpRequestTest::test_request_validation_requires_strong_password` | 422 |
| T8 | Same password as current | Feature | PHPUnit | `ChangePasswordOtpRequestTest::test_request_fails_when_new_equals_current` | 400 |
| T9 | Notification after confirm | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_sends_notification_on_success` | PasswordChanged notification |
| T10 | E2E two-step | E2E | Cypress | `change-password/customer-change-password-otp.cy.js` | Full API flow |
| T11 | Re-request invalidates prior OTP | Feature | PHPUnit | `ChangePasswordOtpConfirmTest::test_new_request_invalidates_previous_otp` | First token fails confirm |

## Mock OTP

Non-production uses `config('services.otp.mock_code')` default **111111**.

## API endpoints

| Step | Method | Path |
|------|--------|------|
| Request | POST | `/api/v1/customer/password/change/request` |
| Confirm | POST | `/api/v1/customer/password/change/confirm` |

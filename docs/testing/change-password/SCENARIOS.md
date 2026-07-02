# Gherkin Scenarios: Customer Change Password OTP

Canonical acceptance criteria for QA. See [`TEST_MATRIX.md`](./TEST_MATRIX.md) for test ID mapping.

---

## Story: Two-step password change with OTP

```gherkin
Story: As a customer, I want to change my password with OTP confirmation so that unauthorized changes are harder.

Scenario: Successful two-step password change
  Given I am logged in as an active customer with phone verified
  And my current password is "Password1!"
  When I POST /api/v1/customer/password/change/request with:
    | current_password        | Password1!  |
    | password                | NewSecure1! |
    | password_confirmation   | NewSecure1! |
  Then the response status is 201
  And the response contains otp_token and expires_at
  And my stored password hash still validates "Password1!"
  When I POST /api/v1/customer/password/change/confirm with:
    | otp_token               | <from request> |
    | otp                     | 111111         |
    | current_password        | Password1!     |
    | password                | NewSecure1!    |
    | password_confirmation   | NewSecure1!    |
  Then the response status is 200
  And the response contains token and refresh_token
  And login with "Password1!" fails
  And login with "NewSecure1!" succeeds
  And a PasswordChanged notification was sent
  And a customer event "password_changed" was logged

Scenario: Request rejected with wrong current password
  Given I am logged in as a customer
  When I POST password/change/request with wrong current_password
  Then the response status is 400
  And the message contains "Current password is incorrect"
  And no row exists in customer_action_otps for this customer

Scenario: Request rejected when new password equals current
  Given I am logged in with password "Password1!"
  When I POST password/change/request with new password "Password1!"
  Then the response status is 400
  And the message indicates new password must differ

Scenario: Confirm rejected with wrong OTP
  Given I have completed a successful password change request
  When I POST password/change/confirm with otp 999999
  Then the response status is 422
  And my password hash is unchanged
  And the OTP row consumed_at is null

Scenario: Confirm rejected with expired otp_token
  Given I have a password change OTP that expired
  When I POST password/change/confirm with that otp_token
  Then the response status is 422
  And my password hash is unchanged

Scenario: Confirm rejected when password differs from request
  Given I requested password change for "NewSecure1!"
  When I POST password/change/confirm with password "OtherPass1!"
  Then the response status is 422
  And my password hash is unchanged

Scenario: Unauthenticated access denied
  When I POST password/change/request without Authorization
  Then the response status is 401
  When I POST password/change/confirm without Authorization
  Then the response status is 401

Scenario: New request invalidates previous OTP
  Given I requested password change and received otp_token A
  When I request again and receive otp_token B
  And I confirm with otp_token A and valid otp
  Then the response status is 422
  And my password hash is unchanged
```

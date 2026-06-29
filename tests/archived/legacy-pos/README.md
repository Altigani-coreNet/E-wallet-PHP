# Legacy Soft-POS tests (archived)

These tests cover the original Soft-POS stack (terminals, user groups, merchant/admin
dashboards, merchant API auth). They are **not deleted** — only moved out of the
default test run while e-wallet is the active focus.

Run opt-in when POS work resumes:

```powershell
php artisan test tests/archived/legacy-pos
```

Files:

- `UserGroupTest.php` — terminal user groups
- `PosTransactionTest.php` — POS transaction API
- `AdminDashboardTest.php` — admin dashboard views/stats
- `ApiAuthTest.php` — merchant user register/login (Passport)
- `MerchantDashboardTest.php` — merchant dashboard

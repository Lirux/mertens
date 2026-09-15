---
paths:
  - '{app/Models/User.php,app/Http/Controllers/Settings/**,app/Http/Requests/Settings/**,config/fortify.php,database/factories/UserFactory.php,tests/Feature/Auth/**,tests/Feature/Settings/**}'
---

# Settings

## Password authentication without two-factor authentication
The transfer-project prototype intentionally does not implement 2FA. Keep Fortify two-factor authentication disabled and do not add challenge screens or two-factor factory states. Authentication and security tests should exercise password login, email verification and password updates without skipping for 2FA.

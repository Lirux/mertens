---
paths:
  - '{app/Models/User.php,app/Policies/AssetPolicy.php,app/Http/Middleware/HandleInertiaRequests.php,database/seeders/**,resources/js/pages/assets/**,resources/js/pages/dashboard.tsx}'
---

# Pages

## Two web roles and reproducible demo accounts
Verified employees may read assets; only asset_manager users may create, edit, record maintenance or delete. New registrations default to employee; never accept role through registration/profile mass assignment. Derive UI write capabilities from server policies. Demo seeding creates verified test@example.com (manager) and mitarbeiter@example.com (employee); keep API bearer-token abilities independent of web roles.

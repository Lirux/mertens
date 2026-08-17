---
paths:
  - '{app/Http/**,app/Models/PersonalAccessToken.php,routes/api.php,config/sanctum.php}'
---

# Models

## Asset API uses bearer-only Sanctum
Protect `/api/v1/assets` with Sanctum bearer tokens. GET operations require `assets:read`; POST, PUT, and DELETE require `assets:write`. Sanctum tokens persist through the MongoDB-backed `App\Models\PersonalAccessToken`; do not enable Sanctum SPA-cookie routes or store plaintext tokens.

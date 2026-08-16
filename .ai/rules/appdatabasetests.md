---
paths:
  - '{app,database,tests}/**/*Asset*.php'
---

# Appdatabasetests

## Asset persistence uses native MongoDB values
Treat Asset IDs as opaque strings backed by MongoDB ObjectId; do not introduce UUID storage. Use status `inactive` (not `out_of_service`). Store `acquisition_value` through the model's `decimal:2` cast so MongoDB receives Decimal128, and validate `currency` as a three-letter uppercase ISO 4217 code.

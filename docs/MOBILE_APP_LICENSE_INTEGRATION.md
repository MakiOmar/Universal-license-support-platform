# Mobile App License Integration

How an iOS / Android (or Flutter / React Native) app **registers (activates)** and **validates** a ULSP license.

Base URL: `{API_BASE}/api/v1`  
Example: `https://your-domain.com/api/v1`

All license integration endpoints require an API key:

```http
X-API-Key: {your_product_api_key}
Accept: application/json
Content-Type: application/json
```

Create the API key in Filament (**API Keys**) or use the demo key after seeding: `ulsp_demo_api_key_123456` (local only).

---

## Concepts

| Term | Meaning for mobile |
|---|---|
| **License key** | Purchased key (e.g. `DEMO-AO1V-FVCP-W6WR-VOLI`) entered by the user |
| **Activate / register** | Bind this device to the license (`POST /licenses/activate`) |
| **Validate** | Check the license is still active for this device (`POST /licenses/validate`) |
| **Deactivate** | Unbind this device (`POST /licenses/deactivate`) |
| **activation_type** | Use `device_id` for mobile apps |
| **activation_value** | Stable device identifier (see below) |
| **activation_hash** | Server-generated id for this binding — store it after activate for deactivate |

### Device ID recommendations

Use a **stable per-install or per-device** id and send it as `activation_value`:

- **Android:** `Settings.Secure.ANDROID_ID` or an app-scoped UUID persisted in secure storage
- **iOS:** identifierForVendor (`UIDevice.identifierForVendor`) or a Keychain UUID
- Prefer an app-generated UUID in secure storage if privacy / reset policies require it

Do **not** put PII (email, phone) in `activation_value`.

---

## Recommended app flow

```
┌─────────────┐     activate      ┌─────────────┐
│ User enters │ ───────────────►  │ ULSP API    │
│ license key │                   │             │
└─────────────┘                   └──────┬──────┘
                                         │ activation_hash
                                         ▼
                                  ┌─────────────┐
                                  │ Save locally│
                                  │ key + hash  │
                                  └──────┬──────┘
                                         │
                    on launch / periodically
                                         ▼
                                  POST /licenses/validate
                                  (license_key + device_id)
                                         │
                     ┌───────────────────┴───────────────────┐
                     ▼                                       ▼
              valid + activation_valid              invalid / expired
              → unlock features                     → lock / re-activate UI
```

1. User pastes license key.
2. App calls **activate** with `device_id` + device value.
3. Persist `license_key` and `activation_hash` (and optionally `activation_value`) in secure storage.
4. On each cold start (and optionally every N hours), call **validate**.
5. If `valid` is false or `activation_valid` is false → treat as unlicensed.
6. On logout / “remove license”, call **deactivate** with stored `activation_hash`.

---

## 1. Register / activate device

`POST /api/v1/licenses/activate`

### Request

```http
POST /api/v1/licenses/activate
X-API-Key: ulsp_demo_api_key_123456
Content-Type: application/json

{
  "license_key": "DEMO-AO1V-FVCP-W6WR-VOLI",
  "activation_type": "device_id",
  "activation_value": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

### Success (200 / 201)

Response is wrapped as a Laravel resource (`data`):

```json
{
  "data": {
    "id": 1,
    "activation_type": "device_id",
    "activation_value": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "activation_hash": "f1e2d3c4b5a697887766554433221100ffeeddccbbaa99887766554433221100",
    "status": "active",
    "activated_at": "2026-08-02T12:00:00.000000Z",
    "last_check_at": "2026-08-02T12:00:00.000000Z"
  }
}
```

**Store** `data.activation_hash` — required later for deactivate.

Re-activating the same device is idempotent: an already-active binding is refreshed (`last_check_at` updated).

### Failure

| Situation | Typical response |
|---|---|
| Missing / bad API key | `401` `{ "message": "API key required." }` or invalid key |
| Invalid / expired / suspended license | `422` validation error on `license_key` |
| Max activations reached | `422` (activation limit exceeded) |

---

## 2. Validate license (heartbeat)

`POST /api/v1/licenses/validate`

Call on app launch and before premium features.

### Request

```http
POST /api/v1/licenses/validate
X-API-Key: ulsp_demo_api_key_123456
Content-Type: application/json

{
  "license_key": "DEMO-AO1V-FVCP-W6WR-VOLI",
  "activation_type": "device_id",
  "activation_value": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

`activation_type` / `activation_value` are optional but **should be sent on mobile** so the API can confirm this device is registered (`activation_valid`).

### Success (200) — licensed

```json
{
  "valid": true,
  "reason": null,
  "activation_valid": true,
  "activations_used": 1,
  "max_activations": 3,
  "expires_at": "2027-08-02T12:00:00.000000Z",
  "license": { }
}
```

Treat as OK only when:

- `valid === true`, and
- `activation_valid === true` (when you sent device fields)

### Success (200) — not licensed

```json
{
  "valid": false,
  "reason": "license_expired"
}
```

Possible `reason` values:

| reason | Meaning |
|---|---|
| `license_not_found` | Unknown key |
| `license_suspended` | Admin suspended |
| `license_cancelled` | Cancelled / refunded |
| `license_expired` | Past `expires_at` |

If `valid` is true but `activation_valid` is false, this device is not registered — call **activate** again (or show “device not registered”).

---

## 3. Deactivate / unregister device

`POST /api/v1/licenses/deactivate`

### Request

```http
POST /api/v1/licenses/deactivate
X-API-Key: ulsp_demo_api_key_123456
Content-Type: application/json

{
  "license_key": "DEMO-AO1V-FVCP-W6WR-VOLI",
  "activation_hash": "f1e2d3c4b5a697887766554433221100ffeeddccbbaa99887766554433221100"
}
```

### Success

```json
{ "message": "Activation deactivated." }
```

Then clear local secure storage.

---

## 4. Optional: list activations

`GET /api/v1/licenses/by-key/{license_key}/activations`

```http
GET /api/v1/licenses/by-key/DEMO-AO1V-FVCP-W6WR-VOLI/activations
X-API-Key: ulsp_demo_api_key_123456
```

Useful for support / debug screens (shows bound devices).

---

## cURL examples

```bash
API=https://your-domain.com/api/v1
KEY=ulsp_demo_api_key_123456
LICENSE=DEMO-AO1V-FVCP-W6WR-VOLI
DEVICE=a1b2c3d4-e5f6-7890-abcd-ef1234567890

# Activate
curl -s -X POST "$API/licenses/activate" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $KEY" \
  -d "{\"license_key\":\"$LICENSE\",\"activation_type\":\"device_id\",\"activation_value\":\"$DEVICE\"}"

# Validate
curl -s -X POST "$API/licenses/validate" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $KEY" \
  -d "{\"license_key\":\"$LICENSE\",\"activation_type\":\"device_id\",\"activation_value\":\"$DEVICE\"}"
```

---

## Pseudocode (mobile client)

```text
function registerLicense(licenseKey):
  deviceId = getOrCreateStableDeviceId()
  response = POST /licenses/activate {
    license_key: licenseKey,
    activation_type: "device_id",
    activation_value: deviceId
  }
  secureStore.save("license_key", licenseKey)
  secureStore.save("activation_hash", response.data.activation_hash)
  secureStore.save("device_id", deviceId)
  unlockApp()

function checkLicenseOnLaunch():
  licenseKey = secureStore.get("license_key")
  deviceId = secureStore.get("device_id")
  if missing: showActivateScreen(); return

  result = POST /licenses/validate {
    license_key: licenseKey,
    activation_type: "device_id",
    activation_value: deviceId
  }

  if result.valid and result.activation_valid:
    unlockApp()
  else:
    lockApp()
    showActivateScreen(reason: result.reason)
```

---

## Security notes

- Keep `X-API-Key` in app config / remote config — treat it as a **product** credential, not a user secret. Prefer certificate pinning and obfuscation; rotate keys in Filament if leaked.
- Store license key + `activation_hash` in **Keychain / EncryptedSharedPreferences**, not plain SharedPreferences / UserDefaults.
- Always use HTTPS.
- Do not rely on client-only checks; always re-validate against the API for paid features when online.
- Offline policy is app-specific (e.g. grace period using last successful validate timestamp).

---

## Related docs

- OpenAPI: [`docs/openapi/openapi.yaml`](openapi/openapi.yaml)
- Platform README: [`../README.md`](../README.md)

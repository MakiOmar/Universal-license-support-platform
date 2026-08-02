# Laravel + Nuxt + Vue CORS Playbook

Abstract, repeatable steps to avoid CORS issues for any stack with:
- Laravel backend (API + Sanctum)
- Nuxt site-front
- Vue admin dashboard (Vite/SPA)

Use this as a checklist for new projects and environments.

## Overview

CORS allows web applications running on one domain (e.g., `http://localhost:3000`) to make requests to a server running on a different domain (e.g., `http://localhost:8000`). This is essential for frontend-backend communication during development.

## Current Setup

This Laravel application uses Laravel's built-in CORS handling. The configuration allows requests from:
- `http://localhost:3000` (Frontend development server)
- `http://localhost:8000` (Laravel backend server)
- `127.0.0.1:3000` and `127.0.0.1:8000` (Alternative localhost formats)
- `::1` (IPv6 localhost)

## Configuration Methods

### Method 1: Using Environment Variables (Recommended for Laravel 11+)

Laravel 11+ handles CORS automatically, but you can configure it using environment variables in your `.env` file:

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1
FRONTEND_URL=http://localhost:3000
```

The `SANCTUM_STATEFUL_DOMAINS` variable is already configured in `config/sanctum.php` and includes localhost variations.

### Method 2: Publishing CORS Configuration File

If you need more granular control, you can publish the CORS configuration file:

```bash
php artisan vendor:publish --tag="cors"
```

This creates a `config/cors.php` file. Edit it to configure CORS settings:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173', // Vite default port
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

**Important Configuration Notes:**

- **`paths`**: Specifies which routes CORS applies to. `api/*` covers all API routes, and `sanctum/csrf-cookie` is needed for Laravel Sanctum authentication.
- **`allowed_origins`**: List of frontend URLs that can make requests. Add your frontend development URL here.
- **`allowed_methods`**: HTTP methods allowed (GET, POST, PUT, DELETE, etc.). `['*']` allows all methods.
- **`allowed_headers`**: Headers that can be sent with requests. `['*']` allows all headers.
- **`supports_credentials`**: Set to `true` to allow cookies and authentication headers. When `true`, you cannot use `'*'` in `allowed_origins` - you must specify exact origins.

### Method 3: Middleware Configuration

In Laravel 11+, CORS middleware is automatically applied. If you need to customize it, you can modify `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    // CORS is handled automatically, but you can add custom middleware here
    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})
```

## Verification

To verify CORS is working correctly:

1. **Check Browser Console**: Open your frontend application and check the browser's developer console for CORS errors.

2. **Test API Call**: Make a test request from your frontend:
   ```javascript
   fetch('http://localhost:8000/api/test')
     .then(response => response.json())
     .then(data => console.log(data))
     .catch(error => console.error('CORS Error:', error));
   ```

3. **Check Response Headers**: In the browser's Network tab, verify that the response includes CORS headers:
   - `Access-Control-Allow-Origin: http://localhost:3000`
   - `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
   - `Access-Control-Allow-Headers: Content-Type, Authorization, Accept`

## Troubleshooting

### Common Issues

1. **CORS Error: "No 'Access-Control-Allow-Origin' header"**
   - Ensure your frontend URL is listed in `allowed_origins`
   - Clear config cache: `php artisan config:clear`
   - Restart your Laravel server

2. **Preflight OPTIONS Request Failing**
   - Verify `allowed_methods` includes the method you're using
   - Check that `allowed_headers` includes any custom headers you're sending

3. **Credentials Not Working**
   - Ensure `supports_credentials` is set to `true`
   - Make sure you're not using `'*'` in `allowed_origins` when `supports_credentials` is `true`
   - In your frontend, include `credentials: 'include'` in fetch requests

4. **Configuration Not Applying**
   - Clear config cache: `php artisan config:clear`
   - Clear route cache: `php artisan route:clear`
   - Restart the Laravel development server

### Testing CORS with cURL

You can test CORS configuration using cURL:

```bash
# Test preflight request
curl -X OPTIONS http://localhost:8000/api/test \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -v

# Test actual request
curl -X GET http://localhost:8000/api/test \
  -H "Origin: http://localhost:3000" \
  -v
```

Look for `Access-Control-Allow-Origin` in the response headers.

## Production Considerations

For production environments:

1. **Restrict Origins**: Only allow your production frontend domain:
   ```php
   'allowed_origins' => [
       'https://yourdomain.com',
   ],
   ```

2. **Use Environment Variables**: Configure CORS via `.env`:
   ```env
   FRONTEND_URL=https://yourdomain.com
   ```

3. **Set Max Age**: Enable caching of preflight requests:
   ```php
   'max_age' => 86400, // 24 hours
   ```

4. **Review Security**: Ensure `supports_credentials` is only `true` if necessary, and never use wildcards in `allowed_origins` when credentials are enabled.

## Additional Resources

- [Laravel CORS Documentation](https://laravel.com/docs/cors)
- [MDN CORS Guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)

---

# Frontend (Nuxt & Vue) CORS Checklist

Follow these steps in every project that uses Laravel as API + Nuxt (site) + Vue (admin).

## 1) Align API base URLs
- Nuxt: set `runtimeConfig.public.apiBase` to your Laravel origin (dev: `http://localhost:8000`).
- Vue/Vite admin: set `VITE_API_BASE_URL` to the same origin.
- Always call the API with that base URL (no mixed ports).

## 2) Match credentials settings
- In Laravel CORS, keep `supports_credentials = true` and list explicit origins (no `*`).
- In frontends:
  - Fetch/`useFetch`: `credentials: 'include'`.
  - Axios: `withCredentials: true`.
- Ensure cookies are on the same scheme/host/port list as `SANCTUM_STATEFUL_DOMAINS`.

## 3) Allowed origins (dev and prod)
- Dev: `http://localhost:3000` (Nuxt), `http://localhost:5173` (Vite Vue), plus `http://localhost:8000` for direct calls.
- Prod: set `FRONTEND_URL` (Nuxt site) and `ADMIN_URL` (Vue admin) domains explicitly in Laravel CORS.

## 4) Preflight sanity
- Methods: allow `GET, POST, PUT, PATCH, DELETE, OPTIONS`.
- Headers: allow `Content-Type, Authorization, X-Requested-With, Accept`.
- If sending custom headers, add them to `allowed_headers`.

## 5) Dev proxy option (optional but helpful)
- If you prefer same-origin during dev:
  - Nuxt `vite.server.proxy`: proxy `/api` to `http://localhost:8000`.
  - Vue/Vite `server.proxy`: proxy `/api` to `http://localhost:8000`.
- Keep API calls relative (e.g., `/api/...`) when using proxy.

## 6) Cache and restart
- After CORS changes in Laravel: `php artisan config:clear` and restart the server.
- For Nuxt/Vite: restart the dev servers so proxy/runtime config picks up changes.

## 7) Quick smoke test
- From Nuxt/Vue console:
  ```js
  await $fetch('/api/ping', { baseURL: useRuntimeConfig().public.apiBase, credentials: 'include' })
  // or Axios: axios.get('/api/ping', { baseURL: import.meta.env.VITE_API_BASE_URL, withCredentials: true })
  ```
- Check Network tab: response should include `Access-Control-Allow-Origin` matching your frontend origin and `Set-Cookie` when applicable.


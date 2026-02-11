# Installation

## Requirements

Before installing, ensure your environment meets these requirements:

- **PHP**: 8.1 or higher
- **Laravel**: 10.x, 11.x, or 12.x
- **Livewire**: 3.3 or higher (including 4.x)

## Install via Composer

Install the package using Composer:

```bash
composer require drpshtiwan/livewire-async-select
```

The package will automatically register its service provider.

## Publish Assets

Publish the CSS assets to your public directory:

```bash
php artisan vendor:publish --tag=async-select-assets
```

This will copy `async-select.css` to `public/vendor/async-select/`.

## Setup Your Layout

Add the styles and scripts stack to your layout file (e.g., `resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Your App</title>
    @asyncSelectStyles
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    @stack('scripts')  {{-- Required for Alpine.js components --}}
</body>
</html>
```

::: warning Important
The `@stack('scripts')` directive is **required** in your layout. The component uses this stack to register its Alpine.js component definition.
:::

Or manually include the CSS:

```html
<link rel="stylesheet" href="{{ asset('vendor/async-select/async-select.css') }}">
```

## Publish Configuration (Optional)

To customize default settings, publish the configuration file:

```bash
php artisan vendor:publish --tag=async-select-config
```

This creates `config/async-select.php` where you can set defaults for:
- Placeholder text
- Minimum search length
- Search delay
- UI theme (tailwind or bootstrap)
- Class prefix
- And more...

## Publish Views (Optional)

To customize the component's appearance, publish the views:

```bash
php artisan vendor:publish --tag=async-select-views
```

Views will be published to `resources/views/vendor/async-select/`.

## Internal Authentication Setup (Optional)

If you plan to use internal authentication for secure same-domain API requests:

### Step 1: Generate Secret

```bash
php artisan async-select:generate-secret
```

This command will:
- Generate a secure base64-encoded secret
- Automatically add `ASYNC_SELECT_INTERNAL_SECRET` to your `.env` file
- Overwrite existing secret if `--force` flag is used

### Step 2: Apply async-auth Middleware to Routes

::: warning Important: Middleware Required for Authentication
**You MUST apply the `async-auth` middleware to your API routes if you want authentication to work.** Without the middleware, internal authentication tokens will not be verified and users will not be authenticated.
:::
The package automatically registers the `async-auth` middleware globally. You can use it instead of the regular `auth` middleware in your routes:

```php
// ✅ Apply middleware for authenticated routes
Route::middleware(['async-auth'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// ❌ Without middleware - authentication won't work
Route::get('/api/users/search', [UserController::class, 'search']);
```

The `async-auth` middleware:
- Works exactly like `auth` middleware when no internal header is present
- Automatically handles internal authentication when `X-Internal-User` header is present
- Supports all guard specifications: `async-auth:web`, `async-auth:sanctum`, `async-auth:api`, etc.
- Supports multiple guards: `async-auth:web,sanctum` (tries first, falls back to second)
- Supports session persistence: `async-auth:web,persist` (for web routes)
- No manual registration required - it's automatically available!

**Examples:**

```php
// Default guard
Route::middleware(['async-auth'])->get('/api/users/search', ...);

// Sanctum
Route::middleware(['async-auth:sanctum'])->get('/api/users/search', ...);

// Web with session
Route::middleware(['web', 'async-auth:web'])->get('/api/users/search', ...);

// Web with session persistence
Route::middleware(['web', 'async-auth:web,persist'])->get('/api/users/search', ...);

// Multiple guards
Route::middleware(['async-auth:web,sanctum'])->get('/api/users/search', ...);
```

[Learn more about async-auth middleware →](/guide/authentication.html#middleware-setup)

### Step 3: Enable Globally (Recommended)

Enable internal authentication globally in `config/async-select.php`:

```php
return [
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', true),
    // ... other config
];
```

Or set in your `.env` file:

```bash
ASYNC_SELECT_USE_INTERNAL_AUTH=true
```

When enabled globally, **all** AsyncSelect components will automatically use internal authentication for internal endpoints.

**Usage:**

```html
<!-- No need to pass use-internal-auth when enabled globally -->
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    placeholder="Search users..."
/>
```

**That's it!** The `async-auth` middleware is automatically registered, so you can start using it immediately in your routes. No manual registration needed.

[Learn more about internal authentication →](/guide/authentication.html#internal-authentication)

## Verify Installation

Create a test route to verify the installation:

```php
// routes/web.php
Route::get('/test-async-select', function () {
    return view('test-async-select');
});
```

Create the view:

```html
<!-- resources/views/test-async-select.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Test Async Select</title>
    @asyncSelectStyles
    @livewireStyles
</head>
<body style="padding: 2rem;">
    <div style="max-width: 28rem;">
        <livewire:async-select
            name="test"
            wire:model="test"
            :options="[
                ['value' => '1', 'label' => 'Option 1'],
                ['value' => '2', 'label' => 'Option 2'],
                ['value' => '3', 'label' => 'Option 3'],
            ]"
            placeholder="Select an option..."
        />
    </div>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
```

Visit `/test-async-select` and you should see a working select component!

## Styling

The package comes with pre-built Tailwind CSS styles that use the `las-` prefix to avoid conflicts with your application's styles. You don't need to have Tailwind CSS in your project - the component styles are self-contained.

### Alpine.js

Livewire 3.3+ (including 4.x) includes Alpine.js by default, so no additional setup is required. If you're using an older version or need to install Alpine.js separately:

```bash
npm install alpinejs
```

And in your `app.js`:

```javascript
import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start()
```

## Troubleshooting

### Component Not Found

If you see "Component [async-select] not found":

```bash
php artisan livewire:discover
composer dump-autoload
php artisan config:clear
```

### Styles Not Working

Ensure your CSS framework (Tailwind or Bootstrap) is properly loaded in your layout file.

### Alpine.js Errors

Make sure Alpine.js is loaded. Livewire 3.3+ and 4.x include Alpine.js by default.

## Next Steps

- [Quick Start Guide →](/guide/quickstart.html)
- [View All Features →](/guide/features.html)

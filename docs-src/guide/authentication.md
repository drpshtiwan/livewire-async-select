# Authentication

The AsyncSelect component supports custom headers and internal authentication for secure API requests.

## Quick Start: Enable Internal Auth Globally

Enable internal authentication globally so all AsyncSelect components automatically authenticate requests to internal endpoints:

### Step 1: Generate Secret

```bash
php artisan async-select:generate-secret
```

This automatically adds `ASYNC_SELECT_INTERNAL_SECRET` to your `.env` file.

### Step 2: Enable Global Configuration

Edit `config/async-select.php`:

```php
return [
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', true),
    // ... other config
];
```

Or set in `.env`:

```bash
ASYNC_SELECT_USE_INTERNAL_AUTH=true
```

### Step 3: Apply async-auth Middleware to Routes

::: warning Important: Middleware Required for Authentication
**You MUST apply the `async-auth` middleware to your API routes if you want authentication to work.** Without the middleware, internal authentication tokens will not be verified and users will not be authenticated.

The package automatically registers the `async-auth` middleware globally. You can use it instead of the regular `auth` middleware:

```php
Route::middleware(['async-auth'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});
```

**For authenticated endpoints, always apply the middleware:**
```php
// ✅ Correct - Middleware applied
Route::middleware(['async-auth'])->get('/api/users/search', ...);

// ❌ Wrong - No middleware, authentication won't work
Route::get('/api/users/search', ...);
```

The `async-auth` middleware:
- Works exactly like `auth` middleware when no internal header is present
- Automatically handles internal authentication when `X-Internal-User` header is present
- Supports all guard specifications: `async-auth:web`, `async-auth:sanctum`, etc.
- Supports additional flags: `async-auth:web,persist` for session persistence
:::

### Step 5: Use Component

**No need to pass `use-internal-auth` - it's enabled globally!**

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    placeholder="Search users..."
/>
```

**All AsyncSelect components will automatically use internal authentication!**

[See complete example with controller →](#complete-example-global-configuration)

## Custom Headers

Pass custom headers (e.g., for authentication) with HTTP requests to your endpoints.

### Basic Usage

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :headers="[
        'Authorization' => 'Bearer ' . $token,
        'X-Custom-Header' => 'custom-value'
    ]"
/>
```

### With Livewire Properties

```php
class UserSelector extends Component
{
    public $userId;
    public $apiToken;

    public function mount()
    {
        $this->apiToken = auth()->user()->api_token;
    }

    public function render()
    {
        return view('livewire.user-selector');
    }
}
```

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :headers="[
        'Authorization' => 'Bearer ' . $this->apiToken,
        'X-API-Version' => 'v2'
    ]"
/>
```

### Dynamic Headers

Headers can be updated dynamically:

```php
public function updatedApiToken()
{
    // Headers will be automatically updated on next request
    $this->dispatch('$refresh');
}
```

## Internal Authentication

For requests to endpoints on the same domain, you can use internal authentication. This automatically generates signed tokens that authenticate the current user without requiring session cookies or API tokens.

### Setup

#### 1. Generate Secret

Run the artisan command to generate a secure secret:

```bash
php artisan async-select:generate-secret
```

This will:
- Generate a base64-encoded secret
- Add `ASYNC_SELECT_INTERNAL_SECRET` to your `.env` file
- Overwrite existing secret if `--force` flag is used

**Manual Setup:**

If you prefer to set it manually:

```bash
# Generate a secret
php -r "echo base64_encode(random_bytes(32));"

# Add to .env
ASYNC_SELECT_INTERNAL_SECRET=your-generated-secret-here
```

#### 2. Enable Internal Auth

You can enable internal authentication in two ways:

##### Option 1: Per-Component (Recommended for testing)

Enable internal authentication on individual components:

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :use-internal-auth="true"
/>
```

##### Option 2: Global Configuration (Recommended for production)

Enable internal authentication globally in `config/async-select.php`:

```php
return [
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', true),
    // ... other config
];
```

Or via environment variable:

```bash
ASYNC_SELECT_USE_INTERNAL_AUTH=true
```

When enabled globally, **all** AsyncSelect components will automatically use internal authentication for internal endpoints. You can still override it per-component:

```html
<!-- Uses config (e.g., enabled globally) -->
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
/>

<!-- Overrides config (disables for this component) -->
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :use-internal-auth="false"
/>
```

**When `use-internal-auth` is enabled:**
- The component automatically generates a signed token for the authenticated user
- The token is sent in the `X-Internal-User` header
- The `Authorization` header is automatically removed to avoid conflicts
- Only works for internal endpoints (same domain)
- Works seamlessly with all AsyncSelect components when enabled globally

### How It Works

1. **Token Generation**: When `use-internal-auth` is `true` and the endpoint is internal, the component generates a signed token containing:
   - User ID
   - Request method, path, and host (request binding)
   - Expiry time (60 seconds)
   - Nonce (prevents replay attacks)

2. **Token Transmission**: The token is sent in the `X-Internal-User` header

3. **Token Verification**: Your endpoint middleware verifies the token and authenticates the user

### Middleware Setup

The package automatically registers the `async-auth` middleware. **No manual registration required!**

::: warning Required for Authentication
**You MUST apply the `async-auth` middleware to your routes for authentication to work.** The middleware verifies internal authentication tokens and authenticates users.

The `async-auth` middleware is registered globally and can be used exactly like the `auth` middleware:

```php
// ✅ Apply middleware for authenticated routes
Route::middleware(['async-auth'])->get('/api/users/search', function () {
    // User is automatically authenticated via internal auth token (if header present)
    // Or via normal authentication (if no header)
    $user = auth()->user();
    
    return response()->json([
        'data' => User::where('name', 'like', "%{$request->get('search')}%")
            ->get()
            ->map(fn($u) => [
                'value' => $u->id,
                'label' => $u->name,
            ])
    ]);
});
```

**Without the middleware, authentication will not work:**
```php
// ❌ No middleware - authentication won't work
Route::get('/api/users/search', function () {
    // auth()->user() will be null even if X-Internal-User header is present
});
```
:::

### Applying Middleware to Routes

::: warning Important
**Always apply the `async-auth` middleware to routes that require authentication.** Without it, the internal authentication token will not be verified and users will not be authenticated.

#### Single Route

```php
use Illuminate\Support\Facades\Route;

// ✅ Apply middleware for authentication
Route::middleware(['async-auth'])->get('/api/users/search', function () {
    $user = auth()->user(); // Now authenticated
    
    return response()->json([
        'data' => User::where('name', 'like', "%{$request->get('search')}%")
            ->get()
            ->map(fn($u) => [
                'value' => $u->id,
                'label' => $u->name,
            ])
    ]);
});
```

#### Route Groups

```php
Route::middleware(['async-auth'])->prefix('api')->group(function () {
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/selected', [UserController::class, 'selected']);
    Route::get('/products/search', [ProductController::class, 'search']);
});
```

#### With Guard Specification

```php
// Use with default guard (web)
Route::middleware(['async-auth'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// Use with web guard
Route::middleware(['async-auth:web'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// Use with Sanctum
Route::middleware(['async-auth:sanctum'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// Use with API guard
Route::middleware(['async-auth:api'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// Use with multiple guards (tries first, falls back to second)
Route::middleware(['async-auth:web,sanctum'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});

// Use with custom guard
Route::middleware(['async-auth:admin'])->group(function () {
    Route::get('/admin/users/search', [AdminController::class, 'search']);
});
```

#### With Session Persistence

```php
// Persist login in session (requires web middleware group)
Route::middleware(['web', 'async-auth:web,persist'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});
```

**Note:** The `persist` flag only works with session-based guards (like `web`). It stores the authentication in the session for subsequent requests.
:::

### Complete Example: Global Configuration

Here's a complete, step-by-step example of setting up internal authentication globally:

#### Step 1: Generate Secret

```bash
php artisan async-select:generate-secret
```

This adds `ASYNC_SELECT_INTERNAL_SECRET` to your `.env` file.

#### Step 2: Enable Global Configuration

**Option A: Via Config File**

Edit `config/async-select.php`:

```php
return [
    // Enable internal auth globally for all AsyncSelect components
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', true),
    
    'internal' => [
        'secret' => env('ASYNC_SELECT_INTERNAL_SECRET', ''),
        // ... other internal config
    ],
];
```

**Option B: Via Environment Variable**

Add to your `.env` file:

```bash
ASYNC_SELECT_USE_INTERNAL_AUTH=true
ASYNC_SELECT_INTERNAL_SECRET=your-generated-secret-here
```

#### Step 3: Apply async-auth Middleware to Routes

The `async-auth` middleware is automatically registered by the package. Simply use it in your routes:

```php
// routes/api.php or routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['async-auth'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
    Route::get('/api/users/selected', [UserController::class, 'selected']);
});
```

**Note:** The `async-auth` middleware works exactly like `auth` middleware, but also handles internal authentication automatically when the `X-Internal-User` header is present.

#### Step 5: Create Controller

```php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request)
    {
        // User is automatically authenticated via internal auth
        $user = auth()->user();
        $userId = auth()->id();
        
        $search = $request->get('search', '');
        
        $users = User::query()
            ->when($search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
                'email' => $user->email,
            ]);

        return response()->json(['data' => $users]);
    }

    public function selected(Request $request)
    {
        // User is automatically authenticated
        $selected = $request->get('selected', []);
        
        $users = User::whereIn('id', (array) $selected)
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ]);

        return response()->json(['data' => $users]);
    }
}
```

#### Step 6: Use Component

**No need to pass `use-internal-auth` - it's enabled globally!**

```html
<!-- resources/views/livewire/user-selector.blade.php -->
<div>
    <livewire:async-select
        endpoint="/api/users/search"
        selected-endpoint="/api/users/selected"
        wire:model="userId"
        placeholder="Search users..."
    />
    
    @if($userId)
        <p>Selected User ID: {{ $userId }}</p>
    @endif
</div>
```

**That's it!** All AsyncSelect components will automatically use internal authentication for internal endpoints.

#### Example: Multiple Components

When enabled globally, all components automatically use internal auth:

```html
<!-- All these components automatically use internal auth -->
<livewire:async-select endpoint="/api/users" wire:model="userId" />
<livewire:async-select endpoint="/api/products" wire:model="productId" />
<livewire:async-select endpoint="/api/categories" wire:model="categoryId" />
<livewire:async-select endpoint="/api/orders" wire:model="orderId" />
```

No need to pass `:use-internal-auth="true"` to each component!

### Complete Example: Per-Component Configuration

If you prefer to enable internal auth only for specific components:

```html
<!-- This component uses internal auth -->
<livewire:async-select
    endpoint="/api/users/search"
    selected-endpoint="/api/users/selected"
    wire:model="userId"
    :use-internal-auth="true"
    placeholder="Search users..."
/>

<!-- This component does NOT use internal auth -->
<livewire:async-select
    endpoint="/api/external/users"
    wire:model="externalUserId"
    placeholder="Search external users..."
/>
```

### Using with Laravel Sanctum

Simply use `async-auth:sanctum` instead of `auth:sanctum`:

```php
// API routes with Sanctum
Route::middleware(['async-auth:sanctum'])->prefix('api')->group(function () {
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/products/search', [ProductController::class, 'search']);
});
```

The `async-auth` middleware automatically handles both internal authentication (when header present) and normal Sanctum authentication (when no header).

**Example Controller:**

```php
class UserController extends Controller
{
    public function search(Request $request)
    {
        // User is authenticated via Sanctum token OR internal auth
        $user = auth()->user(); // Works with both methods
        
        $search = $request->get('search', '');
        
        $users = User::where('name', 'like', "%{$search}%")
            ->limit(20)
            ->get();
        
        return response()->json(['data' => $users]);
    }
}
```

### Using with Laravel Web Auth

For web routes, use `async-auth:web`:

```php
// Web routes with session-based auth
Route::middleware(['web', 'async-auth:web'])->group(function () {
    Route::get('/dashboard/users/search', [UserController::class, 'search']);
});
```

**With session persistence:**

```php
// Persist login in session (requires web middleware group)
Route::middleware(['web', 'async-auth:web,persist'])->group(function () {
    Route::get('/api/users/search', [UserController::class, 'search']);
});
```

The `persist` flag ensures the user login is stored in the session, useful for web applications.

### Using with API Guard

For API-only authentication:

```php
Route::middleware(['async-auth:api'])->prefix('api')->group(function () {
    Route::get('/users/search', [UserController::class, 'search']);
});
```

### Using with Multiple Guards

Try multiple guards in order (falls back if first fails):

```php
Route::middleware(['async-auth:web,sanctum'])->group(function () {
    // Tries web guard first, then sanctum if web fails
    Route::get('/api/users/search', [UserController::class, 'search']);
});
```

### Using with Custom Guards

Works with any custom guard you've configured:

```php
// Custom guard example
Route::middleware(['async-auth:admin'])->group(function () {
    Route::get('/admin/users/search', [AdminController::class, 'search']);
});
```

### Complete Examples

**Example 1: API Routes with Sanctum**

```php
// routes/api.php
Route::middleware(['async-auth:sanctum'])->prefix('api')->group(function () {
    Route::get('/users/search', function (Request $request) {
        $users = User::where('name', 'like', "%{$request->get('search')}%")
            ->limit(20)
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ]);
        
        return response()->json(['data' => $users]);
    });
});
```

**Example 2: Web Routes with Session**

```php
// routes/web.php
Route::middleware(['web', 'async-auth:web'])->group(function () {
    Route::get('/dashboard/users/search', function (Request $request) {
        $users = User::where('name', 'like', "%{$request->get('search')}%")
            ->limit(20)
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
            ]);
        
        return response()->json(['data' => $users]);
    });
});
```

**Example 3: Mixed Authentication (Web + Sanctum)**

```php
// routes/api.php
Route::middleware(['async-auth:web,sanctum'])->prefix('api')->group(function () {
    // Accepts both web session auth and Sanctum tokens
    Route::get('/users/search', [UserController::class, 'search']);
});
```

**Example 4: Admin Routes with Custom Guard**

```php
// routes/web.php
Route::middleware(['web', 'async-auth:admin'])->prefix('admin')->group(function () {
    Route::get('/users/search', [AdminUserController::class, 'search']);
});
```

### Security Features

The internal authentication system includes several security features:

1. **Request Binding**: Tokens are bound to specific request attributes (method, path, host, body hash) to prevent token reuse
2. **Replay Protection**: Each token includes a nonce that's cached to prevent replay attacks
3. **Short Expiry**: Tokens expire after 60 seconds
4. **Key Rotation**: Supports rotating keys without breaking existing tokens

### Key Rotation

To rotate keys without breaking existing tokens:

1. Generate a new secret:
```bash
php artisan async-select:generate-secret
```

2. Update your `.env`:
```env
ASYNC_SELECT_INTERNAL_SECRET=new-secret-here
ASYNC_SELECT_INTERNAL_PREVIOUS_SECRET=old-secret-here
```

3. Tokens signed with either key will be accepted during the rotation period

4. After all old tokens expire, remove `ASYNC_SELECT_INTERNAL_PREVIOUS_SECRET`

### Configuration

All internal auth settings can be configured in `config/async-select.php`:

```php
return [
    /*
     * Enable internal authentication globally for all AsyncSelect components.
     * When enabled, all components will automatically use internal auth for
     * internal endpoints. You can override this per-component if needed.
     */
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', false),

    'internal' => [
        'secret' => env('ASYNC_SELECT_INTERNAL_SECRET', ''),
        'previous_secret' => env('ASYNC_SELECT_INTERNAL_PREVIOUS_SECRET', ''),
        'nonce_ttl' => env('ASYNC_SELECT_INTERNAL_NONCE_TTL', 120),
        'skew' => env('ASYNC_SELECT_INTERNAL_SKEW', 60),
    ],
];
```

**Environment Variables:**

```bash
# Enable internal auth globally
ASYNC_SELECT_USE_INTERNAL_AUTH=true

# Internal auth secret (required when enabled)
ASYNC_SELECT_INTERNAL_SECRET=your-generated-secret-here

# Optional: Previous secret for key rotation
ASYNC_SELECT_INTERNAL_PREVIOUS_SECRET=old-secret-here

# Optional: Nonce TTL (default: 120 seconds)
ASYNC_SELECT_INTERNAL_NONCE_TTL=120

# Optional: Time skew tolerance (default: 60 seconds)
ASYNC_SELECT_INTERNAL_SKEW=60
```

### Global vs Per-Component Configuration

#### Global Configuration (Recommended)

Enable internal auth globally for all components:

```php
// config/async-select.php
'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', true),
```

**Benefits:**
- ✅ One configuration for all components
- ✅ Consistent authentication across your app
- ✅ Easy to enable/disable app-wide
- ✅ No need to pass `:use-internal-auth="true"` to every component

**Usage:**

```html
<!-- All components automatically use internal auth -->
<livewire:async-select endpoint="/api/users" wire:model="userId" />
<livewire:async-select endpoint="/api/products" wire:model="productId" />
<livewire:async-select endpoint="/api/categories" wire:model="categoryId" />
```

#### Per-Component Configuration

Enable internal auth only for specific components:

```html
<!-- This component uses internal auth -->
<livewire:async-select 
    endpoint="/api/users" 
    wire:model="userId"
    :use-internal-auth="true"
/>

<!-- This component does NOT use internal auth -->
<livewire:async-select 
    endpoint="/api/users" 
    wire:model="userId"
    :use-internal-auth="false"
/>
```

#### Override Global Configuration

When internal auth is enabled globally, you can still disable it for specific components:

```php
// config/async-select.php
'use_internal_auth' => true, // Enabled globally
```

```html
<!-- Uses global config (internal auth enabled) -->
<livewire:async-select endpoint="/api/users" wire:model="userId" />

<!-- Overrides global config (internal auth disabled for this component) -->
<livewire:async-select 
    endpoint="/api/users" 
    wire:model="userId"
    :use-internal-auth="false"
/>
```

### Troubleshooting

#### Token Not Being Sent

- Ensure `use-internal-auth` is set to `true`
- Verify the endpoint is internal (same domain)
- Check that the user is authenticated
- Verify `ASYNC_SELECT_INTERNAL_SECRET` is set in `.env`

#### Middleware Not Authenticating

- The `async-auth` middleware is automatically registered - no manual setup needed
- Verify middleware is applied to the route: `Route::middleware(['async-auth'])`
- Check that the token is being sent in the `X-Internal-User` header (if using internal auth)
- Verify the secret matches between token generation and verification
- If no internal header is present, `async-auth` works exactly like `auth` middleware

#### Authorization Header Removed

When `use-internal-auth` is enabled, the `Authorization` header is automatically removed to avoid conflicts. If you need both, use internal auth for internal endpoints and custom headers for external endpoints.

## Combining Headers and Internal Auth

You can use both custom headers and internal auth:

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :headers="[
        'X-Custom-Header' => 'custom-value',
        'X-API-Version' => 'v2'
    ]"
    :use-internal-auth="true"
/>
```

The component will:
- Send custom headers (except `Authorization` which is removed)
- Add the `X-Internal-User` header with the internal auth token

## Next Steps

- [Async Loading →](/guide/async-loading.html)
- [API Reference →](/guide/api.html)


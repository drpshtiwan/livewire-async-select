# Async Loading

Load options dynamically from your Laravel backend API endpoints.

## Basic Setup

### 1. Define the Endpoint

```html
<livewire:async-select
    name="user_id"
    wire:model="selectedUser"
    endpoint="/api/users/search"
    placeholder="Search users..."
/>
```

### 2. Create the Controller

::: warning Important: Middleware Required for Authentication
**If your endpoint requires authentication, you MUST apply the `async-auth` middleware.** Without it, internal authentication tokens will not be verified and users will not be authenticated.

```php
// routes/api.php or routes/web.php
use App\Models\User;
use Illuminate\Http\Request;

// ✅ Apply middleware for authenticated routes
Route::middleware(['async-auth'])->get('/api/users/search', function (Request $request) {
    $search = $request->get('search', '');
    
    // User is now authenticated (via internal auth or normal auth)
    $user = auth()->user();
    
    $users = User::query()
        ->when($search, function($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        })
        ->limit(20)
        ->get()
        ->map(function($user) {
            return [
                'value' => $user->id,
                'label' => $user->name,
                'email' => $user->email,
                'image' => $user->avatar_url
            ];
        });

    return response()->json(['data' => $users]);
});
```

**Note:** The `async-auth` middleware is automatically registered by the package and works exactly like `auth` middleware, but also handles internal authentication automatically when the `X-Internal-User` header is present.

**Without middleware, authentication won't work:**
```php
// ❌ No middleware - authentication won't work
Route::get('/api/users/search', function (Request $request) {
    // auth()->user() will be null even if X-Internal-User header is present
});
```
:::

**Using with different guards:**

```php
// Default guard
Route::middleware(['async-auth'])->get('/api/users/search', ...);

// Sanctum
Route::middleware(['async-auth:sanctum'])->get('/api/users/search', ...);

// Web guard with session
Route::middleware(['web', 'async-auth:web'])->get('/api/users/search', ...);

// Multiple guards
Route::middleware(['async-auth:web,sanctum'])->get('/api/users/search', ...);
```

## Response Format

Your endpoint must return JSON in this format:

```json
{
  "data": [
    {
      "value": "1",
      "label": "John Doe"
    },
    {
      "value": "2",
      "label": "Jane Smith"
    }
  ]
}
```

### Auto-Detection of Fields

The component automatically detects common field names:

**For value field (in order of priority):**
- `id`
- `value`
- array key

**For label field (in order of priority):**
- `title`
- `name`
- `label`
- `text`

This means you can return data like this without extra configuration:

```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe"
    },
    {
      "id": 2,
      "name": "Jane Smith"
    }
  ]
}
```

### Custom Field Names

If your API uses different field names, specify them explicitly:

```html
<livewire:async-select
    endpoint="/api/products"
    value-field="sku"
    label-field="title"
/>
```

Your API can then return:

```json
{
  "data": [
    {
      "sku": "PROD-001",
      "title": "Product Name",
      "price": 99.99
    }
  ]
}
```

### With Additional Fields

```json
{
  "data": [
    {
      "value": "1",
      "label": "John Doe",
      "email": "john@example.com",
      "image": "https://example.com/avatar.jpg",
      "role": "Admin"
    }
  ]
}
```

## Selected Items Endpoint

When editing forms, you need to load already-selected items. You have two options:

### Option 1: Using selected-endpoint (API Call)

Load selected items from an endpoint:

```html
<livewire:async-select
    wire:model="userId"
    endpoint="/api/users/search"
    selected-endpoint="/api/users/selected"
/>
```

The selected endpoint receives the current value:

```php
Route::middleware(['async-auth'])->get('/api/users/selected', function (Request $request) {
    $selected = $request->get('selected');
    
    $users = User::whereIn('id', (array) $selected)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'image' => $user->avatar_url
        ]);

    return response()->json(['data' => $users]);
});
```
<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
read_file

### Option 2: Using value-labels (No API Call)

::: tip Version 1.1.0 Feature
Use `value-labels` to provide labels directly without making any API requests. Perfect when you already know the labels.
:::

If you already have the labels (e.g., from the form data or previous API calls), use `value-labels` to avoid the API request:

```html
<livewire:async-select
    wire:model="userId"
    endpoint="/api/users/search"
    :value-labels="[
        5 => 'John Doe',
        7 => 'Jane Smith'
    ]"
/>
```

**Benefits of value-labels:**
- ✅ **No API requests** - Labels displayed immediately
- ✅ **Better performance** - Reduces network traffic
- ✅ **Works with pre-selected values** - Labels show on mount
- ✅ **Perfect for edit forms** - Use existing data from the model

**When to use each:**
- **Use `value-labels`** when you already have the labels (form data, model attributes, etc.)
- **Use `selected-endpoint`** when labels need to be fetched from the server or might change

[Learn more about value-labels →](/guide/default-values.html#using-value-labels-no-api-calls-required)

## Configuration

### Minimum Search Length

Require a minimum number of characters before triggering search:

```html
<livewire:async-select
    endpoint="/api/search"
    :min-search-length="3"
/>
```

### Search Parameter Name

Customize the query parameter name:

```html
<livewire:async-select
    endpoint="/api/search"
    search-param="q"
/>
```

Your endpoint will receive: `/api/search?q=searchterm`

### Selected Parameter Name

Customize the parameter for selected items:

```html
<livewire:async-select
    selected-endpoint="/api/selected"
    selected-param="ids"
/>
```

### Auto-load

Load options immediately on mount:

```html
<livewire:async-select
    endpoint="/api/popular-items"
    :autoload="true"
/>
```

### Reload Options

You can programmatically reload options from your Livewire component:

```php
// In your Livewire component
public function refreshUsers()
{
    $this->dispatch('reload-options')->to('async-select');
}
```

Or call the reload method directly if you have a reference:

```html
<button wire:click="$wire.call('reload')">Refresh</button>
```

## Extra Parameters

Pass additional parameters to your endpoints:

```html
<livewire:async-select
    endpoint="/api/cities/search"
    :extra-params="[
        'country_id' => $countryId,
        'active' => true
    ]"
/>
```

Your endpoint receives:
```
/api/cities/search?search=london&country_id=1&active=1
```

## Custom Headers

Pass custom headers (e.g., for authentication) with HTTP requests:

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

[Learn more about headers and authentication →](/guide/authentication.html#custom-headers)

## Internal Authentication

For secure internal API requests, use internal authentication:

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    :use-internal-auth="true"
/>
```

This automatically generates signed tokens for authenticated users when making requests to endpoints on the same domain.

[Learn more about internal authentication →](/guide/authentication.html#internal-authentication)

### Dynamic Parameters

Use Livewire properties for dynamic values:

```php
class MyComponent extends Component
{
    public $countryId;
    public $selectedCity;

    public function render()
    {
        return view('livewire.my-component');
    }
}
```

```html
<livewire:async-select
    wire:model="selectedCity"
    endpoint="/api/cities/search"
    :extra-params="['country_id' => $this->countryId]"
/>
```

## Error Handling

The component automatically handles:
- Network errors
- Invalid responses
- Timeouts

Display user-friendly messages by catching errors in your endpoint:

```php
Route::middleware(['async-auth'])->get('/api/search', function (Request $request) {
    try {
        // Your logic...
        return response()->json(['data' => $results]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load options'
        ], 500);
    }
});
```

## Pagination

The component supports pagination for loading large datasets efficiently.

### Basic Pagination with `paginate()`

```php
Route::middleware(['async-auth'])->get('/api/users/search', function (Request $request) {
    $search = $request->get('search', '');
    $page = $request->get('page', 1);
    $perPage = $request->get('per_page', 20);
    
    $users = User::query()
        ->when($search, function($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data' => $users->items(),
        'current_page' => $users->currentPage(),
        'last_page' => $users->lastPage(),
        'total' => $users->total(),
        'per_page' => $users->perPage(),
    ]);
});
```

### Component Configuration

```html
<livewire:async-select
    endpoint="/api/users/search"
    :per-page="20"
    wire:model="userId"
/>
```

### Load More (Infinite Scroll)

The component automatically detects pagination and shows a "Load More" button:

```php
Route::middleware(['async-auth'])->get('/api/products/search', function (Request $request) {
    $search = $request->get('search', '');
    $page = $request->get('page', 1);
    
    $products = Product::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->paginate(15);

    return response()->json([
        'data' => $products->map(fn($product) => [
            'value' => $product->id,
            'label' => $product->name,
            'price' => $product->price,
        ]),
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
    ]);
});
```

### Supported Pagination Formats

The component supports multiple pagination response formats:

**Laravel Paginator (Recommended):**
```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 5
}
```

**Custom Format with `has_more`:**
```json
{
  "data": [...],
  "has_more": true
}
```

**Laravel API Resources:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 100
  }
}
```

### Complete Pagination Example

**Controller:**
```php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $search = $validated['search'] ?? '';
        $perPage = $validated['per_page'] ?? 20;

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $users->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
                'email' => $user->email,
                'image' => $user->avatar_url,
            ]),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }
}
```

**Livewire Component:**
```php
use Livewire\Component;

class UserSelector extends Component
{
    public $userId;

    public function render()
    {
        return view('livewire.user-selector');
    }
}
```

**Blade View:**
```html
<div>
    <livewire:async-select
        wire:model.live="userId"
        endpoint="/api/users/search"
        :per-page="25"
        :min-search-length="2"
        placeholder="Search users..."
        searchable
    />
    
    @if($userId)
        <p>Selected User ID: {{ $userId }}</p>
    @endif
</div>
```

## Performance Tips

### 1. Optimize Database Queries

```php
$users = User::query()
    ->select(['id', 'name', 'email', 'avatar']) // Only select needed columns
    ->where('name', 'like', "%{$search}%")
    ->limit(20)
    ->get();
```

### 2. Add Database Indexes

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('name');
    $table->index('email');
});
```

### 3. Cache Results

```php
$cacheKey = "user_search_{$search}";
$users = Cache::remember($cacheKey, 300, function() use ($search) {
    return User::where('name', 'like', "%{$search}%")->get();
});
```

### 4. Use Resource Classes

```php
use App\Http\Resources\UserResource;

return response()->json([
    'data' => UserResource::collection($users)
]);
```

## Complete Example

```php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->get('search');
        $role = $request->get('role');
        
        $users = User::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($role, fn($q) => $q->where('role', $role))
            ->with('avatar')
            ->limit(20)
            ->get()
            ->map(function($user) {
                return [
                    'value' => $user->id,
                    'label' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'image' => $user->avatar?->url
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function selected(Request $request)
    {
        $ids = $request->get('selected', []);
        
        $users = User::whereIn('id', $ids)
            ->get()
            ->map(fn($user) => [
                'value' => $user->id,
                'label' => $user->name,
                'image' => $user->avatar?->url
            ]);

        return response()->json(['data' => $users]);
    }
}
```

## Next Steps

- [Multiple Selection →](/guide/multiple-selection.html)
- [Custom Slots →](/guide/custom-slots.html)
- [API Reference →](/guide/api.html)


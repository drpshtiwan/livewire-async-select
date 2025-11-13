# Quick Start

This guide will get you up and running with Livewire Async Select in minutes.

## Basic Usage

### 1. Static Options

The simplest use case with predefined options:

```html
<livewire:async-select
    name="status"
    wire:model="status"
    :options="[
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
        ['value' => 'pending', 'label' => 'Pending']
    ]"
    placeholder="Select status..."
/>
```

### 2. In Livewire Component

```php
namespace App\Livewire;

use App\Models\Country;
use Livewire\Component;

class UserForm extends Component
{
    public $selectedStatus = null;
    public $selectedCountry = null;

    public function render()
    {
        return view('livewire.user-form', [
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            // Collections are automatically converted to arrays
            'countries' => Country::all()->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name
            ])
        ]);
    }

    public function save()
    {
        $this->validate([
            'selectedStatus' => 'required',
            'selectedCountry' => 'required'
        ]);

        // Save logic...
    }
}
```

::: tip Collection Support
The component automatically converts Laravel Collections to arrays. You can pass collections directly without calling `->toArray()` or `->all()`.
:::

```html
<!-- resources/views/livewire/user-form.blade.php -->
<form wire:submit="save">
    <div class="mb-4">
        <label>Status</label>
        <livewire:async-select
            name="status"
            wire:model="selectedStatus"
            :options="$statuses"
            placeholder="Select status..."
        />
        @error('selectedStatus') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="mb-4">
        <label>Country</label>
        <livewire:async-select
            name="country"
            wire:model="selectedCountry"
            :options="$countries"
            placeholder="Select country..."
        />
        @error('selectedCountry') <span class="error">{{ $message }}</span> @enderror
    </div>

    <button type="submit">Save</button>
</form>
```

## Multiple Selection

Enable multiple selection mode:

```html
<livewire:async-select
    name="tags[]"
    wire:model="selectedTags"
    :options="$tags"
    :multiple="true"
    placeholder="Select tags..."
/>
```

## With Images

Display images/avatars alongside options:

```html
<livewire:async-select
    name="user_id"
    wire:model="selectedUser"
    :options="$users"
    placeholder="Select a user..."
/>
```

Where `$users` array includes image URLs:

```php
$users = User::all()->map(fn($user) => [
    'value' => $user->id,
    'label' => $user->name,
    'image' => $user->avatar_url
]);
```

## Async Loading

Load options from an API endpoint:

```html
<livewire:async-select
    name="user_id"
    wire:model="selectedUser"
    endpoint="/api/users/search"
    placeholder="Search users..."
/>
```

Create the endpoint in your routes:

::: warning Important: Middleware Required for Authentication
**If your endpoint requires authentication, you MUST apply the `async-auth` middleware.** Without it, internal authentication tokens will not be verified and users will not be authenticated.

```php
// routes/api.php or routes/web.php
// ✅ Apply middleware for authenticated routes
Route::middleware(['async-auth'])->get('/api/users/search', function (Request $request) {
    $search = $request->get('search');
    
    // User is now authenticated (via internal auth or normal auth)
    $user = auth()->user();
    
    $users = User::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->limit(20)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'image' => $user->avatar_url
        ]);

    return response()->json(['data' => $users]);
});
```

**Note:** The `async-auth` middleware is automatically registered and works exactly like `auth` middleware, but also handles internal authentication automatically when the `X-Internal-User` header is present.

**Without middleware, authentication won't work:**
```php
// ❌ No middleware - authentication won't work
Route::get('/api/users/search', function (Request $request) {
    // auth()->user() will be null even if X-Internal-User header is present
});
```
:::

## Common Patterns

### Search with Minimum Length

```html
<livewire:async-select
    endpoint="/api/products/search"
    :min-search-length="3"
    placeholder="Type at least 3 characters..."
/>
```

### Tags/Chip Mode

Allow users to create custom options:

```html
<livewire:async-select
    name="keywords[]"
    wire:model="keywords"
    :multiple="true"
    :tags="true"
    placeholder="Type and press Enter..."
/>
```

### Clearable Selection

```html
<livewire:async-select
    wire:model="selection"
    :options="$options"
    :clearable="true"
/>
```

### Disable Search

When you have a small, fixed list of options and don't need search functionality:

```html
<livewire:async-select
    wire:model="category"
    :options="$categories"
    :searchable="false"
    placeholder="Select a category..."
/>
```

When `searchable` is `false`:
- Search input fields are hidden
- All options are displayed without filtering
- Useful for small dropdowns with limited options

### With Extra Parameters

Pass additional parameters to your endpoint:

```html
<livewire:async-select
    endpoint="/api/cities/search"
    :extra-params="['country_id' => $countryId]"
    placeholder="Select city..."
/>
```

### With Suffix Button

Add a button on the right side of the select to trigger custom actions:

```html
<livewire:async-select
    name="selectedMedia"
    wire:model="selectedMedia"
    :options="$media"
    :suffix-button="true"
    suffix-button-action="showAddMediaModal"
    placeholder="Select Media..."
/>
```

In your Livewire component, listen to the event:

```php
use Livewire\Attributes\On;

#[On('showAddMediaModal')]
public function showAddMediaModal()
{
    $this->showModal = true;
}
```

### Options Not Updating (Reactivity Issue)

If your options are dynamically loaded or updated and the component isn't reacting to changes, use the `key` attribute to force a re-render:

```html
<livewire:async-select
    name="selectedMedia"
    wire:model="selectedMedia"
    :options="$media"
    placeholder="Select Media..."
    :key="md5(json_encode($media))"
/>
```

This ensures Livewire re-mounts the component whenever the options data changes.

## Setting Default Values

::: tip Version 1.1.0 Update
In version 1.1.0, default values work seamlessly with `wire:model`. Simply set the property value in your Livewire component - no need to pass the `:value` attribute separately.
:::

To pre-select values, simply set the property in your Livewire component. The component automatically uses the property value from `wire:model`:

```php
class UserForm extends Component
{
    public $userId = 5;  // Default value - automatically used by wire:model
    public $tags = [1, 3, 5];  // Default for multiple selection
}
```

In your Blade view, just use `wire:model` - no `:value` attribute needed:

```html
<livewire:async-select
    wire:model="userId"
    :options="$users"
    placeholder="Select user..."
/>
```

Or load from existing data:

```php
public function mount($projectId)
{
    $project = Project::find($projectId);
    $this->categoryId = $project->category_id;
    $this->teamMembers = $project->members->pluck('id')->toArray();
}
```

**With value-labels (No API Calls):**

When you already know the labels, use `value-labels` to display them without making API requests:

```html
<livewire:async-select
    wire:model="categoryId"
    endpoint="/api/categories"
    :value-labels="[
        3 => 'Web Development',  // Label displayed immediately, no API call
    ]"
    placeholder="Select category..."
/>
```

[Learn more about setting default values →](/guide/default-values.html)

## Next Steps

- [Setting Default Values →](/guide/default-values.html)
- [View All Features →](/guide/features.html)
- [Async Loading Details →](/guide/async-loading.html)
- [Custom Slots →](/guide/custom-slots.html)
- [API Reference →](/guide/api.html)


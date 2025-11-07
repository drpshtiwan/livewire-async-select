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

```php
// routes/api.php or routes/web.php
Route::get('/api/users/search', function (Request $request) {
    $search = $request->get('search');
    
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

To pre-select values, simply set the property in your Livewire component:

```php
class UserForm extends Component
{
    public $userId = 5;  // Default value
    public $tags = [1, 3, 5];  // Default for multiple selection
}
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

[Learn more about setting default values →](/guide/default-values.html)

## Next Steps

- [Setting Default Values →](/guide/default-values.html)
- [View All Features →](/guide/features.html)
- [Async Loading Details →](/guide/async-loading.html)
- [Custom Slots →](/guide/custom-slots.html)
- [API Reference →](/guide/api.html)


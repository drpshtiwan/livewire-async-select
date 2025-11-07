# Examples

Real-world examples and use cases with complete code.

## Basic User Selection

Simple user dropdown without customization:

```html
<livewire:async-select
    wire:model="userId"
    endpoint="/api/users/search"
    placeholder="Search users..."
/>
```

**API Endpoint:**

```php
Route::get('/api/users/search', function (Request $request) {
    $search = $request->get('search', '');
    
    $users = User::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->limit(20)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ]);
    
    return response()->json(['data' => $users]);
});
```

## User Selection with Avatar and Email

Custom slots to display rich user information:

```html
<livewire:async-select
    wire:model="userId"
    endpoint="/api/users/search"
    placeholder="Search users..."
>
    <x-slot name="slot" :option="$option">
        <div class="flex items-center gap-3">
            <img 
                src="{{ $option['avatar'] ?? '/default-avatar.png' }}" 
                alt="{{ $option['label'] }}"
                class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
            >
            <div>
                <div class="font-semibold text-gray-900">{{ $option['label'] }}</div>
                <div class="text-sm text-gray-500">{{ $option['email'] }}</div>
            </div>
        </div>
    </x-slot>
    
    <x-slot name="selectedSlot" :option="$option">
        <div class="flex items-center gap-2">
            <img src="{{ $option['avatar'] }}" class="w-6 h-6 rounded-full">
            <span class="font-medium">{{ $option['label'] }}</span>
        </div>
    </x-slot>
</livewire:async-select>
```

**API Endpoint:**

```php
Route::get('/api/users/search', function (Request $request) {
    $search = $request->get('search', '');
    
    $users = User::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%"))
        ->limit(20)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
        ]);
    
    return response()->json(['data' => $users]);
});
```

## Country & City Cascading Selects

Dependent dropdowns where cities filter based on selected country:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use Livewire\Component;

class AddressForm extends Component
{
    public $country_id = null;
    public $city_id = null;
    
    public function updatedCountryId()
    {
        // Reset city when country changes
        $this->city_id = null;
    }

    public function render()
    {
        return view('livewire.address-form');
    }
    
    public function save()
    {
        $this->validate([
            'country_id' => 'required',
            'city_id' => 'required',
        ]);
        
        // Save logic...
    }
}
```

**Blade View:**

```html
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Country
        </label>
        <livewire:async-select
            name="country"
            wire:model.live="country_id"
            endpoint="/api/countries/search"
            placeholder="Select country..."
        />
        @error('country_id')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            City
        </label>
        <livewire:async-select
            name="city"
            wire:model="city_id"
            endpoint="/api/cities/search"
            :extra-params="['country_id' => $country_id]"
            placeholder="Select city..."
            :disabled="!$country_id"
        />
        @error('city_id')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
```

**API Endpoints:**

```php
// Countries
Route::get('/api/countries/search', function (Request $request) {
    $search = $request->get('search', '');
    
    $countries = Country::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->orderBy('name')
        ->limit(50)
        ->get()
        ->map(fn($country) => [
            'value' => $country->id,
            'label' => $country->name,
        ]);
    
    return response()->json(['data' => $countries]);
});

// Cities (filtered by country)
Route::get('/api/cities/search', function (Request $request) {
    $search = $request->get('search', '');
    $countryId = $request->get('country_id');
    
    $cities = City::query()
        ->when($countryId, fn($q) => $q->where('country_id', $countryId))
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->orderBy('name')
        ->limit(50)
        ->get()
        ->map(fn($city) => [
            'value' => $city->id,
            'label' => $city->name,
        ]);
    
    return response()->json(['data' => $cities]);
});
```

## Product Tags (Create Custom Tags)

Allow users to create and select custom tags:

```html
<livewire:async-select
    name="tags[]"
    wire:model="tags"
    :multiple="true"
    :tags="true"
    :options="$existingTags"
    placeholder="Type to add or create tags..."
/>
```

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\Tag;
use Livewire\Component;

class ProductForm extends Component
{
    public $tags = [];
    public $name = '';
    
    public function render()
    {
        $existingTags = Tag::orderBy('name')
            ->get()
            ->map(fn($tag) => [
                'value' => $tag->id,
                'label' => $tag->name,
            ])
            ->toArray();
        
        return view('livewire.product-form', [
            'existingTags' => $existingTags
        ]);
    }
    
    public function save()
    {
        // Tags will be an array of IDs and/or new tag names
        // Handle creating new tags if needed
    }
}
```

## Team Members with Multiple Selection

Select multiple team members with role display:

```html
<livewire:async-select
    wire:model="teamMembers"
    endpoint="/api/users/search"
    :multiple="true"
    :max-selections="10"
    placeholder="Add team members..."
>
    {{-- Dropdown option display --}}
    <x-slot name="slot" :option="$option" :isSelected="$isSelected">
        <div class="flex items-center gap-3">
            <img 
                src="{{ $option['avatar'] }}" 
                alt="{{ $option['label'] }}"
                class="w-8 h-8 rounded-full"
            >
            <div class="flex-1">
                <div class="font-semibold">{{ $option['label'] }}</div>
                <div class="text-xs text-gray-500">{{ $option['email'] }}</div>
                <div class="text-xs text-gray-400">{{ $option['role'] }}</div>
            </div>
            @if ($isSelected)
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            @endif
        </div>
    </x-slot>
    
    {{-- Chip display for selected members --}}
    <x-slot name="selectedSlot" :option="$option">
        <div class="flex items-center gap-1.5">
            <img src="{{ $option['avatar'] }}" class="w-4 h-4 rounded-full">
            <span class="font-medium">{{ $option['label'] }}</span>
        </div>
    </x-slot>
</livewire:async-select>
```

## Product Search with Price and Stock

Complex product selection with detailed information:

```html
<livewire:async-select
    wire:model="productId"
    endpoint="/api/products/search"
    placeholder="Search products..."
>
    <x-slot name="slot" :option="$option" :isDisabled="$isDisabled">
        <div class="flex items-center justify-between gap-3 py-1">
            {{-- Left side: Product info --}}
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <img 
                    src="{{ $option['image'] }}" 
                    alt="{{ $option['label'] }}"
                    class="w-12 h-12 rounded object-cover border border-gray-200 flex-shrink-0"
                >
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 truncate">
                        {{ $option['label'] }}
                    </div>
                    <div class="text-xs text-gray-500">
                        SKU: {{ $option['sku'] }}
                    </div>
                    @if (isset($option['category']))
                        <div class="text-xs text-gray-400">
                            {{ $option['category'] }}
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Right side: Price and stock --}}
            <div class="text-right flex-shrink-0">
                <div class="font-bold text-green-600">
                    ${{ number_format($option['price'], 2) }}
                </div>
                <div class="text-xs {{ $option['stock'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if ($option['stock'] > 0)
                        {{ $option['stock'] }} in stock
                    @else
                        Out of stock
                    @endif
                </div>
            </div>
        </div>
    </x-slot>
    
    <x-slot name="selectedSlot" :option="$option">
        <div class="flex items-center gap-2">
            <img src="{{ $option['image'] }}" class="w-6 h-6 rounded">
            <span class="font-medium">{{ $option['label'] }}</span>
            <span class="text-sm text-gray-500">({{ $option['sku'] }})</span>
        </div>
    </x-slot>
</livewire:async-select>
```

**API Endpoint:**

```php
Route::get('/api/products/search', function (Request $request) {
    $search = $request->get('search', '');
    
    $products = Product::with('category')
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                                     ->orWhere('sku', 'like', "%{$search}%"))
        ->limit(20)
        ->get()
        ->map(fn($product) => [
            'value' => $product->id,
            'label' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->stock_quantity,
            'image' => $product->image_url,
            'category' => $product->category?->name,
            'disabled' => $product->stock_quantity <= 0, // Disable out of stock
        ]);
    
    return response()->json(['data' => $products]);
});
```

## Status Dropdown with Colors

Status selection with colored indicators:

```html
<livewire:async-select
    wire:model="status"
    :options="[
        ['value' => 'active', 'label' => 'Active', 'color' => 'green'],
        ['value' => 'pending', 'label' => 'Pending', 'color' => 'yellow'],
        ['value' => 'inactive', 'label' => 'Inactive', 'color' => 'red'],
        ['value' => 'archived', 'label' => 'Archived', 'color' => 'gray']
    ]"
    placeholder="Select status..."
>
    <x-slot name="slot" :option="$option">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 
                @if($option['color'] === 'green') bg-green-500
                @elseif($option['color'] === 'yellow') bg-yellow-500
                @elseif($option['color'] === 'red') bg-red-500
                @else bg-gray-500
                @endif">
            </span>
            <span class="font-medium">{{ $option['label'] }}</span>
        </div>
    </x-slot>
    
    <x-slot name="selectedSlot" :option="$option">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full 
                @if($option['color'] === 'green') bg-green-500
                @elseif($option['color'] === 'yellow') bg-yellow-500
                @elseif($option['color'] === 'red') bg-red-500
                @else bg-gray-500
                @endif">
            </span>
            <span>{{ $option['label'] }}</span>
        </div>
    </x-slot>
</livewire:async-select>
```

## Grouped Options Example

Organize options into categories:

```html
<livewire:async-select
    wire:model="item"
    :options="[
        ['value' => 'apple', 'label' => 'Apple', 'group' => 'Fruits'],
        ['value' => 'banana', 'label' => 'Banana', 'group' => 'Fruits'],
        ['value' => 'orange', 'label' => 'Orange', 'group' => 'Fruits'],
        ['value' => 'carrot', 'label' => 'Carrot', 'group' => 'Vegetables'],
        ['value' => 'broccoli', 'label' => 'Broccoli', 'group' => 'Vegetables'],
        ['value' => 'spinach', 'label' => 'Spinach', 'group' => 'Vegetables']
    ]"
    placeholder="Select an item..."
/>
```

## Media Selection with Add Button

Select media with an "Add" button to upload new media items:

```html
<livewire:async-select
    name="selectedMedia"
    wire:model="selectedMedia"
    :options="$media"
    :suffix-button="true"
    suffix-button-action="showAddMediaModal"
    placeholder="Select Media..."
    :key="md5(json_encode($media))"
/>
```

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\Media;
use Livewire\Component;
use Livewire\Attributes\On;

class MediaSelector extends Component
{
    public $selectedMedia = null;
    
    public $media = [];
    public $showModal = false;
    
    public function mount()
    {
        $this->loadMedia();
    }
    
    public function loadMedia()
    {
        $this->media = Media::latest()
            ->get()
            ->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->title,
                'image' => $item->thumbnail_url,
            ])
            ->toArray();
    }
    
    #[On('showAddMediaModal')]
    public function showAddMediaModal()
    {
        $this->showModal = true;
    }
    
    #[On('mediaUploaded')]
    public function handleMediaUploaded()
    {
        $this->loadMedia(); // Reload media options
        $this->showModal = false;
    }
    
    public function render()
    {
        return view('livewire.media-selector');
    }
}
```

**Note**: The `:key="md5(json_encode($media))"` attribute ensures the component re-renders when the `$media` array changes after uploading new media.

## Complete Form Example

Full working form with validation:

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Category;
use Livewire\Component;

class CreateProjectForm extends Component
{
    public $name = '';
    public $description = '';
    public $owner_id = null;
    public $category_id = null;
    public $team_members = [];
    public $tags = [];
    
    public function render()
    {
        return view('livewire.create-project-form');
    }
    
    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|min:3',
            'description' => 'required',
            'owner_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'team_members' => 'required|array|min:1',
            'team_members.*' => 'exists:users,id',
            'tags' => 'array'
        ]);
        
        // Create project logic...
        
        session()->flash('message', 'Project created successfully!');
        return redirect()->route('projects.index');
    }
}
```

**Blade View:**

```html
<div class="max-w-3xl mx-auto p-6">
    <form wire:submit="save" class="space-y-6">
        {{-- Project Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Project Name
            </label>
            <input 
                type="text" 
                wire:model="name" 
                class="w-full rounded-lg border-gray-300"
            >
            @error('name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        {{-- Category --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Category
            </label>
            <livewire:async-select
                wire:model="category_id"
                endpoint="/api/categories"
                placeholder="Select category..."
            />
            @error('category_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        {{-- Project Owner --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Project Owner
            </label>
            <livewire:async-select
                wire:model="owner_id"
                endpoint="/api/users/search"
                placeholder="Select owner..."
            >
                <x-slot name="slot" :option="$option">
                    <div class="flex items-center gap-3">
                        <img src="{{ $option['avatar'] }}" class="w-8 h-8 rounded-full">
                        <div>
                            <div class="font-semibold">{{ $option['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $option['email'] }}</div>
                        </div>
                    </div>
                </x-slot>
            </livewire:async-select>
            @error('owner_id')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        {{-- Team Members --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Team Members
            </label>
            <livewire:async-select
                wire:model="team_members"
                endpoint="/api/users/search"
                :multiple="true"
                placeholder="Add team members..."
            >
                <x-slot name="slot" :option="$option" :isSelected="$isSelected">
                    <div class="flex items-center gap-2">
                        <img src="{{ $option['avatar'] }}" class="w-8 h-8 rounded-full">
                        <span>{{ $option['label'] }}</span>
                        @if ($isSelected)
                            <span class="text-green-500 ml-auto">✓</span>
                        @endif
                    </div>
                </x-slot>
            </livewire:async-select>
            @error('team_members')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        
        {{-- Tags --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tags (optional)
            </label>
            <livewire:async-select
                wire:model="tags"
                :multiple="true"
                :tags="true"
                endpoint="/api/tags"
                placeholder="Add or create tags..."
            />
        </div>
        
        {{-- Submit --}}
        <div class="flex justify-end gap-3">
            <button 
                type="button" 
                class="px-4 py-2 border border-gray-300 rounded-lg"
            >
                Cancel
            </button>
            <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Create Project
            </button>
        </div>
    </form>
</div>
```

## Next Steps

- [Custom Slots Documentation →](/guide/custom-slots.html)
- [API Reference →](/guide/api.html)
- [Async Loading →](/guide/async-loading.html)


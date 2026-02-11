# Custom Slots

Fully customize how options and selected items are displayed using Blade slots.

## Understanding Slots

Livewire Async Select provides two powerful slots for customization:

1. **`slot`** - Customize how each option appears in the dropdown
2. **`selectedSlot`** - Customize how selected items are displayed

## Slot Syntax (Livewire 3.3+ / 4.x)

Use plain named slots. The component injects slot variables automatically.

```html
<livewire:async-select :options="$users">
    <livewire:slot name="slot">
        {{ $option['label'] }}
        @if ($isSelected) ✓ @endif
    </livewire:slot>

    <livewire:slot name="selectedSlot">
        {{ $option['label'] }}
    </livewire:slot>
</livewire:async-select>
```

You do not need to pass `:option`, `:isSelected`, `:isDisabled`, or `:multiple` on `<livewire:slot>`.
These variables are available automatically in both local and remote option rendering.

## Starting Simple

### Basic Usage (No Slots)

Without any custom slots, the component uses default rendering:

```html
<livewire:async-select
    name="user_id"
    wire:model="selectedUser"
    :options="[
        ['value' => '1', 'label' => 'John Doe'],
        ['value' => '2', 'label' => 'Jane Smith'],
        ['value' => '3', 'label' => 'Bob Wilson']
    ]"
    placeholder="Select a user..."
/>
```

This displays simple text labels - perfect for basic dropdowns.

## Option Slot (`slot`)

The `slot` customizes each option in the dropdown menu.

### Example 1: Simple Custom Text

```html
<livewire:async-select :options="$users">
    <livewire:slot name="slot">
        <div class="flex items-center gap-2">
            <span class="font-medium">{{ $option['label'] }}</span>
            <span class="text-xs text-gray-500">(ID: {{ $option['value'] }})</span>
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Example 2: With Avatar Images

```html
<livewire:async-select :options="$users">
    <livewire:slot name="slot">
        <div class="flex items-center gap-3">
            <img 
                src="{{ $option['image'] ?? '/default-avatar.png' }}" 
                alt="{{ $option['label'] }}"
                class="w-8 h-8 rounded-full object-cover"
            >
            <div>
                <div class="font-semibold">{{ $option['label'] }}</div>
                @if (isset($option['email']))
                    <div class="text-xs text-gray-500">{{ $option['email'] }}</div>
                @endif
            </div>
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Example 3: Using All Available Variables

```html
<livewire:async-select :options="$users">
    <livewire:slot name="slot">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-medium">{{ $option['label'] }}</span>
                @if ($isDisabled)
                    <span class="text-xs text-red-500">(Unavailable)</span>
                @endif
            </div>
            
            @if ($isSelected)
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            @endif
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Example 4: Product Options with Rich Content

```html
<livewire:async-select endpoint="/api/products/search">
    <livewire:slot name="slot">
        <div class="flex items-center justify-between gap-3 py-1">
            {{-- Left side: Product info --}}
            <div class="flex items-center gap-3 flex-1">
                <img 
                    src="{{ $option['image'] }}" 
                    class="w-12 h-12 rounded object-cover border border-gray-200"
                >
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 truncate">
                        {{ $option['label'] }}
                    </div>
                    <div class="text-xs text-gray-500">
                        SKU: {{ $option['sku'] ?? 'N/A' }}
                    </div>
                </div>
            </div>
            
            {{-- Right side: Price --}}
            <div class="text-right">
                <div class="font-bold text-green-600">
                    ${{ number_format($option['price'] ?? 0, 2) }}
                </div>
                <div class="text-xs text-gray-500">
                    Stock: {{ $option['stock'] ?? 0 }}
                </div>
            </div>
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Available Variables in Option Slot

| Variable | Type | Description |
|----------|------|-------------|
| `$option` | array | The complete option data (includes value, label, and any custom fields) |
| `$isSelected` | boolean | Whether this option is currently selected |
| `$isDisabled` | boolean | Whether this option is disabled |
| `$multiple` | boolean | Whether the component is in multiple selection mode |

## Selected Item Slot (`selectedSlot`)

The `selectedSlot` customizes how selected items appear:
- In **single selection**: Shows in the input field
- In **multiple selection**: Shows in each chip/tag

### Example 1: Single Selection Display

```html
<livewire:async-select :options="$users">
    {{-- Dropdown options --}}
    <livewire:slot name="slot">
        <div class="flex items-center gap-2">
            <img src="{{ $option['image'] }}" class="w-8 h-8 rounded-full">
            <span>{{ $option['label'] }}</span>
        </div>
    </livewire:slot>
    
    {{-- Selected item display in input field --}}
    <livewire:slot name="selectedSlot">
        <div class="flex items-center gap-2">
            <img src="{{ $option['image'] }}" class="w-6 h-6 rounded-full">
            <span class="font-medium">{{ $option['label'] }}</span>
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Example 2: Multiple Selection Chips

```html
<livewire:async-select 
    :multiple="true" 
    :options="$teamMembers"
>
    {{-- Dropdown options --}}
    <livewire:slot name="slot">
        <div class="flex items-center gap-3">
            <img src="{{ $option['image'] }}" class="w-8 h-8 rounded-full">
            <div>
                <div class="font-semibold">{{ $option['label'] }}</div>
                <div class="text-xs text-gray-500">{{ $option['role'] }}</div>
            </div>
            @if ($isSelected)
                <span class="text-green-500 text-sm">✓</span>
            @endif
        </div>
    </livewire:slot>
    
    {{-- Chip display for selected items --}}
    <livewire:slot name="selectedSlot">
        <span class="font-medium">{{ $option['label'] }}</span>
        <span class="text-xs opacity-75">({{ $option['role'] }})</span>
    </livewire:slot>
</livewire:async-select>
```

### Example 3: Tag-Style Display

```html
<livewire:async-select :multiple="true" :options="$tags">
    <livewire:slot name="selectedSlot">
        <div class="flex items-center gap-1">
            @if (isset($option['color']))
                <span class="w-2 h-2 rounded-full" style="background-color: {{ $option['color'] }}"></span>
            @endif
            <span>{{ $option['label'] }}</span>
        </div>
    </livewire:slot>
</livewire:async-select>
```

### Available Variables in Selected Slot

| Variable | Type | Description |
|----------|------|-------------|
| `$option` | array | The selected option data (includes value, label, and any custom fields) |

## Complete Real-World Example

Here's a complete example with a Livewire component:

**Livewire Component:**

```php
<?php
// app/Livewire/TeamBuilder.php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class TeamBuilder extends Component
{
    public $teamLeader = null;
    public $teamMembers = [];
    
    public function render()
    {
        $users = User::active()->get()->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'email' => $user->email,
            'image' => $user->avatar_url,
            'role' => $user->role,
            'department' => $user->department
        ])->toArray();
        
        return view('livewire.team-builder', [
            'users' => $users
        ]);
    }
    
    public function save()
    {
        $this->validate([
            'teamLeader' => 'required',
            'teamMembers' => 'required|array|min:1'
        ]);
        
        // Save team logic...
        session()->flash('message', 'Team saved successfully!');
    }
}
```

**Blade View:**

```html
<!-- resources/views/livewire/team-builder.blade.php -->

<div class="max-w-2xl mx-auto p-6 space-y-6">
    <form wire:submit="save">
        {{-- Single Selection: Team Leader --}}
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Team Leader <span class="text-red-500">*</span>
            </label>
            
            <livewire:async-select
                name="team_leader"
                wire:model="teamLeader"
                :options="$users"
                placeholder="Select team leader..."
            >
                {{-- Option in dropdown --}}
                <livewire:slot name="slot">
                    <div class="flex items-center gap-3">
                        <img 
                            src="{{ $option['image'] }}" 
                            class="w-10 h-10 rounded-full border-2 {{ $isSelected ? 'border-blue-500' : 'border-gray-200' }}"
                            alt="{{ $option['label'] }}"
                        >
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">
                                {{ $option['label'] }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $option['email'] }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-medium text-gray-700">
                                {{ $option['role'] }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $option['department'] }}
                            </div>
                        </div>
                    </div>
                </livewire:slot>
                
                {{-- Selected display --}}
                <livewire:slot name="selectedSlot">
                    <div class="flex items-center gap-2">
                        <img src="{{ $option['image'] }}" class="w-6 h-6 rounded-full">
                        <span class="font-medium">{{ $option['label'] }}</span>
                        <span class="text-sm text-gray-500">({{ $option['role'] }})</span>
                    </div>
                </livewire:slot>
            </livewire:async-select>
            
            @error('teamLeader')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        {{-- Multiple Selection: Team Members --}}
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
                Team Members <span class="text-red-500">*</span>
            </label>
            
            <livewire:async-select
                name="team_members[]"
                wire:model="teamMembers"
                :options="$users"
                :multiple="true"
                :max-selections="10"
                placeholder="Add team members..."
            >
                {{-- Option in dropdown --}}
                <livewire:slot name="slot">
                    <div class="flex items-center gap-3">
                        <img 
                            src="{{ $option['image'] }}" 
                            class="w-8 h-8 rounded-full"
                            alt="{{ $option['label'] }}"
                        >
                        <div class="flex-1">
                            <div class="font-semibold">{{ $option['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $option['department'] }}</div>
                        </div>
                        @if ($isSelected)
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                </livewire:slot>
                
                {{-- Chip display --}}
                <livewire:slot name="selectedSlot">
                    <span class="font-medium">{{ $option['label'] }}</span>
                </livewire:slot>
            </livewire:async-select>
            
            @error('teamMembers')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <div class="flex justify-end">
            <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Create Team
            </button>
        </div>
    </form>
</div>
```

## Best Practices

### 1. Keep It Simple
Don't over-complicate your slot templates. The simpler, the better for performance.

### 2. Use Conditional Rendering
Show different content based on state:

```html
<livewire:slot name="slot">
    <div class="flex items-center gap-2">
        <span :class="{ 'font-bold': isSelected, 'text-gray-400': isDisabled }">
            {{ $option['label'] }}
        </span>
    </div>
</livewire:slot>
```

### 3. Optimize Images
Use appropriately sized images to avoid performance issues:

```html
<img src="{{ $option['thumbnail'] ?? $option['image'] }}" class="w-8 h-8">
```

### 4. Handle Missing Data
Always check if optional fields exist:

```html
@if (isset($option['subtitle']))
    <div class="text-sm text-gray-500">{{ $option['subtitle'] }}</div>
@endif
```

### 5. Match Styling Between Slots
Ensure `slot` and `selectedSlot` have consistent styling:

```html
{{-- Both slots use similar image sizes and layout --}}
<livewire:slot name="slot">
    <div class="flex items-center gap-2">
        <img src="{{ $option['image'] }}" class="w-8 h-8 rounded-full">
        <span>{{ $option['label'] }}</span>
    </div>
</livewire:slot>

<livewire:slot name="selectedSlot">
    <div class="flex items-center gap-2">
        <img src="{{ $option['image'] }}" class="w-6 h-6 rounded-full">
        <span>{{ $option['label'] }}</span>
    </div>
</livewire:slot>
```

## Tips for Multiple Selection

When using multiple selection with custom chips:

1. **Keep chips compact** - Use smaller images and shorter text
2. **Limit information** - Show only essential data in chips
3. **Consider truncation** - Long labels should be truncated

```html
<livewire:slot name="selectedSlot">
    <span class="truncate max-w-[150px]">{{ $option['label'] }}</span>
</livewire:slot>
```

## Suffix Button with Modals

::: tip Version 1.1.0 Feature
When using a suffix button with custom slots, the dropdown automatically closes when the button is clicked. This is especially useful when opening modals that provide data through slots.
:::

The suffix button is perfect for opening modals to add or select items. When clicked, it automatically closes the dropdown, ensuring a clean user experience when modals open.

### Example: Opening a Modal to Add Items

**Livewire Component:**

```php
<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class MediaSelector extends Component
{
    public $selectedMedia = [];
    public $showAddModal = false;
    
    #[On('showAddMediaModal')]
    public function showAddMediaModal()
    {
        $this->showAddModal = true;
    }
    
    #[On('mediaAdded')]
    public function handleMediaAdded($mediaId, $mediaLabel, $mediaImage)
    {
        // Add the new media to selection
        if (!in_array($mediaId, $this->selectedMedia)) {
            $this->selectedMedia[] = $mediaId;
        }
    }
    
    public function render()
    {
        return view('livewire.media-selector');
    }
}
```

**Blade View:**

```html
<div>
    <livewire:async-select
        wire:model="selectedMedia"
        :multiple="true"
        endpoint="/api/media/search"
        :suffix-button="true"
        suffix-button-action="showAddMediaModal"
        placeholder="Select media or add new..."
    >
        {{-- Custom slot for selected items --}}
        <livewire:slot name="selectedSlot">
            <div class="flex items-center gap-2">
                <img src="{{ $option['image'] }}" class="w-6 h-6 rounded object-cover">
                <span class="font-medium">{{ $option['label'] }}</span>
            </div>
        </livewire:slot>
        
        {{-- Custom slot for dropdown options --}}
        <livewire:slot name="slot">
            <div class="flex items-center gap-3">
                <img src="{{ $option['image'] }}" class="w-10 h-10 rounded object-cover">
                <div>
                    <div class="font-semibold">{{ $option['label'] }}</div>
                    <div class="text-xs text-gray-500">{{ $option['size'] ?? 'N/A' }}</div>
                </div>
            </div>
        </livewire:slot>
    </livewire:async-select>
    
    {{-- Modal that opens when suffix button is clicked --}}
    @if ($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <h2 class="text-xl font-bold mb-4">Add New Media</h2>
                <!-- Modal content -->
                <button 
                    wire:click="$dispatch('mediaAdded', { mediaId: 1, mediaLabel: 'New Image', mediaImage: '/path/to/image.jpg' })"
                    class="px-4 py-2 bg-blue-600 text-white rounded"
                >
                    Add Media
                </button>
                <button 
                    wire:click="$set('showAddModal', false)"
                    class="px-4 py-2 bg-gray-300 rounded"
                >
                    Close
                </button>
            </div>
        </div>
    @endif
</div>
```

### How It Works

1. **User clicks suffix button** - The dropdown automatically closes
2. **Event is dispatched** - The `suffix-button-action` event is fired
3. **Modal opens** - Your Livewire component handles the event and opens the modal
4. **User adds item** - New items can be added to the selection
5. **Labels displayed** - Use `value-labels` to display new items without API calls

### Benefits

- **Clean UX** - Dropdown closes automatically when modal opens
- **No conflicts** - Prevents dropdown from interfering with modal
- **Better focus management** - Focus moves to modal instead of staying in dropdown
- **Seamless integration** - Works perfectly with custom slots

## Next Steps

- [View More Examples →](/guide/examples.html)
- [API Reference →](/guide/api.html)
- [Async Loading →](/guide/async-loading.html)
- [Setting Default Values →](/guide/default-values.html)

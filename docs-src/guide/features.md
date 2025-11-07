# Features

Livewire Async Select comes packed with features to handle any select use case.

## 🚀 Asynchronous Loading

Load options dynamically from API endpoints with built-in search and filtering.

```html
<livewire:async-select
    endpoint="/api/users/search"
    wire:model="userId"
    placeholder="Search users..."
/>
```

**Features:**
- Automatic debouncing
- Minimum search length configuration
- Loading states
- Error handling

[Learn more →](/guide/async-loading.html)

## 🔍 Search & Filter

Real-time search through local or remote options.

```html
<livewire:async-select
    :options="$largeDataset"
    :min-search-length="2"
    placeholder="Type to search..."
/>
```

**Features:**
- Client-side filtering for local options
- Server-side search for remote data
- Configurable minimum length
- Debounced input

## 🎯 Multiple Selection

Select multiple items with beautiful chip/tag display.

```html
<livewire:async-select
    wire:model="selectedItems"
    :options="$options"
    :multiple="true"
    placeholder="Select multiple..."
/>
```

**Features:**
- Visual chips for selected items
- Easy removal of individual selections
- Maximum selection limits
- Bulk selection support

[Learn more →](/guide/multiple-selection.html)

## 🏷️ Tags Mode

Create custom tags on-the-fly.

```html
<livewire:async-select
    wire:model="tags"
    :multiple="true"
    :tags="true"
    placeholder="Create tags..."
/>
```

**Features:**
- Create custom options
- Prevent duplicates
- Custom validation
- Mix predefined and custom options

## 🎨 Custom Rendering

Fully customize how options and selected items appear.

```html
<livewire:async-select :options="$users">
    <x-slot name="slot" :option="$option">
        <div class="flex items-center gap-2">
            <img src="{{ $option['avatar'] }}" class="w-8 h-8 rounded-full">
            <div>
                <div class="font-bold">{{ $option['label'] }}</div>
                <div class="text-sm text-gray-500">{{ $option['email'] }}</div>
            </div>
        </div>
    </x-slot>
</livewire:async-select>
```

[Learn more →](/guide/custom-slots.html)

## 🎭 Theme Support

Built-in support for Tailwind CSS and Bootstrap.

```html
<!-- Tailwind (default) -->
<livewire:async-select theme="tailwind" />

<!-- Bootstrap -->
<livewire:async-select theme="bootstrap" />
```

[Learn more →](/guide/themes.html)

## 📸 Image Support

Display images or avatars alongside option labels.

```html
<livewire:async-select
    :options="[
        ['value' => 1, 'label' => 'John', 'image' => '/avatars/john.jpg'],
        ['value' => 2, 'label' => 'Jane', 'image' => '/avatars/jane.jpg']
    ]"
/>
```

## 👥 Grouped Options

Organize options into labeled groups.

```html
<livewire:async-select
    :options="[
        ['value' => 'apple', 'label' => 'Apple', 'group' => 'Fruits'],
        ['value' => 'carrot', 'label' => 'Carrot', 'group' => 'Vegetables']
    ]"
/>
```

## 🚫 Disabled Options

Mark specific options as non-selectable.

```html
<livewire:async-select
    :options="[
        ['value' => '1', 'label' => 'Available'],
        ['value' => '2', 'label' => 'Sold Out', 'disabled' => true]
    ]"
/>
```

## 🗑️ Clearable

Allow users to clear their selection.

```html
<livewire:async-select
    wire:model="selection"
    :clearable="true"
/>
```

## ➕ Suffix Button

Add a customizable button on the right side of the select input. Perfect for adding new items, opening modals, or triggering custom actions.

```html
<livewire:async-select
    wire:model="selectedMedia"
    :options="$media"
    :suffix-button="true"
    suffix-button-action="showAddMediaModal"
    placeholder="Select Media..."
/>
```

**Features:**
- Customizable icon (defaults to plus icon)
- Dispatches Livewire events on click
- Styled to match the select input
- RTL support

**With Custom Icon:**

```html
<livewire:async-select
    wire:model="selectedMedia"
    :options="$media"
    :suffix-button="true"
    suffix-button-action="showTest"
    suffix-button-icon="<svg>...</svg>"
/>
```

**Listening to Events:**

```php
#[On('suffix-button-clicked')]
public function handleSuffixButton()
{
    // Handle button click
    $this->showModal = true;
}
```

Or with a custom action name:

```html
<livewire:async-select
    suffix-button-action="showAddMediaModal"
/>
```

```php
#[On('showAddMediaModal')]
public function showAddMediaModal()
{
    $this->showModal = true;
}
```

## ⌨️ Keyboard Navigation

Full keyboard support for accessibility:

- **Arrow Up/Down**: Navigate options
- **Enter**: Select highlighted option
- **Escape**: Close dropdown
- **Backspace**: Remove last selection (multiple mode)
- **Tab**: Navigate away

## 🔗 Two-way Binding

Native Livewire wire:model support:

```html
<livewire:async-select wire:model.live="value" />
```

Supports all wire:model modifiers:
- `wire:model.live`
- `wire:model.blur`
- `wire:model.defer`

## 📦 Collection Support

Automatically converts Laravel Collections to arrays:

```php
// No need to call ->toArray() or ->all()
$users = User::all()->map(fn($user) => [
    'value' => $user->id,
    'label' => $user->name
]);

// Pass directly to component
return view('form', ['users' => $users]);
```

```html
<livewire:async-select :options="$users" />
```

Works with:
- Eloquent Collections
- Support Collections
- Database Collections
- Lazy Collections

## 🎯 Extra Parameters

Pass additional parameters to API endpoints:

```html
<livewire:async-select
    endpoint="/api/cities"
    :extra-params="['country_id' => $countryId, 'active' => true]"
/>
```

## 📱 Responsive

Works perfectly on all screen sizes:
- Desktop
- Tablet
- Mobile devices

## ♿ Accessible

Built with accessibility in mind:
- ARIA attributes
- Keyboard navigation
- Screen reader support
- Focus management

## 🔄 Loading States

Visual feedback during async operations:
- Spinner during search
- Loading indicator
- Disabled state while fetching

## ✅ Validation

Full Laravel validation support:

```php
$this->validate([
    'selectedUser' => 'required|exists:users,id'
]);
```

## 🎨 Customizable

- Publishable views
- Configurable options
- Custom styling
- Slot-based rendering

## Performance Features

- **Debouncing**: Reduces API calls
- **Lazy Loading**: Load options on demand
- **Caching**: Optional result caching
- **Minimal Bundle**: Small JavaScript footprint

## Next Steps

- [Async Loading →](/guide/async-loading.html)
- [Multiple Selection →](/guide/multiple-selection.html)
- [Custom Slots →](/guide/custom-slots.html)
- [Themes →](/guide/themes.html)


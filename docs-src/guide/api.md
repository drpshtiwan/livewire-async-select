# API Reference

Complete reference for all component properties and methods.

## Component Properties

### Basic Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `name` | string | null | HTML input name attribute |
| `wire:model` | string | required | Livewire model binding |
| `value` | string\|int\|array | null | Default/pre-selected value(s). When using `wire:model`, this is optional as the component automatically uses the property value from `wire:model` (v1.1.0) |
| `placeholder` | string | 'Select an option' | Placeholder text |
| `ui` | string | config('async-select.ui', 'tailwind') | UI theme: 'tailwind' or 'bootstrap'. Uses config value if not provided |
| `error` | string | null | Validation error message to display |

### Data Source

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `options` | array\|Collection | [] | Static options array or Laravel Collection (auto-converted) |
| `endpoint` | string | null | API endpoint for async loading |
| `selected-endpoint` | string | null | Endpoint for loading selected items |
| `value-labels` | array | [] | Map of value => label (or value => [label, image]) for displaying labels **without making any API requests**. When provided, labels are displayed immediately without calling `selected-endpoint` or `endpoint` (v1.1.0) |

### Behavior

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `multiple` | boolean | false | Enable multiple selection |
| `clearable` | boolean | true | Show clear button |
| `tags` | boolean | false | Enable tag creation |
| `autoload` | boolean | false | Load options on mount |
| `searchable` | boolean | true | Enable search functionality. When `false`, search input fields are hidden and filtering is disabled |

### Search Configuration

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `min-search-length` | integer | 2 | Minimum characters before search |
| `search-param` | string | 'search' | Query parameter name for search |
| `selected-param` | string | 'selected' | Query parameter for selected items |

### Advanced

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `extra-params` | array | [] | Additional query parameters |
| `headers` | array | [] | Custom HTTP headers to send with requests (e.g., for authentication) |
| `use-internal-auth` | boolean | config('async-select.use_internal_auth', false) | Enable internal authentication for same-domain endpoints. Uses config value if not provided |
| `max-selections` | integer | 0 | Maximum selections (multiple mode). `0` means unlimited |
| `value-field` | string | null | Custom value field name |
| `label-field` | string | null | Custom label field name |

### Suffix Button

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `suffix-button` | boolean | false | Show a button on the right side of the input |
| `suffix-button-icon` | string | null | Custom icon HTML for the suffix button (defaults to plus icon) |
| `suffix-button-action` | string | null | Livewire event name to dispatch when button is clicked (defaults to 'suffix-button-clicked') |
| `suffix-button-action-params` | array | [] | Payload data passed with the suffix button event |

::: tip Version 1.1.0 Enhancement
When the suffix button is clicked, the dropdown automatically closes. This is especially useful when opening modals to add or select items, providing a cleaner user experience.
:::

## Working with Collections

The component automatically converts Laravel Collections to arrays. You can pass collections directly:

```php
// All of these work automatically
$options = User::all()->map(fn($user) => [
    'value' => $user->id,
    'label' => $user->name
]);

// Or use pluck
$options = Category::pluck('name', 'id');

// Or with models directly (auto-detected fields)
$options = User::all(); // Uses id/value and name/label/title fields

// Pass to component - no need to call ->toArray() or ->all()
return view('component', ['users' => $options]);
```

**In Blade:**

```html
<livewire:async-select 
    :options="$users"  {{-- Collection is automatically converted --}}
    wire:model="selectedUser"
/>
```

## Option Format

### Basic Option

```php
[
    'value' => '1',
    'label' => 'Option Label'
]
```

### With Image

```php
[
    'value' => '1',
    'label' => 'John Doe',
    'image' => 'https://example.com/avatar.jpg'
]
```

### With Group

```php
[
    'value' => 'apple',
    'label' => 'Apple',
    'group' => 'Fruits'
]
```

### Group Container Format

```php
[
    [
        'label' => 'Fruits',
        'options' => [
            ['value' => 'apple', 'label' => 'Apple'],
            ['value' => 'banana', 'label' => 'Banana'],
        ],
    ],
    [
        'label' => 'Vegetables',
        'options' => [
            ['value' => 'carrot', 'label' => 'Carrot'],
        ],
    ],
]
```

### Disabled Option

```php
[
    'value' => '1',
    'label' => 'Sold Out',
    'disabled' => true
]
```

### Custom Fields

```php
[
    'value' => '1',
    'label' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'Admin',
    'custom_field' => 'Any value'
]
```

## Slots

### Option Slot

```html
<livewire:slot name="slot">
    <!-- Custom option rendering -->
</livewire:slot>
```

### Selected Item Slot

```html
<livewire:slot name="selectedSlot">
    <!-- Custom selected item rendering -->
</livewire:slot>
```

Available variables inside option slot: `$option`, `$isSelected`, `$isDisabled`, `$multiple`.
Available variables inside selected slot: `$option`.

## Events

The component emits standard Livewire events through `wire:model`.

### Listen to Changes

```php
class MyComponent extends Component
{
    public $selectedValue;

    public function updated($property)
    {
        if ($property === 'selectedValue') {
            // Value changed
        }
    }
}
```

## Configuration File

All default values can be set in `config/async-select.php`:

```php
return [
    'placeholder' => 'Select an option',
    'min_search_length' => 2,
    'search_delay' => 300,
    'search_param' => 'search',
    'selected_param' => 'selected',
    'autoload' => false,
    'multiple' => false,
    'ui' => 'tailwind',
    'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', false),
];
```

### UI Theme Configuration

The default UI theme can be configured globally:

```php
'ui' => env('ASYNC_SELECT_UI', 'tailwind'),
```

This sets the default theme for all components. You can still override it per-component:

```html
<!-- Uses config default -->
<livewire:async-select :options="$options" />

<!-- Overrides config -->
<livewire:async-select :options="$options" ui="bootstrap" />
```

### Internal Authentication Configuration

Internal authentication can be enabled globally:

```php
'use_internal_auth' => env('ASYNC_SELECT_USE_INTERNAL_AUTH', false),
```

When enabled globally, all AsyncSelect components will automatically use internal authentication for internal endpoints:

```html
<!-- Uses config (internal auth enabled globally) -->
<livewire:async-select endpoint="/api/users" wire:model="userId" />

<!-- Overrides config (disables for this component) -->
<livewire:async-select 
    endpoint="/api/users" 
    wire:model="userId"
    :use-internal-auth="false"
/>
```

[Learn more about internal authentication →](/guide/authentication.html#internal-authentication)

## Environment Variables

```bash
ASYNC_SELECT_PLACEHOLDER="Select an option"
ASYNC_SELECT_MIN_SEARCH_LENGTH=2
ASYNC_SELECT_SEARCH_DELAY=300
ASYNC_SELECT_SEARCH_PARAM=search
ASYNC_SELECT_SELECTED_PARAM=selected
ASYNC_SELECT_AUTOLOAD=false
ASYNC_SELECT_MULTIPLE=false
ASYNC_SELECT_UI=tailwind

# Internal Authentication (Optional)
ASYNC_SELECT_USE_INTERNAL_AUTH=false
ASYNC_SELECT_INTERNAL_SECRET=your-base64-encoded-secret
ASYNC_SELECT_INTERNAL_PREVIOUS_SECRET=old-secret-for-key-rotation
ASYNC_SELECT_INTERNAL_NONCE_TTL=120
ASYNC_SELECT_INTERNAL_SKEW=60
```

### Generating Internal Auth Secret

Use the artisan command to generate a secure secret:

```bash
php artisan async-select:generate-secret
```

This automatically adds `ASYNC_SELECT_INTERNAL_SECRET` to your `.env` file.

## Authentication

The component supports custom headers and internal authentication:

- **Custom Headers**: Pass authentication tokens or custom headers with requests
- **Internal Auth**: Automatically authenticate requests to same-domain endpoints

[Learn more →](/guide/authentication.html)

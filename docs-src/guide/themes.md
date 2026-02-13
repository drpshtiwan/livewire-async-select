# Themes & Styling

Livewire Async Select supports multiple UI themes and uses Tailwind CSS with a custom `las-` prefix to avoid conflicts with your application's styles.

## UI Themes

The component supports two UI themes:
- **Tailwind** (default) - Modern, clean design using Tailwind CSS
- **Bootstrap** - Classic Bootstrap styling

### Bootstrap Version Support

- **Bootstrap 5+**: use the default `@asyncSelectStyles`
- **Bootstrap 4**: include both `@asyncSelectStyles` and `@asyncSelectBootstrapV4Styles`

```blade
@asyncSelectStyles
@asyncSelectBootstrapV4Styles
```

Manual include:

```html
<link rel="stylesheet" href="{{ asset('vendor/async-select/async-select.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/async-select/async-select-bootstrap-v4.css') }}">
```

### How Views Are Loaded

The component automatically loads the appropriate view based on the `ui` property:

- When `ui="tailwind"` (or not specified), it loads: `async-select::livewire.async-select`
- When `ui="bootstrap"`, it loads: `async-select::livewire.async-select-bootstrap`

The view files are located in:
- `resources/views/livewire/async-select.blade.php` (Tailwind theme)
- `resources/views/livewire/async-select-bootstrap.blade.php` (Bootstrap theme)

### Setting the Theme

You can set the theme per-component:

```html
<!-- Tailwind (default) -->
<livewire:async-select :options="$options" ui="tailwind" />

<!-- Bootstrap -->
<livewire:async-select :options="$options" ui="bootstrap" />
```

### Global Configuration

Set the default theme globally in `config/async-select.php`:

```php
return [
    'ui' => env('ASYNC_SELECT_UI', 'tailwind'),
];
```

Or via environment variable:

```bash
ASYNC_SELECT_UI=tailwind
```

When configured globally, all components will use this theme by default. You can still override it per-component:

```html
<!-- Uses config default (e.g., 'tailwind') -->
<livewire:async-select :options="$options" />

<!-- Overrides config -->
<livewire:async-select :options="$options" ui="bootstrap" />
```

**Benefits of global configuration:**
- ✅ Consistent theme across all components
- ✅ Easy to switch themes app-wide
- ✅ Still allows per-component overrides
- ✅ Environment-based configuration

## Pre-built Styles

The component comes with pre-compiled Tailwind CSS that includes all necessary styles. You don't need to have Tailwind CSS in your project - the styles are self-contained and ready to use.

### Class Prefix

All CSS classes are prefixed with `las-` (Livewire Async Select):

```html
<!-- Example classes used internally -->
<div class="las-flex las-items-center las-justify-between">
  <span class="las-text-sm las-text-gray-700">Option</span>
</div>
```

This prefix ensures that the component's styles won't conflict with your application's Tailwind configuration or custom CSS.

## Benefits of the Prefix

- ✅ **No Style Conflicts** - Component styles are isolated
- ✅ **Works Everywhere** - Use with any CSS framework
- ✅ **No Build Required** - Pre-compiled and ready to use
- ✅ **Consistent Look** - Same appearance across all projects

## Custom Styling

If you want to customize the component's appearance, you have several options:

### Option 1: Custom Slots

Use slots to render custom HTML for options and selected items:

```html
<livewire:async-select name="user_id" :endpoint="$endpoint">
    <x-slot name="option" let:option="option">
        <div class="flex items-center gap-2">
            <img src="{{ $option['avatar'] }}" class="w-6 h-6 rounded-full">
            <span class="font-medium">{{ $option['label'] }}</span>
        </div>
    </x-slot>
</livewire:async-select>
```

### Option 2: Publish and Modify Views

Publish the views to customize the structure:

```bash
php artisan vendor:publish --tag=async-select-views
```

This publishes all view templates to:
```
resources/views/vendor/async-select/livewire/
├── async-select.blade.php          (Tailwind theme)
└── async-select-bootstrap.blade.php (Bootstrap theme)
```

Once published, Laravel will automatically use your published views instead of the package views. You can then modify the HTML structure for each theme independently.

**Note:** When you publish views, both theme files are published. Modify the one that matches your `ui` setting, or customize both if you use multiple themes in your application.

### Option 3: Create a Custom Theme

You can create your own custom theme by following the view naming convention:

1. **Create your custom view file:**
   ```
   resources/views/vendor/async-select/livewire/async-select-mytheme.blade.php
   ```

2. **Use your custom theme:**
   ```html
   <livewire:async-select :options="$options" ui="mytheme" />
   ```

The component's `render()` method will automatically load `async-select::livewire.async-select-mytheme` when `ui="mytheme"` is specified.

**View Loading Logic:**
- If `ui="tailwind"`, loads: `async-select::livewire.async-select`
- If `ui="bootstrap"`, loads: `async-select::livewire.async-select-bootstrap`
- If `ui="mytheme"`, loads: `async-select::livewire.async-select-mytheme`

**Tip:** Start by copying one of the existing theme files (`async-select.blade.php` or `async-select-bootstrap.blade.php`) as a template for your custom theme.

### Option 4: Override Specific Styles

Add custom CSS to override specific styles:

```css
/* In your app.css */
.las-select-trigger {
    border-color: #your-color !important;
}

.las-option[aria-selected="true"] {
    background-color: #your-highlight-color !important;
}
```

## Color Customization

The component uses a primary color scheme. You can override the primary colors by adding custom CSS:

```css
:root {
    --las-primary-50: #your-color;
    --las-primary-100: #your-color;
    --las-primary-500: #your-color;
    --las-primary-600: #your-color;
    /* ... etc */
}
```

Or target specific elements:

```css
/* Change the focus ring color */
.las-select-trigger:focus {
    --tw-ring-color: #your-color !important;
}

/* Change selected option background */
.las-option[aria-selected="true"] {
    background-color: #your-color !important;
}

/* Change chip/tag colors in multiple mode */
.las-chip {
    background-color: #your-bg-color !important;
    color: #your-text-color !important;
}
```

## Dark Mode

To add dark mode support, you can override the styles conditionally:

```css
@media (prefers-color-scheme: dark) {
    .las-select-trigger {
        background-color: #1f2937;
        border-color: #374151;
        color: #f3f4f6;
    }
    
    .las-dropdown {
        background-color: #1f2937;
        border-color: #374151;
    }
    
    .las-option:hover {
        background-color: #374151;
    }
}
```

Or use a dark mode class:

```css
.dark .las-select-trigger {
    background-color: #1f2937;
    border-color: #374151;
    color: #f3f4f6;
}
```

## Building from Source

If you want to modify the source CSS and rebuild:

1. Clone the repository
2. Install dependencies:
   ```bash
   npm install
   ```

3. Modify source CSS:
   - `resources/css/async-select.css` (default styles)
   - `resources/css/async-select-bootstrap-v4.css` (Bootstrap 4 compatibility)

4. Build:
   ```bash
   npm run build
   ```

The compiled CSS will be in:
- `dist/async-select.css`
- `dist/async-select-bootstrap-v4.css`

See [BUILDING.md](https://github.com/drpshtiwan/livewire-async-select/blob/main/BUILDING.md) for more details.

## CSS File Size

The pre-compiled CSS is optimized and minified:

- **Minified**: ~15KB
- **Gzipped**: ~4KB

This small footprint ensures fast page loads while providing all the styles needed for the component.

## Browser Support

The component styles are compatible with:

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- iOS Safari (latest)
- Chrome Android (latest)

## Accessibility

The component includes ARIA attributes and keyboard navigation, with styles that support:

- Focus indicators
- Disabled states
- Selected states
- Hover states
- High contrast mode

All interactive elements are properly styled to indicate their state to users.

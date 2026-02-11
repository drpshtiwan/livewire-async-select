# Troubleshooting

Common issues and solutions.

## Component Not Found

**Error**: `Component [async-select] not found`

**Solution**:
```bash
php artisan livewire:discover
composer dump-autoload
php artisan config:clear
```

## Styles Not Working

**Issue**: Component appears unstyled

**Solution**:
- Ensure Tailwind CSS or Bootstrap is loaded
- Check your CSS build process
- Verify Alpine.js is loaded

## Alpine.js Errors

**Error**: `Alpine is not defined` or `Cannot read property 'data' of undefined`

**Solution**:
Livewire 3.3+ (including 4.x) includes Alpine.js by default. Make sure you're using Livewire 3.3+ or 4.x:

```bash
composer require livewire/livewire:^3.3
```

## Component Not Interactive

**Issue**: Dropdown doesn't open, search doesn't work, options can't be selected

**Most Common Cause**: Missing `@stack('scripts')` in your layout

**Solution**:
Add `@stack('scripts')` to your layout file **after** `@livewireScripts`:

```blade
<!DOCTYPE html>
<html>
<head>
    @asyncSelectStyles
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    @stack('scripts')  {{-- Required! --}}
</body>
</html>
```

The component uses this stack to register its Alpine.js component. Without it, the component will render but won't be interactive.

## AJAX Requests Failing

**Issue**: Endpoint not returning data

**Checklist**:
1. Verify endpoint URL is correct
2. Check response format matches required structure
3. Look at browser Network tab for errors
4. Ensure proper CORS headers if using separate API

## Options Not Updating

**Issue**: Component not reacting to changes in the `options` prop, especially when options are dynamically loaded or updated

**Solution**: Use the `key` attribute with a hash of your options to force Livewire to re-render the component when options change:

```html
<livewire:async-select
    name="selectedMedia"
    wire:model="selectedMedia"
    :options="$media"
    placeholder="Select Media..."
    :key="md5(json_encode($media))"
/>
```

This ensures that whenever the `$media` data changes, Livewire will detect the key change and completely re-render the component with the new options.

**Why this works**: Livewire's reactivity system sometimes doesn't detect deep changes in arrays or collections. By using a `key` attribute that changes when your options change, you explicitly tell Livewire to re-mount the component.

## Custom Slot Output Is Blank

**Issue**: Dropdown rows appear empty or you see fragment comments like `FRAGMENT:type=slot` in the HTML.

**Cause**: Slot syntax mismatch after upgrading Livewire.

**Solution**:
1. Use named slots without passing slot attributes:

```html
<livewire:async-select :options="$users">
    <livewire:slot name="slot">
        <span>{{ $option['label'] }}</span>
    </livewire:slot>

    <livewire:slot name="selectedSlot">
        <span>{{ $option['label'] }}</span>
    </livewire:slot>
</livewire:async-select>
```

2. Clear compiled views:

```bash
php artisan view:clear
php artisan optimize:clear
```

## Wire:model Not Working

**Issue**: Selected value not updating in Livewire component

**Solution**:
- Use `wire:model.live` for instant updates
- Check property is public in component
- Verify property name matches

## Tests Failing

Run tests with:
```bash
composer test
```

Clear caches:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Need More Help?

- [GitHub Issues](https://github.com/drpshtiwan/livewire-async-select/issues)
- [Discussion Board](https://github.com/drpshtiwan/livewire-async-select/discussions)

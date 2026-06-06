# Livewire Async Select

A powerful async select component for Laravel Livewire with Alpine.js - a modern, lightweight alternative to Select2.

[![Latest Version](https://img.shields.io/packagist/v/drpshtiwan/livewire-async-select.svg?style=flat-square)](https://packagist.org/packages/drpshtiwan/livewire-async-select)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/drpshtiwan/livewire-async-select/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/drpshtiwan/livewire-async-select/actions?query=workflow%3Atests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/drpshtiwan/livewire-async-select.svg?style=flat-square)](https://packagist.org/packages/drpshtiwan/livewire-async-select)
[![Code Style](https://img.shields.io/badge/code%20style-pint-orange?style=flat-square)](https://github.com/laravel/pint)
[![License](https://img.shields.io/github/license/drpshtiwan/livewire-async-select?style=flat-square)](LICENSE)

## 🎥 Demo

[![Livewire Async Select Demo](assets/async-select.png)](https://www.youtube.com/watch?v=xwfKgZu49gg)

**[▶️ Watch Full Demo on YouTube](https://www.youtube.com/watch?v=xwfKgZu49gg)**

## ✨ Features

- 🚀 **Asynchronous Loading** - Load options dynamically from API endpoints
- 🔍 **Search & Filter** - Built-in search with debouncing
- 🎯 **Multiple Selection** - Beautiful chip/tag display
- ⚡ **Alpine.js Powered** - Lightweight, no jQuery dependency
- 🎨 **Styled with Tailwind CSS** - Pre-built styles with `las-` prefix
- 🎭 **Custom Slots** - Fully customizable rendering
- 📦 **Easy Integration** - Native Livewire component
- 🔄 **Two-way Binding** - Full wire:model support
- 🔒 **No Style Conflicts** - All classes prefixed with `las-`
- 🔐 **Authentication Support** - Custom headers and internal authentication
- 🛡️ **Secure Internal Auth** - Signed tokens with replay protection

## 📚 Documentation

**[📖 Full Documentation](https://livewire-select.thejano.com/)**

Complete guides, examples, and API reference available at:

### **[https://livewire-select.thejano.com/](https://livewire-select.thejano.com/)**

To build and view the documentation locally, see **[DOCS.md](DOCS.md)**.

## ⚡ Quick Install

1. **Install via Composer:**

```bash
composer require drpshtiwan/livewire-async-select
```

2. **Publish the CSS assets:**

```bash
php artisan vendor:publish --tag=async-select-assets
```

3. **Setup your layout (important!):**

```blade
<head>
    @asyncSelectStyles
    {{-- Optional: only if using Bootstrap 4 theme styling --}}
    {{-- @asyncSelectBootstrapV4Styles --}}
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    @stack('scripts')  {{-- Required! --}}
</body>
```

> **⚠️ Important:** The `@stack('scripts')` directive is required for the component to work properly.

## 🎯 Basic Usage

```blade
<livewire:async-select
    name="user_id"
    wire:model="selectedUser"
    endpoint="/api/users/search"
    placeholder="Search users..."
/>
```

### Custom Slots (Livewire 3.3+ / 4.x)

```blade
<livewire:async-select :options="$users" wire:model="selectedUser">
    <livewire:slot name="slot">
        <div>{{ $option['label'] }}</div>
    </livewire:slot>

    <livewire:slot name="selectedSlot">
        <strong>{{ $option['label'] }}</strong>
    </livewire:slot>
</livewire:async-select>
```

Slot variables are provided automatically:
- option slot: `$option`, `$isSelected`, `$isDisabled`, `$multiple`
- selected slot: `$option`

**API Route with async-auth middleware:**

```php
// Default guard (web)
Route::middleware(['async-auth'])->get('/api/users/search', function (Request $request) {
    $users = User::where('name', 'like', "%{$request->get('search')}%")
        ->limit(20)
        ->get();
    
    return response()->json(['data' => $users]);
});

// With Sanctum
Route::middleware(['async-auth:sanctum'])->get('/api/users/search', function (Request $request) {
    // Works with Sanctum tokens or internal auth
    $users = User::where('name', 'like', "%{$request->get('search')}%")
        ->limit(20)
        ->get();
    
    return response()->json(['data' => $users]);
});

// With web guard and session persistence
Route::middleware(['web', 'async-auth:web,persist'])->get('/api/users/search', function (Request $request) {
    // Persists login in session
    $users = User::where('name', 'like', "%{$request->get('search')}%")
        ->limit(20)
        ->get();
    
    return response()->json(['data' => $users]);
});
```

The `async-auth` middleware is automatically registered and works exactly like `auth` middleware, but also handles internal authentication automatically when the `X-Internal-User` header is present. Supports all guards: `async-auth:web`, `async-auth:sanctum`, `async-auth:api`, etc.

**[→ View full documentation](https://livewire-select.thejano.com/)**

## 📋 Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, or 13.x
- Livewire 3.3+ or 4.x

## 🆚 Why This Package?

| Feature | Livewire Async Select | Select2 |
|---------|----------------------|---------|
| jQuery Dependency | ❌ No | ✅ Yes |
| Livewire Integration | ✅ Native | ⚠️ Manual |
| Bundle Size | 🟢 Small | 🟡 Large |
| Modern Stack | ✅ Yes | ❌ Legacy |

## 🤝 Contributing

Contributions are welcome! Please see the [documentation](https://drpshtiwan.github.io/livewire-async-select/) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 🙏 Credits

- [Dr. Pshtiwan Mahmood](https://github.com/drpshtiwan)
- [All Contributors](https://github.com/drpshtiwan/livewire-async-select/contributors)

## 🔗 Links

- **[📚 Documentation](https://livewire-select.thejano.com/)**
- **[📦 Packagist](https://packagist.org/packages/drpshtiwan/livewire-async-select)**
- **[🐛 Issues](https://github.com/drpshtiwan/livewire-async-select/issues)**
- **[💬 Discussions](https://github.com/drpshtiwan/livewire-async-select/discussions)**

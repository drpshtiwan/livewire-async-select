<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;

test('custom named slots can access local option data', function () {
    $html = Blade::render(<<<'BLADE'
<livewire:async-select :options="$options" value="2">
    <livewire:slot name="slot">
        @verbatim
            <div class="slot-option">{{ $option['label'] }}|{{ $option['role'] ?? 'none' }}|{{ $isSelected ? 'selected' : 'not-selected' }}|{{ $multiple ? 'multiple' : 'single' }}</div>
        @endverbatim
    </livewire:slot>

    <livewire:slot name="selectedSlot">
        @verbatim
            <div class="slot-selected">{{ $option['label'] }}|{{ $option['role'] ?? 'none' }}</div>
        @endverbatim
    </livewire:slot>
</livewire:async-select>
BLADE, [
        'options' => [
            ['value' => '1', 'label' => 'John Doe', 'role' => 'Admin'],
            ['value' => '2', 'label' => 'Jane Smith', 'role' => 'Manager'],
        ],
    ]);

    expect($html)->toContain('slot-option');
    expect($html)->toContain('John Doe|Admin|not-selected|single');
    expect($html)->toContain('Jane Smith|Manager|selected|single');
    expect($html)->toContain('slot-selected');
    expect($html)->toContain('Jane Smith|Manager');
});

test('custom named slots can access remote option data', function () {
    Http::fake([
        'https://example.com/users*' => Http::response([
            'data' => [
                ['id' => 10, 'text' => 'Remote User 1', 'sku' => 'SKU-10'],
                ['id' => 11, 'text' => 'Remote User 2', 'sku' => 'SKU-11'],
            ],
        ]),
    ]);

    $html = Blade::render(<<<'BLADE'
<livewire:async-select
    endpoint="https://example.com/users"
    :autoload="true"
    value-field="id"
    label-field="text"
>
    <livewire:slot name="slot">
        @verbatim
            <div class="remote-slot">{{ $option['label'] }}|{{ $option['sku'] ?? 'missing' }}</div>
        @endverbatim
    </livewire:slot>
</livewire:async-select>
BLADE);

    expect($html)->toContain('remote-slot');
    expect($html)->toContain('Remote User 1|SKU-10');
    expect($html)->toContain('Remote User 2|SKU-11');
});

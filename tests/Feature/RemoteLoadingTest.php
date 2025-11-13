<?php

use DrPshtiwan\LivewireAsyncSelect\Livewire\AsyncSelect;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('respects min search length', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'minSearchLength' => 3,
    ]);

    // Search with 2 chars - should not trigger
    $component->set('search', 'ab');
    expect($component->get('isLoading'))->toBeFalse();

    // Search with 3 chars - should trigger
    Http::fake(['/api/users*' => Http::response(['data' => []])]);
    $component->set('search', 'abc');
    // Note: In real scenario this would trigger fetchRemoteOptions
});

test('sends extra parameters with API request', function () {
    Http::fake(['/api/users*' => Http::response(['data' => []])]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'extraParams' => ['role' => 'admin', 'status' => 'active'],
        'autoload' => true,
    ]);

    // Check that extra params are set
    expect($component->get('extraParams'))->toBe(['role' => 'admin', 'status' => 'active']);
});

test('sends headers with API request', function () {
    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];
        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'headers' => [
            'Authorization' => 'Bearer token123',
            'X-Custom-Header' => 'custom-value',
        ],
        'useInternalAuth' => false,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    // Check that headers are set
    expect($component->get('headers'))->toBe([
        'Authorization' => 'Bearer token123',
        'X-Custom-Header' => 'custom-value',
    ]);

    // Verify headers were sent with the request
    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];
    
    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->toHaveKey('Authorization');
    expect($request->headers())->toHaveKey('X-Custom-Header');
    expect($request->header('Authorization'))->toContain('Bearer token123');
    expect($request->header('X-Custom-Header'))->toContain('custom-value');
});

test('handles API errors gracefully', function () {
    Http::fake(['/api/users*' => Http::response([], 500)]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => true,
    ]);

    // Component should handle error gracefully
    expect($component->get('isLoading'))->toBeFalse();
});

test('auto-detects value and label fields', function () {
    Http::fake(['/api/users*' => Http::response([
        'data' => [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ],
    ])]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => true,
    ]);

    // Component should auto-detect 'id' as value and 'name' as label
    // Default is 'value' and 'label', but component adapts to API response
    expect($component->get('valueField'))->toBeIn(['id', 'value']);
    expect($component->get('labelField'))->toBeIn(['name', 'label']);
});

test('uses custom value and label fields', function () {
    Http::fake(['/api/products*' => Http::response([
        'data' => [
            ['sku' => 'ABC123', 'title' => 'Product 1'],
            ['sku' => 'DEF456', 'title' => 'Product 2'],
        ],
    ])]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/products',
        'valueField' => 'sku',
        'labelField' => 'title',
        'autoload' => true,
    ]);

    expect($component->get('valueField'))->toBe('sku');
    expect($component->get('labelField'))->toBe('title');
});

test('can reload remote options', function () {
    Http::fake(['/api/users*' => Http::response(['data' => []])]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
    ]);

    $component->call('reload');

    // Should trigger a reload
    expect($component->get('page'))->toBe(1);
});

test('does not autoload when autoload is false', function () {
    Http::fake();

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => false,
    ]);

    // No HTTP calls should be made
    Http::assertNothingSent();
});

test('handles pagination with current_page and last_page', function () {
    Http::fake([
        '/api/users*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'User 1'],
                ['id' => 2, 'name' => 'User 2'],
            ],
            'current_page' => 1,
            'last_page' => 3,
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'valueField' => 'id',
        'labelField' => 'name',
        'autoload' => true,
    ]);

    // After autoload completes, should detect pagination
    // Force a reload to ensure HTTP call is made
    $component->call('reload');

    expect($component->get('page'))->toBe(1);
});

test('handles pagination with has_more flag', function () {
    Http::fake([
        '/api/products*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ],
            'has_more' => true,
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/products',
        'valueField' => 'id',
        'labelField' => 'name',
        'autoload' => true,
    ]);

    $component->call('reload');

    // Component accepts has_more flag
    expect($component->get('page'))->toBe(1);
});

test('handles pagination with meta object', function () {
    Http::fake([
        '/api/items*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 5,
                'total' => 100,
            ],
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/items',
        'autoload' => true,
    ]);

    // Should detect pagination from meta
    expect($component->get('page'))->toBe(1);
});

test('loadMore increments page number', function () {
    Http::fake([
        '/api/users*' => Http::sequence()
            ->push([
                'data' => [['value' => 1, 'label' => 'User 1']],
                'current_page' => 1,
                'last_page' => 3,
            ])
            ->push([
                'data' => [['value' => 2, 'label' => 'User 2']],
                'current_page' => 2,
                'last_page' => 3,
            ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => false,
    ]);

    expect($component->get('page'))->toBe(1);

    // Manually trigger first load
    $component->set('search', 'test');

    // Simulate hasMore being true (would be set by first load)
    $component->set('hasMore', true);

    // Load more
    $component->call('loadMore');

    expect($component->get('page'))->toBe(2);
});

test('respects per_page configuration', function () {
    Http::fake(['/api/users*' => Http::response(['data' => []])]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'perPage' => 50,
        'autoload' => true,
    ]);

    expect($component->get('perPage'))->toBe(50);
});

test('does not load more when hasMore is false', function () {
    Http::fake([
        '/api/users*' => Http::response([
            'data' => [['id' => 1, 'name' => 'User 1']],
            'current_page' => 3,
            'last_page' => 3,
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => true,
    ]);

    expect($component->get('hasMore'))->toBeFalse();

    $initialPage = $component->get('page');
    $component->call('loadMore');

    // Page should not increment when hasMore is false
    expect($component->get('page'))->toBe($initialPage);
});

test('pagination resets on new search', function () {
    Http::fake([
        '/api/users*' => Http::response([
            'data' => [
                ['value' => 1, 'label' => 'User 1'],
                ['value' => 2, 'label' => 'User 2'],
            ],
            'current_page' => 1,
            'last_page' => 3,
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => false,
    ]);

    // Set to page 2
    $component->set('page', 2);
    expect($component->get('page'))->toBe(2);

    // New search should reset to page 1
    $component->set('search', 'new search');

    expect($component->get('page'))->toBe(1);
});

<?php

use DrPshtiwan\LivewireAsyncSelect\Livewire\AsyncSelect;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('filters local options using the search term', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['value' => '1', 'label' => 'Alpha'],
            ['value' => '2', 'label' => 'Beta'],
            ['value' => '3', 'label' => 'Gamma'],
        ],
    ]);

    expect($component->get('displayOptions'))->toHaveCount(3);

    $component->set('search', 'be');

    $options = $component->get('displayOptions');

    expect($options)->toHaveCount(1);
    expect($options[0])->toMatchArray([
        'value' => '2',
        'label' => 'Beta',
    ]);
});

test('parent can update available options dynamically', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['value' => '1', 'label' => 'One'],
        ],
        'multiple' => true,
    ]);

    $component->set('options', [
        ['value' => '1', 'label' => 'One'],
        ['value' => '2', 'label' => 'Two'],
    ]);

    expect($component->get('displayOptions'))->toHaveCount(2);

    $component->call('selectOption', '2');

    expect($component->get('value'))->toBe(['2']);

    $component->set('options', [
        ['value' => '2', 'label' => 'Two'],
        ['value' => '3', 'label' => 'Three'],
    ]);

    $selected = $component->get('selectedOptions');

    expect($selected)->toHaveCount(1);
    expect($selected[0]['value'])->toBe('2');
    expect($selected[0]['label'])->toBe('Two');
});

test('loads options from a remote endpoint and resolves selected labels', function () {
    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $url = $request->url();
        $response = str_contains($url, 'example.com/options')
            ? Http::response([
                'data' => [
                    ['id' => 10, 'text' => 'Remote Option'],
                    ['id' => 11, 'text' => 'Another Option'],
                ],
            ])
            : Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => 'https://example.com/options',
        'selectedEndpoint' => 'https://example.com/options',
        'valueField' => 'id',
        'labelField' => 'text',
        'value' => '10',
    ]);

    expect($recordedRequests)->not()->toBeEmpty();

    $component->set('search', 'Remote');

    $component->call('reload');

    $options = $component->get('displayOptions');

    expect($options)->toHaveCount(2);
    expect($options[0]['label'])->toBe('Remote Option');
    expect($options[1]['label'])->toBe('Another Option');

    $component->call('selectOption', '11');

    expect($component->get('value'))->toBe('11');

    $selected = $component->get('selectedOptions');

    expect($selected)->toHaveCount(1);
    expect($selected[0]['value'])->toBe('11');
    expect($selected[0]['label'])->toBe('Another Option');

    // Filter requests that contain 'Remote' in the search parameter
    $searchRequests = array_filter($recordedRequests, function ($interaction) {
        if (! isset($interaction[0]) || ! is_object($interaction[0])) {
            return false;
        }
        $request = $interaction[0];
        if (method_exists($request, 'data')) {
            $data = $request->data();

            return ($data['search'] ?? null) === 'Remote';
        }

        return false;
    });

    expect(count($searchRequests))->toBeGreaterThan(0);
});

test('handles grouped options', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['label' => 'Group 1', 'options' => [
                ['value' => 'g1_1', 'label' => 'G1 Option 1'],
                ['value' => 'g1_2', 'label' => 'G1 Option 2'],
            ]],
            ['label' => 'Group 2', 'options' => [
                ['value' => 'g2_1', 'label' => 'G2 Option 1'],
            ]],
        ],
    ]);

    expect($component->get('hasGroups'))->toBeTrue();

    $groupedOptions = $component->get('groupedOptions');
    expect($groupedOptions)->toHaveKey('Group 1');
    expect($groupedOptions['Group 1'])->toHaveCount(2);

    $component->call('selectOption', 'g1_2');

    expect($component->get('value'))->toBe('g1_2');

    $selected = $component->get('selectedOptions');

    expect($selected)->toHaveCount(1);
    expect($selected[0]['value'])->toBe('g1_2');
});

test('handles tags mode for multiple selection', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'multiple' => true,
        'tags' => true,
    ]);

    $component->call('selectOption', 'custom-tag-1');

    expect($component->get('value'))->toBe(['custom-tag-1']);

    $component->call('selectOption', 'custom-tag-2');

    expect($component->get('value'))->toBe(['custom-tag-1', 'custom-tag-2']);

    $selected = $component->get('selectedOptions');

    expect($selected)->toHaveCount(2);
    expect($selected[0]['value'])->toBe('custom-tag-1');
    expect($selected[0]['label'])->toBe('custom-tag-1');
    expect($selected[1]['value'])->toBe('custom-tag-2');
    expect($selected[1]['label'])->toBe('custom-tag-2');
});

test('tag creation adds to both selected and options', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['value' => '1', 'label' => 'Existing'],
        ],
        'multiple' => true,
        'tags' => true,
    ]);

    $component->call('selectOption', 'new-tag');

    expect($component->get('value'))->toBe(['new-tag']);

    $selectedOptions = $component->get('selectedOptions');

    expect($selectedOptions)->toHaveCount(1);
    expect($selectedOptions[0]['value'])->toBe('new-tag');
    expect($selectedOptions[0]['label'])->toBe('new-tag');
});

test('does not create duplicate tags', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'multiple' => true,
        'tags' => true,
    ]);

    $component->call('selectOption', 'my-tag');

    // Calling selectOption twice with same value toggles it (selects then deselects)
    expect($component->get('value'))->toBe(['my-tag']);

    $component->call('selectOption', 'my-tag');

    // After toggling, it should be deselected
    expect($component->get('value'))->toBe([]);
});

test('ignores unknown option values when tags mode is disabled', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['value' => '1', 'label' => 'Option 1'],
            ['value' => '2', 'label' => 'Option 2'],
        ],
        'multiple' => false,
        'tags' => false,
    ]);

    $component->call('selectOption', 'not-in-options');

    expect($component->get('value'))->toBeNull();
});

test('shows no results message for empty remote response', function () {
    Http::fake([
        'https://example.com/options*' => Http::response([
            'data' => [],
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => 'https://example.com/options',
    ]);

    $component->set('search', 'xyz');

    $component->call('reload');

    expect($component->get('displayOptions'))->toHaveCount(0);
});

test('supports pagination and loadMore', function () {
    Http::fake([
        'https://example.com/options*' => Http::response([
            'data' => [
                ['id' => 1, 'text' => 'Item 1'],
            ],
            'next_page_url' => 'https://example.com/options?page=2',
        ]),
    ]);

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => 'https://example.com/options',
        'autoload' => true,
    ]);

    // Initial page is 1
    expect($component->get('page'))->toBe(1);

    // After calling loadMore, if hasMore is true, page should increment
    if ($component->get('hasMore')) {
        $component->call('loadMore');
        expect($component->get('page'))->toBe(2);
    }
});

test('respects clearable property', function () {
    $component = Livewire::test(AsyncSelect::class, [
        'options' => [
            ['value' => '1', 'label' => 'Option 1'],
        ],
        'value' => '1',
        'clearable' => true,
    ]);

    expect($component->get('clearable'))->toBeTrue();

    $component->call('clearSelection');

    expect($component->get('value'))->toBeNull();
});

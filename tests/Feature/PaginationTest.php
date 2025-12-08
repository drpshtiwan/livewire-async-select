<?php

namespace DrPshtiwan\LivewireAsyncSelect\Tests\Feature;

use DrPshtiwan\LivewireAsyncSelect\Livewire\AsyncSelect;
use DrPshtiwan\LivewireAsyncSelect\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use ReflectionClass;

class PaginationTest extends TestCase
{
    /** @test */
    public function it_appends_pages_correctly_preserving_numeric_keys()
    {
        Http::fake([
            'api/users?search=John&page=1*' => Http::response([
                'data' => [
                    ['id' => '1', 'name' => 'John Doe'],
                ],
                'has_more' => true,
            ]),
            'api/users?search=John&page=2*' => Http::response([
                'data' => [
                    ['id' => '2', 'name' => 'John Smith'],
                ],
                'has_more' => false,
            ]),
        ]);

        $component = Livewire::test(AsyncSelect::class, [
            'endpoint' => 'api/users',
            'valueField' => 'id',
            'labelField' => 'name',
            'searchable' => true,
        ]);

        $component->set('search', 'John')
            ->assertSet('search', 'John');

        $component->call('loadMore');

        // Inspect valid internal state using Reflection as property is protected
        $instance = $component->instance();
        $reflection = new ReflectionClass($instance);
        $property = $reflection->getProperty('remoteOptionsMap');
        $property->setAccessible(true);
        $remoteOptionsMap = $property->getValue($instance);

        // With the bug (missing persistence), Page 1 (key '1') will be missing.
        $this->assertArrayHasKey('1', $remoteOptionsMap, 'Page 1 key "1" missing - replaced instead of appended');
        $this->assertArrayHasKey('2', $remoteOptionsMap, 'Page 2 key "2" missing');
    }
}

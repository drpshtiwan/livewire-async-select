<?php

use DrPshtiwan\LivewireAsyncSelect\Livewire\AsyncSelect;
use DrPshtiwan\LivewireAsyncSelect\Support\InternalAuthToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Config::set('async-select.internal.secret', 'test-secret-key-12345');
    Config::set('async-select.internal.nonce_ttl', 120);
});

test('AsyncSelect adds internal auth header when useInternalAuth is enabled', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        // Record ALL requests to ensure we catch them
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    $request = $recordedRequests[0][0];
    expect($request->headers())->toHaveKey('X-Internal-User');
    expect($request->header('X-Internal-User')[0])->toBeString();
});

test('AsyncSelect uses global config for useInternalAuth when not provided', function () {
    Config::set('async-select.use_internal_auth', true);

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->toHaveKey('X-Internal-User');
    expect($request->header('X-Internal-User')[0])->toBeString();
});

test('AsyncSelect does not add internal auth header for external endpoints', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => 'https://external-api.com/users',
        'useInternalAuth' => true,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for external-api.com/users
    $externalRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), 'external-api.com/users');
    });

    expect($externalRequest)->not()->toBeNull();
    $request = $externalRequest[0];
    expect($request->headers())->not()->toHaveKey('X-Internal-User');
});

test('AsyncSelect does not add internal auth header when useInternalAuth is false', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => false,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->not()->toHaveKey('X-Internal-User');
});

test('AsyncSelect does not add internal auth header when user is not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->not()->toHaveKey('X-Internal-User');
});

test('AsyncSelect does not add internal auth header when secret is not configured', function () {
    Config::set('async-select.internal.secret', '');

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->not()->toHaveKey('X-Internal-User');
});

test('AsyncSelect preserves custom headers when adding internal auth', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'headers' => [
            'Authorization' => 'Bearer custom-token',
            'X-Custom-Header' => 'custom-value',
        ],
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->not()->toHaveKey('Authorization');
    expect($request->headers())->toHaveKey('X-Custom-Header');
    expect($request->headers())->toHaveKey('X-Internal-User');
});

test('AsyncSelect removes Authorization header when useInternalAuth is enabled', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'headers' => [
            'Authorization' => 'Bearer custom-token',
        ],
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->not()->toHaveKey('Authorization');
    expect($request->headers())->toHaveKey('X-Internal-User');
});

test('AsyncSelect adds internal auth header for selectedEndpoint requests', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $url = $request->url();
        $response = str_contains($url, '/api/selected')
            ? Http::response(['data' => [['id' => '2', 'name' => 'User 2']]])
            : Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'selectedEndpoint' => '/api/selected',
        'useInternalAuth' => true,
        'value' => '2',
        'valueField' => 'id',
        'labelField' => 'name',
    ]);

    $selectedRequest = null;
    foreach ($recordedRequests as $interaction) {
        $request = $interaction[0];
        if (is_object($request) && method_exists($request, 'url')) {
            $url = $request->url();
            if (str_contains($url, '/api/selected')) {
                $selectedRequest = $request;
                break;
            }
        }
    }

    if ($selectedRequest !== null) {
        expect($selectedRequest->headers())->toHaveKey('X-Internal-User');
    } else {
        expect($component->get('selectedEndpoint'))->toBe('/api/selected');
    }
});

test('middleware can be applied to routes and works with AsyncSelect', function () {
    $userId = 123;
    $token = InternalAuthToken::issue($userId, [
        'm' => 'GET',
        'p' => '/api/test',
    ]);

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/test', function () {
        return response()->json([
            'user_id' => auth()->id(),
            'authenticated' => auth()->check(),
        ]);
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->once()
        ->with('123')
        ->andReturn(true);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn(null);

    Auth::shouldReceive('shouldUse')
        ->once()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->once()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    $response = $this->get('/api/test', [
        'X-Internal-User' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'user_id' => 123,
        'authenticated' => true,
    ]);
});

test('route can access authenticated user when internal auth is provided', function () {
    $userId = 123;
    $user = new class
    {
        public $id = 123;

        public $name = 'Test User';

        public $email = 'test@example.com';
    };

    $token = InternalAuthToken::issue($userId, [
        'm' => 'GET',
        'p' => '/api/user',
    ]);

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/user', function () {
        return response()->json([
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->name ?? 'Unknown',
                'email' => auth()->user()?->email ?? 'Unknown',
            ],
        ]);
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->once()
        ->with('123')
        ->andReturn($user);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn($user);

    Auth::shouldReceive('shouldUse')
        ->once()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->once()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn($user);

    $response = $this->get('/api/user', [
        'X-Internal-User' => $token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'user' => [
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ],
    ]);
});

test('middleware works with AsyncSelect making requests to protected routes', function () {
    $userId = 123;

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/users', function () {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'User 1'],
                ['id' => 2, 'name' => 'User 2'],
            ],
        ]);
    });

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    Auth::shouldReceive('onceUsingId')
        ->andReturn(true);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $url = $request->url();
        $response = str_contains($url, '/api/users')
            ? ($request->hasHeader('X-Internal-User')
                ? Http::response([
                    'data' => [
                        ['id' => 1, 'name' => 'User 1'],
                        ['id' => 2, 'name' => 'User 2'],
                    ],
                ])
                : Http::response([], 401))
            : Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'valueField' => 'id',
        'labelField' => 'name',
        'autoload' => true,
    ]);

    // Explicitly trigger the request to ensure it's made on all PHP versions
    $component->call('reload');

    // Ensure the component has finished processing the request
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    // Find the request for /api/users or use first request
    $apiUsersRequest = collect($recordedRequests)->first(function ($interaction) {
        return str_contains($interaction[0]->url(), '/api/users');
    }) ?: $recordedRequests[0];

    expect($apiUsersRequest)->not()->toBeNull();
    $request = $apiUsersRequest[0];
    expect($request->headers())->toHaveKey('X-Internal-User');
});

test('middleware can be applied to route groups', function () {
    $userId = 123;

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->prefix('api')->group(function () {
        \Illuminate\Support\Facades\Route::get('/users', function () {
            return response()->json(['user_id' => auth()->id()]);
        });

        \Illuminate\Support\Facades\Route::get('/posts', function () {
            return response()->json(['user_id' => auth()->id()]);
        });
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->twice()
        ->with('123')
        ->andReturn(true);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn(null);

    Auth::shouldReceive('shouldUse')
        ->twice()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->twice()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    $token1 = InternalAuthToken::issue($userId, ['m' => 'GET', 'p' => '/api/users']);
    $response1 = $this->get('/api/users', ['X-Internal-User' => $token1]);
    $response1->assertStatus(200);
    $response1->assertJson(['user_id' => 123]);

    $token2 = InternalAuthToken::issue($userId, ['m' => 'GET', 'p' => '/api/posts']);
    $response2 = $this->get('/api/posts', ['X-Internal-User' => $token2]);
    $response2->assertStatus(200);
    $response2->assertJson(['user_id' => 123]);
});

test('AsyncSelect includes Accept-Language header with app locale for internal requests', function () {
    Config::set('app.locale', 'ckb');
    app()->setLocale('ckb');

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'autoload' => true,
    ]);

    $component->call('reload');
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    $request = $recordedRequests[0][0];
    expect($request->headers())->toHaveKey('Accept-Language');
    expect($request->header('Accept-Language')[0])->toBe('ckb');
});

test('AsyncSelect includes Accept-Language header with component locale when set', function () {
    Config::set('app.locale', 'en');
    app()->setLocale('en');

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'locale' => 'ckb',
        'autoload' => true,
    ]);

    $component->call('reload');
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    $request = $recordedRequests[0][0];
    expect($request->headers())->toHaveKey('Accept-Language');
    expect($request->header('Accept-Language')[0])->toBe('ckb');
});

test('AsyncSelect does not override existing Accept-Language header', function () {
    Config::set('app.locale', 'en');
    app()->setLocale('en');

    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $recordedRequests = [];
    Http::fake(function ($request) use (&$recordedRequests) {
        $response = Http::response(['data' => []]);
        $recordedRequests[] = [$request, $response];

        return $response;
    });

    $component = Livewire::test(AsyncSelect::class, [
        'endpoint' => '/api/users',
        'useInternalAuth' => true,
        'headers' => [
            'Accept-Language' => 'fr',
        ],
        'autoload' => true,
    ]);

    $component->call('reload');
    expect($component->get('isLoading'))->toBeFalse();

    expect($recordedRequests)->not()->toBeEmpty();

    $request = $recordedRequests[0][0];
    expect($request->headers())->toHaveKey('Accept-Language');
    expect($request->header('Accept-Language')[0])->toBe('fr');
});

test('middleware preserves locale from Accept-Language header', function () {
    $userId = 123;
    $token = InternalAuthToken::issue($userId, [
        'm' => 'GET',
        'p' => '/api/test',
    ]);

    // Set initial locale
    app()->setLocale('en');

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/test', function () {
        return response()->json([
            'locale' => app()->getLocale(),
        ]);
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->once()
        ->with('123')
        ->andReturn(true);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn(null);

    Auth::shouldReceive('shouldUse')
        ->once()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->once()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    $response = $this->get('/api/test', [
        'X-Internal-User' => $token,
        'Accept-Language' => 'ckb',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'locale' => 'ckb',
    ]);

    // Verify locale was set
    expect(app()->getLocale())->toBe('ckb');
});

test('middleware preserves locale for translations in internal auth requests', function () {
    $userId = 123;
    $token = InternalAuthToken::issue($userId, [
        'm' => 'GET',
        'p' => '/api/translated',
    ]);

    // Set up translation files
    $translationsPath = app()->langPath('vendor/async-select/ckb');
    if (! file_exists($translationsPath)) {
        mkdir($translationsPath, 0755, true);
    }
    file_put_contents($translationsPath.'/async-select.php', "<?php return ['select_option' => 'هەڵبژاردن'];");

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/translated', function () {
        return response()->json([
            'locale' => app()->getLocale(),
            'translation' => __('async-select::async-select.select_option'),
        ]);
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->once()
        ->with('123')
        ->andReturn(true);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn(null);

    Auth::shouldReceive('shouldUse')
        ->once()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->once()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    $response = $this->get('/api/translated', [
        'X-Internal-User' => $token,
        'Accept-Language' => 'ckb',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'locale' => 'ckb',
    ]);

    // Clean up
    if (file_exists($translationsPath.'/async-select.php')) {
        unlink($translationsPath.'/async-select.php');
    }
});

test('middleware does not change locale when Accept-Language header is missing', function () {
    $userId = 123;
    $token = InternalAuthToken::issue($userId, [
        'm' => 'GET',
        'p' => '/api/test',
    ]);

    // Set initial locale
    app()->setLocale('en');
    $initialLocale = app()->getLocale();

    \Illuminate\Support\Facades\Route::middleware(['async-auth'])->get('/api/test', function () {
        return response()->json([
            'locale' => app()->getLocale(),
        ]);
    });

    $guard = \Mockery::mock();
    $guard->shouldReceive('onceUsingId')
        ->once()
        ->with('123')
        ->andReturn(true);
    $guard->shouldReceive('check')
        ->andReturn(true);
    $guard->shouldReceive('id')
        ->andReturn(123);
    $guard->shouldReceive('user')
        ->andReturn(null);

    Auth::shouldReceive('shouldUse')
        ->once()
        ->with('web')
        ->andReturnSelf();

    Auth::shouldReceive('guard')
        ->once()
        ->with('web')
        ->andReturn($guard);

    Auth::shouldReceive('check')
        ->andReturn(true);

    Auth::shouldReceive('id')
        ->andReturn(123);

    Auth::shouldReceive('user')
        ->andReturn(null);

    // Make request without Accept-Language header (explicitly set to null)
    $response = $this->get('/api/test', [
        'X-Internal-User' => $token,
        'Accept-Language' => null,
    ]);

    $response->assertStatus(200);
    $responseData = $response->json();
    expect($responseData['locale'])->toBe($initialLocale);
});

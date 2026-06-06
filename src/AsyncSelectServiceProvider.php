<?php

namespace DrPshtiwan\LivewireAsyncSelect;

use DrPshtiwan\LivewireAsyncSelect\Console\GenerateInternalSecretCommand;
use DrPshtiwan\LivewireAsyncSelect\Http\Middleware\InternalAuthenticate;
use DrPshtiwan\LivewireAsyncSelect\Livewire\AsyncSelect;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AsyncSelectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/async-select.php', 'async-select');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'async-select');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'async-select');

        if (class_exists(Livewire::class)) {
            Livewire::component('async-select', AsyncSelect::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateInternalSecretCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/async-select.php' => config_path('async-select.php'),
        ], 'async-select-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/async-select'),
        ], 'async-select-views');

        $this->publishes([
            __DIR__.'/../dist/async-select.css' => public_path('vendor/async-select/async-select.css'),
            __DIR__.'/../dist/async-select-bootstrap-v4.css' => public_path('vendor/async-select/async-select-bootstrap-v4.css'),
        ], 'async-select-assets');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/async-select'),
        ], 'async-select-lang');

        $this->registerBladeDirectives();
        $this->registerMiddleware();
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $middlewareClass = InternalAuthenticate::class;

        $router->aliasMiddleware('async-auth', $middlewareClass);
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('asyncSelectStyles', function () {
            return "<?php echo '<link rel=\"stylesheet\" href=\"' . asset('vendor/async-select/async-select.css') . '\">'; ?>";
        });

        Blade::directive('asyncSelectBootstrapV4Styles', function () {
            return "<?php echo '<link rel=\"stylesheet\" href=\"' . asset('vendor/async-select/async-select-bootstrap-v4.css') . '\">'; ?>";
        });
    }

    public static function getInternalAuthMiddlewareClass(): string
    {
        return InternalAuthenticate::class;
    }
}

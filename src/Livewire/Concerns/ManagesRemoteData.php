<?php

namespace DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns;

use Illuminate\Support\Facades\Http;
use Throwable;

trait ManagesRemoteData
{
    /**
     * Normalized remote options keyed by their value.
     *
     * @var array<string, array{value: string, label: string, image?: string}>
     */
    protected array $remoteOptionsMap = [];

    public function loadMore(): void
    {
        if ($this->endpoint === null || ! $this->hasMore || $this->isLoading) {
            return;
        }

        $this->page++;
        $this->fetchRemoteOptions($this->search, true);
    }

    public function reload(): void
    {
        if ($this->endpoint === null) {
            return;
        }

        $this->remoteOptionsMap = [];
        $this->fetchRemoteOptions($this->search);
    }

    protected function fetchRemoteOptions(?string $term, bool $append = false): void
    {
        if ($this->endpoint === null) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $http = Http::acceptJson()->timeout(5);
            
            if (property_exists($this, 'headers') && ! empty($this->headers)) {
                $http = $http->withHeaders($this->headers);
            }
            
            $response = $http->get($this->endpoint, array_merge($this->extraParams, [
                $this->searchParam => $term,
                'page' => $this->page,
                'per_page' => $this->perPage,
            ]));

            if (! $response->successful()) {
                $this->errorMessage = 'Failed to load options. Please try again.';
                if (! $append) {
                    $this->remoteOptionsMap = [];
                }

                return;
            }

            $payload = $response->json();
            $items = $this->extractOptionsFromPayload($payload);
            $normalized = $this->normalizeOptions($items);

            // Check for pagination metadata
            // Support both custom format and Laravel's standard pagination format
            if (isset($payload['has_more'])) {
                $this->hasMore = $payload['has_more'];
            } elseif (isset($payload['hasMore'])) {
                $this->hasMore = $payload['hasMore'];
            } elseif (isset($payload['current_page'], $payload['last_page'])) {
                // Laravel's standard pagination format
                $this->hasMore = $payload['current_page'] < $payload['last_page'];
            } elseif (isset($payload['meta']['total'])) {
                $total = $payload['meta']['total'];
                $currentCount = ($this->page * $this->perPage);
                $this->hasMore = $currentCount < $total;
            } else {
                $this->hasMore = false;
            }

            if ($append) {
                $this->remoteOptionsMap = array_merge($this->remoteOptionsMap, $normalized);
            } else {
                $this->remoteOptionsMap = $normalized;
            }

            $this->cacheOptions($normalized);
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Network error. Please check your connection.';
            if (! $append) {
                $this->remoteOptionsMap = [];
            }
        } finally {
            $this->isLoading = false;
        }
    }

    protected function fetchSelectedOptions(array $values, string $endpoint): void
    {
        if ($values === []) {
            return;
        }

        try {
            $http = Http::acceptJson()->timeout(5);
            
            if (property_exists($this, 'headers') && ! empty($this->headers)) {
                $http = $http->withHeaders($this->headers);
            }
            
            $response = $http->get($endpoint, array_merge($this->extraParams, [
                $this->selectedParam => implode(',', $values),
            ]));

            if (! $response->successful()) {
                return;
            }

            $payload = $response->json();
            $items = $this->extractOptionsFromPayload($payload);
            $normalized = $this->normalizeOptions($items);

            $this->cacheOptions($normalized);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    protected function ensureLabelsForSelected(): void
    {
        if (method_exists($this, 'processValueLabels')) {
            $this->processValueLabels();
        }

        $values = $this->selectedValues();
        $missing = array_values(array_filter($values, fn (string $value): bool => ! isset($this->optionCache[$value])));

        if ($missing === []) {
            return;
        }

        if (property_exists($this, 'valueLabels') && ! empty($this->valueLabels)) {
            $processed = [];
            foreach ($missing as $value) {
                $valueKey = $this->keyForValue($value);
                if ($valueKey === null) {
                    continue;
                }

                $labelData = null;

                if (isset($this->valueLabels[$valueKey])) {
                    $labelData = $this->valueLabels[$valueKey];
                } elseif (isset($this->valueLabels[$value])) {
                    $labelData = $this->valueLabels[$value];
                } else {
                    foreach ($this->valueLabels as $key => $data) {
                        $normalizedKey = $this->keyForValue($key);
                        if ($normalizedKey === $valueKey || (string) $normalizedKey === (string) $valueKey || (string) $normalizedKey === (string) $value || (string) $key === (string) $value || (string) $key === (string) $valueKey) {
                            $labelData = $data;
                            break;
                        }
                    }
                }

                if ($labelData === null) {
                    continue;
                }

                if (is_string($labelData) || is_numeric($labelData)) {
                    $processed[$valueKey] = [
                        'value' => $valueKey,
                        'label' => (string) $labelData,
                    ];
                } elseif (is_array($labelData)) {
                    $label = $labelData['label'] ?? $labelData['text'] ?? $valueKey;
                    $processed[$valueKey] = [
                        'value' => $valueKey,
                        'label' => (string) $label,
                    ];
                    if (isset($labelData['image'])) {
                        $processed[$valueKey]['image'] = (string) $labelData['image'];
                    }
                }
            }

            if (! empty($processed)) {
                $this->cacheOptions($processed);
                $missing = array_values(array_filter($missing, fn (string $value): bool => ! isset($this->optionCache[$value])));
            }
        }

        if ($missing === []) {
            return;
        }

        if ($this->selectedEndpoint !== null) {
            $this->fetchSelectedOptions($missing, $this->selectedEndpoint);
        } elseif ($this->endpoint !== null) {
            $this->fetchSelectedOptions($missing, $this->endpoint);
        }
    }

    protected function extractOptionsFromPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $candidates = ['data', 'results', 'items'];

        foreach ($candidates as $candidate) {
            if (isset($payload[$candidate]) && is_array($payload[$candidate])) {
                return $payload[$candidate];
            }
        }

        return $payload;
    }
}

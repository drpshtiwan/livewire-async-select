<?php

namespace DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait ManagesOptions
{
    /**
     * Normalized local options keyed by their value.
     *
     * @var array<string, array{value: string, label: string, image?: string}>
     */
    protected array $optionsMap = [];

    /**
     * Cache of all known options keyed by their value.
     *
     * @var array<string, array{value: string, label: string, image?: string}>
     */
    protected array $optionCache = [];

    protected function setOptions(array|Collection $options): void
    {
        // Convert Collection to array if needed
        if ($options instanceof Collection) {
            $options = $options->all();
        }

        $this->options = $options;
        $normalized = $this->normalizeOptions($options);
        $this->optionsMap = $normalized;
        $this->cacheOptions($normalized);
    }

    protected function rebuildOptionCache(): void
    {
        $normalized = $this->normalizeOptions($this->options);
        $this->optionsMap = $normalized;
        $this->optionCache = $normalized;
    }

    /**
     * @param  array<int|string, mixed>|Collection<int|string, mixed>  $options
     * @return array<string, array{value: string, label: string}>
     */
    protected function normalizeOptions(array|Collection $options): array
    {
        if ($options instanceof Collection) {
            $options = $options->all();
        }

        $normalized = [];

        foreach ($options as $key => $option) {
            $value = null;
            $label = null;
            $image = null;
            $disabled = false;
            $group = null;
            $rawOptionData = [];

            if (is_array($option)) {
                $rawOptionData = $option;

                // Group container format:
                // ['label' => 'Group Name', 'options' => [...]]
                if (isset($option['options']) && is_array($option['options'])) {
                    $groupName = Arr::get($option, 'label')
                        ?? Arr::get($option, 'group')
                        ?? Arr::get($option, 'name');

                    $nestedOptions = $this->normalizeOptions($option['options']);

                    foreach ($nestedOptions as $nestedValue => $nestedOption) {
                        if ($groupName !== null && ! isset($nestedOption['group'])) {
                            $nestedOption['group'] = (string) $groupName;
                        }

                        $normalized[$nestedValue] = $nestedOption;
                    }

                    continue;
                }

                // Value field: Custom field or auto-detect
                if ($this->valueField) {
                    $value = Arr::get($option, $this->valueField);
                } else {
                    $value = Arr::get($option, 'id')
                        ?? Arr::get($option, 'value')
                        ?? ($option[$key] ?? null);
                }

                // Label field: Custom field or auto-detect
                if ($this->labelField) {
                    $label = Arr::get($option, $this->labelField);
                } else {
                    $label = Arr::get($option, 'title')
                        ?? Arr::get($option, 'name')
                        ?? Arr::get($option, 'label')
                        ?? Arr::get($option, 'text');
                }

                // Disabled field
                $disabled = (bool) Arr::get($option, 'disabled', false);

                // Group field
                $group = Arr::get($option, 'group') ?? Arr::get($option, 'optgroup');

                // Image field: Only if imageField is set (not null)
                if ($this->imageField !== null) {
                    if ($this->imageField === '') {
                        // Empty string means auto-detect
                        $image = Arr::get($option, 'image')
                            ?? Arr::get($option, 'avatar')
                            ?? Arr::get($option, 'img')
                            ?? Arr::get($option, 'photo')
                            ?? Arr::get($option, 'picture')
                            ?? Arr::get($option, 'thumbnail');
                    } else {
                        // Use specified field
                        $image = Arr::get($option, $this->imageField);
                    }
                }
            } elseif (is_object($option)) {
                if (method_exists($option, 'toArray')) {
                    $rawOptionData = (array) $option->toArray();
                } elseif ($option instanceof \JsonSerializable) {
                    $rawOptionData = (array) $option->jsonSerialize();
                } else {
                    $rawOptionData = (array) $option;
                }

                // Value field: Custom field or auto-detect
                if ($this->valueField) {
                    $value = data_get($option, $this->valueField);
                } else {
                    $value = data_get($option, 'id')
                        ?? data_get($option, 'value');
                }

                // Label field: Custom field or auto-detect
                if ($this->labelField) {
                    $label = data_get($option, $this->labelField);
                } else {
                    $label = data_get($option, 'title')
                        ?? data_get($option, 'name')
                        ?? data_get($option, 'label')
                        ?? data_get($option, 'text');
                }

                // Disabled field
                $disabled = (bool) data_get($option, 'disabled', false);

                // Group field
                $group = data_get($option, 'group') ?? data_get($option, 'optgroup');

                // Image field: Only if imageField is set (not null)
                if ($this->imageField !== null) {
                    if ($this->imageField === '') {
                        // Empty string means auto-detect
                        $image = data_get($option, 'image')
                            ?? data_get($option, 'avatar')
                            ?? data_get($option, 'img')
                            ?? data_get($option, 'photo')
                            ?? data_get($option, 'picture')
                            ?? data_get($option, 'thumbnail');
                    } else {
                        // Use specified field
                        $image = data_get($option, $this->imageField);
                    }
                }
            } elseif (is_string($option) || is_numeric($option)) {
                if (is_string($key) || is_int($key)) {
                    $value = $key;
                }

                $label = $option;
            }

            if ($value === null || $label === null) {
                continue;
            }

            $valueKey = $this->keyForValue($value);

            if ($valueKey === null) {
                continue;
            }

            $normalizedOption = [
                'value' => $valueKey,
                'label' => (string) $label,
            ];

            if (! empty($rawOptionData)) {
                $normalizedOption = array_replace($rawOptionData, $normalizedOption);
            }

            if ($image !== null) {
                $normalizedOption['image'] = (string) $image;
            }

            if ($disabled) {
                $normalizedOption['disabled'] = true;
            }

            if ($group !== null) {
                $normalizedOption['group'] = (string) $group;
            }

            $normalized[$valueKey] = $normalizedOption;
        }

        return $normalized;
    }

    /**
     * @param  array<string, array{value: string, label: string}>  $options
     * @return array<string, array{value: string, label: string}>
     */
    protected function filterOptions(array $options): array
    {
        if ($this->search === '') {
            return $options;
        }

        $needle = mb_strtolower($this->search);

        return array_filter($options, fn (array $option): bool => str_contains(mb_strtolower($option['label']), $needle));
    }

    protected function cacheOptions(array $options): void
    {
        if ($options === []) {
            return;
        }

        $this->optionCache = array_replace($this->optionCache, $options);
    }
}

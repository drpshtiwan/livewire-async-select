<?php

namespace DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns;

trait ManagesSelection
{
    public function selectOption(string $value): void
    {
        $value = $this->keyForValue($value);

        if ($value === null) {
            return;
        }

        // Ensure optionsMap is populated (needed for tests and after hydration)
        if (empty($this->optionsMap) && ! empty($this->options)) {
            $this->rebuildOptionCache();
        }

        // Check if the option is disabled
        $allOptions = array_replace($this->optionsMap, $this->remoteOptionsMap);
        $canCreateTag = $this->tags && $this->multiple;
        $isRemoteMode = $this->endpoint !== null;

        // In local mode, reject unknown values to prevent invalid selections.
        // In remote mode, the current option map may not contain all fetched values after hydration.
        if (! isset($allOptions[$value]) && ! $canCreateTag && ! $isRemoteMode) {
            return;
        }

        if (isset($allOptions[$value]) && ($allOptions[$value]['disabled'] ?? false)) {
            return;
        }

        if ($this->multiple) {
            $current = array_map(fn (mixed $item): string => (string) $item, is_array($this->value) ? $this->value : []);

            if (in_array($value, $current, true)) {
                // Deselecting
                $current = array_values(array_filter($current, fn (string $item): bool => $item !== $value));
            } else {
                // Check max selections limit
                if ($this->maxSelections > 0 && count($current) >= $this->maxSelections) {
                    return;
                }

                $current[] = $value;
            }

            $this->value = $current;
        } else {
            $this->value = $value;
            $this->search = '';
        }
        $this->ensureLabelsForSelected();
    }

    public function createTag(): void
    {
        if (! $this->tags || ! $this->multiple || trim($this->search) === '') {
            return;
        }

        $tagValue = trim($this->search);

        // Check if already selected
        $current = array_map(fn (mixed $item): string => (string) $item, is_array($this->value) ? $this->value : []);

        if (in_array($tagValue, $current, true)) {
            $this->search = '';

            return;
        }

        // Check max selections limit
        if ($this->maxSelections > 0 && count($current) >= $this->maxSelections) {
            return;
        }

        // Add to options cache if not exists
        if (! isset($this->optionCache[$tagValue])) {
            $this->optionCache[$tagValue] = [
                'value' => $tagValue,
                'label' => $tagValue,
            ];
        }

        // Add to selection
        $current[] = $tagValue;
        $this->value = $current;
        $this->search = '';

        $this->ensureLabelsForSelected();
    }

    public function clearSelection(?string $value = null): void
    {
        if ($this->multiple) {
            $current = array_map(fn (mixed $item): string => (string) $item, is_array($this->value) ? $this->value : []);

            if ($value !== null) {
                $needle = (string) $value;
                $current = array_values(array_filter($current, fn (string $item): bool => $item !== $needle));
            } else {
                $current = [];
            }

            $this->value = $current;
        } else {
            $this->value = null;
        }

        $this->ensureLabelsForSelected();
    }

    public function removeLastSelection(): void
    {
        if (! $this->multiple) {
            return;
        }

        $current = array_map(fn (mixed $item): string => (string) $item, is_array($this->value) ? $this->value : []);

        if (count($current) === 0) {
            return;
        }

        array_pop($current);
        $this->value = array_values($current);

        $this->ensureLabelsForSelected();
    }

    protected function setValue(array|int|string|null $value): void
    {
        if ($this->multiple) {
            $values = is_array($value) ? $value : ($value === null ? [] : [$value]);
            $values = array_map(fn (mixed $item): ?string => $this->keyForValue($item), $values);
            $values = array_values(array_filter($values, fn (?string $item): bool => $item !== null));

            $this->value = array_values(array_unique(array_map(fn (string $item): string => $item, $values)));

            return;
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        $this->value = $value === null || $value === '' ? null : $this->keyForValue($value);
    }

    /**
     * @return array<int, string>
     */
    protected function selectedValues(): array
    {
        if ($this->multiple) {
            $values = is_array($this->value) ? $this->value : [];

            $values = array_map(fn (mixed $value): ?string => $this->keyForValue($value), $values);

            return array_values(array_filter($values, fn (?string $item): bool => $item !== null));
        }

        if ($this->value === null || $this->value === '') {
            return [];
        }

        $value = $this->keyForValue($this->value);

        return $value === null ? [] : [$value];
    }
}

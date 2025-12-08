<?php

namespace DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns;

trait HasComputedProperties
{
    public function getDisplayOptionsProperty(): array
    {
        $options = $this->endpoint !== null ? $this->remoteOptionsMap : $this->optionsMap;

        if ($this->endpoint === null) {
            $options = $this->filterOptions($options);
        }

        return array_values($options);
    }

    public function getGroupedOptionsProperty(): array
    {
        $options = $this->displayOptions;
        $grouped = [];
        $ungrouped = [];

        foreach ($options as $option) {
            $group = $option['group'] ?? null;

            if ($group !== null) {
                if (! isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][] = $option;
            } else {
                $ungrouped[] = $option;
            }
        }

        // If we have groups, return organized structure
        if (! empty($grouped)) {
            $result = [];

            // Add ungrouped items first (if any)
            if (! empty($ungrouped)) {
                $result['_ungrouped'] = $ungrouped;
            }

            // Add grouped items
            foreach ($grouped as $groupName => $groupOptions) {
                $result[$groupName] = $groupOptions;
            }

            return $result;
        }

        // No groups, return flat structure
        return ['_flat' => $ungrouped];
    }

    public function getHasGroupsProperty(): bool
    {
        foreach ($this->displayOptions as $option) {
            if (isset($option['group'])) {
                return true;
            }
        }

        return false;
    }

    public function getSelectedOptionsProperty(): array
    {
        $values = $this->selectedValues();

        $selected = [];

        foreach ($values as $value) {

            if (isset($this->optionCache[$value])) {
                $selected[] = $this->optionCache[$value];

                continue;
            }

            $valueKey = $this->keyForValue($value);
            $labelData = null;

            if (property_exists($this, 'valueLabels') && ! empty($this->valueLabels)) {
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

                if ($labelData !== null) {
                    if (is_string($labelData) || is_numeric($labelData)) {
                        $option = [
                            'value' => $valueKey,
                            'label' => (string) $labelData,
                        ];
                    } elseif (is_array($labelData)) {
                        $label = $labelData['label'] ?? $labelData['text'] ?? $valueKey;
                        $option = [
                            'value' => $valueKey,
                            'label' => (string) $label,
                        ];
                        if (isset($labelData['image'])) {
                            $option['image'] = (string) $labelData['image'];
                        }
                    } else {
                        $option = [
                            'value' => $valueKey,
                            'label' => (string) $labelData,
                        ];
                    }

                    $this->cacheOptions([$valueKey => $option]);
                    $selected[] = $option;

                    continue;
                }
            }

            $selected[] = [
                'value' => $value,
                'label' => $value,
            ];
        }

        return $selected;
    }

    public function getHasSelectionProperty(): bool
    {
        return count($this->selectedValues()) > 0;
    }

    public function getMaxSelectionsReachedProperty(): bool
    {
        if (! $this->multiple || $this->maxSelections === 0) {
            return false;
        }

        return count($this->selectedValues()) >= $this->maxSelections;
    }

    public function getImageSizeClassProperty(): string
    {
        return match ($this->imageSize) {
            'sm' => $this->ui === 'bootstrap' ? 'h-4 w-4' : 'h-4 w-4',
            'md' => $this->ui === 'bootstrap' ? 'h-6 w-6' : 'h-6 w-6',
            'lg' => $this->ui === 'bootstrap' ? 'h-8 w-8' : 'h-8 w-8',
            'xl' => $this->ui === 'bootstrap' ? 'h-10 w-10' : 'h-12 w-12',
            default => $this->ui === 'bootstrap' ? 'h-8 w-8' : 'h-8 w-8',

        };
    }

    public function getIsRtlProperty(): bool
    {
        if (! $this->autoDetectRtl) {
            return false;
        }

        $rtlLocales = ['ar', 'ku', 'ckb', 'fa', 'ur', 'he', 'arc', 'az', 'dv', 'ff', 'ha'];

        return in_array($this->locale, $rtlLocales);
    }
}

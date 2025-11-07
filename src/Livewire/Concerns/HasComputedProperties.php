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
            $selected[] = $this->optionCache[$value] ?? [
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
            'sm' => 'h-4 w-4',
            'md' => 'h-6 w-6',
            'lg' => 'h-8 w-8',
            'xl' => 'h-10 w-10',
            default => 'h-6 w-6',
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

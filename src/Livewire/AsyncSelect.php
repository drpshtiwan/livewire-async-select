<?php

namespace DrPshtiwan\LivewireAsyncSelect\Livewire;

use DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns\HasComputedProperties;
use DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns\HasUtilities;
use DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns\ManagesOptions;
use DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns\ManagesRemoteData;
use DrPshtiwan\LivewireAsyncSelect\Livewire\Concerns\ManagesSelection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class AsyncSelect extends Component
{
    use HasComputedProperties;
    use HasUtilities;
    use ManagesOptions;
    use ManagesRemoteData;
    use ManagesSelection;

    #[Modelable]
    public array|int|string|null $value = null;

    /**
     * Raw options passed from the parent component.
     * Can be an array or Laravel Collection (will be automatically converted to array).
     *
     * @var array<int|string, mixed>|Collection
     */
    public array|Collection $options = [];

    public ?string $name = null;

    public bool $multiple = false;

    public ?string $endpoint = null;

    public ?string $selectedEndpoint = null;

    public string $search = '';

    public string $placeholder = '';

    public bool $autoDetectRtl = true;

    public int $minSearchLength = 2;

    public string $searchParam = 'search';

    public string $selectedParam = 'selected';

    public bool $autoload = false;

    public array $extraParams = [];

    public array $headers = [];

    public bool $useInternalAuth = false;

    public ?string $valueField = null;

    public ?string $labelField = null;

    public ?string $imageField = null;

    public string $imageSize = 'md';

    public bool $tags = false;

    public int $maxSelections = 0;

    public bool $closeOnSelect = false;

    public bool $clearable = true;

    public string $ui = 'tailwind';

    public string $locale;

    public bool $isLoading = false;

    public ?string $errorMessage = null;

    public ?string $error = null;

    public int $page = 1;

    public int $perPage = 20;

    public bool $hasMore = false;

    public bool $suffixButton = false;

    public ?string $suffixButtonIcon = null;

    public ?string $suffixButtonAction = null;

    public array $suffixButtonActionParams = [];

    public array $valueLabels = [];

    public bool $searchable = true;

    public array $clonedRemoteOptionsMap = [];

    public function mount(
        array|int|string|null $value = null,
        array|Collection $options = [],
        ?string $endpoint = null,
        bool $multiple = false,
        ?string $name = null,
        string $placeholder = '',
        ?string $valueField = null,
        ?string $labelField = null,
        ?string $imageField = null,
        string $imageSize = 'md',
        bool $tags = false,
        int $maxSelections = 0,
        bool $closeOnSelect = false,
        bool $clearable = true,
        string $searchParam = 'search',
        string $selectedParam = 'selected',
        int $minSearchLength = 2,
        bool $autoload = false,
        array $extraParams = [],
        array $headers = [],
        ?bool $useInternalAuth = null,
        ?string $selectedEndpoint = null,
        ?string $ui = null,
        ?string $locale = null,
        ?string $error = null,
        bool $suffixButton = false,
        ?string $suffixButtonIcon = null,
        ?string $suffixButtonAction = null,
        array $suffixButtonActionParams = [],
        array $valueLabels = [],
        bool $searchable = true,
    ): void {
        $this->endpoint = $endpoint;
        $this->multiple = $multiple;
        $this->name = $name;
        $this->valueField = $valueField;
        $this->labelField = $labelField;
        $this->imageField = $imageField;
        $this->imageSize = $imageSize;
        $this->tags = $tags;
        $this->maxSelections = max(0, $maxSelections);
        $this->closeOnSelect = $closeOnSelect;
        $this->clearable = $clearable;
        $this->searchParam = $searchParam;
        $this->selectedParam = $selectedParam;
        $this->minSearchLength = max(0, $minSearchLength);
        $this->autoload = $autoload;
        $this->extraParams = $extraParams;
        $this->headers = $headers;
        $this->useInternalAuth = $useInternalAuth ?? config('async-select.use_internal_auth', false);
        $this->selectedEndpoint = $selectedEndpoint;
        $this->ui = strtolower($ui ?: config('async-select.ui', 'tailwind'));
        $this->error = $error;
        $this->suffixButton = $suffixButton;
        $this->suffixButtonIcon = $suffixButtonIcon;
        $this->suffixButtonAction = $suffixButtonAction;
        $this->suffixButtonActionParams = $suffixButtonActionParams;
        $this->valueLabels = $valueLabels;
        $this->locale = $locale ?? app()->getLocale();
        $this->configureRtl();
        $this->placeholder = $placeholder ?: __('async-select::async-select.select_option');
        $this->searchable = $searchable;

        $this->setOptions($options);
        $this->processValueLabels();

        $initialValue = $value ?? $this->value;
        $this->setValue($initialValue);

        if ($this->endpoint !== null && ($this->autoload || $this->search !== '')) {
            $this->fetchRemoteOptions($this->search);
        }

        $this->ensureLabelsForSelected();
    }

    public function hydrate(): void
    {
        if ($this->options instanceof Collection) {
            $this->options = $this->options->all();
        }

        $this->rebuildOptionCache();
        $this->processValueLabels();

        $this->setValue($this->value);

        $this->ensureLabelsForSelected();
    }

    public function updatedOptions(array|Collection $options): void
    {
        $this->setOptions($options);
        $this->ensureLabelsForSelected();
    }

    public function updatedEndpoint(?string $endpoint): void
    {
        $this->endpoint = $endpoint;
        $this->remoteOptionsMap = [];

        if ($this->endpoint !== null && ($this->autoload || $this->search !== '')) {
            $this->fetchRemoteOptions($this->search);
        }
    }

    public function updatedValue($value): void
    {
        $this->setValue($value);
        $this->processValueLabels();
        $this->ensureLabelsForSelected();
    }

    public function updatedValueLabels(array $valueLabels): void
    {
        $this->valueLabels = $valueLabels;
        $this->processValueLabels();
        $this->ensureLabelsForSelected();
    }

    public function updatedSearch(string $value): void
    {
        if ($this->endpoint === null) {
            return;
        }

        if ($value === '' && ! $this->autoload) {
            $this->remoteOptionsMap = [];

            return;
        }

        if ($value === '' && $this->autoload) {
            $this->page = 1;
            $this->remoteOptionsMap = [];
            $this->fetchRemoteOptions('');

            return;
        }

        if ($value !== '' && mb_strlen($value) < $this->minSearchLength) {
            if ($this->autoload) {
                $this->page = 1;
                $this->remoteOptionsMap = [];
                $this->fetchRemoteOptions('');
            }

            return;
        }

        $this->page = 1;
        $this->remoteOptionsMap = [];
        $this->fetchRemoteOptions($value);
        $this->clonedRemoteOptionsMap = $this->remoteOptionsMap;
    }

    public function handleSuffixButtonClick(): void
    {
        if (! empty($this->suffixButtonAction)) {
            $this->dispatch($this->suffixButtonAction, data: $this->suffixButtonActionParams);
        } else {
            $this->dispatch('suffix-button-clicked', data: $this->suffixButtonActionParams);
        }
    }

    protected function processValueLabels(): void
    {
        $valueLabels = array_replace($this->clonedRemoteOptionsMap, $this->valueLabels);

        if (empty($valueLabels)) {
            return;
        }

        $processed = [];

        foreach ($valueLabels as $value => $labelData) {
            $valueKey = $this->keyForValue($value);

            if ($valueKey === null) {
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
        }
    }

    public function render()
    {
        $viewName = $this->ui === 'tailwind' ? 'async-select' : 'async-select-'.$this->ui;

        $displayOptions = $this->displayOptions;
        $groupedOptions = $this->groupedOptions;
        $hasGroups = $this->hasGroups;
        $selectedOptions = $this->selectedOptions;
        $hasSelection = $this->hasSelection;
        $isRtl = $this->isRtl;
        $imageSizeClass = $this->imageSizeClass;

        $selectedValues = collect($selectedOptions)
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->all();

        // Backward compatibility for environments where slots are exposed as callables via attributes.
        $attributes = $this->getAttributes();
        $legacyOptionSlot = $attributes->get('slot');
        $legacySelectedSlot = $attributes->get('selectedSlot');

        return view("async-select::livewire.{$viewName}", [
            'displayOptions' => $displayOptions,
            'groupedOptions' => $groupedOptions,
            'hasGroups' => $hasGroups,
            'selectedOptions' => $selectedOptions,
            'hasSelection' => $hasSelection,
            'isRtl' => $isRtl,
            'imageSizeClass' => $imageSizeClass,
            'selectedValues' => $selectedValues,
            'legacyOptionSlot' => is_callable($legacyOptionSlot) ? $legacyOptionSlot : null,
            'legacySelectedSlot' => is_callable($legacySelectedSlot) ? $legacySelectedSlot : null,
        ]);
    }
}

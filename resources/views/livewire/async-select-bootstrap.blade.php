@include('async-select::livewire.scripts')

@php
    $namedSlots = $slots ?? null;
    $hasNamedOptionSlot = isset($namedSlots) && method_exists($namedSlots, 'has') && $namedSlots->has('slot');
    $hasNamedSelectedSlot = isset($namedSlots) && method_exists($namedSlots, 'has') && $namedSlots->has('selectedSlot');
    $hasLegacyOptionSlot = isset($legacyOptionSlot) && is_callable($legacyOptionSlot);
    $hasLegacySelectedSlot = isset($legacySelectedSlot) && is_callable($legacySelectedSlot);
    $hasCustomOptionSlot = $hasLegacyOptionSlot || $hasNamedOptionSlot;
    $hasCustomSelectedSlot = $hasLegacySelectedSlot || $hasNamedSelectedSlot;

    $renderNamedSlot = function (string $name, array $data = []) use ($namedSlots): string {
        if (! isset($namedSlots) || ! method_exists($namedSlots, 'get')) {
            return '';
        }

        $slotObject = $namedSlots->get($name);
        $slotContent = $slotObject->content ?? '';

        if (! is_string($slotContent) || trim($slotContent) === '') {
            return '';
        }

        return \Illuminate\Support\Facades\Blade::render($slotContent, $data);
    };
@endphp

<div class="async-select async-select-bootstrap" id="las-ui-{{ $ui }}" data-las-ui="{{ ucfirst($ui) }}"
    data-las-locale="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div x-data="asyncSelect({ multiple: {{ $this->multiple ? 'true' : 'false' }}, tags: {{ $this->tags ? 'true' : 'false' }} })" x-on:click.outside="close()" x-on:keydown.escape.window="close()"
        x-on:keydown.arrow-down.prevent="highlightNext()" x-on:keydown.arrow-up.prevent="highlightPrevious()"
        x-on:keydown.enter.prevent="handleEnter()" x-on:keydown.tab="if (!handleTab()) { $event.preventDefault(); }"
        x-effect="if (highlighted >= optionCount()) { highlighted = Math.max(optionCount() - 1, 0); }"
        class="async-select-container position-relative w-100">
        @if ($name)
            @if ($this->multiple)
                @foreach ($selectedOptions as $hiddenOption)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $hiddenOption['value'] }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $this->value }}">
            @endif
        @endif

        <div class="async-select-input-wrapper position-relative w-100">
            <div class="async-select-input-group input-group ">
                <div class="async-select-trigger form-control {{ $error ? 'border-danger' : '' }} d-flex align-items-center flex-wrap"
                    style="min-height: 43px; cursor: text;"
                    x-on:click="if ($refs.search) { $refs.search.focus(); openDropdown(); } else { openDropdown(); }">
                    @if ($this->multiple)
                        @foreach ($selectedOptions as $chip)
                            @php
                                $chipValue = $chip['value'];
                                $chipLabel = $chip['label'];
                                $chipImage = $chip['image'] ?? null;
                            @endphp

                            <span
                                class="async-select-chip badge p-2 text-bg-light mb-1 d-inline-flex align-items-center {{ $isRtl ? 'ms-1' : 'me-1' }}"
                                style="gap: 0.25rem;" data-selected="{{ $chipValue }}"
                                wire:key="async-select-chip-{{ md5($chipValue) }}">
                                @if ($hasCustomSelectedSlot)
                                    @if ($hasLegacySelectedSlot)
                                        {!! $legacySelectedSlot($chip) !!}
                                    @else
                                        {!! $renderNamedSlot('selectedSlot', ['option' => $chip]) !!}
                                    @endif
                                @else
                                    @if ($chipImage)
                                        <img src="{{ $chipImage }}" alt="{{ $chipLabel }}"
                                            class="rounded {{ $imageSizeClass }}">
                                    @endif

                                    <span class="text-truncate text-primary"
                                        style="max-width: 150px;">{{ $chipLabel }}</span>
                                @endif

                                <button type="button" class="btn-close btn-close-dark {{ $isRtl ? 'me-1' : 'ms-1' }}"
                                    style="font-size: 0.65rem;" wire:click="clearSelection({{ Js::from($chipValue) }})"
                                    x-on:click.stop
                                    aria-label="{{ __('async-select::async-select.remove') }}"></button>
                            </span>
                        @endforeach
                        @if ($this->searchable)
                            <input x-ref="search" type="text" wire:model.live.debounce.300ms="search"
                                placeholder="{{ count($selectedOptions) ? '' : $placeholder }}"
                                class="border-0 bg-transparent p-0"
                                style="min-width: 120px; outline: none; flex: 1 1 auto;" x-on:click="openDropdown()"
                                autocomplete="off">
                        @endif
                    @else
                        @if ($hasSelection)
                            @if ($hasCustomSelectedSlot)
                                @if ($hasLegacySelectedSlot)
                                    {!! $legacySelectedSlot($selectedOptions[0]) !!}
                                @else
                                    {!! $renderNamedSlot('selectedSlot', ['option' => $selectedOptions[0]]) !!}
                                @endif
                            @else
                                <span class="text-truncate"
                                    style="flex: 1 1 auto;">{{ $selectedOptions[0]['label'] }}</span>
                            @endif
                        @else
                            @if ($this->searchable)
                                <input x-ref="search" type="text" wire:model.live.debounce.300ms="search"
                                    placeholder="{{ $placeholder }}" class="border-0 bg-transparent p-0 w-100"
                                    style="outline: none;" x-on:click="openDropdown()" autocomplete="off">
                            @endif
                        @endif
                    @endif
                </div>

                <div class="async-select-actions input-group-text d-flex align-items-center gap-1 {{ $error ? 'border-danger' : '' }}"
                    style="padding: 0.375rem 0.5rem;">
                    @if ($hasSelection && $this->clearable)
                        <button type="button" class="btn-close p-0"
                            style="font-size: 0.75rem; width: 1.25rem; height: 1.25rem; opacity: 0.5;"
                            wire:click="clearSelection()" x-on:click.stop
                            title="{{ __('async-select::async-select.clear') }}"
                            aria-label="{{ __('async-select::async-select.clear') }}"></button>
                    @endif

                    <button type="button"
                        class="btn btn-link p-0 border-0 d-flex align-items-center justify-content-center"
                        style="font-size: 0.875rem; min-width: 1.5rem; min-height: 1.5rem; color: inherit;"
                        x-on:click.stop="toggle()" aria-label="{{ __('async-select::async-select.toggle') }}">
                        <svg class="async-select-chevron"
                            style="width: 1rem; height: 1rem; transition: transform 0.2s; display: inline-block; vertical-align: middle; flex-shrink: 0; pointer-events: none;"
                            x-bind:style="{ transform: open ? 'rotate(180deg)' : 'rotate(0deg)' }" viewBox="0 0 15 15"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 6L7.5 9.5L11 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" fill="none" />
                        </svg>
                    </button>
                </div>

                @if ($this->suffixButton)
                    <button type="button"
                        class="async-select-suffix-button btn btn-icon btn-outline d-flex align-items-center justify-content-center {{ !$isRtl ? 'rounded-end rounded-start-0' : 'rounded-start rounded-end-0' }}"
                        style="min-height: 43px; white-space: nowrap; flex-shrink: 0;"
                        x-on:click.stop.prevent="handleSuffixButtonClick($event)"
                        title="{{ __('async-select::async-select.add') }}"
                        aria-label="{{ __('async-select::async-select.add') }}">
                        @if ($this->suffixButtonIcon)
                            <span
                                class="d-inline-flex align-items-center justify-content-center">{!! $this->suffixButtonIcon !!}</span>
                        @else
                            <svg class="async-select-suffix-icon"
                                style="width: 1.25rem; height: 1.5rem !important; display: inline-block !important; vertical-align: middle; flex-shrink: 0; pointer-events: none;"
                                viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.5 3.5V11.5M3.5 7.5H11.5" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" fill="none" />
                            </svg>
                        @endif
                    </button>
                @endif
            </div>

            <div x-show="open && !(tags && multiple)" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" x-cloak
                class="async-select-dropdown position-absolute w-100 mt-1 rounded border shadow-lg"
                style="z-index: 1050; top: 100%; left: 0; right: 0;">
                @if (!$this->multiple && $hasSelection && $this->searchable)
                    <div class="mb-1 px-1">
                        <input x-ref="searchDropdown" type="text" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('async-select::async-select.search') }}" class="form-control"
                            style="{{ $isRtl ? 'text-align: right; direction: rtl;' : '' }}" autocomplete="off">
                    </div>
                @endif

                @if ($isLoading)
                    <div class="d-flex align-items-center px-3 py-2 text-muted small" style="gap: 0.5rem;">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">{{ __('async-select::async-select.loading') }}</span>
                        </div>
                        <span>{{ __('async-select::async-select.loading') }}</span>
                    </div>
                @endif

                <div x-ref="options" role="listbox" style="max-height: 200px; overflow-y: auto;"
                    x-on:scroll.debounce.150ms="
                    if ($el.scrollHeight - $el.scrollTop - $el.clientHeight < 50) {
                        if ({{ $hasMore ? 'true' : 'false' }} && !{{ $isLoading ? 'true' : 'false' }}) {
                            $wire.loadMore();
                        }
                    }
                ">
                    @if ($hasGroups)
                        @php $globalIndex = 0; @endphp
                        @foreach ($groupedOptions as $groupName => $groupOptions)
                            @if ($groupName !== '_flat' && $groupName !== '_ungrouped')
                                <div class="list-group-item list-group-item-secondary small fw-bold">
                                    {{ $groupName }}
                                </div>
                            @endif

                            @foreach ($groupOptions as $option)
                                @php
                                    $optionValue = $option['value'];
                                    $optionLabel = $option['label'];
                                    $optionImage = $option['image'] ?? null;
                                    $isSelected = in_array($optionValue, $selectedValues, true);
                                    $isDisabled = $option['disabled'] ?? false;
                                @endphp

                                <div wire:key="async-select-option-{{ md5($optionValue) }}"
                                    class="async-select-option list-group-item list-group-item-action d-flex align-items-center {{ $isDisabled ? 'disabled' : '' }}"
                                    style="gap: 0.5rem;"
                                    :class="{
                                        'active': highlighted === {{ $globalIndex }} && !
                                            {{ $isDisabled ? 'true' : 'false' }},
                                    }"
                                    data-option-index="{{ $globalIndex }}" data-value="{{ $optionValue }}"
                                    data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                    data-disabled="{{ $isDisabled ? 'true' : 'false' }}" role="option"
                                    aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                    aria-disabled="{{ $isDisabled ? 'true' : 'false' }}"
                                    x-on:mouseenter="if (!{{ $isDisabled ? 'true' : 'false' }}) { highlight({{ $globalIndex }}); }"
                                    x-on:click.stop="if (!{{ $isDisabled ? 'true' : 'false' }}) { selectValue({{ Js::from($optionValue) }}); }">
                                    @if ($hasCustomOptionSlot)
                                        {{-- Custom slot rendering --}}
                                        @if ($hasLegacyOptionSlot)
                                            {!! $legacyOptionSlot([
                                                'option' => $option,
                                                'isSelected' => $isSelected,
                                                'isDisabled' => $isDisabled,
                                                'multiple' => $this->multiple,
                                            ]) !!}
                                        @else
                                            {!! $renderNamedSlot('slot', [
                                                'option' => $option,
                                                'isSelected' => $isSelected,
                                                'isDisabled' => $isDisabled,
                                                'multiple' => $this->multiple,
                                            ]) !!}
                                        @endif
                                    @else
                                        @if ($this->multiple)
                                            <input class="form-check-input {{ $isRtl ? 'ms-2' : 'me-2' }}"
                                                type="checkbox" :checked="{{ $isSelected ? 'true' : 'false' }}"
                                                disabled>
                                        @endif

                                        @if ($optionImage)
                                            <img src="{{ $optionImage }}" alt="{{ $optionLabel }}"
                                                class="rounded"
                                                style="max-width: 32px; max-height: 32px; object-fit: cover;">
                                        @endif

                                        <span class="text-truncate text-primary"
                                            style="flex: 1 1 auto;">{{ $optionLabel }}</span>

                                        @if (!$this->multiple && $isSelected)
                                            <svg style="width: 1rem !important; height: 1rem !important; display: inline-block; vertical-align: middle; flex-shrink: 0;"
                                                class="text-primary" viewBox="0 0 15 15" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.5 3.5L6 9L3.5 6.5" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                                @php $globalIndex++; @endphp
                            @endforeach
                        @endforeach

                        @if ($globalIndex === 0)
                            <div class="list-group-item text-center text-muted py-4">
                                {{ $isLoading ? __('async-select::async-select.searching') : __('async-select::async-select.no_results') }}
                            </div>
                        @endif
                    @else
                        @forelse ($displayOptions as $index => $option)
                            @php
                                $optionValue = $option['value'];
                                $optionLabel = $option['label'];
                                $optionImage = $option['image'] ?? null;
                                $isSelected = in_array($optionValue, $selectedValues, true);
                                $isDisabled = $option['disabled'] ?? false;
                            @endphp

                            <div wire:key="async-select-option-{{ md5($optionValue) }}"
                                class="async-select-option list-group-item list-group-item-action d-flex align-items-center {{ $isDisabled ? 'disabled' : '' }}"
                                style="gap: 0.5rem;"
                                :class="{
                                    'active': highlighted === {{ $index }} && !
                                        {{ $isDisabled ? 'true' : 'false' }},
                                }"
                                data-option-index="{{ $index }}" data-value="{{ $optionValue }}"
                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                data-disabled="{{ $isDisabled ? 'true' : 'false' }}" role="option"
                                aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                aria-disabled="{{ $isDisabled ? 'true' : 'false' }}"
                                x-on:mouseenter="if (!{{ $isDisabled ? 'true' : 'false' }}) { highlight({{ $index }}); }"
                                x-on:click.stop="if (!{{ $isDisabled ? 'true' : 'false' }}) { selectValue({{ Js::from($optionValue) }}); }">
                                @if ($hasCustomOptionSlot)
                                    {{-- Custom slot rendering --}}
                                    @if ($hasLegacyOptionSlot)
                                        {!! $legacyOptionSlot([
                                            'option' => $option,
                                            'isSelected' => $isSelected,
                                            'isDisabled' => $isDisabled,
                                            'multiple' => $this->multiple,
                                        ]) !!}
                                    @else
                                        {!! $renderNamedSlot('slot', [
                                            'option' => $option,
                                            'isSelected' => $isSelected,
                                            'isDisabled' => $isDisabled,
                                            'multiple' => $this->multiple,
                                        ]) !!}
                                    @endif
                                @else
                                    {{-- Default rendering --}}
                                    @if ($this->multiple)
                                        <input class="form-check-input {{ $isRtl ? 'ms-2' : 'me-2' }}"
                                            type="checkbox" :checked="{{ $isSelected ? 'true' : 'false' }}" disabled>
                                    @endif

                                    @if ($optionImage)
                                        <img src="{{ $optionImage }}" alt="{{ $optionLabel }}" class="rounded"
                                            style="max-width: 32px; max-height: 32px; object-fit: cover;">
                                    @endif

                                    <span class="text-truncate" style="flex: 1 1 auto;">{{ $optionLabel }}</span>

                                    @if (!$this->multiple && $isSelected)
                                        <svg style="width: 1rem !important; height: 1rem !important; display: inline-block; vertical-align: middle; flex-shrink: 0;"
                                            class="text-primary" viewBox="0 0 15 15" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.5 3.5L6 9L3.5 6.5" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                {{ $isLoading ? __('async-select::async-select.searching') : __('async-select::async-select.no_results') }}
                            </div>
                        @endforelse
                    @endif
                </div>

                @if ($hasMore && $endpoint)
                    <div class="border-top p-2" wire:loading wire:target="loadMore">
                        <div class="d-flex align-items-center justify-content-center text-muted small"
                            style="gap: 0.5rem;">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span
                                    class="visually-hidden">{{ __('async-select::async-select.loading_more') }}</span>
                            </div>
                            <span>{{ __('async-select::async-select.loading_more') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($error)
            <span class="text-danger">{{ $error }}</span>
        @endif
    </div>
</div>

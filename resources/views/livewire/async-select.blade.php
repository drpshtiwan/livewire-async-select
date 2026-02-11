@include('async-select::livewire.scripts')

@php
    $hasNamedOptionSlot = isset($slots) && method_exists($slots, 'has') && $slots->has('slot');
    $hasNamedSelectedSlot = isset($slots) && method_exists($slots, 'has') && $slots->has('selectedSlot');
    $hasLegacyOptionSlot = isset($legacyOptionSlot) && is_callable($legacyOptionSlot);
    $hasLegacySelectedSlot = isset($legacySelectedSlot) && is_callable($legacySelectedSlot);
    $hasCustomOptionSlot = $hasLegacyOptionSlot || $hasNamedOptionSlot;
    $hasCustomSelectedSlot = $hasLegacySelectedSlot || $hasNamedSelectedSlot;

    $renderNamedSlot = function (string $name, array $data = []) use ($slots): string {
        if (! isset($slots) || ! method_exists($slots, 'get')) {
            return '';
        }

        $slotObject = $slots->get($name);
        $slotContent = $slotObject->content ?? '';

        if (! is_string($slotContent) || trim($slotContent) === '') {
            return '';
        }

        return \Illuminate\Support\Facades\Blade::render($slotContent, $data);
    };
@endphp

<div class="las-async-select-wrapper" id="las-ui-{{ $ui }}" data-las-ui="{{ ucfirst($ui) }}"
    data-las-locale="{{ $locale }}">
    <div x-data="asyncSelect({ multiple: {{ $this->multiple ? 'true' : 'false' }}, tags: {{ $this->tags ? 'true' : 'false' }} })" x-on:click.outside="close()" x-on:keydown.escape.window="close()"
        x-on:keydown.arrow-down.prevent="highlightNext()" x-on:keydown.arrow-up.prevent="highlightPrevious()"
        x-on:keydown.enter.prevent="handleEnter()" x-on:keydown.tab="if (!handleTab()) { $event.preventDefault(); }"
        x-effect="if (highlighted >= optionCount()) { highlighted = Math.max(optionCount() - 1, 0); }"
        class="las-relative las-w-full">
        @if ($name)
            @if ($this->multiple)
                @foreach ($selectedOptions as $hiddenOption)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $hiddenOption['value'] }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $this->value }}">
            @endif
        @endif

        <div class="las-relative las-w-full">
            {{-- Input Group Container --}}
            <div class="las-flex las-items-stretch las-w-full">
                {{-- Main Select Container --}}
                @php
                    if ($isRtl) {
                        $suffixButtonClass = $this->suffixButton ? 'las-rounded-l-none' : 'las-rounded-l-md';
                    } else {
                        $suffixButtonClass = $this->suffixButton ? 'las-rounded-r-none' : 'las-rounded-r-md';
                    }
                @endphp
                <div class="las-select-trigger las-relative las-flex las-min-h-[40px] las-flex-1 
            las-cursor-text las-items-center las-rounded-l-md {{ $suffixButtonClass }} 
            las-border las-border-gray-300 las-bg-white las-px-3 las-py-2 las-text-sm 
            las-transition-colors hover:las-border-gray-400 focus-within:las-outline-none 
            focus-within:las-ring-2 focus-within:las-ring-primary-500 
            focus-within:las-ring-offset-2 focus-within:las-z-10 {{ $this->suffixButton ? 'focus-within:las-border-r-0' : '' }}"
                    x-on:click="if ($refs.search) { $refs.search.focus(); openDropdown(); } else { 
            openDropdown(); }">
                    {{-- Selected Values / Tags --}}
                    <div class="las-flex las-min-w-0 las-flex-1 las-flex-wrap las-items-center las-gap-1.5">
                        @if ($this->multiple)
                            @foreach ($selectedOptions as $chip)
                                @php
                                    $chipValue = $chip['value'];
                                    $chipLabel = $chip['label'];
                                    $chipImage = $chip['image'] ?? null;
                                @endphp

                                <span
                                    class="las-inline-flex las-items-center las-gap-1 las-rounded-md las-border las-border-gray-200 las-bg-primary-100 las-px-2 las-py-0.5 las-text-xs las-font-medium las-text-primary-800"
                                    data-selected="{{ $chipValue }}"
                                    wire:key="async-select-chip-{{ md5($chipValue) }}">
                                    @if ($hasCustomSelectedSlot)
                                        {{-- Custom selected item rendering --}}
                                        @if ($hasLegacySelectedSlot)
                                            {!! $legacySelectedSlot($chip) !!}
                                        @else
                                            {!! $renderNamedSlot('selectedSlot', ['option' => $chip]) !!}
                                        @endif
                                    @else
                                        {{-- Default chip rendering --}}
                                        @if ($chipImage)
                                            <img src="{{ $chipImage }}" alt="{{ $chipLabel }}"
                                                class="{{ $imageSizeClass }} las-shrink-0 las-rounded las-object-cover">
                                        @endif

                                        <span class="las-max-w-[150px] las-truncate">{{ $chipLabel }}</span>
                                    @endif

                                    <button type="button"
                                        class="las-icon-button las-inline-flex las-items-center las-justify-center las-rounded-sm las-text-gray-500 hover:las-bg-gray-200 hover:las-text-gray-700 focus:las-outline-none"
                                        wire:click="clearSelection({{ Js::from($chipValue) }})" x-on:click.stop
                                        aria-label="{{ __('async-select::async-select.remove') }}">
                                        <svg class="las-h-3 las-w-3" viewBox="0 0 12 12" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 3l6 6m0-6L3 9" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach

                            {{-- Search Input for Multiple --}}
                            @if ($this->searchable)
                                <input x-ref="search" type="text" wire:model.live.debounce.300ms="search"
                                    placeholder="{{ count($selectedOptions) ? '' : $placeholder }}"
                                    class="las-min-w-[120px] las-flex-1 las-border-0 las-bg-transparent las-p-0 las-text-base las-text-gray-900 placeholder:las-text-gray-400 focus:las-outline-none focus:las-ring-0"
                                    x-on:click="openDropdown()" autocomplete="off">
                            @endif
                        @else
                            {{-- Single Select Display --}}
                            @if ($hasSelection)
                                @if ($hasCustomSelectedSlot)
                                    {{-- Custom selected item rendering --}}
                                    @if ($hasLegacySelectedSlot)
                                        {!! $legacySelectedSlot($selectedOptions[0]) !!}
                                    @else
                                        {!! $renderNamedSlot('selectedSlot', ['option' => $selectedOptions[0]]) !!}
                                    @endif
                                @else
                                    {{-- Default single select display --}}
                                    <span
                                        class="las-flex-1 las-truncate las-text-base las-text-gray-900">{{ $selectedOptions[0]['label'] }}</span>
                                @endif
                            @else
                                @if ($this->searchable)
                                    <input x-ref="search" type="text" wire:model.live.debounce.300ms="search"
                                        placeholder="{{ $placeholder }}"
                                        class="las-w-full las-border-0 las-bg-transparent las-p-0 las-text-base las-text-gray-900 placeholder:las-text-gray-400 focus:las-outline-none focus:las-ring-0"
                                        x-on:click="openDropdown()" autocomplete="off">
                                @endif
                            @endif
                        @endif
                    </div>

                    {{-- Action Icons --}}
                    <div class="las-flex las-shrink-0 las-items-center las-gap-1 las-pl-2">
                        @if ($hasSelection && $this->clearable)
                            <button type="button"
                                class="las-icon-button las-flex las-h-5 las-w-5 las-shrink-0 las-items-center las-justify-center las-rounded-sm las-text-gray-400 hover:las-text-gray-900 focus:las-outline-none"
                                wire:click="clearSelection()" x-on:click.stop
                                title="{{ __('async-select::async-select.clear') }}">
                                <svg class="las-h-4 las-w-4" viewBox="0 0 15 15" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.5 3.5L3.5 11.5M3.5 3.5L11.5 11.5" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </button>
                        @endif

                        <button type="button"
                            class="las-icon-button las-flex las-h-5 las-w-5 las-shrink-0 las-items-center las-justify-center las-text-gray-400"
                            x-on:click.stop="toggle()">
                            <svg class="las-h-4 las-w-4 las-transition-transform las-duration-200"
                                x-bind:class="{ 'las-rotate-180': open }" viewBox="0 0 15 15" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6L7.5 9.5L11 6" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
                {{-- Suffix Button (Input Group Style) --}}
                @if ($this->suffixButton)
                    <button type="button"
                        class="las-suffix-button las-flex las-min-h-[40px] las-shrink-0 las-items-center las-justify-center {{ !$isRtl ? 'las-rounded-r-md las-rounded-l-none las-border-l-0' : 'las-rounded-l-md las-rounded-r-none las-border-r-0' }} las-border las-border-gray-300 las-bg-white las-px-3 las-text-sm las-font-medium las-text-gray-700 las-cursor-pointer las-transition-colors hover:las-bg-gray-50 hover:las-text-gray-900 hover:las-border-gray-400 focus:las-outline-none focus:las-ring-2 focus:las-ring-primary-500 focus:las-ring-offset-2"
                        x-on:click.stop.prevent="handleSuffixButtonClick($event)"
                        title="{{ __('async-select::async-select.add') }}">
                        @if ($this->suffixButtonIcon)
                            <span class="las-flex las-items-center las-justify-center">{!! $this->suffixButtonIcon !!}</span>
                        @else
                            <svg class="las-h-5 las-w-5" viewBox="0 0 15 15" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.5 3.5V11.5M3.5 7.5H11.5" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        @endif
                    </button>
                @endif
            </div>

            {{-- Dropdown Menu --}}
            <div x-show="open && !(tags && multiple)" x-transition:enter="las-transition las-ease-out las-duration-100"
                x-transition:enter-start="las-opacity-0 las-scale-95"
                x-transition:enter-end="las-opacity-100 las-scale-100"
                x-transition:leave="las-transition las-ease-in las-duration-75"
                x-transition:leave-start="las-opacity-100 las-scale-100"
                x-transition:leave-end="las-opacity-0 las-scale-95" x-cloak
                class="las-absolute las-left-0 las-right-0 las-z-50 las-mt-2 las-rounded-md las-bg-white las-border las-border-gray-200 las-p-1 las-text-gray-900 las-shadow-lg las-outline-none">
                {{-- Search Box (for single select when open) --}}
                @if (!$this->multiple && $hasSelection && $this->searchable)
                    <div class="las-mb-1 las-px-1">
                        <input x-ref="searchDropdown" type="text" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('async-select::async-select.search') }}"
                            class="las-flex las-h-9 las-w-full las-rounded-md las-border las-border-gray-300 las-bg-white las-px-3 las-py-1 las-text-sm las-shadow-sm placeholder:las-text-gray-400 focus:las-border-gray-400 focus:las-outline-none focus:las-ring-1 focus:las-ring-primary-500"
                            autocomplete="off">
                    </div>
                @endif

                {{-- Loading State --}}
                @if ($isLoading)
                    <div class="las-flex las-items-center las-gap-2 las-px-3 las-py-2 las-text-xs las-text-gray-500">
                        <svg class="las-h-4 las-w-4 las-animate-spin" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle class="las-opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                            <path class="las-opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>{{ __('async-select::async-select.loading') }}</span>
                    </div>
                @endif

                {{-- Options List --}}
                <div class="las-max-h-64 las-overflow-y-auto" x-ref="options" role="listbox"
                    x-on:scroll.debounce.150ms="
                    if ($el.scrollHeight - $el.scrollTop - $el.clientHeight < 50) {
                        if ({{ $hasMore ? 'true' : 'false' }} && !{{ $isLoading ? 'true' : 'false' }}) {
                            $wire.loadMore();
                        }
                    }
                ">
                    @if ($hasGroups)
                        {{-- Grouped Options --}}
                        @php $globalIndex = 0; @endphp
                        @foreach ($groupedOptions as $groupName => $groupOptions)
                            @if ($groupName !== '_flat' && $groupName !== '_ungrouped')
                                <div class="las-px-2 las-py-1.5 las-text-xs las-font-semibold las-text-gray-500">
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
                                    class="las-relative las-flex las-items-center las-gap-2 las-rounded-sm las-px-2 las-py-1.5 las-text-base las-outline-none las-transition-colors {{ $isDisabled ? 'las-cursor-not-allowed las-opacity-50' : 'las-cursor-default las-select-none' }}"
                                    :class="{
                                        'las-bg-primary-50 las-text-primary-900': highlighted ===
                                            {{ $globalIndex }} && !{{ $isDisabled ? 'true' : 'false' }},
                                        'las-text-gray-900': highlighted !== {{ $globalIndex }}
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
                                        {{-- Default rendering --}}
                                        @if ($this->multiple)
                                            <span
                                                class="las-flex las-h-4 las-w-4 las-shrink-0 las-items-center las-justify-center las-rounded-sm las-border las-transition-colors"
                                                :class="{{ $isSelected ? 'true' : 'false' }} ?
                                                    'las-border-primary-500 las-bg-primary-500 las-text-white' :
                                                    'las-border-gray-300'">
                                                <svg class="las-h-3 las-w-3 las-transition-opacity"
                                                    :class="{{ $isSelected ? 'true' : 'false' }} ? 'las-opacity-100' :
                                                        'las-opacity-0'"
                                                    viewBox="0 0 15 15" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11.5 3.5L6 9L3.5 6.5" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        @endif

                                        @if ($optionImage)
                                            <img src="{{ $optionImage }}" alt="{{ $optionLabel }}"
                                                class="{{ $imageSizeClass }} las-shrink-0 las-rounded las-object-cover">
                                        @endif

                                        <span class="las-flex-1 las-truncate">{{ $optionLabel }}</span>

                                        @if (!$this->multiple)
                                            <svg class="las-h-4 las-w-4 las-transition-opacity"
                                                :class="{{ $isSelected ? 'true' : 'false' }} ? 'las-opacity-100' :
                                                    'las-opacity-0'"
                                                viewBox="0 0 15 15" fill="none"
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
                            <div class="las-px-2 las-py-6 las-text-center las-text-sm las-text-gray-500">
                                {{ $isLoading ? __('async-select::async-select.searching') : __('async-select::async-select.no_results') }}
                            </div>
                        @endif
                    @else
                        {{-- Flat Options --}}
                        @forelse ($displayOptions as $index => $option)
                            @php
                                $optionValue = $option['value'];
                                $optionLabel = $option['label'];
                                $optionImage = $option['image'] ?? null;
                                $isSelected = in_array($optionValue, $selectedValues, true);
                                $isDisabled = $option['disabled'] ?? false;
                            @endphp

                            <div wire:key="async-select-option-{{ md5($optionValue) }}"
                                class="las-relative las-flex las-items-center las-gap-2 las-rounded-sm las-px-2 las-py-1.5 las-text-base las-outline-none las-transition-colors {{ $isDisabled ? 'las-cursor-not-allowed las-opacity-50' : 'las-cursor-default las-select-none' }}"
                                :class="{
                                    'las-bg-primary-50 las-text-primary-900': highlighted === {{ $index }} && !
                                        {{ $isDisabled ? 'true' : 'false' }},
                                    'las-text-gray-900': highlighted !== {{ $index }}
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
                                        <span
                                            class="las-flex las-h-4 las-w-4 las-shrink-0 las-items-center las-justify-center las-rounded-sm las-border las-transition-colors"
                                            :class="{{ $isSelected ? 'true' : 'false' }} ?
                                                'las-border-primary-500 las-bg-primary-500 las-text-white' :
                                                'las-border-gray-300'">
                                            <svg class="las-h-3 las-w-3 las-transition-opacity"
                                                :class="{{ $isSelected ? 'true' : 'false' }} ? 'las-opacity-100' :
                                                    'las-opacity-0'"
                                                viewBox="0 0 15 15" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.5 3.5L6 9L3.5 6.5" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    @endif

                                    @if ($optionImage)
                                        <img src="{{ $optionImage }}" alt="{{ $optionLabel }}"
                                            class="{{ $imageSizeClass }} las-shrink-0 las-rounded las-object-cover">
                                    @endif

                                    <span class="las-flex-1 las-truncate">{{ $optionLabel }}</span>

                                    @if (!$this->multiple)
                                        <svg class="las-h-4 las-w-4 las-transition-opacity"
                                            :class="{{ $isSelected ? 'true' : 'false' }} ? 'las-opacity-100' : 'las-opacity-0'"
                                            viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.5 3.5L6 9L3.5 6.5" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <div class="las-px-2 las-py-6 las-text-center las-text-sm las-text-gray-500">
                                {{ $isLoading ? __('async-select::async-select.searching') : __('async-select::async-select.no_results') }}
                            </div>
                        @endforelse
                    @endif
                </div>

                {{-- Load More Indicator --}}
                @if ($hasMore && $endpoint)
                    <div class="las-border-t las-border-gray-200 las-p-2" wire:loading wire:target="loadMore">
                        <div
                            class="las-flex las-items-center las-justify-center las-gap-2 las-py-1 las-text-sm las-text-gray-500">
                            <svg class="las-h-4 las-w-4 las-animate-spin" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle class="las-opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="las-opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span>{{ __('async-select::async-select.loading_more') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Validation Error Message --}}
        @if ($error)
            <p class="las-mt-1 las-text-sm las-text-red-600">{{ $error }}</p>
        @endif
    </div>
</div>

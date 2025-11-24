@use('Illuminate\Support\Js')

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('asyncSelect', (config = {}) => ({
        open: false,
        highlighted: 0,
        multiple: Boolean(config.multiple ?? false),
        tags: Boolean(config.tags ?? false),
        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.focusSearch());
            }
        },
        openDropdown() {
            if (! this.open) {
                this.open = true;
                this.$nextTick(() => this.focusSearch());
            }
        },
        close() {
            if (! this.open) {
                return;
            }

            this.open = false;
            this.highlighted = 0;
        },
        focusSearch() {
            if (this.$refs.search) {
                this.$refs.search.focus();
            } else if (this.$refs.searchDropdown) {
                this.$refs.searchDropdown.focus();
            }
        },
        optionCount() {
            if (! this.$refs.options) {
                return 0;
            }

            return this.$refs.options.querySelectorAll('[data-option-index]').length;
        },
        optionElement(index) {
            if (! this.$refs.options) {
                return null;
            }

            return this.$refs.options.querySelector('[data-option-index="' + index + '"]');
        },
        highlight(index) {
            this.highlighted = index;
        },
        highlightNext() {
            const total = this.optionCount();

            if (total === 0) {
                return;
            }

            this.openDropdown();

            let nextIndex = (this.highlighted + 1) % total;
            let attempts = 0;

            // Skip disabled options
            while (attempts < total) {
                const element = this.optionElement(nextIndex);
                if (element && element.dataset.disabled !== 'true') {
                    this.highlighted = nextIndex;
                    break;
                }
                nextIndex = (nextIndex + 1) % total;
                attempts++;
            }

            this.scrollHighlightedIntoView();
        },
        highlightPrevious() {
            const total = this.optionCount();

            if (total === 0) {
                return;
            }

            this.openDropdown();

            let prevIndex = (this.highlighted - 1 + total) % total;
            let attempts = 0;

            // Skip disabled options
            while (attempts < total) {
                const element = this.optionElement(prevIndex);
                if (element && element.dataset.disabled !== 'true') {
                    this.highlighted = prevIndex;
                    break;
                }
                prevIndex = (prevIndex - 1 + total) % total;
                attempts++;
            }

            this.scrollHighlightedIntoView();
        },
        scrollHighlightedIntoView() {
            const element = this.optionElement(this.highlighted);

            if (! element) {
                return;
            }

            element.scrollIntoView({ block: 'nearest' });
        },
        selectHighlighted() {
            const element = this.optionElement(this.highlighted);

            if (! element) {
                return;
            }

            // Don't select disabled options
            if (element.dataset.disabled === 'true') {
                return;
            }

            this.selectValue(element.dataset.value);
        },
        selectValue(value) {
            if (typeof value === 'undefined') {
                return;
            }

            this.$wire.selectOption(value);

            if (! this.multiple) {
                this.close();
            } else {
                // Clear search after selection in multiple mode
                this.$wire.set('search', '');
                if (this.$refs.search) {
                    this.$refs.search.focus();
                }
            }
        },
        handleEnter() {
            // If tags mode and there's search text, create a tag
            if (this.tags && this.multiple) {
                const hasSearch = this.$refs.search && this.$refs.search.value.trim() !== '';
                if (hasSearch) {
                    // If dropdown has options, select highlighted one
                    if (this.open && this.optionCount() > 0) {
                        this.selectHighlighted();
                    } else {
                        // Otherwise, create a new tag
                        this.$wire.createTag();
                    }
                    return;
                }
            }

            // Default behavior: select highlighted option if dropdown is open
            if (this.open && this.optionCount() > 0) {
                this.selectHighlighted();
            }
        },
        handleTab() {
            // If tags mode and there's search text, create a tag
            if (this.tags && this.multiple) {
                const hasSearch = this.$refs.search && this.$refs.search.value.trim() !== '';
                if (hasSearch) {
                    // If dropdown has options, select highlighted one
                    if (this.open && this.optionCount() > 0) {
                        this.selectHighlighted();
                    } else {
                        // Otherwise, create a new tag
                        this.$wire.createTag();
                    }
                    return false; // Prevent default tab behavior
                }
            }

            // Multiple mode with dropdown open and options: select highlighted
            if (this.multiple && this.open && this.optionCount() > 0) {
                this.selectHighlighted();
                return false; // Prevent default tab behavior
            }

            return true; // Allow default tab behavior
        },
        handleSuffixButtonClick(event) {
            // Close dropdown and blur search inputs
            this.close();
            if (this.$refs.search) {
                this.$refs.search.blur();
            }
            if (this.$refs.searchDropdown) {
                this.$refs.searchDropdown.blur();
            }
            // Call Livewire method to dispatch event
            this.$wire.handleSuffixButtonClick();
        }
    }));
});
</script>
@endpush
@endonce

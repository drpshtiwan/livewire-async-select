# Changelog

All notable changes to this project are documented in this file.

## [3.2.0]
### Added
- Added support for Bootstrap 4 theme

## [3.1.0]
### Fixed
- Fixed undefined variable: slots in async-select-bootstrap.blade.php

## [3.0.0]

### Fixed
- Livewire v4 slot rendering regression that caused blank option rows and fragment markers to appear in dropdown options.
- Custom slot rendering compatibility across Tailwind and Bootstrap views for both local and remote options.
- Selection validation to reject unknown values in local mode when tags are disabled.
- Grouped option normalization for container-style input (`label` + `options`).
- Key merge behavior in selection checks by preserving option keys (`array_replace` instead of `array_merge`).

### Changed
- Option normalization now preserves extra option fields (for example `role`, `sku`) so custom slots can access full option payload data.
- Added backward-compatible callable slot support while keeping Livewire named-slot support.

### Added
- New feature tests for custom slots with local and remote data:
  - `tests/Feature/CustomSlotsTest.php`
- Additional selection and grouping coverage in:
  - `tests/Feature/AsyncSelectTest.php`

### Documentation
- Updated slot usage examples and troubleshooting guidance for Livewire 3.3+/4.x.
- Corrected API docs defaults and behavior notes (`max-selections`, grouped options format, slot syntax).
- Updated docs in:
  - `README.md`
  - `docs-src/guide/api.md`
  - `docs-src/guide/custom-slots.md`
  - `docs-src/guide/troubleshooting.md`

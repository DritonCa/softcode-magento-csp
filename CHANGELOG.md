# Changelog

## [Unreleased]
### Added
- `Test/Unit/Helper/DataTest.php` — a unit test specifying how the admin whitelist
  is parsed into per-directive hosts (invalid JSON, grouping, skipping incomplete
  rows) and how the default seed values are generated.
- `.gitignore` for `/vendor/` and editor/OS artefacts.
### Removed
- 15 stray Windows `*:Zone.Identifier` files that had been committed to the repo.

## [1.1.0]
### Changed
- Refactor plugin and helper to strict types, constructor DI and Magento coding
  standard; remove an unused dependency and add null-safe config parsing.
### Added
- composer.json metadata, MIT license, CI (PHP lint + Magento coding standard),
  and a README documenting configuration and behaviour.

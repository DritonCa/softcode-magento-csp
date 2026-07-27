# Changelog

## [Unreleased]
### Security
- **Strict host validation on save.** New `Model\HostValidator` (wired into
  `Config\Backend\ArraySerialized::beforeSave`) accepts only documented
  host-sources and rejects a bare `*`, wildcards on a bare TLD, non-host schemes
  (`data:`, `javascript:` …), keyword sources (`'unsafe-inline'` …), paths and
  unknown directives — each with a reason. A bad entry now aborts the save.
- **Canonicalisation + deduplication** of hosts per directive, on save and again
  when the policy is built (`Helper\Data`), so the emitted policy has no duplicate
  or inconsistently-cased sources.
- **Added `etc/acl.xml`** defining `Softcode_CspWhitelist::config`, the resource the
  admin section already referenced, so configuration access is properly gated.
### Added
- `Test/Unit/Model/HostValidatorTest.php` — accept/reject/canonicalise/dedupe rules
  (35 assertions across the validator).
- `Test/Unit/Helper/DataTest.php` — parsing, grouping, incomplete-row skipping,
  read-side deduplication and default seed generation.
- `.gitignore` for `/vendor/` and editor/OS artefacts.
### Removed
- Emptied the shipped `etc/csp_whitelist.xml`: dropped a large commented-out block
  of duplicated, environment-/customer-specific hosts. Hosts are managed in admin.
- 15 stray Windows `*:Zone.Identifier` files that had been committed to the repo.
- Sloppy leftover comments in `etc/adminhtml/system.xml`.

## [1.1.0]
### Changed
- Refactor plugin and helper to strict types, constructor DI and Magento coding
  standard; remove an unused dependency and add null-safe config parsing.
### Added
- composer.json metadata, MIT license, CI (PHP lint + Magento coding standard),
  and a README documenting configuration and behaviour.

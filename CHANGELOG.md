# Changelog

All notable changes to TASLibPHP are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

Every release has a **Security** heading listing the vulnerabilities fixed in it, or "None". See [SECURITY.md](SECURITY.md) to report one.

## [Unreleased]

### Security

- None.

## [1.2.85] - 2026-10-20

### Added

- Seek paging for grids: pass `'pagingmode' => 'seek'` to `UI::Grid`/`UI::GridBoxed` to page with an anchor row (`KeysetPager`) instead of a deep `OFFSET`. Mark sort columns with `'seek' => 'sql expression'` (or `true`). Non-seekable shapes and stale tokens fall back to offset paging.

### Security

- Grid sort column (`ob`) and direction (`d`) are now validated against the grid's own columns and `asc`/`desc`. Before this, both request values were placed in the `ORDER BY` clause, so a crafted URL could inject SQL into a grid query. Upgrade if you use `Grid` with request-driven sorting. No CVE has been assigned.

## [1.2.84] - 2026-10-08

### Added

- Pluggable grid filters: new `GridFilter` namespace with `TextFilter`, `DateFilter` (single date or range), `SelectFilter` (array or SQL source) and the `IGridFilter` interface.
- `Execute`, `ExecuteScalar`, `ExecuteScalarRow` and `ExecuteAll` accept a values array and run as prepared statements.

### Changed

- PHP 8.2 or higher is now required.
- Query-based dropdown filters load in the same parallel batch as the grid data when a `DBPool` is configured.

### Fixed

- Filter header row now closes every column cell.

### Security

- None.

## [1.2.83] - 2026-03-13

### Changed

- Link generation in the `Grid` class.

### Security

- None.

## [1.2.82] - 2026-03-13

### Changed

- PHPDoc comment fixes.

### Security

- None.

Releases before 1.2.82 are listed as tags on [GitHub](https://github.com/TrueArrowSoftware/TASLibPHP/tags).

[Unreleased]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.85...HEAD
[1.2.85]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.84...1.2.85
[1.2.84]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.83...1.2.84
[1.2.83]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.82...1.2.83
[1.2.82]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.81...1.2.82

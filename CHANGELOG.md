# Changelog

All notable changes to TASLibPHP are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

Every release has a **Security** heading listing the vulnerabilities fixed in it, or "None". See [SECURITY.md](SECURITY.md) to report one.

## [Unreleased]

### Security

- None.

## [1.2.86] - 2026-10-31

A security hardening release across the library. No CVE has been assigned to any item. Read **Changed** before upgrading: several behaviours are now stricter.

### Security

- **Sort parameters:** every sort input is checked against the columns the page offers and `asc`/`desc` (new `DataFormat::ReturnSortOrder`, `DataValidate::IsSortColumn`, `IsSortDirection`). This covers `Grid` (`ob`, `d`, and the session values) and the `orderby`/`direction` parameters of `Utility::CreateCSV`, `GenerateCSV`, `CreateReportPDF`, `GenerateReportPDF` and `CSV::CreateCSV`, which still put the URL value into `ORDER BY` in 1.2.85 and earlier (SQL injection). `ImageFile` validates the `orderby` it is given.
- **SQL:** table and column names are quoted with `DB::QuoteIdentifier`. `InsertBulk`, `InsertMultiArray`, `ReplaceMultiArrayByID` and `ReplaceArrayById` bind their values; before, they were written into the SQL text (SQL injection). `DB::Delete` only takes a numeric id and `GetAutoIncrementID` escapes the table name. `Utility::Paging` and `Grid` can bind values for `?` placeholders in the where condition.
- **Output encoding (XSS):** attribute values, option text, textarea content, pager links and grid row ids are always encoded (`HTML::Encode`), and `href`/`src` values that are `javascript:`, `vbscript:` or `data:` URLs are replaced by `#` (`HTML::Url`). Grid cells, messages, labels and PDF report cells are encoded when `Config::$EscapeOutput` is on, which is off by default.
- **Uploads:** the type is read from the file content, never from the name or the browser. Images must be JPEG, PNG, GIF, WebP or SVG within `MaxPixels`; SVG files are sanitized (new dependency `enshrined/svg-sanitize`) and limited by `MaxSvgSize`. Documents need an allowed extension and may not contain a page, script or program. Every upload is limited by `UserFile::$MaxFileSize`. The default file savers refuse paths outside the upload folder, and `ImageFile::GetResizePath` reads only `img-` session keys.
- **Exports:** CSV and PDF exports are written to unpredictable temporary files that are deleted afterwards. The `$filename` argument used to be a path under `cache/` (so `../x.php` could write anywhere) and is now only the download name. CSV cells that could run as a spreadsheet formula get a leading `'`, and values are quoted with real CSV rules. `wkhtmltopdf` runs with local file access disabled.
- **Network:** `DirectConnection` and `AsyncHttp` allow only http and https, also after a redirect, and cap connecting at 30 seconds. `DirectConnection::$VerifySSL` and `$Timeout` are new; `AsyncHttp` uses `$VerifySSL` as its default for `ssl_verify`. `AsyncQuery::runParallel` has a `$timeout`. `FTP` can connect over TLS (`$UseSSL`) and removes CR, LF and NUL from remote paths, so a path cannot add a second FTP command.
- **Mail:** `AppConfig['SMTP-TLS']` now forces TLS (STARTTLS, or SMTPS on port 465). The reply-to address is parsed properly, and a failed send no longer logs the mailer object, which holds the SMTP password.
- **Redirects and sessions:** new `Web::RedirectLocal` and `Web::IsLocalUrl` follow only same-site targets. `Web::ResetSession` issues a new session id. `Web::DownloadHeader` quotes the file name, sends `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`.
- **Authentication and permissions:** `DataFormat::GenerateVerificationCode` returns a random value (it was `md5($username << 2)`, computable by anyone). `Utility::CreateGUID` is random. `Permission` grants only on a clear yes. `Cron` lock names cannot point outside `cache/`, and creating a lock is atomic.
- **Secrets and data exposure:** the database, pool and FTP passwords are hidden from `var_dump` and `print_r`, and FTP errors no longer print the caller's variables. `TemplateHandler::PrepareContent` never puts an `AppConfig` entry whose name contains `pass`, `secret`, `token`, `private` or `credential` into a template, and data values cannot pull in other `{keywords}`. The file log can move outside the web root with `AppConfig['LogPath']` and is created with mode 0750.
- **Input handling:** `Entity` never loads members starting with `_` (so a posted `_tablename` cannot change the table), and a wrong-typed value leaves a typed property unchanged. `DataValidate::ValidateURL` accepts only http and https, and `IsDate` returns `false` instead of throwing for arrays and other non-strings. `Utility::AutoLoad` ignores anything that is not a class name. Grid page numbers are capped. The `color` grid type accepts only a colour value.

### Added

- `Config::$EscapeOutput` and `Config::GetUserRoleID()`, which reads the current user's role when asked instead of once at load.
- `HTML::Encode`, `HTML::Content`, `HTML::Url`, and a `$html` argument on `HTML::ReadOnly`. A grid column, form field or message with `'html' => true` stays raw when escaping is on.
- `DB::QuoteIdentifier`, and the `Grid` query option `params`.
- `DataFormat::ReturnSortOrder`, `DataValidate::IsSortColumn`, `DataValidate::IsSortDirection`.
- `CSV::WriteRow` (the formula guard) and the `CSV` class in its own `src/CSV.php`.
- `ImageFile::$MaxPixels`, `$MaxSvgSize`, `DetectExtension()`, `GetResizePath()`; `UserFile::$MaxFileSize`; `DocumentFile::$AllowedExtensions`.
- `DirectConnection::$VerifySSL`, `$Timeout`; `FTP::$UseSSL`; `AsyncQuery::runParallel(..., ?float $timeout)`.
- `Web::RedirectLocal`, `Web::IsLocalUrl` and the `AllowedHosts` setting.
- `AppConfig['LogPath']`.
- New dependency `enshrined/svg-sanitize`.

### Changed

- `GenerateVerificationCode()` returns a random code. Store it (hashed, with an expiry) and compare against the stored value: it can no longer be recomputed from the username, so code that recomputes it to check a link will fail.
- `DB::Delete()` returns `false` unless the id is numeric.
- Table and column names are quoted, so pass bare names (`users`, `db.users`), not names that already contain backticks or aliases. An empty name throws `InvalidArgumentException`.
- `InsertMultiArray` and `ReplaceMultiArrayByID` return `false` (and set an error) when there are no rows.
- The `$filename` of the CSV and PDF export functions is only the name the browser receives.
- Grid and UI links that are `javascript:`, `vbscript:` or `data:` URLs, including `data:` image sources, are rendered as `#`.
- `TemplateHandler` no longer fills placeholders named after secret-like `AppConfig` keys, for example `{ForgotPasswordURL}`.
- Non-numeric CSV text that starts with `=`, `+`, `-` or `@` gets a leading `'` in exports.
- Only JPEG, PNG, GIF, WebP and SVG images are accepted, and documents are limited to `DocumentFile::$AllowedExtensions`.
- `AppConfig['SMTP-TLS']` set to `true` now requires TLS where it used to be optional.
- A miss in `Utility::AutoLoad` is no longer written to the log.
- `HTML::Encode` keeps entities already in the text (`AT&amp;T` stays as is), so values escaped earlier are not escaped twice.

### Fixed

- `Entity::GetError()` threw a `TypeError` on every call.
- `EmailOptions::GetReplyTo()` always returned the sender.
- Prepared `INSERT`, `UPDATE` and `DELETE` run through `DB::Execute` with values now return `true` on success, and the statement is closed.
- `CSV` could not be autoloaded because its file was named `CSVHandler.php`. It now lives in `CSV.php`, and `CSVHandler.php` still loads it.
- `Grid` with page `0` or a very large page built an invalid limit.
- `ImageFile` no longer decodes images over `MaxPixels`, which could exhaust memory.
- A failing log insert could call itself forever.

## [1.2.85] - 2026-10-20

### Added

- Seek paging for grids: set `'pagingmode' => 'seek'` in the `Grid` options (or in the `$param` of `UI::HTMLGridFromRecordSet`/`UI::HTMLGridForPublic`) to page with an anchor row (`KeysetPager`) instead of a deep `OFFSET`. Mark sort columns with `'seek' => 'sql expression'` (or `true`). Non-seekable shapes and stale tokens fall back to offset paging.

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

[Unreleased]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.86...HEAD
[1.2.86]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.85...1.2.86
[1.2.85]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.84...1.2.85
[1.2.84]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.83...1.2.84
[1.2.83]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.82...1.2.83
[1.2.82]: https://github.com/TrueArrowSoftware/TASLibPHP/compare/1.2.81...1.2.82

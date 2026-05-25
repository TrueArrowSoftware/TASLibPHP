# Welcome to TASLibPHP

**TASLibPHP** is a lightweight PHP library designed to help developers build web applications faster — without the overhead of a full framework.

## What is TASLibPHP?

TASLibPHP (TrueArrow Software Library for PHP) is an open-source PHP library that provides a collection of reusable components and helpers for common web development tasks. It is designed for developers who want the power and convenience of utility classes without being locked into a heavy framework.

Current version: **1.2.83** | Requires: **PHP >= 8.0**

## Features

- **Database Access** – Simplified database connectivity and query execution via the `DB` class.
- **Data Validation** – Input validation helpers through `DataValidate`.
- **Data Formatting** – Tools for formatting and transforming data with `DataFormat`.
- **File Handling** – Manage user uploads, images, documents, and CSV files (`UserFile`, `ImageFile`, `DocumentFile`, `CSVHandler`).
- **HTML & UI Helpers** – Generate HTML elements and UI components programmatically (`HTML`, `UI`).
- **Template Engine** – Simple server-side templating with `TemplateHandler`.
- **Web Utilities** – HTTP and URL helpers through `Web` and `WebUI`.
- **Email Support** – Send emails via SMTP using [PHPMailer](https://github.com/PHPMailer/PHPMailer) integration.
- **FTP Support** – FTP file transfer utilities via the `FTP` class.
- **Async Processing** – Background task support through the `Async` module.
- **Cron Jobs** – Schedule recurring tasks with `Cron`.
- **Logging** – Application logging via the `Log` class.
- **Permissions** – Role-based access control with the `Permission` class.
- **Array Helpers** – Utility functions for array manipulation via `ArrayHelper`.
- **Grid / Pagination** – Data grid and pagination support with `Grid`.
- **Configuration** – Centralized application configuration through `Config`.

## Installation

Install TASLibPHP via [Composer](https://getcomposer.org/):

```bash
composer require truearrowsoftware/taslib
```

## Quick Start

1. Copy the `sample/configure.php` file into your project root and adjust the settings for your environment.
2. Include Composer's autoloader and the configuration file at the top of every page:

```php
<?php
require_once __DIR__ . '/configure.php';
```

3. You can now use any TASLibPHP class in the `TAS\Core` namespace:

```php
<?php
// Example: run a database query
$result = $GLOBALS['db']->Execute('SELECT * FROM users');
while ($row = $GLOBALS['db']->FetchArray($result)) {
    echo $row['username'] . PHP_EOL;
}
```

## Configuration

The `configure.php` file is the central place to set up your project. Key settings include:

| Setting | Description |
|---|---|
| `HOST` | Database host (default: `localhost`) |
| `LOCAL_USER` | Database username |
| `LOCAL_PASSWORD` | Database password |
| `LOCAL_DB` | Database name |
| `URL_FOLDERPATH` | Web root folder path |
| `ADMIN_EMAIL` | Administrator email address |
| `DeveloperMode` | Enable verbose error reporting (`true`/`false`) |
| `UseSMTPAuth` | Use SMTP for outgoing mail (`true`/`false`) |

For local overrides (e.g. on your development machine) create a `configure.local.php` file alongside `configure.php` — it will be loaded automatically and should **not** be committed to version control.

## Requirements

- PHP 8.0 or higher
- Extensions: `curl`, `json`, `filter`
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) ~6.1 (installed automatically via Composer)

## License

TASLibPHP is released under the [MIT License](../LICENSE).

## Contributing

Contributions, bug reports, and feature requests are welcome! Please open an issue or a pull request on [GitHub](https://github.com/TrueArrowSoftware/TASLibPHP).

---

> **Note:** This library is still under active development. Some features may not be fully tested. Check back regularly for updates and improved documentation.

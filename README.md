# TASLibPHP

[![Latest Version](https://img.shields.io/packagist/v/truearrowsoftware/taslib.svg)](https://packagist.org/packages/truearrowsoftware/taslib)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**TASLibPHP** (True Arrow Software Library for PHP) is a lightweight toolkit for building PHP web applications quickly, without the weight of a full framework. It gives you database access, data grids, validation, file handling, templating, email and async helpers under a single `TAS\Core` namespace.

**Current version:** `1.2.84`

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage Examples](#usage-examples)
  - [Database](#database)
  - [Data Grid](#data-grid)
  - [Grid Header Filters](#grid-header-filters)
  - [Parallel Queries](#parallel-queries)
- [Configuration](#configuration)
- [What's New in 1.2.84](#whats-new-in-1284)
- [Running Tests](#running-tests)
- [Contributing](#contributing)
- [License](#license)

---

## Features

| Area | Classes | What you get |
|---|---|---|
| **Database** | `DB`, `DirectConnection` | MySQLi wrapper with prepared-statement support, insert/update/upsert helpers, bulk inserts, JSON export |
| **Async** | `Async\DBPool`, `Async\AsyncQuery`, `Async\AsyncHttp`, `Async\FiberRunner` | Connection pooling, parallel MySQL queries, parallel HTTP requests, Fiber-based task runner |
| **Data Grid** | `Grid`, `GridFilter\*`, `UI\GridBootstrap` | Sortable, paged HTML grids with header filters, totals, row actions and Bootstrap rendering |
| **Entities** | `Entity` | Base model with table-driven validation, loading from DB/array and JSON serialization |
| **Validation & Formatting** | `DataValidate`, `DataFormat` | Input validation, sanitizing, date/number/currency formatting |
| **HTML & UI** | `HTML`, `UI`, `WebUI\*` | Form inputs, dropdowns from arrays or record sets, date/time elements |
| **Templating** | `TemplateHandler` | Keyword-based templates and navigation menu generation |
| **Files** | `UserFile`, `ImageFile`, `DocumentFile`, `CSVHandler`, `IO`, `FTP` | Uploads, image processing, documents, CSV import/export, FTP transfer |
| **Assets** | `Assets\AssetManager` | Asset registration and output for pages |
| **Infrastructure** | `Config`, `Cron`, `Log`, `Permission`, `Web`, `Utility`, `ArrayHelper` | Configuration, scheduled jobs, logging, role permissions, HTTP and array utilities |
| **Email** | via [PHPMailer](https://github.com/PHPMailer/PHPMailer) | SMTP mail with configurable TLS/auth |

## Requirements

- PHP **8.2** or higher
- MySQL / MariaDB (via `mysqli`, with `mysqlnd` for async queries)
- PHP extensions: `curl`, `json`, `filter`, `mysqli`
- [Composer](https://getcomposer.org/)

## Installation

```bash
composer require truearrowsoftware/taslib
```

To pin this release:

```bash
composer require truearrowsoftware/taslib:1.2.84
```

## Quick Start

1. Copy [`sample/configure.php`](sample/configure.php) to your project root and adjust it for your environment.
2. Optionally create `configure.local.php` next to it for machine-specific settings (keep it out of version control).
3. Include the configuration at the top of every entry point:

```php
<?php
require_once __DIR__ . '/configure.php';

$users = $GLOBALS['db']->ExecuteAll('SELECT UserID, Name FROM users WHERE Status = ?', [1]);
```

`configure.php` loads the Composer autoloader, connects `$GLOBALS['db']`, and fills `$GLOBALS['AppConfig']`.

## Usage Examples

### Database

All query methods accept an optional array of values. When values are passed, the query runs as a prepared statement, so user input never needs manual escaping.

```php
$db = $GLOBALS['db'];

// Result set
$rs = $db->Execute('SELECT * FROM orders WHERE CustomerID = ? AND Total > ?', [$customerId, 100.0]);
while ($row = $db->FetchArray($rs)) {
    echo $row['OrderID'], PHP_EOL;
}

// Single value / single row / all rows
$count = $db->ExecuteScalar('SELECT COUNT(*) FROM orders WHERE CustomerID = ?', [$customerId]);
$order = $db->ExecuteScalarRow('SELECT * FROM orders WHERE OrderID = ?', [$orderId]);
$rows  = $db->ExecuteAll('SELECT * FROM orders WHERE Status = ?', ['open']);

// Write helpers
$db->Insert('orders', ['CustomerID' => $customerId, 'Total' => 250.00]);
$newId = $db->GeneratedID();
$db->Update('orders', ['Total' => 300.00], $newId, 'OrderID');
$db->Delete('orders', $newId, 'OrderID');
```

### Data Grid

```php
use TAS\Core\Grid;

$options = Grid::DefaultOptions();
$options['gridid'] = 'orders';
$options['gridurl'] = 'orders.php';
$options['fields'] = [
    'OrderID'   => ['name' => 'Order #', 'type' => 'number'],
    'Customer'  => ['name' => 'Customer', 'type' => 'string'],
    'Total'     => ['name' => 'Total', 'type' => 'currency', 'showtotal' => true],
    'CreatedOn' => ['name' => 'Created', 'type' => 'date'],
    'IsPaid'    => ['name' => 'Paid', 'type' => 'onoff'],
];

$query = Grid::DefaultQueryOptions();
$query['basicquery'] = 'SELECT o.OrderID, c.Name AS Customer, o.Total, o.CreatedOn, o.IsPaid
                        FROM orders o JOIN customers c ON c.CustomerID = o.CustomerID';
$query['indexfield'] = 'OrderID';
$query['tablename'] = 'orders';
$query['defaultorderby'] = 'OrderID';
$query['defaultsortdirection'] = 'desc';

echo (new Grid($options, $query))->Render();
```

Supported column types include `string`, `longstring`, `number`/`numeric`, `currency`, `date`, `datetime`, `phone`, `onoff`, `flag`, `globalarray` and `callback`.

### Grid Header Filters

Enable header filters with `showheaderfilter`. Each column picks a filter by type (text for most, a date picker for `date`/`datetime`), or you can set one explicitly with the `filter` key:

```php
use TAS\Core\GridFilter\SelectFilter;
use TAS\Core\GridFilter\DateFilter;

$options['showheaderfilter'] = true;
$options['filterdata'] = $_POST;

// Dropdown from a static array
$options['fields']['Status']['filter'] = new SelectFilter(['open' => 'Open', 'closed' => 'Closed']);

// Dropdown from a query (value column, label column)
$options['fields']['Customer']['filter'] = new SelectFilter(
    'SELECT CustomerID, Name FROM customers ORDER BY Name', 'CustomerID', 'Name'
);

// Date range (renders "{gridid}-filter-{field}" and "...-end" inputs)
$options['fields']['CreatedOn']['filter'] = new DateFilter(range: true);
```

Filters only render inputs. Building the `WHERE` clause from the posted values stays in your code. Custom filters implement `TAS\Core\GridFilter\IGridFilter`.

### Parallel Queries

With a `DBPool`, independent queries run at the same time over separate connections:

```php
use TAS\Core\Async\DBPool;
use TAS\Core\Async\AsyncQuery;

$GLOBALS['dbpool'] = new DBPool(HOST, LOCAL_USER, LOCAL_PASSWORD, LOCAL_DB, maxConnections: 5);

$results = AsyncQuery::runParallel([
    'orders'    => 'SELECT * FROM orders ORDER BY OrderID DESC LIMIT 10',
    'customers' => 'SELECT COUNT(*) FROM customers',
], $GLOBALS['dbpool']);
```

When `$GLOBALS['dbpool']` is set, `Grid` automatically runs its data query, count query and any query-based `SelectFilter` sources in parallel.

## Configuration

Key settings in `configure.php` / `configure.local.php`:

| Setting | Description |
|---|---|
| `HOST`, `LOCAL_USER`, `LOCAL_PASSWORD`, `LOCAL_DB` | Database connection |
| `URL_FOLDERPATH` | Web root folder path |
| `ADMIN_EMAIL` | Administrator email address |
| `$GLOBALS['AppConfig']['DeveloperMode']` | Verbose error reporting |
| `$GLOBALS['AppConfig']['PageSize']` | Default grid page size |
| `$GLOBALS['AppConfig']['UseSMTPAuth']`, `SMTPServer`, `SMTPServerPort`, `SMTP-TLS` | Outgoing mail |
| `$GLOBALS['AppConfig']['UploadPath']`, `TemplatePath`, `cache` | File system locations |

A reference database schema is available in [`database/db.sql`](database/db.sql).

## What's New in 1.2.84

- **PHP 8.2+** is now required.
- **Prepared statements everywhere:** `Execute`, `ExecuteScalar`, `ExecuteScalarRow` and `ExecuteAll` accept a values array.
- **Pluggable grid filters:** new `GridFilter` namespace with `TextFilter`, `DateFilter` (single date or range) and `SelectFilter` (array or SQL source), plus the `IGridFilter` interface for custom filters.
- **Faster grids:** query-based dropdown filters load in the same parallel batch as the grid data when a `DBPool` is configured.
- Fixed the filter header row so every column cell is closed correctly.

## Running Tests

```bash
composer install
cp test/configure.local.sample.php test/configure.local.php   # set your test DB credentials
php ./vendor/bin/phpunit --bootstrap ./test/bootstrap.php ./test
```

## Contributing

Bug reports, feature requests and pull requests are welcome on [GitHub](https://github.com/TrueArrowSoftware/TASLibPHP/issues). Please include a short description, the PHP version and steps to reproduce for bugs.

## License

Released under the [MIT License](LICENSE). © True Arrow Software.

> **Note:** TASLibPHP is under active development. Some components are not yet fully covered by tests. Check the [docs](docs/Home.md) and release notes when upgrading.

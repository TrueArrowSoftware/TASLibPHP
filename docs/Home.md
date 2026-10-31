# Welcome to TASLibPHP

**TASLibPHP** is a lightweight PHP library for building web applications quickly, without the weight of a full framework. Everything lives under one namespace, `TAS\Core`.

Current version: **1.2.86** | Requires: **PHP >= 8.2** | License: [MIT](../LICENSE)

| Page | What is in it |
|---|---|
| **Home** (this page) | Install, setup, configuration and task-based guides |
| [API Reference](API-Reference.md) | Every public class and method |
| [Changelog](../CHANGELOG.md) | What changed in each release, including security fixes |
| [Contributing](../CONTRIBUTING.md) | Reporting bugs, sending patches, test and style rules |
| [Security policy](../SECURITY.md) | How to report a vulnerability |

## Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Setup](#setup)
4. [Configuration](#configuration)
5. [Guides](#guides)
   - [Database](#database)
   - [Entities](#entities)
   - [Validation and formatting](#validation-and-formatting)
   - [Data grids](#data-grids)
   - [Grid header filters](#grid-header-filters)
   - [Seek paging](#seek-paging)
   - [Parallel work (Async)](#parallel-work-async)
   - [Output escaping](#output-escaping)
   - [Files and images](#files-and-images)
   - [Email](#email)
   - [Templates](#templates)
   - [Redirects and downloads](#redirects-and-downloads)
   - [Logging, cron locks and permissions](#logging-cron-locks-and-permissions)
6. [Security notes](#security-notes)
7. [Running the tests](#running-the-tests)

## Features

| Area | Classes |
|---|---|
| Database | `DB`, `DirectConnection` |
| Async | `Async\DBPool`, `Async\AsyncQuery`, `Async\AsyncHttp`, `Async\FiberRunner` |
| Data grid | `Grid`, `GridFilter\*`, `KeysetPager`, `UI\GridBootstrap` |
| Entities | `Entity` |
| Validation and formatting | `DataValidate`, `DataFormat` |
| HTML and UI | `HTML`, `UI`, `WebUI\*` |
| Templating | `TemplateHandler` |
| Files | `UserFile`, `ImageFile`, `DocumentFile`, `CSV`, `IO`, `FTP`, `Interface\IFileSaver` |
| Infrastructure | `Config`, `Cron`, `Log`, `Permission`, `Web`, `Utility`, `ArrayHelper` |
| Email | `Utility::SendHTMLMail`, `Utility::DoEmail` (via [PHPMailer](https://github.com/PHPMailer/PHPMailer)) |

## Installation

```bash
composer require truearrowsoftware/taslib
```

Requirements:

- PHP 8.2 or higher
- Extensions: `curl`, `json`, `filter`, `mysqli` (with `mysqlnd` for the async query classes)
- MySQL or MariaDB
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) ~6.1 and [enshrined/svg-sanitize](https://github.com/darylldoyle/svg-sanitizer) (both installed by Composer; the second cleans uploaded SVG images)

## Setup

TASLibPHP expects a few globals to exist before you call it. A minimal `configure.php` that is enough for the database, grids and validation:

```php
<?php
session_start();
date_default_timezone_set('UTC');

require_once __DIR__ . '/vendor/autoload.php';

$GLOBALS['AppConfig'] = [
    'PhysicalPath' => __DIR__,          // used for cache/, logs, uploads
    'PageSize'     => 50,               // default grid page size
    'DebugMode'    => false,            // true prints queries
    'DeveloperMode' => false,           // true also redirects all email, see "Email"
    'Currency'     => '$',              // prefix for currency grid columns
];

$GLOBALS['db'] = new TAS\Core\DB('localhost', 'dbuser', 'dbpassword', 'dbname');
if (!$GLOBALS['db']->Connect()) {
    exit('Database connection failed');
}
```

Then, in any page:

```php
<?php
require_once __DIR__ . '/configure.php';

$users = $GLOBALS['db']->ExecuteAll('SELECT UserID, Name FROM users WHERE Status = ?', [1]);
```

[`sample/configure.php`](../sample/configure.php) is a fuller starting point. It also loads the enumeration, module and role tables used by `Permission`, so it needs those tables in your own schema. A reference schema for the tables the library itself uses (`document`, `emailcms`, `images`, `log`) is in [`database/db.sql`](../database/db.sql).

Put machine-specific values (database password, SMTP password) in a `configure.local.php` that is **not** committed to version control.

### The `Tables` map

Several classes read table names from `$GLOBALS['Tables']`, a map of logical name to real table name:

```php
$GLOBALS['Tables'] = [
    'log'      => 'log',        // Log::AddEvent
    'images'   => 'images',     // ImageFile
    'document' => 'document',   // DocumentFile
    'emailcms' => 'emailcms',   // Utility::DoEmail
    'userrole' => 'userrole',   // Permission
];
```

Define only the keys for the classes you use.

## Configuration

### `$GLOBALS['AppConfig']` keys read by the library

| Key | Used by | Meaning |
|---|---|---|
| `PhysicalPath` | `Cron`, `Log`, `CSV`, `Utility`, files | Project root. `cache/` under it holds logs, lock files and generated files. |
| `PageSize` | `Grid`, `Utility::Paging` | Default rows per page (grid default is 50 if unset) |
| `DebugMode` | `Grid`, `Utility`, `CSV` | `true` prints the SQL being run. Must be set when you use `Grid`. |
| `DeveloperMode` | `Utility::SendHTMLMail` | `true` sends every email to `DeveloperEmail` instead of the real recipient |
| `DeveloperEmail` | `Utility::SendHTMLMail` | Recipient used in developer mode |
| `Currency` | `Grid` | Prefix for `currency` columns, e.g. `$` |
| `UploadPath`, `UploadURL` | `UserFile`, `ImageFile`, `DocumentFile` | Where uploads are stored and the URL they are served from |
| `HomeURL` | files, `Grid` | Site base URL |
| `cache` | `ImageFile` | Folder for generated image files |
| `TemplatePath` | `TemplateHandler`, `Utility::DoEmail` | Folder with `.tpl` files (`mail.tpl` for emails) |
| `SiteName`, `SenderEmail` | `Utility::DoEmail` | Default sender name and address |
| `UseSMTPAuth`, `SMTPServer`, `SMTPServerPort`, `SMTPUsername`, `SMTPPassword` | `Utility::SendHTMLMail` | SMTP login. If `UseSMTPAuth` is not `true`, PHP `mail()` is used. |
| `SMTP-TLS` | `Utility::SendHTMLMail` | `true` requires TLS (STARTTLS, or SMTPS on port 465), so the login cannot be read by a man in the middle. Without it TLS is used only if the server offers it. |
| `AllowedHosts` | `Web::IsLocalUrl`, `Web::RedirectLocal` | Extra host names a request-supplied redirect may go to (array). The request's own host, `Domain` and the host of `HomeURL`, `SecureURL`, `NonSecureURL` and `AdminURL` are always allowed. |
| `Domain` | `Web::IsLocalUrl` | Your site's domain |
| `LogPath` | `Log::cLog` | Folder for the file log. Default is `cache/` under `PhysicalPath`, which may be served by the web server: set this to a folder outside the web root. |
| `NoImage_Listing` | `UI` | Placeholder image URL |

Every scalar `AppConfig` value is also available as a `{Key}` placeholder in [templates](#templates).

### `TAS\Core\Config`

Static settings you can change at runtime:

| Member | Default | Meaning |
|---|---|---|
| `Config::$DateFormatDB` | `Y-m-d` | Database date format |
| `Config::$DateTimeFormatDB` | `Y-m-d H:i:s` | Database date-time format |
| `Config::$DisplayDateFormat` | `m/d/Y` | Date format shown to users (used by `Grid` `date` columns) |
| `Config::$DisplayDateTimeFormat` | `m/d/Y H:i:s` | Date-time format shown to users |
| `Config::$EscapeOutput` | `false` | `true` encodes grid cells, messages, labels and PDF report cells, see [Output escaping](#output-escaping) |
| `Config::GetUserRoleID()` | | Role id of the signed-in user, read when called (`$UserRoleID` is only filled once, when the class loads). A value you assign to `$UserRoleID` wins. |
| `Config::$FileSaverImage`, `Config::$FileSaverDocument` | local-disk savers | Swap in your own `IFileSaver` to store files elsewhere (S3, Azure Blob, ...) |
| `Config::set($key, $value)`, `Config::get($key)` | | Your own settings; `get` returns `null` for an unknown key |

## Guides

### Database

`TAS\Core\DB` wraps `mysqli`. Pass a values array and the query runs as a prepared statement, so user input needs no manual escaping.

```php
$db = $GLOBALS['db'];

// One value, one row, all rows
$count = $db->ExecuteScalar('SELECT COUNT(*) FROM orders WHERE CustomerID = ?', [$customerId]);
$order = $db->ExecuteScalarRow('SELECT * FROM orders WHERE OrderID = ?', [$orderId]);
$rows  = $db->ExecuteAll('SELECT * FROM orders WHERE Status = ?', ['open']);

// Result set
$rs = $db->Execute('SELECT * FROM orders WHERE Total > ?', [100.0]);
while ($row = $db->Fetch($rs)) {      // associative array
    echo $row['OrderID'], PHP_EOL;
}

// Write helpers take column => value arrays
$db->Insert('orders', ['CustomerID' => $customerId, 'Total' => 250.00]);
$newId = $db->GeneratedID();
$db->Update('orders', ['Total' => 300.00], $newId, 'OrderID');
$db->Delete('orders', $newId, 'OrderID');
```

Things to know:

- `Execute` returns `false` on failure. `LastError()` and `LastErrors()` return what went wrong.
- `Insert`, `Update`, `Update2` and `InsertUpdate` bind the values from the array, and work out the column types from the table unless you pass a `$datatype` string.
- `Update2($table, $values, ['col' => $value, ...])` updates the rows matching all the given column conditions (joined with `AND`), instead of one id field. It returns `false` if the conditions array is empty.
- `InsertBulk`, `InsertMultiArray` and `ReplaceMultiArrayByID` bind their values and write the rows in chunks. Every row needs the same columns as the first row. They return `false` when there are no rows.
- Table and column names are quoted for you (`DB::QuoteIdentifier`). Pass bare names such as `orders` or `shop.orders`, not names that already contain backticks or an alias. `Delete` accepts only a numeric id and returns `false` for anything else.
- `DBRecordSet($table, $orderby, $where)` takes SQL text for `$orderby` and `$where`. Only pass text you wrote, never request data.
- Set `$db->Debug = true` to print each insert or update query.
- `DB::Count($rs)`, `DB::Reset($rs)`, `DB::GetColumns($table)` and `DB::GetTableInformation($table)` are static.

`DirectConnection::SendGET`, `SendPOST` and their `...Batch` variants call third-party HTTP endpoints with cURL.

### Entities

`Entity` is a base class for table-backed models. Declare a public property per column; `LoadFromDB` and `LoadFromArray` match the property name, lowercased, to the column or array key.

```php
use TAS\Core\Entity;

class Customer extends Entity
{
    public int $customerid = 0;
    public string $name = '';

    public function __construct(int $id = 0)
    {
        parent::__construct();
        $this->_tablename = 'customers';
        if ($id > 0) {
            $this->LoadFromDB($GLOBALS['db']->Execute('SELECT * FROM customers WHERE customerid = ?', [$id]));
        }
    }
}

$c = new Customer(7);
echo $c->IsLoaded() ? $c->name : 'not found';
echo $c->ToJson();
```

Validation helpers:

- `Entity::InputValidate($fields, $values)` checks form input against field definitions (`required`, `type` of `email`, `url` or `date`). Messages are collected in `Entity::GetErrors()`.
- `Entity::Validate($values, $table)` checks values against the real column types and sizes of a table.
- `Entity::ParsePostToArray($fields)` turns a posted form into a clean array by field type.

### Validation and formatting

```php
use TAS\Core\DataValidate;
use TAS\Core\DataFormat;

DataValidate::ValidateEmail('a@b.com');                 // true
DataValidate::ValidateURL('https://example.com');       // true
DataValidate::IsDate('2026-10-21');                     // true

DataFormat::FormatPhone('1234567890');                  // "(123) 456-7890"
DataFormat::CleanPhone('(123)-456-7890');               // "1234567890"
DataFormat::FormatBytes(1536000);                       // "1.46M"
DataFormat::CreateSlug('Hello World & Co!');            // "hello-world-co"
DataFormat::FormatString('jOHN smith');                 // "John Smith"
DataFormat::DateToDBFormat('10/21/2026 10:30 am');      // "2026-10-21 10:30:00"
DataFormat::DBToDateFormat('2026-10-21 10:30:00', 'd M Y'); // "21 Oct 2026"
```

`DataFormat::DoSecure` trims a string and strips script and HTML markup from it. It is not a substitute for prepared statements: for SQL, use the values-array form (see [Database](#database)).

### Data grids

`Grid` renders a sortable, paged HTML table from a query. It needs `$GLOBALS['db']` and the `AppConfig` key `DebugMode`. It also reads `PageSize` (optional, default 50) and `Currency` (for `currency` columns).

With the default offset paging, the grid's `tablename` must also appear as a **value** in `$GLOBALS['Tables']` (see [The `Tables` map](#the-tables-map)). Without it the pager throws "Invalid table name". Seek paging does not need it.

```php
use TAS\Core\Grid;

$GLOBALS['Tables']['orders'] = 'orders';     // lets the pager find the table

$options = Grid::DefaultOptions();
$options['gridid']  = 'orders';
$options['gridurl'] = 'orders.php';
$options['fields']  = [
    'OrderID'   => ['name' => 'Order #',  'type' => 'number'],
    'Customer'  => ['name' => 'Customer', 'type' => 'string'],
    'Total'     => ['name' => 'Total',    'type' => 'currency', 'showtotal' => true],
    'CreatedOn' => ['name' => 'Created',  'type' => 'date'],
    'IsPaid'    => ['name' => 'Paid',     'type' => 'onoff'],
];

$query = Grid::DefaultQueryOptions();
$query['basicquery'] = 'SELECT o.OrderID, c.Name AS Customer, o.Total, o.CreatedOn, o.IsPaid
                        FROM orders o JOIN customers c ON c.CustomerID = o.CustomerID';
$query['indexfield']           = 'OrderID';
$query['tablename']            = 'orders';
$query['defaultorderby']       = 'OrderID';
$query['defaultsortdirection'] = 'desc';

echo (new Grid($options, $query))->Render();
```

**Grid options** (`Grid::DefaultOptions()`):

| Key | Default | Meaning |
|---|---|---|
| `gridurl` | `''` | Page the grid links back to (falls back to `index.php`) |
| `gridid`, `tagname` | `mygrid`, `grid` | Identify the grid; sort choices are remembered in the session under these |
| `pagesize` | `AppConfig['PageSize']` or 50 | Rows per page |
| `allowsorting`, `allowpaging` | `true` | Turn header sorting or the pager off |
| `showtotalrecord`, `totalrecordtext` | `true`, `{totalrecord} Records` | Record count line |
| `allowselection` | `false` | Add a checkbox column |
| `roworder` | `false` | Mark the table for drag-to-reorder |
| `fields` | `[]` | Column definitions, see below |
| `option` | `[]` | Row action icons (edit, delete, ...). `$grid->DefaultIcon()` returns the standard pair. |
| `rowconditioncallback` | `[]` | Callable `fn($row, $cssClass)` returning an extra CSS class for a row |
| `norecordtext` | `No record found` | Text for an empty result |
| `showheaderfilter`, `filterdata` | `false`, `[]` | Header filters, see [below](#grid-header-filters) |
| `pagingmode` | `offset` | `offset` or `seek`, see [Seek paging](#seek-paging) |

**Query options** (`Grid::DefaultQueryOptions()`):

| Key | Meaning |
|---|---|
| `basicquery` | The `SELECT ... FROM ...` part, without `WHERE`/`ORDER BY`/`LIMIT` |
| `whereconditions` | The `WHERE ...` part, appended after `basicquery` |
| `params` | Values for the `?` placeholders in `whereconditions`, bound by the driver. Use this, not string concatenation, for anything that comes from a request. With `params` set the grid does not use the parallel `DBPool` path. |
| `defaultorderby`, `defaultsortdirection` | Sort used when the URL has none (`asc` or `desc`) |
| `orderby` | Extra tie-break sorts, like `['name asc']` |
| `noorderby` | `true` when your query already has its own `ORDER BY` |
| `indexfield` | Unique column; used for row ids and as the final sort tie-breaker |
| `tablename` | Used by the offset pager. It must be a key (or a value) of `$GLOBALS['Tables']`. |
| `pagingquery`, `pagingqueryend` | Replacement count query and its suffix, when the grid query is too complex to count directly |
| `recordshowlimit` | With paging off, show at most this many rows |

**Column definition** (`fields['Column']`):

| Key | Meaning |
|---|---|
| `name` | Header text (required) |
| `type` | One of `string`, `longstring`, `number`/`numeric`, `currency`, `date`, `datetime`, `phone`, `onoff`, `flag`, `globalarray`, `callback`/`cb`, `image`, `color` |
| `showtotal` | `number`, `currency` and `callback` columns: add a total row |
| `link`, `linkfield` | Make the cell a link. `{1}` in `link` is replaced by the row's `linkfield` value. |
| `function` | `callback` columns: `fn($row, $field)` returns the cell HTML |
| `DateFormat` | `date`/`datetime` columns: PHP date format |
| `number-decimal` | `number` columns: show two decimals |
| `postsymbol` | `currency` columns: put this after the number instead of the `Currency` prefix |
| `sortstring` | Sort by this SQL expression instead of the column name |
| `html` | Keep this column's text raw when `Config::$EscapeOutput` is on, see [Output escaping](#output-escaping) |
| `filter` | A [filter object](#grid-header-filters) for this column |
| `seek` | SQL expression for [seek paging](#seek-paging) |

Sorting is safe to expose: the `ob` (column) and `d` (`asc`/`desc`) request parameters, and any value kept in the session, are used only if they name a column the grid offers (a key or `sortstring` of `fields`, the default column, or an `orderby` entry). Anything else falls back to the default sort.

Rendering goes through `Grid::$UI`, a `UI\IGridUI`. It defaults to `UI\GridBootstrap`; assign your own implementation to change the markup.

The older `UI::HTMLGridFromRecordSet` and `UI::HTMLGridForPublic` are deprecated wrappers around `Grid`.

### Grid header filters

Set `showheaderfilter` and pick a filter per column (text for most columns, a date picker for `date`/`datetime`), or set one explicitly:

```php
use TAS\Core\GridFilter\SelectFilter;
use TAS\Core\GridFilter\DateFilter;

$options['showheaderfilter'] = true;
$options['filterdata'] = $_POST;

$options['fields']['Status']['filter'] = new SelectFilter(['open' => 'Open', 'closed' => 'Closed']);

$options['fields']['Customer']['filter'] = new SelectFilter(
    'SELECT CustomerID, Name FROM customers ORDER BY Name', 'CustomerID', 'Name'
);

$options['fields']['CreatedOn']['filter'] = new DateFilter(range: true);
```

Filters only **render** inputs (`{gridid}-filter-{field}`, plus `...-end` for a date range). Building the `WHERE` clause from the submitted values is your code's job, and it must use prepared statements or escaping. Write your own filter by implementing `GridFilter\IGridFilter::Render(string $name, array $filterdata): string`.

### Seek paging

Offset paging gets slow on deep pages of big tables. Seek paging (`KeysetPager`) keeps an anchor row in the page links instead:

```php
$options['pagingmode'] = 'seek';
$query['indexfield']   = 'OrderID';
$options['fields']['OrderID']['seek']   = true;
$options['fields']['CreatedOn']['seek'] = 'o.CreatedOn';   // SQL expression usable in WHERE
```

Rules:

- Every sortable column needs a `seek` key (`true`, or an SQL expression that works in `WHERE`, not a select alias).
- The expression must never be `NULL`, and the row must carry the raw value of that expression.
- `indexfield` is the last, unique tie-breaker. In a join, set `indexexpr` in the query options if the bare name is ambiguous.
- If the sort is not seekable, or the link's token is stale or missing, the grid falls back to offset paging, so every URL still shows the right page.

With `DebugMode` on, seek mode prints which mode was used and why it fell back.

### Parallel work (Async)

All the async helpers use PHP Fibers and need PHP 8.1+ (the library needs 8.2).

```php
use TAS\Core\Async\DBPool;
use TAS\Core\Async\AsyncQuery;
use TAS\Core\Async\AsyncHttp;
use TAS\Core\Async\FiberRunner;

// Independent queries at the same time, over separate connections
$GLOBALS['dbpool'] = new DBPool('localhost', 'dbuser', 'dbpassword', 'dbname', maxConnections: 5);
$r = AsyncQuery::runParallel([
    'orders'    => 'SELECT * FROM orders ORDER BY OrderID DESC LIMIT 10',
    'customers' => 'SELECT COUNT(*) FROM customers',
], $GLOBALS['dbpool']);                       // $r['orders'] and $r['customers'] are mysqli_result

// Several HTTP requests at once
$res = AsyncHttp::runParallel([
    'a' => ['url' => 'https://api.example.com/a'],
    'b' => ['url' => 'https://api.example.com/b', 'method' => 'POST', 'data' => 'k=v', 'timeout' => 10],
]);                                           // response bodies keyed by label, false on failure

// Run your own callables concurrently
$out = FiberRunner::run([fn() => 1 + 1, fn() => 'done']);
```

Each request config takes `url`, `method` (`GET` or `POST`), `data`, `headers`, `timeout` (seconds, default 30) and `ssl_verify` (defaults to `DirectConnection::$VerifySSL`, which is `false`). Set `DirectConnection::$VerifySSL = true` once in your configuration to check certificates for every request. Only `http` and `https` URLs are followed, also after a redirect, and connecting is limited to 30 seconds. `DirectConnection::$Timeout` limits a whole `DirectConnection` request (0 means no limit).

`AsyncQuery::runParallel($queries, $pool, 1000, 30.0)` takes a timeout in seconds as its fourth argument: when it runs out, the unfinished connections are closed and an exception is thrown. When `$GLOBALS['dbpool']` is a `DBPool`, `Grid` runs its data query, count query and query-based `SelectFilter` sources in parallel. Without a pool everything runs normally, one query after another.

`FiberRunner::runSettled($tasks)` keeps going when a task throws and returns `['results' => ..., 'errors' => ...]`. `FTP::PutBatch` and `FTP::GetBatch` upload and download many files in parallel. `CSV::ExportCSVAsync` exports large queries in parallel chunks.

### Output escaping

The library builds HTML from your data, so it encodes what it can without changing how existing pages look.

**Always encoded:** attribute values (`id`, `name`, `value`, `class`), `<option>` text, textarea content, pager links and grid row ids. `HTML::Encode()` is the function used, and you can call it yourself:

```php
use TAS\Core\HTML;

HTML::Encode('<b>"Tom" & Jerry</b>');    // &lt;b&gt;&quot;Tom&quot; &amp; Jerry&lt;/b&gt;
HTML::Encode('AT&amp;T');                // AT&amp;T  (an entity already in the text is kept, not escaped twice)
HTML::Url('javascript:alert(1)');        // #         (javascript:, vbscript: and data: URLs are replaced)
HTML::Url('/orders.php?a=1&b=2');        // /orders.php?a=1&amp;b=2
```

**Encoded only when you opt in:** content that may carry HTML you intended, namely grid `string`, `longstring` and `globalarray` cells, `HTML::ReadOnly` values, `UI::UIMessageDisplay` messages, check/radio list labels and PDF report cells. It is off by default so existing pages look the same. Turn it on to close stored XSS there:

```php
TAS\Core\Config::$EscapeOutput = true;
```

Then mark the places that really hold HTML with `'html' => true`: a grid column (`$options['fields']['Notes']['html'] = true`), a form field passed to `UI::GetFormHTML`/`GetReadOnlyHTML`, or a message passed to `UIMessageDisplay`. `HTML::Content($value, $html = false)` is the helper that applies the rule.

Links in a grid (`link` columns, row icons, `image` and `onoff` icons) always go through `HTML::Url`, so a `javascript:`, `vbscript:` or `data:` target is shown as `#`. This includes `data:image/...` image sources.

### Files and images

`ImageFile` and `DocumentFile` handle uploads, store a database row per file (tables `images` and `document`), and link a file to a record through a `LinkerType` and `linkerid`.

```php
$img = new TAS\Core\ImageFile();
$img->LinkerType = 'product';
$img->ThumbnailSize = [['width' => 120, 'height' => 90]];
$result = $img->Upload($_FILES, true, $productId);    // saves files and rows; each entry gets 'UploadStatus' and 'ID'

$images = TAS\Core\ImageFile::GetLinkerImage($productId, 'product');
```

Needs `AppConfig` `UploadPath`, `UploadURL`, `HomeURL`, `cache`, the `Tables` entries `images`/`document`, and the table definitions in [`database/db.sql`](../database/db.sql).

What is accepted (the name and type the browser sends are ignored; the file's content decides):

| Setting | Default | Meaning |
|---|---|---|
| `UserFile::$MaxFileSize` | 25 MB | Largest upload of any kind. PHP's `upload_max_filesize` still applies first. |
| `ImageFile::$MaxPixels` | 50,000,000 | Largest image (width x height); bigger ones are refused before they are decoded |
| `ImageFile::$MaxSvgSize` | 2 MB | Largest SVG |
| `DocumentFile::$AllowedExtensions` | pdf, txt, csv, rtf, doc(x), xls(x), ppt(x), odt, ods, odp, zip, jpg, jpeg, png, gif, webp | Documents with another extension are refused. Do not add types a server runs or a browser renders (`php`, `phtml`, `html`, `svg`, ...). |

Images must be JPEG, PNG, GIF, WebP or SVG. An SVG is cleaned by `enshrined/svg-sanitize` before it is stored (scripts, `on*` handlers, `javascript:` links, `foreignObject` and remote references are removed), and a file that cannot be cleaned is refused. Documents containing a page, script or program are refused. After a failed `Upload`, `Error` holds the reason.

Where the bytes go is decided by an `Interface\IFileSaver`. The default saver moves files into `UploadPath`; to use cloud storage, implement the interface and assign it before use:

```php
TAS\Core\Config::$FileSaverImage = new MyS3ImageSaver();
```

`CSV::ExportCSV($sql, $path, $fields)` writes a query to a CSV file at `$path`, and `CSV::CreateCSV($SQLQuery, $filename, $tagname, $param)` streams a grid-style query as a download. In `CreateCSV` (and `Utility::CreateCSV`, `GenerateCSV`, `CreateReportPDF`, `GenerateReportPDF`) the `$filename` is only the name the browser receives; the real file is a temporary one that is deleted afterwards. In CSV cells, text that starts with `=`, `+`, `-` or `@` and is not a number gets a leading `'`, so a spreadsheet shows it instead of running it as a formula.

### Email

```php
use TAS\Core\Utility;

Utility::SendHTMLMail('to@example.com', 'Subject', '<p>Hello</p>', 'Hello', 'noreply@example.com', 'My Site');
```

- It sends through SMTP when `AppConfig['UseSMTPAuth']` is `true`, otherwise through PHP `mail()`. Set `AppConfig['SMTP-TLS'] = true` to require TLS (SMTPS on port 465, STARTTLS otherwise).
- **With `DeveloperMode` on, every message goes to `AppConfig['DeveloperEmail']`** instead of the real recipient. Turn it off on production.
- `Utility::DoEmail($emailId, $keywords, $to, ...)` loads a stored template from the `emailcms` table, fills `{keyword}` placeholders and sends it. `$to` can hold several addresses separated by `,` or `;`. Reply-to and cc come from an `Options\EmailOptions` object.

### Templates

`TemplateHandler::PrepareContent($content, $keywords)` replaces `{key}` with values from `$keywords`, and also fills any scalar `AppConfig` value:

```php
echo TAS\Core\TemplateHandler::PrepareContent('Hi {name}, welcome to {SiteName}', ['name' => 'Sam']);
// Hi Sam, welcome to <your SiteName>
```

An array value is expanded as `{key-subkey}`. `GenerateNavigationMenu` builds a menu filtered by permission, and `GenerateNavigationMenuWithoutPermission` builds one for everyone.

Two safeguards: keywords are replaced in a single pass, so a value that looks like another keyword (`{another}`) stays text (an `AppConfig` placeholder such as `{HomeURL}` inside a value is still filled, which CMS content relies on), and `AppConfig` entries whose name contains `pass`, `secret`, `token`, `private` or `credential` (any case) are never placed in a template. A placeholder such as `{ForgotPasswordURL}` is therefore left as it is; pass that value in `$keywords` instead.

### Redirects and downloads

```php
use TAS\Core\Web;

Web::RedirectLocal($_GET['return'] ?? '', 'index.php');   // follows only same-site targets
Web::IsLocalUrl('https://evil.example/');                  // false
Web::IsLocalUrl('/admin/list.php?page=2');                 // true
```

- Use `RedirectLocal` whenever the target comes from the request (`?return=...`, a form field). It follows a relative path, or an http(s) URL on this request's host, your `Domain`, the hosts of your `*URL` settings or an `AllowedHosts` entry. Anything else, including `//host`, `/\host` and `javascript:`, goes to the fallback. `Web::Redirect` still goes anywhere, for URLs your code builds.
- `Web::DownloadHeader($name)` quotes the file name safely and sends `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`.
- `Web::ResetSession()` destroys the session and issues a new session id.

### Logging, cron locks and permissions

```php
TAS\Core\Log::AddEvent(['message' => 'Import failed', 'file' => $name], 'high');  // log table
TAS\Core\Log::cLog('plain text line');                                            // cache/logYYYYMMDD.log

if (TAS\Core\Cron::CreateScriptLock('nightly')) {      // false if another run holds the lock
    // ... work ...
    TAS\Core\Cron::UnlockScript('nightly');
}
```

- `Log::AddEvent` writes to the `log` table when `$GLOBALS['db']` exists, otherwise to the file log. The message array must contain a `message` key.
- A cron lock older than the timer (10 minutes by default) is treated as stale and removed.
- `Permission` loads role permissions from the `userrole` table (a JSON `permission` column per role). Set `modules` and `action`, call `Reload()`, then ask `CheckOperationPermission($module, $operation, $roleId)` or `CheckModulePermission($module, $roleId)`.

## Security notes

What the library does for you since 1.2.86: it quotes table and column names, binds the values of every `Insert`/`Update`/bulk call, validates every sort parameter, encodes attributes and options, checks upload content, and limits redirects and outgoing requests to safe targets. What stays yours:

- **Query text.** Use the values-array form of `Execute`, `ExecuteScalar`, `ExecuteScalarRow` and `ExecuteAll` for anything that comes from a request, and the grid's `params` option for the `?` placeholders in `whereconditions`. Never put request data into `basicquery`, `whereconditions`, `orderby` or the `$where`/`$orderby` of `DBRecordSet`.
- **Turn on `Config::$EscapeOutput`** unless every grid cell, message and label you show is trusted HTML (see [Output escaping](#output-escaping)).
- **Network.** Set `DirectConnection::$VerifySSL = true` and `SMTP-TLS` to `true` in production, and `FTP::$UseSSL` where the server supports FTPS.
- **Redirects.** Use `Web::RedirectLocal` for any redirect target taken from a request.
- **Verification codes.** `GenerateVerificationCode` returns a random value: store it hashed with an expiry and compare against what you stored.
- **Debug settings.** Keep `DeveloperMode` and `DebugMode` off in production: they print queries and reroute email. Point `AppConfig['LogPath']` outside the web root.
- Report vulnerabilities as described in [SECURITY.md](../SECURITY.md), and read the **Security** heading of each release in the [changelog](../CHANGELOG.md).

## Running the tests

```bash
composer install
cp test/configure.local.sample.php test/configure.local.php   # set your test database credentials
php ./vendor/bin/phpunit --bootstrap ./test/bootstrap.php ./test
```

The test bootstrap opens a database connection, so a reachable MySQL/MariaDB instance is needed. See [CONTRIBUTING.md](../CONTRIBUTING.md) for the rule that new features come with tests.

---

> This library is under active development and some components are not yet fully covered by tests. Check the [changelog](../CHANGELOG.md) when upgrading.

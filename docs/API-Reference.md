# API Reference

Every public class in `TAS\Core`, listed by area. For setup and worked examples see [Home](Home.md). Method names are PascalCase (`ExecuteAll`), except the `Async` classes, which use camelCase.

Conventions: `static` methods are called as `Class::Method()`. A parameter written `= value` is optional. Where the return value is not stated, the method returns `true`/`false` for success, or its result as described.

**Contents:** [Database](#database) · [Entities](#entities) · [Validation and formatting](#validation-and-formatting) · [Grid](#grid) · [HTML and UI](#html-and-ui) · [Async](#async) · [Files](#files) · [Infrastructure](#infrastructure) · [Utility](#utility) · [Notes](#notes)

---

## Database

### `DB`

MySQL wrapper over `mysqli`. Construct it, then call `Connect()`.

```php
$db = new TAS\Core\DB($server = 'localhost', $user = 'root', $password = '', $DBname = 'demo');
```

| Member | Description |
|---|---|
| `$Charset`, `$Collation` | Connection character set (`utf8mb4`) and collation (`utf8mb4_bin`) |
| `$Debug` | `true` prints insert and update queries |
| `$MySqlObject` | The underlying `mysqli` object |
| `$MysqlVersion`, `$lastError` | Server version and collected errors |
| `Connect()` | Open the connection. Returns `true` on success. |
| `IsConnected()` | `true` when connected |
| `CloseDB()` | Close the connection and clear the error log |
| `LastError()`, `LastErrors()` | The last error, or all errors, from the previous operations |
| `Escape($str)` | Escape a string for SQL (use prepared statements where you can) |
| `Execute($query, ?array $values = null)` | Run any query. With `$values` it runs as a prepared statement. Returns a result set, or `false` on failure. |
| `ExecuteScalar($query, ?array $values = null)` | First column of the first row, or `false` |
| `ExecuteScalarRow($query, ?array $values = null)` | First row as an array |
| `ExecuteAll(string $query, ?array $values = null): array` | All rows as an array of arrays |
| `ClearStoredResults()` | Clear pending result sets after a stored procedure call |
| `RowCount($result)` / `static Count($result)` | Number of rows in a result set (prefer `DB::Count`) |
| `GeneratedID()` | Auto-increment id of the last insert |
| `static Reset(mysqli_result $rs)` | Move a result set back to its first row |
| `Fetch($result)` | Next row as an associative array, or `false` |
| `FetchArray($result)` | Next row with both numeric and column-name keys |
| `FetchRow($result)` | Next row with numeric keys |
| `Insert($table, $values, $datatype = '')` | Insert one row from a `column => value` array |
| `Update($table, $values, $editid, $editfield, $datatype = '')` | Update the row where `$editfield = $editid` |
| `Update2(string $table, array $values, array $editcondition, string $datatype = '')` | Update rows matching all `column => value` conditions. `false` if the conditions array is empty. |
| `InsertUpdate($table, $values, $datatype = '')` | Insert, or update when the key already exists |
| `ReplaceArrayById($table, $values)` | `REPLACE` one row |
| `InsertBulk($table, $values, $failonError = false)` | Insert many rows (array of `column => value` arrays) in one query. Values are written into the SQL as given, not bound, and are not validated: escape untrusted data with `Escape()` first. |
| `InsertMultiArray($table, $values = [])` | Insert several rows |
| `ReplaceMultiArrayByID($table, $values = [])` | Replace several rows |
| `DBRecordSet($table, $orderby = '', $where = 'status = 1')` | All rows of a table matching a condition |
| `Delete($table, $id, $idfield)` | Delete the row where `$idfield = $id` |
| `FirstColumnArray($query)` | First column of every row, as a flat array |
| `static Columns($result)` | Column names of a result set |
| `static GetColumns($table)` | Column names of a table |
| `static GetTableInformation($table)` | Column types, sizes and nullability of a table |
| `static GetAutoIncrementID($table)` | The table's next auto-increment value |
| `static ToJSON(mysqli_result $recordset)` | Result set as a JSON string |

### `DirectConnection` (extends `Entity`)

HTTP calls to third-party servers using cURL.

| Method | Description |
|---|---|
| `static SendPOST($url, $request, $headerextra = '')` | POST and return the response |
| `static SendGET($url, $headerextra = '')` | GET and return the response |
| `static SendPOSTBatch(array $requests): array` | Several POSTs at once |
| `static SendGETBatch(array $urls): array` | Several GETs at once |

---

## Entities

### `Entity`

Base class for table-backed models. Subclasses set `$this->_tablename` and declare public properties named after the columns.

| Member | Description |
|---|---|
| `__construct()` | Reset the error list |
| `IsLoaded()` | `true` once loaded from the database or an array |
| `static SetError($error, $level = 0)` | Add an error message |
| `static GetError()` | The last error. See [Notes](#notes). |
| `static GetErrors(): array` | All errors (`message`, `level`) |
| `ToJson(): string` | The object as JSON |
| `ObjectAsArray()` | The object as an array |
| `EmailKeywords()` | The object flattened into lowercase `keyword => value` pairs for `TemplateHandler::PrepareContent` |
| `static GetTableName()` | The `_tablename` of the class |
| `LoadFromDB($rs)` | Fill typed properties (`string`, `int`, `float`, `bool`, `DateTime`) from the next row of a result set. Matches the lowercase property name to the column. |
| `LoadFromArray(array $data)` | Fill properties from an array whose keys are lowercase property names |
| `LoadFromRecordSet($rs)` | Older version of `LoadFromDB` (deprecated) |
| `static InputValidate($fields, $values)` | Check form values against field definitions (`required`, and `type` of `email`, `url`, `date`) |
| `static Validate($values, $table)` | Check values against a table's column types and sizes |
| `static ValidateAgainstTable($postdata, $table, $callback = null)` | Older validator (deprecated, use `InputValidate`) |
| `static GetFieldsGeneric($table = '', $param = [])` | Build form field definitions from a table's columns |
| `static ParsePostToArray($fields)` | Turn `$_POST` into a clean array for the given field definitions |

### `Assets\Asset` and `Assets\AssetsFolder` (extend `Entity`)

Records for stored assets and the folders they sit in. `Asset(int $id = 0)` with `Load(int $id)`; `AssetsFolder(DB $db, string $table, int $folderID = 0)` with `Load`, `Add`, `Update`, `Delete`, `GetFields`. Properties mirror the columns (`AssetID`, `Filename`, `URL`, `FolderID`, `Caption`, `ContentType`, ...).

---

## Validation and formatting

### `DataValidate` (all static)

| Method | Description |
|---|---|
| `ValidateEmail($email)` | Is it a valid email address |
| `ValidateURL($url)` | Is it a valid URL |
| `ValidateIP($ip)` | Is it a valid IP address |
| `ValidatePhoneFormat($phone, $length = 10)` | After removing spaces, dashes, brackets and dots, does the phone have `$length` digits. `$length` must be 5 to 12 or an exception is thrown. |
| `IsDate(string $date)` | Is it a valid date |
| `ContainDigits(string $str)` | Does it contain a digit |
| `ValidatePassword(string $password): bool` | Check against the password rules, or throw an exception |

### `DataFormat` (all static)

| Method | Description |
|---|---|
| `CleanPhone(string $phone, int $length = 10)` | Strip spaces, dashes, brackets, dots: `(123)-456-7890` becomes `1234567890` |
| `FormatPhone(string $phone, int $length = 10)` | `1234567890` becomes `(123) 456-7890` |
| `FormatString(string $str)` | Lowercase then capitalise each word |
| `FormatBytes($size, $precision = 2)` | `1536000` becomes `1.46M` |
| `CreateSlug(string $string)` | URL-safe lowercase slug |
| `RemoveNumberFormat(string $number)` | Keep only digits and dots and return a float (`"1,234.50"` gives `1234.5`) |
| `DBToDateTimeFormat($DBDate, $format = '')` | Database date-time to display format |
| `DBToDateFormat($DBDate, $format = '')` | Database date to display format |
| `DateToDBFormat($date, $readformat = 'm/d/Y H:i a')` | User input to `Y-m-d H:i:s` |
| `HumanizeTime(int $timestamp, ?int $starttime = null)` | Elapsed-time text such as "just now". `$starttime` defaults to now. Throws `InvalidArgumentException` for a non-positive timestamp. |
| `GetAge(int $dob): string` | Age in years and months from a timestamp |
| `DoSecure($a_value)` | Trim and strip script/HTML markup. Not a replacement for prepared statements. |
| `DoSecureArray(array $a_value): array` | `DoSecure` on every element |
| `DBString($a_value)` | Escape a string with the database function |
| `RemoveSlashes($a_value)` | Remove slashes added by web forms |
| `RemoveWhiteSpace($value)` | Remove line breaks and trim spaces and tabs |
| `CleanJunkCharacters($v)` | Remove junk characters |
| `ReturnNumericGet($var = 'id', $return = 'index.php')` | The positive integer in `$_GET[$var]`. Otherwise redirects to `$return`, or returns `false` when `$return` is `false`. |
| `ValidatePassword($str)` | Password strength check |
| `GenerateRandomPassword($length = 12)` | Random password string |
| `GenerateVerificationCode($username)` | Verification code derived from a username |
| `InverseHex($color)`, `RGBToHex(int $r, int $g, int $b)` | Colour helpers |

### `ArrayHelper` (all static)

| Method | Description |
|---|---|
| `ArraySum(array $arr, $memberName)` | Sum a property across an array of **objects** (not arrays) |
| `Search2DArray($needle, $column, $array)` | Key of the first row whose `$column` equals `$needle`, or `-1` |
| `SinglizeArray(array $a): array` | Flatten nested arrays; nested keys are joined with `-` (`['b' => ['c' => 2]]` gives `'b-c' => 2`) |
| `Contain(string $str, array $arr)` | Does `$str` contain any word of `$arr` (case-insensitive) |
| `ObjectToJsonLowercase($object)` | JSON of an object with all keys lowercased |
| `ChangeArrayKeyCase($array, $case = CASE_LOWER)` | Change key case recursively |

---

## Grid

### `Grid`

```php
$grid = new TAS\Core\Grid($options = null, $queryoptions = null);
echo $grid->Render();
```

| Member | Description |
|---|---|
| `$Options`, `$QueryOptions` | The two option arrays. See the tables in [Home: Data grids](Home.md#data-grids). |
| `static $UI` | The `UI\IGridUI` used for markup (default `UI\GridBootstrap`) |
| `static DefaultOptions(): array` | Default display options |
| `static DefaultQueryOptions(): array` | Default query options |
| `DefaultIcon(): array` | The standard `edit` and `delete` row icons |
| `Render(): string` | Run the queries and return the HTML |
| `ProcessColumnData($field, $val, $row, &$RowValueTotal)` | Format one cell and add to column totals |

### `GridFilter\IGridFilter` and implementations

| Class | Description |
|---|---|
| `IGridFilter` | `Render(string $name, array $filterdata): string`, returns the filter input HTML |
| `TextFilter` | Text box |
| `DateFilter(bool $range = false)` | Date input. With `$range`, adds a second `{name}-end` input. |
| `SelectFilter(array\|string $source, string $valueCol = '', string $labelCol = '', string $selectText = 'All')` | Dropdown. `$source` is a `value => label` array, or an SQL query with the value and label column names. |

### `KeysetPager`

Seek ("keyset") paging used by `Grid` when `pagingmode` is `seek`. You normally do not call it directly. See [Home: Seek paging](Home.md#seek-paging).

| Method | Description |
|---|---|
| `static Build($orderby, $direction, $extra, $fields, $indexField, $indexExpr = ''): ?self` | Create a pager, or `null` when a sort column is not seekable |
| `OrderBy(bool $reverse = false): string` | The `ORDER BY` list (reversed to read backwards from an anchor) |
| `Seek(array $values, bool $forward, bool $inclusive, callable $escape): string` | `WHERE` condition for the rows after or before an anchor |
| `static Where(string $where, string $condition): string` | Combine the grid's `WHERE` with the seek condition |
| `Hash(string $salt): string` | Ties a cursor to the sort and filter it was made for |
| `Encode(...)`, `Decode(string $token, string $hash)` | Make and read the cursor token |
| `Plan(int $page, int $pagesize, ?array $cursor, ?int $total = null): array` | How to read a page |
| `static Links(...)` | The pager bar, every link carrying the cursor |

### `UI\IGridUI`, `UI\GridBootstrap`

The markup hooks `Grid` calls while rendering: `GetBeforeTable`, `GetTableStart($id, $cssClass, $dataUrlPage)`, `GetAtTableHeadStart`, `GetAtTableHeader`, `GetAtTableHeadEnd`, `GetCssClassForSortingIcon`, `GetAtTableBody`, `GetAtTableFooter`, `GetTableEnd`, `GetAfterTable`, `GetNoRecordFound($text)`. `GridBootstrap` is the Bootstrap implementation. Implement `IGridUI` and assign it to `Grid::$UI` for other markup.

---

## HTML and UI

### `HTML` (all static)

Form control builders. Each returns an HTML string, with `$id`, `$value`, `$name`, `$isrequired` and a CSS class (default `form-control`).

| Method | Description |
|---|---|
| `InputBox`, `InputText`, `InputEmail`, `InputPassword`, `InputHidden`, `InputFile` | Text-like inputs (`InputText` is a textarea) |
| `InputDate`, `InputDateTime`, `InputColour` | Date, date-time and colour inputs |
| `InputCheckBox`, `InputRadio`, `InputSelect` | Choice inputs |
| `Label($label, $for = '', $isrequired = false)` | A label; required ones get the `requiredfield` class |
| `InputWrapper($inputcode)`, `FormField($label, $wrapper, $tag = '')`, `FormButton($html)` | Wrap controls into form rows |
| `ReadOnly($value, $id, $css)` | Read-only display of a value |

### `UI` (all static)

| Method | Description |
|---|---|
| `ArrayToDropDown($array, $selected)` | `<option>` tags from an array |
| `MultiArrayToDropDown($array, string $val, string $label, $selected)` | Options from an array of rows |
| `RecordSetToDropDown($rs, $selected, $indexCol, $labelCol, $showSelect = true, $selectText = 'Select')` | Options from a result set |
| `RecordSetToDataListOptions($rs, $labelCol)` | `<datalist>` options |
| `NumericRangeToDropDown($start, $end, $step, $selected, $showSelect = true, $showSelectName = 'Select')` | Numeric options |
| `MonthDropDown(string $selected = '')` | Month options |
| `ArrayToCheckRadioList(...)`, `RecordSetToCheckRadioList(...)` | Checkbox or radio lists |
| `FormFromTable($table, $param = [])` | A whole entry form for a table |
| `GetFormHTML($param = [])`, `GetReadOnlyHTML($param = [])` | Form HTML, editable or read-only |
| `UIMessageDisplay($messages)` | Show messages after a form action |
| `HTMLGridFromRecordSet`, `HTMLGridForPublic` | Deprecated wrappers over `Grid` |
| `GetEmailContent($EmailID)`, `ImageURLFinder($imageObj, $width, $height)` | Helpers used by email and images |

### `WebUI\*`

Object-style HTML elements. `IElement` has `Render(): string`. `Element` adds `AddChild(string $html)`, `AddChildElement(IElement $child)`, `SetAttribute(string $key, ?string $value)`. `InputDate` and `InputDateTime` take `$args` and have `SetValue($value)`; `InputTime` takes `$args`. Their CSS class names come from `Config::$WebUI_DateCSS` and `Config::$WebUI_DateTimeCSS`.

---

## Async

Fiber-based helpers. See [Home: Parallel work](Home.md#parallel-work-async).

| Class | Methods |
|---|---|
| `Async\DBPool` | `new DBPool($server, $user, $password, $dbName, $maxConnections = 5, $charset = 'utf8mb4', $collation = 'utf8mb4_bin', $debug = false)`. `acquire(): DB`, `release(DB $db)`, `getStats(): array`, `getMaxConnections(): int`, `shutdown()` |
| `Async\AsyncQuery` | `static runParallel(array $queries, DBPool $pool, int $pollIntervalUs = 1000): array` (results are `mysqli_result`), `static runParallelScalar(array $queries, DBPool $pool): array` (first value of each), `static runSequential(array $queries, ?DB $db = null): array` |
| `Async\AsyncHttp` | `static runParallel(array $requests, int $pollIntervalUs = 10000): array`, `static getBatch(array $urls): array`, `static postBatch(array $posts): array`. Request keys: `url`, `method`, `data`, `headers`, `timeout`, `ssl_verify`. |
| `Async\FiberRunner` | `static isSupported(): bool`, `static run(array $tasks): array` (re-throws a task's exception), `static runSettled(array $tasks): array` (returns `results` and `errors`), `static suspend(mixed $value = null)` |

---

## Files

### `UserFile` (base class)

Path and error handling for uploads. Members: `$Path`, `$FullPath`, `$BaseUrl`, `$FileType`, `$Error`, `$Errors`, static `$MAX_FILE_PER_FOLDER`. Methods: `GetFileName($ext)`, `SetError`, `CleanError`, `LastError`, `LastErrors`, `Connect`, `FindPathForNew($table = '')`, `FindFolder($fileid, $forURL = false)`, `FindFullPath($fileid)`.

### `ImageFile` (extends `UserFile`)

Properties: `$LinkerType`, `$FileSaver`, `$ThumbnailSize` (list of `['width' => , 'height' => ]`).

| Method | Description |
|---|---|
| `Validate($file = '')` | Accept image files only |
| `Upload($file, $save = true, $linkerid = 0)` | Store the files in a `$_FILES`-style array. Returns it with `UploadStatus` and `ID` per file. |
| `Save($file, $filedata, $linkerid = '', $outID = 0)` | Save the database row |
| `GetImageOnLinker(int $linkerid, bool $toponly = false, string $orderby = ...)` | Images of one record, for this object's `LinkerType` |
| `static GetLinkerImage(int $linkerID, string $linkerType, bool $topOnly = false, string $orderby = ...)` | Same, with the type given |
| `GetImageLinkerType(bool $toponly = false, string $orderby = ...)` | All images of this `LinkerType` |
| `GetImage($imageid, $orderby = ...)` | One image |
| `DeleteImage(int $imageid)`, `DeleteImageOnLinker($linkerid)` | Delete one image, or all images of a record |
| `SetDefautlImage($imageId, $linkerId)`, `SetImageCaption($imageId, $caption)` | Default image and caption |
| `GenerateThumbnails($path, $filename, $ext)`, `ResetThumbnails(int $imageid)`, `static DeleteThumbnails(int $imageid)` | Thumbnail management |
| `GetResizedImage($path, $w, $h)`, `DoResize(...)`, `static CreateImage($imageType, $path, $img)` | Resizing |
| `static GetResizedImageURL($path, $currenturl, $w, $h, $noImage, $resizeScript = '')` | URL of an on-the-fly resized image |
| `static ImageGrid($linkertype = 'cms', $filters = [], $parameters = [])` | HTML grid of images |
| `static CallBackImageUrl($row, $field)` | Grid callback that builds a thumbnail URL |

### `DocumentFile` (extends `UserFile`)

`new DocumentFile($linkertype = '')`. `Validate`, `Upload($file, $save = true, $linkerid = 0)`, `Save`, `GetDocument($docid)`, `GetDocumentOnLinker($linkerid)`, `static GetLinkerDocument(int $linkerid, $linkertype)`, `DeleteDocument(int $documentid)`, `DeleteDocumentOnLinker($linkerid)`, `static Delete($documentID)`, `static DownloadURL($document)`, `SetFileCaption`, `static DocumentForm(...)`, `static DocumentGrid(...)`.

### `Interface\IFileSaver` and the default savers

`IFileSaver` decides where file bytes go: `SaveFile(string $source, string $destination): bool`, `Copy($source, $destination, $filename): bool`, `GetContent(int $assetID)`, `ProcessFile(int $assetID)`, `SetClassObject(UserFile $object)`, `Delete($path, $filename): bool`. `Default\FileSaverDefaultImage` and `Default\FileSaverDefaultDocument` store on local disk. Replace them through `Config::$FileSaverImage` and `Config::$FileSaverDocument`.

### `CSV`

Defined in `src/CSVHandler.php`; `require_once` it, since Composer cannot autoload it (class and file names differ).

| Method | Description |
|---|---|
| `static CreateCSV($SQLQuery, $filename, $tagname, array $param = [])` | Build the query, write the file and send it as a download |
| `static ExportCSV($SQLQuery, $filename = '', array $fields = [])` | Write a query's rows to a CSV file |
| `static ExportCSVAsync(string $SQLQuery, string $filename = '', array $fields = [], int $chunkSize = 5000): bool` | Large export in parallel chunks (needs `$GLOBALS['dbpool']`, else falls back to `ExportCSV`) |

### `FTP`

`new FTP($server, $username, $password, $path = '/', $port = '21')`. `Connect()`, `Disconnect()`, `Put($local, $remote, $mode)`, `Get($local, $remote, $mode)`, `Delete($remote)`, `GetList($dir = '', $trypassive = true)`, `GetRawList(...)`. Parallel: `static PutBatch(array $files, $server, $username, $password, $path, $port, int $mode): array`, `static GetBatch(...)`.

### `IO`

`static GetExtension($filename)`: the extension after the last dot, or `''`.

---

## Infrastructure

| Class | Members |
|---|---|
| `Config` | Static `$DateFormatDB`, `$DateTimeFormatDB`, `$DisplayDateFormat`, `$DisplayDateTimeFormat`, `$WebUI_DateCSS`, `$WebUI_DateTimeCSS`, `$UserRoleID`, `$FileSaverImage`, `$FileSaverDocument`. `static set($key, $value)`, `static get($key)` (`null` if unset). |
| `Log` | `static AddEvent($message, $level)`: array with a `message` key; saved to the `log` table, or the file log when there is no database. `static cLog($msg)`: append a line to `cache/logYYYYMMDD.log`. |
| `Cron` | `static CreateScriptLock($script)`, `static IsScriptLocked($script, $timer = 10)` (a lock older than `$timer` minutes is stale and removed), `static UnlockScript($script)`. Lock files live in `cache/` under `AppConfig['PhysicalPath']`. |
| `Permission` | Set `$modules`, `$action`, `$usertype`; `Reload()` loads role permissions from the `userrole` table. `CheckOperationPermission($module, $operation, $userlevel)`, `CheckModulePermission($module, $userlevel)` (the `access` operation). Unknown combinations return `false`. |
| `Web` | `static AppendQueryString(string $url, string $querystring): string` (merges and keeps the `#fragment`), `static Redirect($url)` (sends `Location` and exits), `static DownloadHeader($file, $type = 'application/octet-stream')`, `static NoBrowserCache()`, `static ResetSession()` |
| `TemplateHandler` | `static PrepareContent(string $content, array $keywords)`, `static TemplateChooser(string $pagetemplate)`, `static InsertTemplateContent($Templatefile, $content_keyword)`, `static DOMInnerHTML(DOMNode $element)`, `static GenerateNavigationMenu($nav = [], $class = '', $returnLiOnly = false)`, `static GenerateNavigationMenuWithoutPermission(...)`. `$TemplateName` maps `single`, `home`, `admin`, `login`, `popup` to `.tpl` files. |
| `Options\EmailOptions` | `$replyTo`, `$replyName`, `$cc`; passed to `Utility::DoEmail` |
| `Assets\Asset`, `Assets\AssetsFolder` | See [Entities](#entities) |

---

## Utility

All static.

| Method | Description |
|---|---|
| `SendHTMLMail($to, $subject, $html_body, $text_body, $fromemail, $fromName = '', $returnpath = '', $attachment = null)` | Send mail with PHPMailer (SMTP or `mail()`). In `DeveloperMode` it goes to `AppConfig['DeveloperEmail']`. |
| `DoEmail(int $EmailID, array $keywords, $to, $sender = null, $attachment = null, ?EmailOptions $options = null)` | Send a stored template from the `emailcms` table. Several recipients are separated by `,` or `;`. |
| `Paging(string $tablename, string $pagename, int $start = 0, $condition = '', $querystring = '', $isMultiTable = false, $param = [])` | Build the page-number bar. `$tablename` must be in `$GLOBALS['Tables']`. `$param['pagesize']` overrides `PageSize`. |
| `CreateGUID()`, `CreateGUIDString($length = 10)` | Unique id, or a short id without dashes |
| `PasswordValidation($password)` | Password rule check |
| `ListFiles($filter, $directory)` | Directory contents |
| `SortArrayInOrder(array $array, array $order, ?string $key = null)` | Sort an array to follow another array's order |
| `Cast($destination, $sourceObject)` | Copy an object into another class |
| `AutoLoad($classname)` | Autoloader for classes in your project's `include/classes` folder |
| `ExportCSV`, `CreateCSV`, `GenerateCSV`, `CreateReportPDF`, `GenerateReportPDF` | Report and export helpers for the older grid query format |
| `Search2DArray`, `SinglizeArray`, `Contain` | Deprecated copies of the `ArrayHelper` methods |

---

## Notes

Behaviours worth knowing when you read the code:

- **`CSV` and `Assets\AssetManager`:** `CSVHandler.php` declares class `CSV` and `Assets/AssetManager.php` declares class `AssetsManager`. Neither name matches its file, so the Composer PSR-4 autoloader does not find them. `AssetsManager` is a stub with no methods yet.
- **Prepared statements:** only the values-array forms of `Execute`, `ExecuteScalar`, `ExecuteScalarRow`, `ExecuteAll`, `Insert`, `Update`, `Update2` and `InsertUpdate` bind their values. Anything you concatenate into a query string yourself, or pass to `InsertBulk`, must be escaped by you.
- **Deprecated:** `UI::HTMLGridFromRecordSet`, `UI::HTMLGridForPublic`, `Entity::LoadFromRecordSet`, `Entity::ValidateAgainstTable` and the `Utility` copies of the `ArrayHelper` methods are marked `@deprecated` in the source. Prefer their replacements named above.

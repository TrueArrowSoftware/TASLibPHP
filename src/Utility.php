<?php

namespace TAS\Core;

use PHPMailer\PHPMailer\PHPMailer;
use TAS\Core\Async\FiberRunner;

class Utility
{
    /**
     * Store the path for include file/classes from given path in autoload.
     *
     * @var [array] array of Physical class paths
     */
    public static $IncludePath;

    /**
     * @deprecated
     *
     * with CreateReportPDF function
     *
     * @param array $SQLQuery
     * @param string $filename
     * @param string $reporttitle
     * @param array $param
     * @param string $tagname
     * @param string $template
     * @return bool
     */
    public static function GenerateReportPDF($SQLQuery, $filename, $reporttitle, $param, $tagname, $template)
    {
        [$orderby, $orderdirection] = DataFormat::ReturnSortOrder($tagname, (string) ($param['defaultorder'] ?? ''), (string) ($param['defaultsort'] ?? ''), $param['fields'] ?? [], (array) ($SQLQuery['orderby'] ?? []));

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby . ' ' . $orderdirection)) {
                    $sortstring .= ', ' . $val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');

        $query = $SQLQuery['basicquery'] . $SQLQuery['where'] . " order by {$orderby} {$orderdirection} {$sortstring} ";
        return self::DownloadReportPDF($query, $filename, $reporttitle, $param, $template);
    }

    /**
     * @param array $SQLQuery
     * @param string $filename
     * @param string $reporttitle
     * @param array $param
     * @param string $tagname
     * @param string $template
     * @return bool
     */
    public static function CreateReportPDF($SQLQuery, $filename, $reporttitle, $param, $tagname, $template)
    {
        [$orderby, $orderdirection] = DataFormat::ReturnSortOrder($tagname, (string) ($SQLQuery['defaultorderby'] ?? ''), (string) ($SQLQuery['defaultsortdirection'] ?? ''), $param['fields'] ?? [], (array) ($SQLQuery['orderby'] ?? []));

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby . ' ' . $orderdirection)) {
                    $sortstring .= ', ' . $val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');

        $query = $SQLQuery['basicquery'] . $SQLQuery['whereconditions'] . " order by {$orderby} {$orderdirection} {$sortstring} ";
        return self::DownloadReportPDF($query, $filename, $reporttitle, $param, $template);
    }

    /**
     * @deprecated
     *
     * with CreateCSV function
     *
     * @param array $SQLQuery
     * @param string $filename
     * @param string $tagname
     * @param array $param
     * @return bool
     */
    public static function GenerateCSV($SQLQuery, $filename, $tagname, $param = [])
    {
        [$orderby, $orderdirection] = DataFormat::ReturnSortOrder($tagname, (string) ($param['defaultorder'] ?? ''), (string) ($param['defaultsort'] ?? ''), $param['fields'] ?? [], (array) ($SQLQuery['orderby'] ?? []));

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby . ' ' . $orderdirection)) {
                    $sortstring .= ', ' . $val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');

        $query = $SQLQuery['basicquery'] . $SQLQuery['where'] . " order by {$orderby} {$orderdirection} {$sortstring} ";
        return self::DownloadCSV($query, $filename, $param);
    }

    /**
     * Create CSV is compatible with HTMLGrid function's SQLQuery method.
     * If you need more direct method use ExportCSV. Also it force download.
     *
     * @param array $SQLQuery
     *                          HTMLGrid function comptaible SQLQuery to append sorting
     * @param string $filename
     *                          filename ex export.csv
     * @param string $tagname
     * @param array  $param
     *
     * @return bool
     */
    public static function CreateCSV($SQLQuery, $filename, $tagname, $param = [])
    {
        [$orderby, $orderdirection] = DataFormat::ReturnSortOrder($tagname, (string) ($SQLQuery['defaultorderby'] ?? ''), (string) ($SQLQuery['defaultsortdirection'] ?? ''), $param['fields'] ?? [], (array) ($SQLQuery['orderby'] ?? []));

        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby . ' ' . $orderdirection)) {
                    $sortstring .= ', ' . $val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');
        $query = $SQLQuery['basicquery'] . $SQLQuery['whereconditions'] . " order by {$orderby} {$orderdirection} {$sortstring} ";
        return self::DownloadCSV($query, $filename, $param);
    }

    /**
     * CSV of $query sent as a download. The file only lives in cache/ while it is sent, under a name nobody chose:
     * $filename is just the name the browser gets (it used to be a path under cache/, so "../x.php" wrote anywhere).
     */
    private static function DownloadCSV(string $query, $filename, $param)
    {
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }

        $header = [];
        foreach ($param['fields'] as $field => $val) {
            $header[$field] = $val['name'];
        }
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'header';
            print_r($header);
        }

        $filepath = tempnam($GLOBALS['AppConfig']['PhysicalPath'] . DIRECTORY_SEPARATOR . 'cache', 'csv');
        try {
            $ok = \TAS\Core\Utility::ExportCSV($query, $filepath, $header);
            if ($ok) {
                \TAS\Core\Web::DownloadHeader(basename((string) $filename));
                readfile($filepath);
            }
        } finally { // also when the query throws
            @unlink($filepath);
        }
        if ($ok) {
            exit;
        }

        return false;
    }

    /**
     * Rows of $query as an HTML table in $template, turned into a PDF by wkhtmltopdf and sent as a download. As with
     * DownloadCSV the files only live in cache/ while it runs and $filename is only the download name. The page may
     * read local files from the template's own folder (css, images) and nothing else on disk.
     */
    private static function DownloadReportPDF(string $query, $filename, $reporttitle, $param, $template)
    {
        $rs = $GLOBALS['db']->Execute($query);

        $reportContent['ReportTitle'] = $reporttitle;
        $reportContent['PageTitle'] = $reporttitle;
        $reportContent['Content'] = '';
        if (\TAS\Core\DB::Count($rs) > 0) {
            $reportContent['Content'] .= '<table width="100%"><tr>';
            foreach ($param['fields'] as $key => $val) {
                $reportContent['Content'] .= '<td>' . $val['name'] . '</td>';
            }
            $reportContent['Content'] .= '</tr>';

            while ($row = $GLOBALS['db']->Fetch($rs)) {
                $additionalClass = '';
                if (isset($param['rowcondition']) && is_array($param['rowcondition']) && $row[$param['rowcondition']['column']] == $param['rowcondition']['onvalue']) {
                    $additionalClass = $param['rowcondition']['cssclass'];
                }

                $reportContent['Content'] .= '<tr class="' . HTML::Encode($additionalClass) . ' datarow">';
                foreach ($param['fields'] as $key => $val) {
                    $reportContent['Content'] .= '<td>' . HTML::Content($row[$key], !empty($val['html'])) . '</td>';
                }
                $reportContent['Content'] .= '</tr>';
            }
            $reportContent['Content'] .= '</table>';
        }

        $reportContent['MetaExtra'] = '';
        $content = TemplateHandler::InsertTemplateContent($GLOBALS['AppConfig']['TemplatePath'] . DIRECTORY_SEPARATOR . $template, $reportContent);

        $base = tempnam($GLOBALS['AppConfig']['PhysicalPath'] . DIRECTORY_SEPARATOR . 'cache', 'rpt');
        $htmlfilepath = $base . '.html';
        $pdfpath = $base . '.pdf';
        $out = 1;
        if (false !== file_put_contents($htmlfilepath, $content)) {
            $cmd = ('localhost' == ($_SERVER['HTTP_HOST'] ?? '')) ? 'wkhtmltopdf' : 'xvfb-run wkhtmltopdf';
            $assets = dirname($GLOBALS['AppConfig']['TemplatePath']); // theme/ when TemplatePath is theme/template
            exec($cmd . ' --page-width 8.5in --page-height 11in --margin-left 0.5cm --margin-right 0 --margin-top 1.25cm --margin-bottom 0'
                . ' --disable-local-file-access --allow ' . escapeshellarg($assets) . ' ' . escapeshellarg($htmlfilepath) . ' ' . escapeshellarg($pdfpath), $output, $out);
        }
        if (0 == $out) {
            \TAS\Core\Web::DownloadHeader(basename((string) $filename));
            readfile($pdfpath);
        }
        @unlink($htmlfilepath);
        @unlink($pdfpath);
        @unlink($base);
        if (0 == $out) {
            exit;
        }

        return false;
    }

    /**
     * Generic function to create CSV file on disk.
     *
     * @param string $SQLQuery
     *                         Database SQL Query
     * @param string $filename
     *                         Physical path of csv file
     * @param array  $fields
     *                         list of fields to put in csv
     * @return bool
     */
    public static function ExportCSV($SQLQuery, $filename = '', $fields = [])
    {
        $rs = $GLOBALS['db']->Execute($SQLQuery);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'Total Record found' . \TAS\Core\DB::Count($rs);
        }
        if (\TAS\Core\DB::Count($rs) < 1) {
            return false;
        }
        $fh = '';
        if ('' == $filename) {
            $fh = fopen('export.csv', 'w+');
        } else {
            $fh = fopen($filename, 'w+');
        }
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        if (!is_array($fields) || 0 == count($fields)) {
            $fields = \TAS\Core\DB::Columns($rs);
        }

        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        $fieldindex = array_keys($fields);
        CSV::WriteRow($fh, array_map(fn ($field) => is_object($field) ? $field->name : $field, array_values($fields)));
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fieldindex);
        }
        while ($row = $GLOBALS['db']->FetchArray($rs)) {
            CSV::WriteRow($fh, array_map(fn ($val) => $row[$val], $fieldindex)); // quoting and formula guard
        }
        fclose($fh);

        return true;
    }

    /**
     * Auto Load implementation to enable Loading of classes from /include/classes folder.
     *
     * @param string $classname
     * @return void
     */
    public static function AutoLoad($classname)
    {
        $defaultpaths[] = $GLOBALS['AppConfig']['PhysicalPath'] . '/includes';
        $defaultpaths[] = $GLOBALS['AppConfig']['PhysicalPath'] . '/includes/lib';
        if (is_array(Utility::$IncludePath)) {
            foreach (Utility::$IncludePath as $path) {
                $defaultpaths[] = $path;
            }
        }

        // class_exists($userInput) lands here: only a class name may become part of a path to require
        if (!preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/', (string) $classname)) {
            return;
        }

        if (strpos($classname, '\\') > 0) {
            $t = explode('\\', $classname);
            $classname = $t[count($t) - 1];
        }
        foreach ($defaultpaths as $defaultpath) {
            if (file_exists($defaultpath . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php')) {
                require_once $defaultpath . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php';
            } elseif (file_exists($defaultpath . DIRECTORY_SEPARATOR . $classname . '.php')) {
                require_once $defaultpath . DIRECTORY_SEPARATOR . $classname . '.php';
            } elseif (file_exists($defaultpath . DIRECTORY_SEPARATOR . 'class.' . $classname . '.php')) {
                require_once $defaultpath . DIRECTORY_SEPARATOR . 'class.' . $classname . '.php';
            }
        }
        // A miss is not logged any more: with several autoloaders it is normal (composer's finds the class next), and the
        // DB row per miss ran for every library class on every request when this loader was registered first.
    }

    /**
     * New Paging function. Replacement of incFunctions.php.
     *
     * @param string $tablename
     * @param string $pagename
     * @param int $start
     * @param string $condition
     * @param string $querystring
     * @param bool $isMultiTable
     * @param array $param
     * @return string
     */
    public static function Paging(string $tablename, string $pagename, int $start = 0, $condition = '', $querystring = '', $isMultiTable = false, $param = [])
    {
        global $tables;
        // how many link pages to show

        $pagesize = $param['pagesize'] ?? $GLOBALS['AppConfig']['PageSize'];

        $nLinks = 10;
        if (!isset($tables[$tablename])) {
            $_tablename = array_keys($GLOBALS['Tables'], $tablename);
            if (count($_tablename) > 0) {
                $tablename = $_tablename[0];
            } else {
                throw new \Exception("Invalid table name: {$tablename}");
            }
        }
        if ($isMultiTable) {
            $query = $condition;
        } else {
            $query = 'select count(*) from ' . $GLOBALS['Tables'][$tablename] . " {$condition}";
        }

        if (true == $GLOBALS['AppConfig']['DebugMode']) {
            echo 'Paging Query : ' . $query . "\r\n";
        }

        // $pageQuery = parse_url($pagename, PHP_URL_QUERY);
        // $pagename = str_replace('?'.$pageQuery, '', $pagename);
        // parse_str($querystring, $userQuery);
        // parse_str($pageQuery, $pageQuery2);

        // $querystring = array_merge($userQuery, $pageQuery2);
        // $pagename = $pagename.((count($querystring) > 0) ? '?'.http_build_query($querystring) : '');

        $pagename = Web::AppendQueryString($pagename, $querystring);

        $num = $GLOBALS['db']->ExecuteScalar($query, $param['params'] ?? null); // values for ? in $condition
        $bar = [];
        $pages = 1;
        if ($num > $pagesize) {
            $pages = $num / $pagesize;
            $pages = ceil($pages);
            if (true == $GLOBALS['AppConfig']['DebugMode']) {
                echo 'Page found ' . $pages;
            }
            if ($pages > 0) {
                // Has few Pages
                $previous = $start - 1;
                if ($previous > 0) {
                    $bar[] = '<a href="' . HTML::Encode(Web::AppendQueryString($pagename, 'page=1')) . '" class="ui-state-default ui-corner-all" ><span class="fa fa-angle-double-left"></span></a> ';
                    $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=' . $previous)) . "' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-left\"></span></a> ";
                }
                $startl = $start - ($start % $nLinks) + 1;
                $endl = $startl + $nLinks - 1;
                $endl = min($pages, $endl);
                if ($start > ($nLinks - 1)) {
                    $nStart = 1;
                    $prev = $startl - $nLinks;
                    $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=' . $prev)) . "' class=\"ui-state-default ui-corner-all\" >{$prev}</a> <li>....</li> ";
                } else {
                    $nStart = 0;
                }
                for ($i = $startl - $nStart; $i <= $endl; ++$i) {
                    if ($i == $start) {
                        $bar[] = '<a href="#" class="ui-state-default ui-corner-all active">' . $i . '</a>'; // no need to create a link to current page
                    } else {
                        if (1 == $i) {
                            $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=1')) . "' class=\"ui-state-default ui-corner-all\">{$i}</a>";
                        } else {
                            $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=' . $i)) . "' class=\"ui-state-default ui-corner-all\">{$i}</a>";
                        }
                    }
                }
                $next = $start + 1;
                if ($next <= $pages) {
                    $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=' . $next)) . "' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-right\"></span></a> ";
                    $bar[] = "<a href='" . HTML::Encode(Web::AppendQueryString($pagename, 'page=' . $pages)) . "' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-double-right\"></span></a>";
                }
            }
        }

        return '<ul><li>' . implode('</li><li>', $bar) . '</li></ul><div class="showcurrentpage">' . $start . ' of ' . $pages . '</div>';
    }

    /**
     * Return the list of Directory content.
     *
     * @param string $filter
     *                          regular expression pattern input for preg_match function call
     * @param string $directory
     *                          Directory to look for, make sure you have read permission to directory
     * @return array
     */
    public static function ListFiles($filter, $directory)
    {
        $output = [];
        if ($handle = opendir($directory)) {
            while (false !== ($entry = readdir($handle))) {
                if (!in_array($entry, [
                    '.',
                    '..',
                ]) && !is_dir($directory . DIRECTORY_SEPARATOR . $entry)) {
                    if (preg_match($filter, $entry)) {
                        $output[] = $entry;
                    }
                }
            }
            closedir($handle);
        }

        return $output;
    }

    /**
     * Search in 2D array for value.
     *
     * @deprecated 2.0 Use ArrayHelper::Search2DArray Instead.
     *
     * @param mixed $needle
     * @param string|int $column
     * @param array $array
     * @return int|string
     */
    public static function Search2DArray($needle, $column, $array)
    {
        foreach ($array as $key => $val) {
            if ($val[$column] == $needle) {
                return $key;
            }
        }

        return -1;
    }

    /**
     * Convert multi-dimensions array to single array.
     *
     * @deprecated 2.0 Use ArrayHelper::SinglizeArray Instead.
     *
     * @param array $a
     * @return array
     */
    public static function SinglizeArray($a)
    {
        $output = [];
        foreach ($a as $i => $k) {
            if (is_array($k)) {
                $t = \TAS\Core\Utility::SinglizeArray($k);
                foreach ($t as $i1 => $k) {
                    $output[$i . '-' . $i1] = $k;
                }
            } else {
                $output[$i] = $k;
            }
        }

        return $output;
    }

    /**
     * Sort an given array in given Order Array, use $key for Associative error.
     * check : https://stackoverflow.com/a/61267293/1160395.
     *
     * @deprecated 2.0 Use ArrayHelper::SortArrayInOrder instead.
     *
     * @param array $array
     * @param array $order
     * @param string|null $key
     * @return void
     */
    public static function SortArrayInOrder(array &$array, array $order, ?string $key = null)
    {
        $dict = array_flip($order);
        $positions = array_map(function ($elem) use ($dict, $key) {
            if (null == $key) {
                return $dict[$elem] ?? INF;
            }

            return $dict[$elem[$key]] ?? INF;
        }, $array);
        array_multisort($positions, $array);
    }

    /**
     * Class casting ,ex.
     * $x = cast('A',$b);.
     *
     * @param object|string $destination
     * @param object        $sourceObject
     *
     * @return object
     */
    public static function Cast($destination, $sourceObject)
    {
        if (is_string($destination)) {
            $destination = new $destination();
        }
        $sourceReflection = new \ReflectionObject($sourceObject);
        $destinationReflection = new \ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination, $value);
            } else {
                $destination->{$name} = $value;
            }
        }

        return $destination;
    }

    /**
     * Contain word in array list.
     *
     * @deprecated 2.0 Use ArrayHelper::Contain instead.
     *
     * @param string $str
     * @param array $arr
     * @return bool
     */
    public static function Contain($str, array $arr)
    {
        return ArrayHelper::Contain((string)$str, $arr);
    }

    /**
     * Send email from EMAIL CMS.
     *
     * @param int        $EmailID
     * @param array      $keywords
     * @param string|array $to
     * @param null|string $sender
     * @param null|mixed $attachment
     * @param \TAS\Core\Options\EmailOptions|null $options
     * @return bool
     */
    public static function DoEmail(int $EmailID, array $keywords, $to, $sender = null, $attachment = null, ?\TAS\Core\Options\EmailOptions $options = null)
    {
        if (null == $sender) {
            $sender = $GLOBALS['AppConfig']['SenderEmail'];
        }

        if ($to == null || empty($to)) {
            return false;
        }

        $row = UI::GetEmailContent($EmailID);
        $finalcontent = $row['content'];
        if (!is_array($row)) {
            return false;
        }
        $sendername = ($sender == $GLOBALS['AppConfig']['SenderEmail']) ? $GLOBALS['AppConfig']['SiteName'] : $sender;
        if (1 == $row['usetemplate']) {
            $content = file_get_contents($GLOBALS['AppConfig']['TemplatePath'] . '/mail.tpl');
            if ('' != $content) {
                $finalcontent = $content;
            }
            $finalcontent = str_replace('{Content}', $row['content'], $finalcontent);
        }

        $content = TemplateHandler::PrepareContent($finalcontent, $keywords);
        $subject = TemplateHandler::PrepareContent($row['subject'], $keywords);
        $to = strpos($to, ';') > 0 ? explode(';', $to) : explode(',', $to);

        $replyTo = $sender;
        if ($options != null) {
            $replyTo = $options->GetReplyTo($sender);
        }
        if (is_array($to)) {
            // Use Fibers to send emails in parallel when multiple recipients
            if (count($to) > 1 && FiberRunner::isSupported()) {
                $tasks = [];
                foreach ($to as $emailto) {
                    if (!\TAS\Core\DataValidate::ValidateEmail($emailto)) {
                        continue;
                    }
                    $tasks[] = function () use ($emailto, $subject, $content, $sender, $sendername, $replyTo, $attachment) {
                        return self::SendHTMLMail($emailto, $subject, $content, '', $sender, $sendername, $replyTo, $attachment);
                    };
                }
                if (empty($tasks)) {
                    return false;
                }
                $outcome = FiberRunner::runSettled($tasks);
                $output = true;
                foreach ($outcome['results'] as $result) {
                    if (!$result) {
                        $output = false;
                    }
                }
                return $output;
            }

            // Sequential fallback for single recipient or no Fiber support
            $output = true;
            foreach ($to as $emailto) {
                if (!\TAS\Core\DataValidate::ValidateEmail($emailto)) {
                    return false;
                }
                if (!self::SendHTMLMail($emailto, $subject, $content, '', $sender, $sendername, $replyTo, $attachment)) {
                    $output = false;
                }
            }

            return $output;
        }
        if (!\TAS\Core\DataValidate::ValidateEmail($to)) {
            return false;
        }

        return self::SendHTMLMail($to, $subject, $content, '', $sender, $sendername, $replyTo, $attachment);
    }

    /**
     * Send Email using PHPMailer with or without SMTP.
     *
     * @param string|array $to
     * @param string $subject
     * @param string $html_body
     * @param string $text_body
     * @param string $fromemail
     * @param string $fromName
     * @param string $returnpath
     * @param mixed $attachment
     * @return bool
     */
    public static function SendHTMLMail($to, $subject, $html_body, $text_body, $fromemail, $fromName = '', $returnpath = '', $attachment = null)
    {
        $text_body = null == $text_body ? '' : $text_body;
        $mailstat = true;
        $mail = new PHPMailer();

         // Set character encoding to UTF-8 BEFORE setting any content
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        $mail->IsMail();
        if (true == $GLOBALS['AppConfig']['UseSMTPAuth']) {
            $mail->IsSMTP();
            $mail->SMTPAuth = true; // enable SMTP authentication
            $mail->Host = $GLOBALS['AppConfig']['SMTPServer']; // sets GMAIL as the SMTP server
            $mail->Port = $GLOBALS['AppConfig']['SMTPServerPort'] ?? 25; // set the SMTP port for the GMAIL server
            // SMTP-TLS makes TLS a must, so a man in the middle can't strip STARTTLS and read the login; without it
            // PHPMailer still upgrades when the server offers TLS (as before)
            $mail->SMTPSecure = empty($GLOBALS['AppConfig']['SMTP-TLS']) ? ''
                : (465 == (int) $mail->Port ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS);
            $mail->Username = $GLOBALS['AppConfig']['SMTPUsername']; // GMAIL username
            $mail->Password = $GLOBALS['AppConfig']['SMTPPassword'];
        }

        if (true == $GLOBALS['AppConfig']['DeveloperMode']) {
            $to = $GLOBALS['AppConfig']['DeveloperEmail'];
        }

        if ('' != $returnpath) {
            // a bare address as before (its name is the address), or "Name <address>" from EmailOptions::GetReplyTo
            $reply = PHPMailer::parseAddresses($returnpath)[0] ?? ['address' => $returnpath, 'name' => ''];
            $mail->AddReplyTo($reply['address'], '' !== $reply['name'] ? $reply['name'] : $reply['address']);
        }
        if (null != $html_body && !empty($html_body)) {
            $mail->isHTML(true);
        }
        $mail->From = $fromemail;
        $mail->FromName = $fromName;
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = (empty($text_body) ? $html_body : $text_body);

        $mail->AddAddress($to);
        if (null != $attachment && !empty($attachment)) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
            \TAS\Core\Log::AddEvent([ // not the $mail object: it holds the SMTP password
                'message' => 'IncFunction SendHTMLMail Failed !!!',
                'Mail Error' => $mail->ErrorInfo,
            ], 'debug');
            $mailstat = false;
        }

        return $mailstat;
    }

    /**
     * Create a uniq GUID in {8}-{4}-{4}-{4}-{12}.
     */
    public static function CreateGUID()
    {
        // random, same format as before; the old one hashed the clock (uniqid + date), so it could be guessed
        $hash = strtoupper(bin2hex(random_bytes(16)));

        return substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);
    }

    /**
     * Wrapper for CreateGUID to cut the string in given length and without -.
     *
     * @param int $length
     */
    public static function CreateGUIDString($length = 10)
    {
        $GUID = self::CreateGUID();
        $removeDashGUID = str_replace('-', '', $GUID);

        return substr($removeDashGUID, 0, $length);
    }

    /**
     * Password validation for some rules.
     *
     * @deprecated 1.1.0 use \TAS\Core\DataValidate::ValidatePassword instead.
     *
     * @param [type] $password
     */
    public static function PasswordValidation($password)
    {
        // one rule set, kept in DataValidate; this returns its message instead of throwing it
        try {
            return DataValidate::ValidatePassword((string) $password);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}

<?php

namespace TAS\Core;

use PHPMailer\PHPMailer\PHPMailer;

class Utility
{
    public static $IncludePath;
    
    /**
     * @deprecated
     * 
     * with CreateReportPDF function
     *
     */
    public static function GenerateReportPDF($SQLQuery, $filename, $reporttitle, $param, $tagname, $template)
    {
        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $param['defaultorder'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $param['defaultsort'])));
        
        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');
        
        $query = $SQLQuery['basicquery'].$SQLQuery['where']." order by $orderby $orderdirection $sortstring ";
        $rs = $GLOBALS['db']->Execute($query);
        
        $htmlfilepath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.uniqid().'.html';
        $filecreated = false;
        
        $reportContent['ReportTitle'] = $reporttitle;
        $reportContent['PageTitle'] = $reporttitle;
        $reportContent['Content'] = ''; // $query . "\r\n : :: ". \TAS\Core\DB::Count($rs);
        if (\TAS\Core\DB::Count($rs) > 0) {
            $reportContent['Content'] .= '<table width="100%"><tr>';
            foreach ($param['fields'] as $key => $val) {
                $reportContent['Content'] .= '<td>'.$val['name'].'</td>';
            }
            $reportContent['Content'] .= '</tr>';
            
            while ($row = $GLOBALS['db']->Fetch($rs)) {
                if (isset($param['rowcondition']) && is_array($param['rowcondition'])) {
                    if ($row[$param['rowcondition']['column']] == $param['rowcondition']['onvalue']) {
                        $additionalClass = $param['rowcondition']['cssclass'];
                    } else {
                        $additionalClass = '';
                    }
                } else {
                    $additionalClass = '';
                }
                
                $reportContent['Content'] .= '<tr class="'.$additionalClass.' datarow">';
                foreach ($param['fields'] as $key => $val) {
                    $reportContent['Content'] .= '<td>'.$row[$key].'</td>';
                }
                $reportContent['Content'] .= '</tr>';
            }
            $reportContent['Content'] .= '</table>';
        }
        
        $reportContent['MetaExtra'] = '';
        $content = TemplateHandler::InsertTemplateContent($GLOBALS['AppConfig']['TemplatePath'].DIRECTORY_SEPARATOR.$template, $reportContent);
        file_put_contents($htmlfilepath, $content);
        $pdfpath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename;
        $out = 1;
        if (file_exists($htmlfilepath)) {
            $cmd = 'xvfb-run wkhtmltopdf';
            if ($_SERVER['HTTP_HOST'] == 'localhost') {
                $cmd = 'wkhtmltopdf';
            }
            exec($cmd." --page-width 8.5in --page-height 11in --margin-left 0.5cm --margin-right 0 --margin-top 1.25cm --margin-bottom 0 \"$htmlfilepath\" \"$pdfpath\"", $output, $out);
        }
        if ($out == 0) {
            \TAS\Core\Web::DownloadHeader($filename);
            @unlink($htmlfilepath);
            readfile($pdfpath);
            exit();
        } else {
            return false;
        }
    }

    
    public static function CreateReportPDF($SQLQuery, $filename, $reporttitle, $param, $tagname, $template)
    {
        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $SQLQuery['defaultorderby'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $SQLQuery['defaultsortdirection'])));
        
        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');
        
        $query = $SQLQuery['basicquery'].$SQLQuery['whereconditions']." order by $orderby $orderdirection $sortstring ";
        $rs = $GLOBALS['db']->Execute($query);
        
        $htmlfilepath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.uniqid().'.html';
        $filecreated = false;
        
        $reportContent['ReportTitle'] = $reporttitle;
        $reportContent['PageTitle'] = $reporttitle;
        $reportContent['Content'] = ''; // $query . "\r\n : :: ". \TAS\Core\DB::Count($rs);
        if (\TAS\Core\DB::Count($rs) > 0) {
            $reportContent['Content'] .= '<table width="100%"><tr>';
            foreach ($param['fields'] as $key => $val) {
                $reportContent['Content'] .= '<td>'.$val['name'].'</td>';
            }
            $reportContent['Content'] .= '</tr>';
            
            while ($row = $GLOBALS['db']->Fetch($rs)) {
                if (isset($param['rowcondition']) && is_array($param['rowcondition'])) {
                    if ($row[$param['rowcondition']['column']] == $param['rowcondition']['onvalue']) {
                        $additionalClass = $param['rowcondition']['cssclass'];
                    } else {
                        $additionalClass = '';
                    }
                } else {
                    $additionalClass = '';
                }
                
                $reportContent['Content'] .= '<tr class="'.$additionalClass.' datarow">';
                foreach ($param['fields'] as $key => $val) {
                    $reportContent['Content'] .= '<td>'.$row[$key].'</td>';
                }
                $reportContent['Content'] .= '</tr>';
            }
            $reportContent['Content'] .= '</table>';
        }
        
        $reportContent['MetaExtra'] = '';
        $content = TemplateHandler::InsertTemplateContent($GLOBALS['AppConfig']['TemplatePath'].DIRECTORY_SEPARATOR.$template, $reportContent);
        file_put_contents($htmlfilepath, $content);
        $pdfpath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename;
        $out = 1;
        if (file_exists($htmlfilepath)) {
            $cmd = 'xvfb-run wkhtmltopdf';
            if ($_SERVER['HTTP_HOST'] == 'localhost') {
                $cmd = 'wkhtmltopdf';
            }
            exec($cmd." --page-width 8.5in --page-height 11in --margin-left 0.5cm --margin-right 0 --margin-top 1.25cm --margin-bottom 0 \"$htmlfilepath\" \"$pdfpath\"", $output, $out);
        }
        if ($out == 0) {
            \TAS\Core\Web::DownloadHeader($filename);
            @unlink($htmlfilepath);
            readfile($pdfpath);
            exit();
        } else {
            return false;
        }
    }
    
    /**
     * Generate CSV is compatible with HTMLGrid function's SQLQuery method.
     * If you need more direct method use ExportCSV. Also it force download.
     *
     * @param unknown $SQLQuery
     *                          HTMLGrid function comptaible SQLQuery to append sorting
     * @param unknown $filename
     *                          filename ex export.csv
     * @param unknown $param
     *
     * @return boolean
     */
    
    /**
     * @deprecated
     * 
     * 
     * with CreateCSV function
     *
     */

    public static function GenerateCSV($SQLQuery, $filename, $tagname, $param = array())
    {
        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $param['defaultorder'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $param['defaultsort'])));
        
        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');
        
        $query = $SQLQuery['basicquery'].$SQLQuery['where']." order by $orderby $orderdirection $sortstring ";
        $rs = $GLOBALS['db']->Execute($query);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }
        
        $header = array();
        foreach ($param['fields'] as $field => $val) {
            $header[$field] = $val['name'];
        }
        $filepath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename;
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'header';
            print_r($header);
        }
        
        if (\TAS\Core\Utility::ExportCSV($query, $filepath, $header)) {
            \TAS\Core\Web::DownloadHeader($filename);
            readfile($filepath);
            exit();
        } else {
            return false;
        }
    }
    
    
    
    /**
     * Create CSV is compatible with HTMLGrid function's SQLQuery method.
     * If you need more direct method use ExportCSV. Also it force download.
     *
     * @param unknown $SQLQuery
     *                          HTMLGrid function comptaible SQLQuery to append sorting
     * @param unknown $filename
     *                          filename ex export.csv
     * @param unknown $param
     *
     * @return boolean
     */
    public static function CreateCSV($SQLQuery, $filename, $tagname, $param = array())
    {
        $orderby = ((isset($_GET['orderby'])) ? $_GET['orderby'] : ((isset($_SESSION[$tagname.'_orderby']) ? $_SESSION[$tagname.'_orderby'] : $SQLQuery['defaultorderby'])));
        $orderdirection = ((isset($_GET['direction'])) ? $_GET['direction'] : ((isset($_SESSION[$tagname.'_direction']) ? $_SESSION[$tagname.'_direction'] : $SQLQuery['defaultsortdirection'])));
        
        $sortstring = '';
        if (isset($SQLQuery['orderby']) && is_array($SQLQuery['orderby'])) {
            foreach ($SQLQuery['orderby'] as $key => $val) {
                $tmpsplit = explode(' ', $val);
                if (strtolower($val) != strtolower($orderby.' '.$orderdirection)) {
                    $sortstring .= ', '.$val;
                }
            }
        }
        $sortstring = trim($sortstring, ',');
        $query = $SQLQuery['basicquery'].$SQLQuery['whereconditions']." order by $orderby $orderdirection $sortstring ";
        $rs = $GLOBALS['db']->Execute($query);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo $query;
        }
        
        $header = array();
        foreach ($param['fields'] as $field => $val) {
            $header[$field] = $val['name'];
        }
        $filepath = $GLOBALS['AppConfig']['PhysicalPath'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename;
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'header';
            print_r($header);
        }
        
        if (\TAS\Core\Utility::ExportCSV($query, $filepath, $header)) {
            \TAS\Core\Web::DownloadHeader($filename);
            readfile($filepath);
            exit();
        } else {
            return false;
        }
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
     */
    public static function ExportCSV($SQLQuery, $filename = '', $fields = array())
    {
        $rs = $GLOBALS['db']->Execute($SQLQuery);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            echo 'Total Record found'.\TAS\Core\DB::Count($rs);
        }
        if (\TAS\Core\DB::Count($rs) < 1) {
            return false;
        }
        $fh = '';
        if ($filename == '') {
            $fh = fopen('export.csv', 'w+');
        } else {
            $fh = fopen($filename, 'w+');
        }
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        if (!is_array($fields) || count($fields) == 0) {
            $fields = \TAS\Core\DB::Columns($rs);
        }
        
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fields);
        }
        $csvHeader = '';
        $fieldindex = array();
        foreach ($fields as $index => $field) {
            if (is_object($field)) {
                $fieldname = $field->name;
            } else {
                $fieldname = $field;
            }
            $fieldindex[] = $index;
            $csvHeader .= ',"'.$fieldname.'" ';
        }
        $csvHeader = trim($csvHeader, ',');
        $csvHeader .= "\n";
        
        fwrite($fh, $csvHeader);
        unset($csvHeader);
        if ($GLOBALS['AppConfig']['DebugMode']) {
            print_r($fieldindex);
        }
        while ($row = $GLOBALS['db']->FetchArray($rs)) {
            $dataline = '';
            foreach ($fieldindex as $key => $val) {
                $dataline .= ',"'.$row[$val].'" ';
            }
            $dataline = trim($dataline, ',')."\n";
            fwrite($fh, $dataline);
        }
        fclose($fh);
        
        return true;
    }
    
    /**
     * Auto Load implementation to enable Loading of classes from /include/classes folder.
     *
     * @param string $classname
     */
    public static function AutoLoad($classname)
    {
        $defaultpaths[] = $GLOBALS['AppConfig']['PhysicalPath'].'/includes';
        $defaultpaths[] = $GLOBALS['AppConfig']['PhysicalPath'].'/includes/lib';
        if (is_array(Utility::$IncludePath)) {
            foreach (Utility::$IncludePath as $path) {
                $defaultpaths[] = $path;
            }
        }
        
        $included = false;
        if (strpos($classname, '\\') > 0) {
            $t = explode('\\', $classname);
            $classname = $t[count($t) - 1];
        }
        foreach ($defaultpaths as $defaultpath) {
            if (file_exists($defaultpath.DIRECTORY_SEPARATOR.'class.'.strtolower($classname).'.php')) {
                $included = true;
                require_once $defaultpath.DIRECTORY_SEPARATOR.'class.'.strtolower($classname).'.php';
            } elseif (file_exists($defaultpath.DIRECTORY_SEPARATOR.$classname.'.php')) {
                $included = true;
                require_once $defaultpath.DIRECTORY_SEPARATOR.$classname.'.php';
            } elseif (file_exists($defaultpath.DIRECTORY_SEPARATOR.'class.'.$classname.'.php')) {
                $included = true;
                require_once $defaultpath.DIRECTORY_SEPARATOR.'class.'.$classname.'.php';
            }
        }
        if (!$included) {
            \TAS\Core\Log::AddEvent(array(
                'message' => $classname.' is not found. Fail to load required code.',
            ), 'high');
        }
    }
    
    /**
     * New Paging function. Replacement of incFunctions.php.
     *
     * @param unknown_type $tablename
     * @param unknown_type $pagename
     * @param unknown_type $start
     * @param unknown_type $condition
     * @param unknown_type $querystring
     */
    public static function Paging($tablename, $pagename, $start = 0, $condition = '', $querystring = '', $isMultiTable = false, $param = array())
    {
        global $db, $AppConfig, $tables;
        // how many link pages to show
        
        $pagesize = isset($param['pagesize']) ? $param['pagesize'] : $GLOBALS['AppConfig']['PageSize'];
        
        $nLinks = 10;
        if (!isset($tables[$tablename])) {
            $_tablename = array_keys($GLOBALS['Tables'], $tablename);
            if (count($_tablename) > 0) {
                $tablename = $_tablename[0];
            } else {
                throw new \Exception("Invalid table name: $tablename");
            }
        }
        if ($isMultiTable) {
            $query = $condition;
        } else {
            $query = 'select count(*) from '.$GLOBALS['Tables'][$tablename]." $condition";
        }
        
        if ($GLOBALS['AppConfig']['DebugMode'] == true) {
            echo 'Paging Query : '.$query."\r\n";
        }
        
        $pageQuery = parse_url($pagename, PHP_URL_QUERY);
        $pagename = str_replace('?'.$pageQuery, '', $pagename);
        parse_str($querystring, $userQuery);
        parse_str($pageQuery, $pageQuery2);
        
        $querystring = array_merge($userQuery, $pageQuery2);
        $pagename = $pagename.((count($querystring) > 0) ? '?'.http_build_query($querystring) : '');
        
        $num = $GLOBALS['db']->ExecuteScalar($query);
        $bar = array();
        $pages = 1;
        if ($num > $pagesize) {
            $pages = $num / $pagesize;
            $pages = ceil($pages);
            if ($GLOBALS['AppConfig']['DebugMode'] == true) {
                echo 'Page found '.$pages;
            }
            if ($pages > 0) {
                // Has few Pages
                $previous = $start - 1;
                if ($previous > 0) {
                    $bar[] = '<a href="'.\TAS\Core\Web::AppendQueryString($pagename, 'page=1').'" class="ui-state-default ui-corner-all" ><span class="fa fa-angle-double-left"></span></a> ';
                    $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page='.$previous)."' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-left\"></span></a> ";
                }
                $startl = $start - ($start % $nLinks) + 1;
                $endl = $startl + $nLinks - 1;
                $endl = min($pages, $endl);
                if ($start > ($nLinks - 1)) {
                    $nStart = 1;
                    $prev = $startl - $nLinks;
                    $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page='.$prev)."' class=\"ui-state-default ui-corner-all\" >$prev</a> <li>....</li> ";
                } else {
                    $nStart = 0;
                }
                for ($i = $startl - $nStart; $i <= $endl; ++$i) {
                    if ($i == $start) {
                        $bar[] = '<a href="#" class="ui-state-default ui-corner-all active">'.$i.'</a>'; // no need to create a link to current page
                    } else {
                        if ($i == 1) {
                            $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page=1')."' class=\"ui-state-default ui-corner-all\">$i</a>";
                        } else {
                            $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page='.$i)."' class=\"ui-state-default ui-corner-all\">$i</a>";
                        }
                    }
                }
                $next = $start + 1;
                if ($next <= $pages) {
                    $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page='.$next)."' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-right\"></span></a> ";
                    $bar[] = "<a href='".\TAS\Core\Web::AppendQueryString($pagename, 'page='.$pages)."' class=\"ui-state-default ui-corner-all\" ><span class=\"fa fa-angle-double-right\"></span></a>";
                }
            }
        }
        
        return '<ul><li>'.implode('</li><li>', $bar).'</li></ul><div class="showcurrentpage">'.$start.' of '.$pages.'</div>';
    }
    
    /**
     * Return the list of Directory content.
     *
     * @param string $filter
     *                          regular expression pattern input for preg_match function call
     * @param string $directory
     *                          Directory to look for, make sure you have read permission to directory
     */
    public static function ListFiles($filter, $directory)
    {
        $output = array();
        if ($handle = opendir($directory)) {
            while (false !== ($entry = readdir($handle))) {
                if (!in_array($entry, array(
                    '.',
                    '..',
                )) && !is_dir($directory.DIRECTORY_SEPARATOR.$entry)) {
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
     * @param unknown_type $needle
     * @param unknown_type $column
     * @param unknown_type $array
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
    
    public static function SinglizeArray($a)
    {
        $output = array();
        foreach ($a as $i => $k) {
            if (is_array($k)) {
                $t = \TAS\Core\Utility::SinglizeArray($k);
                foreach ($t as $i1 => $k) {
                    $output[$i.'-'.$i1] = $k;
                }
            } else {
                $output[$i] = $k;
            }
        }
        
        return $output;
    }
    
    /**
     * Class casting ,ex.
     * $x = cast('A',$b);.
     *
     * @param string|object $destination
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
                $destination->$name = $value;
            }
        }
        
        return $destination;
    }
    
    /**
     * Contain word in array list.
     *
     * @param unknown $str
     * @param array   $arr
     *
     * @return boolean
     */
    public static function Contain($str, array $arr)
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Send email from EMAIL CMS.
     *
     * @param unknown $EmailID
     * @param unknown $keywords
     * @param unknown $to
     *
     * @return boolean
     */
    public static function DoEmail($EmailID, $keywords, $to, $sender = null, $attachment = null)
    {
        if ($sender == null) {
            $sender = $GLOBALS['AppConfig']['SenderEmail'];
        }
        
        $row = UI::GetEmailContent($EmailID);
        $finalcontent = $row['content'];
        if (is_array($row)) {
            $sendername = ($sender == $GLOBALS['AppConfig']['SenderEmail']) ? $GLOBALS['AppConfig']['SiteName'] : $sender;
            if ($row['usetemplate'] == 1) {
                $content = file_get_contents($GLOBALS['AppConfig']['TemplatePath'].'/mail.tpl');
                if ($content != '') {
                    $finalcontent = $content;
                }
                $finalcontent = str_replace('{Content}', $row['content'], $finalcontent);
            }
            
            $content = TemplateHandler::PrepareContent($finalcontent, $keywords);
            $subject = TemplateHandler::PrepareContent($row['subject'], $keywords);
            $to = strpos($to, ';') > 0 ? explode(';', $to) : explode(',', $to);
            if (is_array($to)) {
                $output = true;
                foreach ($to as $emailto) {
                    if (!\TAS\Core\DataValidate::ValidateEmail($emailto)) {
                        return false;
                    }
                    if (!self::SendHTMLMail($emailto, $subject, $content, '', $sender, $sendername, $sender, $attachment)) {
                        $output = false;
                    }
                }
                
                return $output;
            } else {
                if (!\TAS\Core\DataValidate::ValidateEmail($to)) {
                    return false;
                }
                
                return self::SendHTMLMail($to, $subject, $content, '', $sender, $sendername, $sender, $attachment);
            }
        } else {
            return false;
        }
    }
    
    public static function SendHTMLMail($to, $subject, $html_body, $text_body = '', $fromemail, $fromName, $returnpath = '', $attachment = null)
    {
        $mail = new PHPMailer();
        $mail->IsMail();
        if ($GLOBALS['AppConfig']['UseSMTPAuth'] == true) {
            $mail->IsSMTP();
            $mail->SMTPAuth = true; // enable SMTP authentication
            $mail->SMTPSecure = ''; // sets the prefix to the servier
            $mail->Host = $GLOBALS['AppConfig']['SMTPServer']; // sets GMAIL as the SMTP server
            $mail->Port = isset($GLOBALS['AppConfig']['SMTPServerPort']) ? $GLOBALS['AppConfig']['SMTPServerPort'] : 25; // set the SMTP port for the GMAIL server
            $mail->Username = $GLOBALS['AppConfig']['SMTPUsername']; // GMAIL username
            $mail->Password = $GLOBALS['AppConfig']['SMTPPassword'];
        }
        
        if ($GLOBALS['AppConfig']['DeveloperMode'] == true) {
            $to = $GLOBALS['AppConfig']['DeveloperEmail'];
        }
        
        if ($returnpath != '') {
            $mail->AddReplyTo($returnpath, $returnpath);
        }
        if ($html_body != null && !empty($html_body)) {
            $mail->isHTML(true);
        }
        $mail->From = $fromemail;
        $mail->FromName = $fromName;
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = (empty($text_body) ? $html_body : $text_body);
        
        $mail->AddAddress($to);
        if ($attachment != null && !empty($attachment)) {
            $mail->AddAttachment($attachment);
        }
        if (!$mail->Send()) {
            \TAS\Core\Log::AddEvent(array(
                'message' => 'IncFunction SendHTMLMail Failed !!!',
                'Mail Error' => $mail->ErrorInfo,
                'mail' => print_r($mail, true),
            ), 'debug');
            $mailstat = false;
        } else {
            $mailstat = true;
        }
        
        return $mailstat;
    }
    
    public static function CreateGUID() {
        $guid = '';
        $namespace = date('Ymdhis');
        $uid = uniqid('', true);
        $data = $namespace;
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash,  0,  8) . '-' .
            substr($hash,  8,  4) . '-' .
            substr($hash, 12,  4) . '-' .
            substr($hash, 16,  4) . '-' .
            substr($hash, 20, 12);
            return $guid;
    }
}

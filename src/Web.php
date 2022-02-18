<?php
namespace TAS\Core;

class Web {
    
    public static function DownloadHeader($file, $type = 'application/octet-stream')
    {
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header("Content-Disposition: attachment; filename=$file");
        header('Content-Type: '.$type);
        header('Content-Transfer-Encoding: binary');
    }

    /**
     * Force browser to not cache this page.
     * Call at top of page after configure.php.
     */
    public static function NoBrowserCache()
    {
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    }

    /**
     * Append QueryString string (ex.
     * abc=xyz&xyz=abc) in given URL.
     *
     * @param unknown $url
     * @param unknown $querystring
     */
    public static function AppendQueryString($url, $querystring)
    {
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $url = str_replace('#'.$fragment, '', $url); // Remove URL # Fragment

        parse_str(parse_url($url, PHP_URL_QUERY), $userQuery);
        parse_str($querystring, $pageQuery);

        $querystring = array_merge($userQuery, $pageQuery);

        // $seperator = (parse_url ( $url, PHP_URL_QUERY ) == NULL) ? '?' : '&';
        $url = str_replace('?'.parse_url($url, PHP_URL_QUERY), '', $url);

        return $url.'?'.http_build_query($querystring).(($fragment != null) ? '#'.$fragment : ''); // Append Fragment again.
    }

    public static function Redirect($url)
    {
        if (trim($url) == '') {
            return false;
        }
        header('Location: '.$url);
        exit();
    }

    public static function ResetSession()
    {
        session_destroy();
        session_start();
    }

}
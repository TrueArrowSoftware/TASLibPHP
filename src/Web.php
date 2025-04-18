<?php

namespace TAS\Core;

class Web
{
    public static function DownloadHeader($file, $type = 'application/octet-stream'): void
    {
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header("Content-Disposition: attachment; filename={$file}");
        header('Content-Type: ' . $type);
        header('Content-Transfer-Encoding: binary');
    }

    /**
     * Force browser to not cache this page.
     * Call at top of page after configure.php.
     */
    public static function NoBrowserCache(): void
    {
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    }

    /**
     * Append QueryString string (ex. abc=xyz&xyz=abc) in given URL.
     *
     * @param string $url
     * @param string $querystring
     */
    public static function AppendQueryString(string $url, string $querystring): string
    {
        if ($url == null) {
            return '';
        }

        if ($querystring == null) {
            return $url;
        }

        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $url = str_replace('#' . $fragment, '', $url); // Remove URL # Fragment

        $userQuery = [];
        $userQuerystring = parse_url($url, PHP_URL_QUERY);
        if (null != $userQuerystring) {
            parse_str($userQuerystring, $userQuery);
        }

        $pageQuery = [];
        parse_str($querystring, $pageQuery);

        $querystring = array_merge($userQuery, $pageQuery);

        // $seperator = (parse_url ( $url, PHP_URL_QUERY ) == NULL) ? '?' : '&';
        $url = str_replace('?' . $userQuerystring, '', $url);

        return $url . '?' . http_build_query($querystring) . ((null != $fragment) ? '#' . $fragment : ''); // Append Fragment again.
    }

    public static function Redirect($url): bool
    {
        if ('' == trim($url)) {
            return false;
        }
        header('Location: ' . $url);

        exit;
        return true;
    }

    public static function ResetSession(): void
    {
        session_destroy();
        session_start();
    }
}

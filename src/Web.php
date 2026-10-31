<?php

namespace TAS\Core;

class Web
{
    public static function DownloadHeader($file, $type = 'application/octet-stream'): void
    {
        // private, no-store: a shared proxy must not keep one user's export for the next one
        header('Cache-Control: private, no-store');
        header('Content-Description: File Transfer');
        // quoted ASCII name for old browsers plus the exact name in filename*; a " or ; in the name can't add parameters
        $ascii = str_replace(['"', '\\'], '_', preg_replace('/[^\x20-\x7e]/', '_', (string) $file));
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode((string) $file));
        header('Content-Type: ' . $type);
        header('X-Content-Type-Options: nosniff');
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

    /**
     * @param string $url
     * @return bool
     */
    public static function Redirect($url): bool
    {
        if ('' == trim($url)) {
            return false;
        }
        header('Location: ' . $url);

        exit;
        return true;
    }

    /**
     * Redirect to a target that came from the request (?return=..., a form field). Only a path on this site or a URL
     * on one of its hosts (see IsLocalUrl) is followed, anything else goes to $fallback. Exits, like Redirect.
     * Redirect itself still goes anywhere, for URLs the app builds (payment gateways etc.).
     *
     * @param string $url
     */
    public static function RedirectLocal($url, string $fallback = 'index.php'): bool
    {
        return self::Redirect(self::IsLocalUrl((string) $url) ? $url : $fallback);
    }

    /**
     * True for a relative URL ('index.php', '/admin/', '../list.php?page=2') or an http(s) URL whose host is this
     * request's host, the host of AppConfig HomeURL/SecureURL/NonSecureURL/AdminURL, Domain, or one in AllowedHosts.
     * False for other hosts, '//host' and '/\host' (browsers read both as another site), 'http:host', javascript: etc.
     */
    public static function IsLocalUrl(string $url): bool
    {
        $url = trim($url);
        if ('' === $url || preg_match('/[\x00-\x1f\\\\]/', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if (false === $parts) {
            return false;
        }
        if (!isset($parts['scheme']) && !isset($parts['host'])) {
            return true;
        }
        if (!isset($parts['host']) || !in_array(strtolower($parts['scheme'] ?? 'http'), ['http', 'https'], true)) {
            return false;
        }

        $config = $GLOBALS['AppConfig'] ?? [];
        $hosts = (array) ($config['AllowedHosts'] ?? []);
        $hosts[] = $config['Domain'] ?? '';
        $hosts[] = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        foreach (['HomeURL', 'SecureURL', 'NonSecureURL', 'AdminURL'] as $key) {
            $hosts[] = (string) parse_url((string) ($config[$key] ?? ''), PHP_URL_HOST);
        }

        return in_array(strtolower($parts['host']), array_filter(array_map('strtolower', $hosts)), true);
    }

    public static function ResetSession(): void
    {
        session_destroy();
        session_start();
        session_regenerate_id(true); // a new id: the cookie still had the old one, so a fixated id would carry on
    }
}

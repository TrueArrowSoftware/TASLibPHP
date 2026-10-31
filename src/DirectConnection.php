<?php

namespace TAS\Core;

use TAS\Core\Async\AsyncHttp;

/**
 * Use to create Direction connection to third party server, it use CURL or fsockopen.
 *
 * @author TAS Team
 */
class DirectConnection extends \TAS\Core\Entity
{
    /**
     * Check the server's TLS certificate. Off by default as before; turn it on unless an endpoint is self-signed.
     * Also the default for AsyncHttp requests that don't set 'ssl_verify'.
     */
    public static bool $VerifySSL = false;

    /**
     * Seconds a whole request may take, 0 for no limit (as before). Connecting is always limited to 30 seconds.
     */
    public static int $Timeout = 0;

    /**
     * Options every request gets: http/https only (also after a redirect), so a URL can't read file:// or speak
     * gopher:// etc. to internal services, plus the TLS and timeout settings above.
     */
    private static function SafeOptions($ch): void
    {
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, self::$VerifySSL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$Timeout);
    }

    /**
     * @param string $url
     * @param string|array $request
     * @param string $headerextra
     * @return bool|string
     */
    public static function SendPOST($url, $request, $headerextra = '')
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url); // Starts the curl handler
            curl_setopt($ch, CURLOPT_URL, $url); // Sets the paypal address for curl
            self::SafeOptions($ch);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns result to a variable instead of echoing
            curl_setopt($ch, CURLOPT_POST, 1); // Set curl to send data using post
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request); // Add the request parameters to the post
            $res = curl_exec($ch); // run the curl process (and return the result to $result
            curl_close($ch);
        } else {
            $header = "POST {$url} HTTP/1.0\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= 'Content-Length: '.strlen($request)."\r\n";
            $header .= $headerextra."\r\n\r\n";

            $fp = fsockopen($url, 80, $errno, $errstr, 30);
            if ($fp) {
                fputs($fp, $header.$request);
                while (!feof($fp)) {
                    $res = fgets($fp, 1024);
                }
            } else {
                return false;
            }
        }

        return $res;
    }

    /**
     * @param string $url
     * @param string $headerextra
     * @return bool|string
     */
    public static function SendGET($url, $headerextra = '')
    {
        $ch = curl_init($url); // Starts the curl handler
        curl_setopt($ch, CURLOPT_URL, $url); // Sets the paypal address for curl
        self::SafeOptions($ch);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns result to a variable instead of echoing
        curl_setopt($ch, CURLOPT_POST, false); // Set curl to send data using post
        $bundle = dirname(dirname(__FILE__)).'/ssl/cert.pem';
        if (is_file($bundle)) { // the library ships none; pointing at a missing file fails every verified request
            curl_setopt($ch, CURLOPT_CAINFO, $bundle);
        }

        $res = curl_exec($ch); // run the curl process (and return the result to $result
        curl_close($ch);

        return $res;
    }

    /**
     * Send multiple POST requests concurrently using curl_multi via Fibers.
     *
     * @param array<string, array{url: string, data: string|array}> $requests Associative array of label => request config
     * @return array<string, string|false> Responses keyed by same labels
     */
    public static function SendPOSTBatch(array $requests): array
    {
        return AsyncHttp::postBatch($requests);
    }

    /**
     * Send multiple GET requests concurrently using curl_multi via Fibers.
     *
     * @param array<string, string> $urls Associative array of label => URL
     * @return array<string, string|false> Responses keyed by same labels
     */
    public static function SendGETBatch(array $urls): array
    {
        return AsyncHttp::getBatch($urls);
    }
}

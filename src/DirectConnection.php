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
    public static function SendPOST($url, $request, $headerextra = '')
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url); // Starts the curl handler
            curl_setopt($ch, CURLOPT_URL, $url); // Sets the paypal address for curl
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

    public static function SendGET($url, $headerextra = '')
    {
        $ch = curl_init($url); // Starts the curl handler
        curl_setopt($ch, CURLOPT_URL, $url); // Sets the paypal address for curl
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Returns result to a variable instead of echoing
        curl_setopt($ch, CURLOPT_POST, false); // Set curl to send data using post
        curl_setopt($ch, CURLOPT_CAINFO, dirname(dirname(__FILE__)).'/ssl/cert.pem');

        // echo "DDDD" . dirname ( dirname ( __FILE__ ) ) . '/ssl/cert.pem';
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

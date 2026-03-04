<?php

namespace TAS\Core\Async;

/**
 * Async HTTP client using curl_multi for concurrent requests within Fibers.
 *
 * Usage:
 *   $results = AsyncHttp::runParallel([
 *       'api1' => ['url' => 'https://api.example.com/data', 'method' => 'GET'],
 *       'api2' => ['url' => 'https://api.example.com/other', 'method' => 'POST', 'data' => 'key=value'],
 *   ]);
 *   // $results['api1'] => response string, $results['api2'] => response string
 *
 * @author TAS Team
 * @since 2.0.0
 * @requires PHP 8.1+, curl extension
 */
class AsyncHttp
{
    /**
     * Execute multiple HTTP requests concurrently using curl_multi.
     *
     * @param array<string, array> $requests Associative array of label => request config.
     *   Each request config supports:
     *     - 'url' (string, required): The URL
     *     - 'method' (string): 'GET' or 'POST' (default 'GET')
     *     - 'data' (string|array): POST data
     *     - 'headers' (array): Extra headers
     *     - 'timeout' (int): Per-request timeout in seconds (default 30)
     *     - 'ssl_verify' (bool): Verify SSL (default false for BC)
     * @param int $pollIntervalUs Poll interval in microseconds (default 10000 = 10ms)
     * @return array<string, string|false> Responses keyed by same labels. False on failure.
     */
    public static function runParallel(array $requests, int $pollIntervalUs = 10000): array
    {
        if (empty($requests) || !function_exists('curl_multi_init')) {
            return [];
        }

        // Single request — use simple curl
        if (count($requests) === 1) {
            $key = array_key_first($requests);
            return [$key => self::executeSingle($requests[$key])];
        }

        $multiHandle = curl_multi_init();
        $handles = [];
        $results = [];

        try {
            // Add all request handles
            foreach ($requests as $key => $config) {
                $ch = self::createHandle($config);
                $handles[$key] = $ch;
                curl_multi_add_handle($multiHandle, $ch);
            }

            // Execute until complete
            $active = null;
            do {
                $status = curl_multi_exec($multiHandle, $active);

                if ($active) {
                    // Wait for activity on any handle, with timeout
                    $selectResult = curl_multi_select($multiHandle, $pollIntervalUs / 1000000);

                    // Yield to the Fiber scheduler while waiting
                    if ($selectResult === 0) {
                        FiberRunner::suspend();
                    }
                }
            } while ($active && $status === CURLM_OK);

            // Collect results
            foreach ($handles as $key => $ch) {
                $error = curl_error($ch);
                if (empty($error)) {
                    $results[$key] = curl_multi_getcontent($ch);
                } else {
                    $results[$key] = false;
                }
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
        } finally {
            curl_multi_close($multiHandle);
        }

        return $results;
    }

    /**
     * Execute multiple GET requests concurrently.
     *
     * @param array<string, string> $urls Associative array of label => URL
     * @return array<string, string|false> Responses keyed by same labels
     */
    public static function getBatch(array $urls): array
    {
        $requests = [];
        foreach ($urls as $key => $url) {
            $requests[$key] = ['url' => $url, 'method' => 'GET'];
        }
        return self::runParallel($requests);
    }

    /**
     * Execute multiple POST requests concurrently.
     *
     * @param array<string, array{url: string, data: string|array}> $posts
     * @return array<string, string|false>
     */
    public static function postBatch(array $posts): array
    {
        $requests = [];
        foreach ($posts as $key => $post) {
            $requests[$key] = array_merge($post, ['method' => 'POST']);
        }
        return self::runParallel($requests);
    }

    /**
     * Create a cURL handle from request config.
     *
     * @param array $config Request configuration
     * @return \CurlHandle
     */
    private static function createHandle(array $config): \CurlHandle
    {
        $ch = curl_init();

        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'GET');
        $timeout = $config['timeout'] ?? 30;
        $sslVerify = $config['ssl_verify'] ?? false;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ('POST' === $method) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($config['data'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $config['data']);
            }
        }

        if (!empty($config['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $config['headers']);
        }

        return $ch;
    }

    /**
     * Execute a single request using basic curl.
     *
     * @param array $config Request configuration
     * @return string|false
     */
    private static function executeSingle(array $config): string|false
    {
        $ch = self::createHandle($config);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

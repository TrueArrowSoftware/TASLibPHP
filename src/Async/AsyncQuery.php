<?php

namespace TAS\Core\Async;

use TAS\Core\DB;

/**
 * Execute multiple DB queries in parallel using mysqli async and Fibers.
 *
 * Uses MYSQLI_ASYNC flag for true non-blocking parallel query execution,
 * combined with mysqli_poll() to check for completed queries.
 *
 * Usage:
 *   $pool = new DBPool('localhost', 'root', '', 'mydb', 5);
 *   $results = AsyncQuery::runParallel([
 *       'data'  => 'SELECT * FROM users LIMIT 10',
 *       'count' => 'SELECT COUNT(*) FROM users',
 *   ], $pool);
 *   // $results['data'] => mysqli_result, $results['count'] => mysqli_result
 *
 * @author TAS Team
 * @since 2.0.0
 * @requires PHP 8.1+
 */
class AsyncQuery
{
    /**
     * Run multiple queries in parallel using mysqli async mode.
     *
     * Each query gets its own connection from the pool. All queries are
     * dispatched with MYSQLI_ASYNC, then polled until complete.
     *
     * @param array<string, string> $queries Associative array of label => SQL query
     * @param DBPool $pool Connection pool to use
     * @param int $pollIntervalUs Poll interval in microseconds (default 1000 = 1ms)
     * @return array<string, \mysqli_result|bool> Results keyed by same labels
     *
     * @throws \Exception If a query fails
     */
    public static function runParallel(array $queries, DBPool $pool, int $pollIntervalUs = 1000): array
    {
        if (empty($queries)) {
            return [];
        }

        // For a single query, just execute normally
        if (count($queries) === 1) {
            $key = array_key_first($queries);
            $db = $pool->acquire();
            try {
                $result = $db->Execute($queries[$key]);
                return [$key => $result];
            } finally {
                $pool->release($db);
            }
        }

        $connections = [];  // key => DB instance
        $mysqliLinks = []; // key => mysqli object
        $results = [];
        $errors = [];

        try {
            // Acquire connections and dispatch queries asynchronously
            foreach ($queries as $key => $query) {
                $db = $pool->acquire();
                $connections[$key] = $db;
                $mysqliLinks[$key] = $db->MySqlObject;

                // Send query with MYSQLI_ASYNC flag
                $db->MySqlObject->query($query, MYSQLI_ASYNC);
            }

            // Poll until all queries complete
            $remaining = array_keys($queries);

            while (!empty($remaining)) {
                // Build arrays for mysqli_poll (needs references)
                $read = $error = $reject = [];
                foreach ($remaining as $key) {
                    $read[] = $mysqliLinks[$key];
                    $error[] = $mysqliLinks[$key];
                    $reject[] = $mysqliLinks[$key];
                }

                // Poll with a short timeout
                $ready = @mysqli_poll($read, $error, $reject, 0, $pollIntervalUs);

                if ($ready === false) {
                    break; // mysqli_poll error
                }

                if ($ready > 0) {
                    // Check which connections have results ready
                    foreach ($remaining as $idx => $key) {
                        $mysqli = $mysqliLinks[$key];

                        // Check if this connection's query is done by trying to reap
                        $result = @$mysqli->reap_async_query();

                        if ($result !== false) {
                            $results[$key] = $result;
                            unset($remaining[$idx]);
                        } elseif ($mysqli->errno) {
                            $errors[$key] = $mysqli->error;
                            unset($remaining[$idx]);
                        }
                    }
                    $remaining = array_values($remaining);
                } else {
                    // No results ready yet — yield to other Fibers if inside one
                    FiberRunner::suspend();
                }
            }
        } finally {
            // Always release connections back to the pool
            foreach ($connections as $key => $db) {
                $pool->release($db);
            }
        }

        if (!empty($errors)) {
            $firstKey = array_key_first($errors);
            throw new \Exception("Async query '{$firstKey}' failed: " . $errors[$firstKey]);
        }

        return $results;
    }

    /**
     * Run multiple queries and return scalar (first column of first row) for each.
     *
     * @param array<string, string> $queries Associative array of label => SQL query
     * @param DBPool $pool Connection pool
     * @return array<string, mixed> Scalar results keyed by same labels
     */
    public static function runParallelScalar(array $queries, DBPool $pool): array
    {
        if (empty($queries)) {
            return [];
        }

        // For single query, optimize
        if (count($queries) === 1) {
            $key = array_key_first($queries);
            $db = $pool->acquire();
            try {
                return [$key => $db->ExecuteScalar($queries[$key])];
            } finally {
                $pool->release($db);
            }
        }

        $rawResults = self::runParallel($queries, $pool);
        $scalarResults = [];

        foreach ($rawResults as $key => $result) {
            if ($result instanceof \mysqli_result && $result->num_rows > 0) {
                $row = $result->fetch_row();
                $scalarResults[$key] = $row[0] ?? false;
                $result->free();
            } else {
                $scalarResults[$key] = false;
            }
        }

        return $scalarResults;
    }

    /**
     * Execute multiple independent queries sequentially on the same connection.
     *
     * Use this when queries are independent but don't need separate connections.
     * Returns all results. This is a simpler alternative when the pool is small.
     *
     * @param array<string, string> $queries Associative array of label => SQL
     * @param DB|null $db DB instance (uses $GLOBALS['db'] if null)
     * @return array<string, mixed>
     */
    public static function runSequential(array $queries, ?DB $db = null): array
    {
        $db = $db ?? $GLOBALS['db'];
        $results = [];

        foreach ($queries as $key => $query) {
            $results[$key] = $db->Execute($query);
        }

        return $results;
    }
}

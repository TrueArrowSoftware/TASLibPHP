<?php

namespace TAS\Core\Async;

use TAS\Core\DB;

/**
 * Connection pool for concurrent DB access with Fibers.
 *
 * Manages multiple mysqli connections so that Fibers running concurrently
 * can each have their own connection. Connections are checked out (acquired)
 * and returned (released) to the pool.
 *
 * Usage:
 *   $pool = new DBPool('localhost', 'root', '', 'mydb', 5);
 *   $conn = $pool->acquire();
 *   // ... use $conn as a normal DB instance ...
 *   $pool->release($conn);
 *   $pool->shutdown();
 *
 * @author TAS Team
 * @since 2.0.0
 * @requires PHP 8.1+
 */
class DBPool
{
    /** @var DB[] Available (idle) connections */
    private array $available = [];

    /** @var DB[] Currently checked-out connections */
    private array $inUse = [];

    /** @var \Fiber[] Fibers waiting for a connection */
    private array $waitQueue = [];

    private string $server;
    private string $user;
    private string $password;
    private string $dbName;
    private int $maxConnections;
    private string $charset;
    private string $collation;
    private bool $debug;

    /**
     * @param string $server       Database server host
     * @param string $user         Database username
     * @param string $password     Database password
     * @param string $dbName       Database name
     * @param int    $maxConnections Maximum number of pooled connections (default 5)
     * @param string $charset      Character set (default utf8mb4)
     * @param string $collation    Collation (default utf8mb4_bin)
     * @param bool   $debug        Enable debug mode on connections
     */
    public function __construct(
        string $server = 'localhost',
        string $user = 'root',
        string $password = '',
        string $dbName = 'demo',
        int $maxConnections = 5,
        string $charset = 'utf8mb4',
        string $collation = 'utf8mb4_bin',
        bool $debug = false
    ) {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->maxConnections = max(1, $maxConnections);
        $this->charset = $charset;
        $this->collation = $collation;
        $this->debug = $debug;
    }

    /**
     * Acquire a DB connection from the pool.
     *
     * If an idle connection is available, returns it immediately.
     * If pool is not at max capacity, creates a new connection.
     * If pool is exhausted, suspends the current Fiber until a connection is released.
     *
     * @return DB A connected DB instance
     * @throws \Exception If unable to create connection
     */
    public function acquire(): DB
    {
        // Try to get an idle connection
        if (!empty($this->available)) {
            $db = array_pop($this->available);

            // Verify the connection is still alive
            if ($db->IsConnected() && @$db->MySqlObject->ping()) {
                $id = spl_object_id($db);
                $this->inUse[$id] = $db;
                return $db;
            }

            // Connection went stale, try to reconnect or create new
            try {
                $db->CloseDB();
            } catch (\Throwable $e) {
                // Ignore close errors on stale connections
            }
        }

        // Create a new connection if under limit
        $totalConnections = count($this->available) + count($this->inUse);
        if ($totalConnections < $this->maxConnections) {
            $db = $this->createConnection();
            $id = spl_object_id($db);
            $this->inUse[$id] = $db;
            return $db;
        }

        // Pool exhausted — suspend the current Fiber until a connection is released
        $fiber = \Fiber::getCurrent();
        if ($fiber !== null) {
            $this->waitQueue[] = $fiber;
            \Fiber::suspend('waiting_for_connection');

            // When resumed, an available connection should be set
            if (!empty($this->available)) {
                $db = array_pop($this->available);
                $id = spl_object_id($db);
                $this->inUse[$id] = $db;
                return $db;
            }
        }

        // Fallback: create connection even if over limit (safety net)
        $db = $this->createConnection();
        $id = spl_object_id($db);
        $this->inUse[$id] = $db;
        return $db;
    }

    /**
     * Release a DB connection back to the pool.
     *
     * If Fibers are waiting for a connection, the first waiting Fiber is resumed.
     *
     * @param DB $db The connection to release
     */
    public function release(DB $db): void
    {
        $id = spl_object_id($db);
        unset($this->inUse[$id]);

        // If a Fiber is waiting, make connection available and resume it
        if (!empty($this->waitQueue)) {
            $this->available[] = $db;
            $waitingFiber = array_shift($this->waitQueue);
            if ($waitingFiber->isSuspended()) {
                $waitingFiber->resume();
            }
        } else {
            $this->available[] = $db;
        }
    }

    /**
     * Get pool statistics for debugging.
     *
     * @return array{available: int, inUse: int, waiting: int, maxConnections: int}
     */
    public function getStats(): array
    {
        return [
            'available' => count($this->available),
            'inUse' => count($this->inUse),
            'waiting' => count($this->waitQueue),
            'maxConnections' => $this->maxConnections,
        ];
    }

    /**
     * Get the maximum number of connections.
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Close all connections in the pool and reset state.
     */
    public function shutdown(): void
    {
        foreach ($this->available as $db) {
            try {
                $db->CloseDB();
            } catch (\Throwable $e) {
                // Ignore close errors during shutdown
            }
        }

        foreach ($this->inUse as $db) {
            try {
                $db->CloseDB();
            } catch (\Throwable $e) {
                // Ignore close errors during shutdown
            }
        }

        $this->available = [];
        $this->inUse = [];
        $this->waitQueue = [];
    }

    /**
     * Create a new DB connection with the pool's configuration.
     *
     * @return DB Connected DB instance
     * @throws \Exception If connection fails
     */
    private function createConnection(): DB
    {
        $db = new DB($this->server, $this->user, $this->password, $this->dbName);
        $db->Charset = $this->charset;
        $db->Collation = $this->collation;
        $db->Debug = $this->debug;
        $db->Connect();

        return $db;
    }

    public function __destruct()
    {
        $this->shutdown();
    }
}

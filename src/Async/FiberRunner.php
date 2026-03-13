<?php

namespace TAS\Core\Async;

/**
 * Lightweight PHP Fiber scheduler for cooperative concurrency.
 *
 * Accepts an array of callables, wraps each in a Fiber, starts them all,
 * and round-robin resumes suspended Fibers until all complete.
 *
 * Usage:
 *   $results = FiberRunner::run([
 *       fn() => $db1->ExecuteScalar($countQuery),
 *       fn() => $db2->Execute($dataQuery),
 *   ]);
 *
 * @author TAS Team
 * @since 2.0.0
 * @requires PHP 8.1+
 */
class FiberRunner
{
    /**
     * Check if Fibers are supported in the current PHP version.
     */
    public static function isSupported(): bool
    {
        return class_exists(\Fiber::class);
    }

    /**
     * Run multiple callables concurrently using Fibers.
     *
     * Each callable is wrapped in a Fiber. They are started in order and
     * resumed round-robin. A callable may call Fiber::suspend() to yield
     * control back to the scheduler (e.g., while waiting for async I/O).
     *
     * If a callable does NOT suspend, it runs to completion immediately.
     *
     * @param array<int|string, callable> $tasks Array of callables to run concurrently
     * @return array<int|string, mixed> Results keyed by the same keys as $tasks
     *
     * @throws \Throwable Re-throws any exception from a Fiber
     */
    public static function run(array $tasks): array
    {
        if (empty($tasks)) {
            return [];
        }

        // If only one task, just execute it directly — no Fiber overhead
        if (count($tasks) === 1) {
            $key = array_key_first($tasks);
            return [$key => ($tasks[$key])()];
        }

        $fibers = [];
        $results = [];
        $errors = [];

        // Create and start all Fibers
        foreach ($tasks as $key => $task) {
            $fibers[$key] = new \Fiber($task);
        }

        // Start all fibers — each runs until first suspend() or completion
        foreach ($fibers as $key => $fiber) {
            try {
                $value = $fiber->start();
                if ($fiber->isTerminated()) {
                    $results[$key] = $fiber->getReturn();
                    unset($fibers[$key]);
                }
            } catch (\Throwable $e) {
                $errors[$key] = $e;
                unset($fibers[$key]);
            }
        }

        // Round-robin resume suspended Fibers until all complete
        while (!empty($fibers)) {
            foreach ($fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    try {
                        $fiber->resume();
                        if ($fiber->isTerminated()) {
                            $results[$key] = $fiber->getReturn();
                            unset($fibers[$key]);
                        }
                    } catch (\Throwable $e) {
                        $errors[$key] = $e;
                        unset($fibers[$key]);
                    }
                } elseif ($fiber->isTerminated()) {
                    $results[$key] = $fiber->getReturn();
                    unset($fibers[$key]);
                }
            }

            // Small yield to prevent tight CPU spinning when all fibers are waiting
            if (!empty($fibers)) {
                usleep(100); // 0.1ms
            }
        }

        // If any Fiber threw an exception, throw the first one
        if (!empty($errors)) {
            $firstKey = array_key_first($errors);
            throw $errors[$firstKey];
        }

        return $results;
    }

    /**
     * Run multiple callables and collect results, continuing even if some fail.
     *
     * Returns an array with 'results' and 'errors' keys.
     *
     * @param array<int|string, callable> $tasks Array of callables to run
     * @return array{results: array, errors: array}
     */
    public static function runSettled(array $tasks): array
    {
        if (empty($tasks)) {
            return ['results' => [], 'errors' => []];
        }

        $fibers = [];
        $results = [];
        $errors = [];

        foreach ($tasks as $key => $task) {
            $fibers[$key] = new \Fiber($task);
        }

        // Start all fibers
        foreach ($fibers as $key => $fiber) {
            try {
                $fiber->start();
                if ($fiber->isTerminated()) {
                    $results[$key] = $fiber->getReturn();
                    unset($fibers[$key]);
                }
            } catch (\Throwable $e) {
                $errors[$key] = $e;
                unset($fibers[$key]);
            }
        }

        // Round-robin resume
        while (!empty($fibers)) {
            foreach ($fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    try {
                        $fiber->resume();
                        if ($fiber->isTerminated()) {
                            $results[$key] = $fiber->getReturn();
                            unset($fibers[$key]);
                        }
                    } catch (\Throwable $e) {
                        $errors[$key] = $e;
                        unset($fibers[$key]);
                    }
                } elseif ($fiber->isTerminated()) {
                    $results[$key] = $fiber->getReturn();
                    unset($fibers[$key]);
                }
            }

            if (!empty($fibers)) {
                usleep(100);
            }
        }

        return ['results' => $results, 'errors' => $errors];
    }

    /**
     * Suspend the current Fiber, yielding control back to the scheduler.
     *
     * Safe to call from within a task passed to run(). If not inside a Fiber,
     * this is a no-op to ensure backward compatibility.
     *
     * @param mixed $value Optional value to pass back to the scheduler
     */
    public static function suspend(mixed $value = null): void
    {
        if (\Fiber::getCurrent() !== null) {
            \Fiber::suspend($value);
        }
    }
}

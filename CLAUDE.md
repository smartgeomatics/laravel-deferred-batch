# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package (`smartgeomatics/laravel-deferred-batch`) that enables deferred batch construction within Laravel job chains. It solves the problem of needing to dynamically build a `PendingBatch` at runtime (when a chain step executes) rather than at dispatch time.

## Commands

- **Run all tests:** `vendor/bin/phpunit`
- **Run a single test:** `vendor/bin/phpunit --filter test_method_name`
- **Install dependencies:** `composer install`

## Architecture

The package has two source files:

- **`src/DeferredBatch.php`** — A queueable job (`ShouldQueue`) that wraps a serializable builder callable. When handled, it invokes the builder which must return a `PendingBatch` or `null` (to skip). It attaches the remainder of the job chain to the batch's `finally` callback and propagates `chainCatchCallbacks` to the batch's `catch` handler. Closures are wrapped in `SerializableClosure` for queue serialization.

- **`src/DeferredBatchServiceProvider.php`** — Auto-discovered service provider (currently a placeholder for future registrations).

### Key Design Decisions

- Builder returns `null` to skip the batch step; the chain continues normally via `dispatchNextJobInChain()`.
- Chain remainder is moved to a `finally` callback (not `then`) so it runs even if individual batch jobs fail but the batch isn't cancelled.
- Chain catch callbacks are forwarded to the batch's `catch` only when the batch does NOT allow failures.

## Dependencies

- Laravel 12 (`illuminate/*` ^12.0)
- PHP 8.2+
- Tests use Orchestra Testbench 10 with in-memory SQLite

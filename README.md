# Laravel Deferred Batch

A Laravel package that lets you lazily construct and dispatch a job batch from within a job chain. Instead of defining batch jobs at dispatch time, you provide a builder callback that runs when the chain reaches that step — allowing you to build the batch based on runtime state.

## Requirements

- PHP 8.2+
- Laravel 12

## Installation

```bash
composer require smartgeomatics/laravel-deferred-batch
```

The service provider is auto-discovered via Laravel's package discovery.

## Usage

### Basic Example

Use `DeferredBatch` as a step in a job chain. The builder callback must return a `PendingBatch` or `null`.

```php
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\PendingBatch;
use SmartGeomatics\DeferredBatch\DeferredBatch;

Bus::chain([
    new PrepareDataJob,

    new DeferredBatch(function () {
        $items = Item::where('status', 'pending')->get();

        if ($items->isEmpty()) {
            return null; // skip batch, continue chain
        }

        return Bus::batch(
            $items->map(fn ($item) => new ProcessItemJob($item))
        )->name('Process pending items');
    }),

    new FinalizeJob,
])->dispatch();
```

### Skipping the Batch

Return `null` from the builder to skip the batch step entirely. The chain continues with the next job as normal.

```php
new DeferredBatch(function () {
    if (! $this->shouldRunBatch()) {
        return null;
    }

    return Bus::batch([...]);
})
```

### Using an Invokable Class

For complex builders or when you need a serializable class instead of a closure:

```php
class BuildReportBatch
{
    public function __invoke(): ?PendingBatch
    {
        $reports = Report::whereNull('generated_at')->get();

        if ($reports->isEmpty()) {
            return null;
        }

        return Bus::batch(
            $reports->map(fn ($report) => new GenerateReportJob($report))
        );
    }
}

Bus::chain([
    new DeferredBatch(new BuildReportBatch),
    new SendReportNotificationJob,
])->dispatch();
```

### Queue and Connection Settings

`DeferredBatch` is a standard queueable job, so you can set its queue and connection:

```php
Bus::chain([
    (new DeferredBatch(function () {
        return Bus::batch([new SomeJob]);
    }))->onQueue('high')->onConnection('redis'),

    new NextJob,
])->dispatch();
```

## How It Works

1. When the chain reaches the `DeferredBatch` job, it invokes your builder callback.
2. If the builder returns `null`, the chain continues to the next job.
3. If it returns a `PendingBatch`, the remaining chain is attached to the batch's `finally` callback and the batch is dispatched.
4. Any `catch` callbacks from the chain are forwarded to the batch (when the batch does not allow failures).

This means the next job in the chain runs after the batch completes, regardless of whether individual batch jobs failed — as long as the batch itself is not cancelled.

## License

MIT

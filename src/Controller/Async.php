<?php

namespace Flat3\OData\Controller;

use Flat3\OData\Exception\Protocol\AcceptedException;
use Flat3\OData\Exception\Protocol\ProtocolException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Async implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETE = 'complete';

    /** @var string $jobId */
    protected $jobId;

    /** @var Transaction $transaction */
    protected $transaction;

    public function __construct(?string $jobId = null)
    {
        $this->jobId = $jobId;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        $this->jobId = $transaction->getId();

        return $this;
    }

    public function getDisk(): Filesystem
    {
        return Storage::disk('odata');
    }

    public function getDataPath(): string
    {
        return $this->getDisk()->path($this->ns('data'));
    }

    public function getMetaPath(): string
    {
        return $this->getDisk()->path($this->ns('meta'));
    }

    public function handle(): void
    {
        if ($this->isDeleted()) {
            return;
        }

        $disk = Storage::disk('odata');

        $dataPath = $this->getDataPath();
        $metaPath = $this->getMetaPath();

        $error = false;

        try {
            $response = $this->transaction->execute()->response($this->transaction);
        } catch (ProtocolException $e) {
            $response = $e->toResponse();
            $error = true;
        }

        file_put_contents($metaPath, $response->toJson());

        if (!$error) {
            $resource = fopen($dataPath, 'w+b');

            if (false === $resource) {
                throw new RuntimeException();
            }

            ob_start(function ($buffer) use ($resource) {
                fwrite($resource, $buffer);
            });

            $response->sendContent();
            ob_end_flush();
            fclose($resource);
        }

        $this->setComplete();
    }

    public function getMonitorUrl(): string
    {
        return Transaction::getResourceUrl().'_flat3/monitor/'.$this->jobId;
    }

    public function getTransactionId(): string
    {
        return $this->transaction->getId();
    }

    public function getStatus(): ?string
    {
        return Cache::get($this->ns('status'));
    }

    public function setComplete(): self
    {
        $this->setStatus(self::STATUS_COMPLETE);

        $callback = $this->transaction->getCallbackUrl();

        if ($callback) {
            Http::get($callback);
        }

        return $this;
    }

    public function setPending(): self
    {
        $this->setStatus(self::STATUS_PENDING);
        return $this;
    }

    public function setStatus(string $status): self
    {
        Cache::put($this->ns('status'), $status);
        return $this;
    }

    public function dispatch()
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = app(Dispatcher::class);
        $this->setPending();
        $dispatcher->dispatch($this);

        $accepted = $this->accepted();

        if ($this->transaction->getPreference('callback')) {
            $accepted->header('preference-applied', 'callback');
        }

        throw $accepted;
    }

    public function ns(string $prefix): string
    {
        return sprintf('%s.%s.%s', $this->jobId, $prefix, 'odata');
    }

    public function getResultMetadata(): array
    {
        return json_decode(file_get_contents($this->getMetaPath()), true);
    }

    public function getResultStream()
    {
        return fopen($this->getDataPath(), 'r');
    }

    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    public function isComplete(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETE;
    }

    public function isDeleted(): bool
    {
        return null === $this->getStatus();
    }

    public function destroy()
    {
        @unlink($this->getDataPath());
        @unlink($this->getMetaPath());
        Cache::forget($this->ns('status'));
    }

    public function accepted(): AcceptedException
    {
        return AcceptedException::factory()
            ->header('location', $this->getMonitorUrl());
    }
}
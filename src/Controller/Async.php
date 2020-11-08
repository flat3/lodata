<?php

namespace Flat3\Lodata\Controller;

use Flat3\Lodata\Exception\Protocol\AcceptedException;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Async
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359016
 * @package Flat3\Lodata\Controller
 */
class Async implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETE = 'complete';

    /**
     * Job ID
     * @var string $jobId
     * @internal
     */
    protected $jobId;

    /**
     * Transaction related to this job
     * @var Transaction $transaction Transaction
     */
    protected $transaction;

    /**
     * Set the job ID
     * @param  string  $jobId  Job ID
     * @return $this
     */
    public function setId(string $jobId): self
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * Set the related transaction
     * @param  Transaction  $transaction  Transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): self
    {
        $transaction->getRequest()->headers->remove('content-type');
        $this->transaction = $transaction;
        $this->jobId = $transaction->getId();

        return $this;
    }

    /**
     * Get the Laravel disk used to store results of this job
     * @return Filesystem Filesystem
     */
    public function getDisk(): Filesystem
    {
        return Storage::disk(config('lodata.disk'));
    }

    /**
     * Get the Laravel disk path to use to store the data result of this job
     * @return string Path
     */
    public function getDataPath(): string
    {
        return $this->getDisk()->path($this->ns('data'));
    }

    /**
     * Get the Laravel disk path to use to store the metadata result of this job
     * @return string Path
     */
    public function getMetaPath(): string
    {
        return $this->getDisk()->path($this->ns('meta'));
    }

    /**
     * Job handle method
     */
    public function handle(): void
    {
        if ($this->isDeleted()) {
            return;
        }

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

        $callback = $this->transaction->getCallbackUrl();

        if ($callback) {
            Http::get($callback);
        }

        $this->setComplete();
    }

    /**
     * Get the monitoring URL to determine the state of this job by an OData client
     * @return string Monitoring URL
     */
    public function getMonitorUrl(): string
    {
        return Transaction::getResourceUrl().'_lodata/monitor/'.$this->jobId;
    }

    /**
     * Get the OData status of this job
     * @return string|null Status
     */
    public function getStatus(): ?string
    {
        return Cache::get($this->ns('status'));
    }

    /**
     * Set that this job is pending
     * @return $this
     */
    public function setPending(): self
    {
        $this->setStatus(self::STATUS_PENDING);
        return $this;
    }

    /**
     * Set that this job is complete
     * @return $this
     */
    public function setComplete(): self
    {
        $this->setStatus(self::STATUS_COMPLETE);
        return $this;
    }

    /**
     * Set the status of this job
     * @param  string  $status  Status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        Cache::put($this->ns('status'), $status);
        return $this;
    }

    /**
     * Dispatch this job to the queue
     */
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

    /**
     * Get the namespace of this job
     * @param  string  $prefix  Prefix
     * @return string Namespace
     */
    public function ns(string $prefix): string
    {
        return sprintf('%s.%s.%s', $this->jobId, $prefix, 'odata');
    }

    /**
     * Get the result metadata of this job
     * @return array Result metadata
     */
    public function getResultMetadata(): array
    {
        return json_decode(file_get_contents($this->getMetaPath()), true);
    }

    /**
     * Get a stream resource representing the data generated by this job
     * @return false|resource Job result stream
     */
    public function getResultStream()
    {
        return fopen($this->getDataPath(), 'r');
    }

    /**
     * Get whether the results of this job are pending
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    /**
     * Get whether this job was deleted
     * @return bool
     */
    public function isDeleted(): bool
    {
        return null === $this->getStatus();
    }

    /**
     * Delete this job
     */
    public function destroy()
    {
        @unlink($this->getDataPath());
        @unlink($this->getMetaPath());
        Cache::forget($this->ns('status'));
    }

    /**
     * Generate an Accepted result for this job
     * @return AcceptedException
     */
    public function accepted(): AcceptedException
    {
        return AcceptedException::factory()
            ->header('location', $this->getMonitorUrl());
    }
}
<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Traits\HasDisk;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * Abstraction layer over Flysystem to provide the same API for some v1 and v3 calls.
 *
 * @method exists(string $path):bool;
 * @method get(string $path): string;
 * @method readStream(string $path): ?resource;
 * @method put(string $path, $contents, $options = []): bool;
 * @method writeStream(string $path, resource $resource, array $options = []): bool;
 * @method getVisibility(string $path): string;
 * @method setVisibility(string $path, string $visibility): string;
 * @method prepend(string $path, string $data): bool;
 * @method append(string $path, string $data): bool;
 * @method delete($paths): bool;
 * @method copy(string $from, string $to): bool;
 * @method move(string $from, string $to): bool;
 * @method size(string $path): int;
 * @method lastModified(string $path): int;
 * @method files(?string $directory, bool $recursive): array;
 * @method allFiles(?string $directory): array;
 * @method directories(?string $directory, bool $recursive): array;
 * @method allDirectories(?string $directory): array;
 * @method makeDirectory(string $path): bool;
 * @method deleteDirectory(string $directory): bool;
 * @method string mimeType(string $path): string;
 * @method string url(string $url): string;
 */
abstract class Filesystem
{
    use ForwardsCalls;
    use HasDisk;

    abstract public function listContents($directory = '', $recursive = false): ?iterable;

    abstract public function getMetadata($path): ?array;

    abstract public function isLocal(): bool;

    /**
     * Dynamically pass method calls to the underlying disk.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->disk, $method, $parameters);
    }
}



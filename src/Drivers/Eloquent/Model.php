<?php

namespace Flat3\OData\Drivers\Eloquent;

use Flat3\OData\Model;
use Flat3\OData\Entity;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class Model
{
    public static function attach()
    {
        /** @var Model $model */
        $model = resolve(Model::class);

        $eloquentModels = self::getAllModels();
    }

    public static function getAllModels(): array
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $models = [];

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            $models = array_merge(collect(File::allFiles(base_path($path)))
                ->map(function ($item) use ($namespace) {
                    $path = $item->getRelativePathName();
                    return sprintf(
                        '\%s%s',
                        $namespace,
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );
                })
                ->filter(function ($class) {
                    $valid = false;
                    if (class_exists($class)) {
                        $reflection = new ReflectionClass($class);
                        $valid = in_array(UsesOData::class, array_keys($reflection->getTraits())) &&
                            !$reflection->isAbstract();
                    }
                    return $valid;
                })
                ->values()
                ->toArray(), $models);
        }

        return $models;
    }
}

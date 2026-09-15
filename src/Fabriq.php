<?php

namespace Karabin\Fabriq;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class Fabriq
{
    /**
     * Binds the Fabriq routes into the controller.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public static function routes($callback = null, array $options = [])
    {
        $callback = $callback ?: function ($router) {
            $router->all();
        };

        $defaultOptions = [
        ];

        $options = array_merge($defaultOptions, $options);

        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

    /**
     * Return a new instance of a model.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function getModelClass(string $key, ...$arguments)
    {
        $class = config('fabriq.models.'.$key);
        if (! $class) {
            throw new InvalidArgumentException('The model key was not found: '.$key);
        }

        return new $class($arguments);
    }

    /**
     * Return the fully qualified model name.
     *
     * @return mixed
     */
    public static function getFqnModel(string $key)
    {
        $class = config('fabriq.models.'.$key);
        if (! $class) {
            throw new InvalidArgumentException('The model key was not found: '.$key);
        }

        return $class;
    }

    /**
     * Return the data transfer object for the specific model.
     *
     *
     * @throws InvalidArgumentException
     */
    public static function getDto(string $key): string
    {
        $class = config('fabriq.data_transfer_objects.'.$key);
        if (! $class) {
            throw new InvalidArgumentException('The model key was not found: '.$key.'. Add it to config/fabriq.php');
        }

        return $class;
    }
}

<?php

namespace CliffordJames\LaravelUrls;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait HasUrl
{
    protected $baseRoute;

    /**
     * Return route based on de model.
     *
     * @param null|string $route
     * @param array $parameters
     *
     * @return string
     */
    public function url($route = null, ...$parameters)
    {
        $route = $this->route($route);

        $parameters = $this->getRouteParameters($route, $parameters);

        return route($route, $parameters);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function route($name = null)
    {
        return $this->getBaseRoute() . '.' . ($name ?: 'show');
    }

    /**
     * Get the base route associated with the model.
     *
     * @return string
     */
    protected function getBaseRoute()
    {
        if (!isset($this->baseRoute)) {
            $this->baseRoute = str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
        }

        return $this->baseRoute;
    }

    protected function getRouteObject($name)
    {
        if (! is_null($route = app('router')->getRoutes()->getByName($name))) {
            return $route;
        }

        throw new InvalidArgumentException("Route [{$name}] not defined.");
    }

    /**
     * Fill in the route parameters with the correct models.
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return array
     */
    protected function getRouteParameters($name, $parameters)
    {
        $parameters = $this->formatRouteParameters($parameters);

        // Get the parameters given in the route and directly assign parameters
        // which are passed with a name.
        $signatureParameters = collect(
            $this->getRouteObject($name)->signatureParameters(UrlRoutable::class)
        );

        return $signatureParameters->mapWithKeys(function ($parameter) use ($parameters) {
            $name = $parameter->getName();
            $type = $parameter->getType()->getName();

            // Named parameters from input.
            if ($found = $parameters->get($name)) {
                return [$name => $found];
            }

            // Search for matching class names.
            $found = $parameters->search(function ($parameter) use ($type) {
                return get_class($parameter) == $type;
            });

            if ($found !== false) {
                return [$name => $parameters->get($found)];
            }

            // Search for matching relation.
            if (($relation = $this->$name) instanceof UrlRoutable) {
                return [$name => $relation];
            }
        })->all();
    }

    /**
     * @param $parameters
     *
     * @return $this
     */
    protected function formatRouteParameters($parameters)
    {
        // If the parameters are passed as an array rather than individual
        // parameters, reformat the parameters array.
        if (count($parameters) == 1 && is_array($parameters[0])) {
            $parameters = $parameters[0];
        }

        return collect($parameters)->push($this);
    }
}

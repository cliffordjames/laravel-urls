<?php

namespace CliffordJames\LaravelUrls;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUrl
{
    protected $baseRoute;

    /**
     * Return route based on de model.
     *
     * @param null|string $routeName
     * @param array $parameters
     *
     * @return string
     */
    public function url($routeName = null, ...$parameters)
    {
        $route = $this->route($routeName);

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
        if (is_null($name)) {
            $name = 'show';
        }

        return "{$this->getBaseRoute()}.{$name}";
    }

    /**
     * Get the base route associated with the model.
     *
     * @return string
     */
    public function getBaseRoute()
    {
        if (!isset($this->baseRoute)) {
            $this->baseRoute = str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
        }

        return $this->baseRoute;
    }

    /**
     * Fill in the route parameters with the correct models.
     *
     * @param string $route
     * @param array $givenParameters
     *
     * @return array
     */
    protected function getRouteParameters($route, $givenParameters)
    {
        $route = app('router')->getRoutes()->getByName($route);

        // If the parameters are passed as an array rather than individual
        // parameters, reformat the parameters array.
        if (count($givenParameters) == 1 && is_array($givenParameters[0])) {
            $givenParameters = $givenParameters[0];
        }

        $givenParameters = collect($givenParameters)->push($this);

        // Get the parameters given in the route and directly assign parameters
        // which are passed with a name.
        $routeParameters = collect($route->signatureParameters(UrlRoutable::class))
            ->mapWithKeys(function ($parameter) use ($givenParameters) {
                $parameterName = $parameter->getName();
                $parameterValue = $givenParameters->get(
                    $parameterName,
                    $parameter->getType()->getName()
                );

                return [$parameterName => $parameterValue];
            });

        // Loop over the passed parameters with an unknown link (numeric index)
        // and check if the class name exists in the route parameters. If so,
        // assign the parameter.
        $givenParameters->each(function ($parameter, $key) use ($routeParameters) {
            if (!is_numeric($key)) {
                return;
            }

            if ($found = $routeParameters->search(get_class($parameter))) {
                $routeParameters[$found] = $parameter;
            }
        });

        // Loop over the route parameters to see if there are any unlinked
        // parameters and look if they can be linked to a relation.
        $routeParameters = $routeParameters->map(function ($parameter, $name) {
            // Parameters which are already linked can be skipped.
            if ($parameter instanceof Model) {
                return $parameter;
            }

            // Parameter exists as a relation.
            if (($relation = $this->$name) instanceof Model) {
                return $relation;
            }

            return null;
        });

        return $routeParameters->filter()->all();
    }
}

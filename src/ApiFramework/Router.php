<?php namespace ApiFramework;

/**
 * Router class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Router extends Core
{

    /**
     * @var array Routes holder
     */
    private $routes = [
        'get' => [],
        'post' => [],
        'put' => [],
        'delete' => [],
    ];

    /**
     * @var array Filters holder
     */
    private $filters = [];

    /**
     * @var array Applied filters
     */
    private $appliedFilters = [];

    /**
     * Register a url
     * 
     * @param string $method Method 
     * @param string $path Route to match
     * @param array $action Controller and method to execute
     * @param array $filter Optional filter to execute
     * @return boolean Success or fail of registration
     */
    public function register ($method, $path, $action, $filter = null) {

        // Create arrays
        $params = [];
        $matches = [];
        $method = strtolower($method);

        // Replace placeholders
        if (preg_match_all('/\{([\w-]+)\}/i', $path, $matches)) {
            $params = end($matches);
        }
        $pattern = preg_replace(
            ['/(\{[\w-]+\})/i', '/\\//'],
            ['([\w-]+)', '\\/'],
            $path
        );

        // Store in the routes array
        return $this->routes[$method][$path] = [
            'path' => $path,
            'pattern' => '/^' . $pattern . '\\/?$/i',
            'class' => $action[0],
            'method' => $action[1],
            'params' => $params,
            'filter' => $filter
        ];
    }


    /**
     * Retrieve registered routes
     * 
     * @param string $method (Optional) Method specific routes.
     * @return array Array of routes
     */
    public function routes ($method = null) {
        if ($method && isset($this->methods[$method])) {
            return $this->routes[$method];
        }
        return $this->routes;
    }


    /**
     * Retrieve model and method to execute
     * 
     * @param string $route Route to match
     * @return array Action and parameters
     */
    public function getAction ($url) {

        // Current route holder
        $current =  false;

        // Get requested method
        $method = $this->app->request->method() ? : 'GET';
        $method = strtolower($method);

        // Check all routes until one matches
        foreach ($this->routes[$method] as $route) {
            $matches = [];
            if (preg_match($route['pattern'], $url, $matches)) {
                if (!empty($matches)) {
                    array_shift($matches);
                    $route['params'] = array_combine(
                        $route['params'],
                        $matches
                    );
                }
                $current = $route;
            }
        }

        // Abort if none of the routes matched
        if (!$current) {
            throw new \Exception('Route not found', 404);
        }

        // Return current route
        return $current;
    }


    /**
     * Register a resource
     * 
     * @param string $route Route for the resource
     * @param string $controller Controller name
     * @param string $filter Optional filter to execute
     * @return boolean Success or fail of registration
     */
    public function resource ($route, $controller, $filter = null) {
        $this->register('get', $route, [$controller, 'index'], $filter);
        $this->register('get', $route . '/{id}', [$controller, 'show'], $filter);
        $this->register('post', $route, [$controller, 'store'], $filter);
        $this->register('put', $route . '/{id}', [$controller, 'update'], $filter);
        $this->register('delete', $route . '/{id}', [$controller, 'destroy'], $filter);
        return true;
    }

    /**
     * Sets a route filter
     * 
     * @param string $filterName Filter name
     * @param array $callback Callback to exectue
     * @return boolean Success or failure of registration
     */
    public function filter ($filterName, $callback) {
        return $this->filters[$filterName] = $callback;
    }

    /**
     * Returns a route filter
     * 
     * @param string $filterName Filter name
     * @return mixed Registered filter
     */
    public function getFilter ($filterName = null) {
        return isset($this->filters[$filterName])? $this->filters[$filterName] : false;
    }

    /**
     * Sets an applied filter
     * 
     * @param string $filterName Filter name
     * @return mixed Registered filter
     */
    public function setAppliedFilter ($filterName = null) {
        return $this->appliedFilters[] = $filterName;
    }

    /**
     * Sets an applied filter
     * 
     * @param string $filterName Filter name
     * @return mixed Registered filter
     */
    public function filtered ($filterName = null) {
        return in_array($filterName, $this->appliedFilters);
    }

    /**
     * Captures non existing functions
     * 
     * @param string $function Function requested to execute
     * @param array $params Params requested for function execution
     * @return boolean
     */
    public function __call ($function, $params)
    {
        if (isset($this->routes[$function])) {
            if (isset($params[2])) {
                $this->register($function, $params[0], $params[1], $params[2]);
            } else {
                $this->register($function, $params[0], $params[1]);
            }
            return true;
        }
        return false;
    }

}
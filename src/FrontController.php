<?php
/**
 * Definition of FrontController
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Controllers;

use FF\Controllers\Exceptions\ControllerInspectionException;
use FF\Controllers\Exceptions\IncompleteRouteException;
use FF\Controllers\Exceptions\ResourceNotFoundException;
use FF\Events\EventBroker;
use FF\Factories\Exceptions\ClassNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException as SymfonyResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class FrontController
 *
 * @package FF\Controllers
 */
class FrontController
{
    const RESERVED_ROUTE_PARAMS = ['controller', 'action', '_route'];

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * @param RouteCollection $routes
     * @return $this
     */
    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;
        return $this;
    }

    /**
     * Retrieves a route's path by name
     *
     * @param string $name
     * @return string
     */
    public function getRoutePath(string $name): string
    {
        $route = $this->routes->get($name);
        if (is_null($route)) return '';

        $path = $route->getPath();
        return !empty($path) ? $path : '/';
    }

    /**
     * Builds a relative path
     *
     * Strips non-filled path tokens from the end of the path.
     * Returns an empty string if $routeName is not found.
     *
     * @param string $routeName
     * @param array $namedArgs
     * @return string
     */
    public function buildPath(string $routeName, array $namedArgs = []): string
    {
        $route = $this->routes->get($routeName);
        if (is_null($route)) return '';

        // add omitted args having defaults in route's definition
        foreach ($route->getDefaults() as $name => $default) {
            if ($name == 'controller' || $name == 'action') continue;
            if (array_key_exists($name, $namedArgs)) continue;

            $namedArgs[$name] = $default;
        }

        // fill-in args in route's path
        $path = $route->getPath();
        foreach ($namedArgs as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }

        // strip unfilled args from end of path
        // e.g. /something/foo/{bar}
        $path = preg_replace('~(/{[^}]+})+$~', '', $path);

        if (empty($path)) $path = '/';

        return $path;
    }


    /**
     * Retrieves parameters of a matching route for the request given
     *
     * @param Request $request
     * @return array|null
     * @throws IncompleteRouteException
     */
    public function match(Request $request): ?array
    {
        $context = new RequestContext();
        $context->fromRequest($request);

        try {
            $matcher = new UrlMatcher($this->routes, $context);
            $pathInfo = $context->getPathInfo();
            $parameters = $matcher->match($pathInfo);
            if (!isset($parameters['controller'])) {
                throw new IncompleteRouteException(
                    'controller param missing from route [' . $parameters['_route'] . ']'
                );
            }
            if (!isset($parameters['action'])) {
                throw new IncompleteRouteException('action param missing from route [' . $parameters['_route'] . ']');
            }

            return $parameters;
        } catch (SymfonyResourceNotFoundException $e) {
            return null;
        }
    }

    /**
     * Dispatches a request
     *
     * @param Request $request
     * @return Response
     * @throws ResourceNotFoundException no route found for request
     * @fires Controllers\OnPreDispatch
     * @fires Controllers\OnPostRoute
     * @fires Controllers\OnPostDispatch
     */
    public function dispatch(Request $request): Response
    {
        EventBroker::getInstance()->fire('Controllers\OnPreDispatch', $request);

        $parameters = $this->match($request);
        if (is_null($parameters)) {
            throw new ResourceNotFoundException('no route found for request [' . $request->getPathInfo() . ']');
        }

        try {
            $controller = ControllersFactory::getInstance()->create($parameters['controller']);
            $action = $parameters['action'];
            $args = $this->extractArgs($parameters);
            $actionArgs = $this->buildActionArgs($controller, $action, $args);
        } catch (ClassNotFoundException $e) {
            throw new ResourceNotFoundException('controller [' . $parameters['controller'] . '] not found', 0, $e);
        } catch(ControllerInspectionException $e) {
            throw new ResourceNotFoundException(
                'action [' . $parameters['action'] . '] not found in controller [' . $parameters['controller'] . ']',
                0,
                $e
            );
        }

        EventBroker::getInstance()->fire('Controllers\OnPostRoute', $request, $controller, $action, $args);

        /** @var Response $response */
        $response = call_user_func_array([$controller, $action], $actionArgs);

        EventBroker::getInstance()->fire('Controllers\OnPostDispatch', $response, $controller, $action, $actionArgs);

        return $response;
    }

    /**
     * Retrieves the action arguments
     *
     * @param array $parameters
     * @return array
     */
    protected function extractArgs(array $parameters): array
    {
        $args = [];
        foreach ($parameters as $key => $value) {
            if (in_array($key, self::RESERVED_ROUTE_PARAMS)) continue;
            $args[$key] = $value;
        }

        return $args;
    }

    /**
     * Builds the arguments list for invoking the desired action
     *
     * @param AbstractController $controller
     * @param string $action
     * @param array $args
     * @return array
     * @throws ResourceNotFoundException
     * @throws ControllerInspectionException
     */
    protected function buildActionArgs(AbstractController $controller, string $action, array $args): array
    {
        $methodArgs = [];
        try {
            $reflection = new \ReflectionMethod($controller, $action);
            foreach ($reflection->getParameters() as $param) {
                $name = $param->getName();
                if (!isset($args[$name])) {
                    if (!$param->isOptional()) {
                        throw new ResourceNotFoundException(
                            'missing required argument [' . $name . '] for action [' . $action . '] '
                            . 'of controller [' . get_class($controller) . ']'
                        );
                    }
                    $methodArgs[] = $param->getDefaultValue();
                } else {
                    $methodArgs[] = $args[$name];
                }
            }
        } catch (\ReflectionException $e) {
            throw new ControllerInspectionException(
                'error while inspecting action [' . $action . '] of controller [' . get_class($controller) . ']',
                0,
                $e
            );
        }

        return $methodArgs;
    }
}

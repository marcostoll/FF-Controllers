<?php
/**
 * Definition of AbstractController
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Controllers;

use FF\Controllers\Exceptions\ControllerInspectionException;
use FF\Events\EventBroker;
use FF\Templating\TemplateRendererInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractController
 *
 * Concrete sub classes should define one or more action methods.
 *
 * Any action method must meet the following requirements:
 *      - must be public
 *      - must not be static
 *      - must return an instance of Symfony\Component\HttpFoundation\Response
 *
 * Action methods may define any number of arguments
 *
 * @package FF\Controllers
 */
abstract class AbstractController
{
    /**
     * Forwards to another controller action
     *
     * This method can be invoked with an arbitrary amount of arguments.
     * Any $args will be passed to the designated forwarded action in the given order.
     *
     * @param AbstractController|string $controller A controller instance or the class identifier of a controller class
     * @param string $action
     * @param array $args
     * @return Response
     * @throws \InvalidArgumentException action not callable
     * @throws \InvalidArgumentException missing required action argument
     * @throws ControllerInspectionException
     * @fires Controllers\OnPreForward
     */
    public function forward($controller, string $action, ...$args)
    {
        if (is_string($controller)) {
            $controller = $this->createController($controller);
        }

        EventBroker::getInstance()->fire('Controllers\OnPreForward', $controller, $action, $args);

        if (!method_exists($controller, $action) || !is_callable([$controller, $action])) {
            throw new \InvalidArgumentException(
                'controller [' . get_class($controller) . '] does not define a callable action [' . $action . ']'
            );
        }

        // gather action arguments
        $methodArgs = [];
        try {
            $reflection = new \ReflectionMethod($controller, $action);
            foreach ($reflection->getParameters() as $index => $param) {
                $name = $param->getName();
                if (!isset($args[$index])) {
                    if (!$param->isOptional()) {
                        throw new \InvalidArgumentException(
                            'missing required argument [' . $name . '] for action [' . $action . '] '
                                . 'of controller [' . get_class($controller) . ']'
                        );
                    }
                    $methodArgs[] = $param->getDefaultValue();
                } else {
                    $methodArgs[] = $args[$index];
                }
            }
        } catch (\ReflectionException $e) {
            throw new ControllerInspectionException('error while inspecting controller class', 0, $e);
        }

        // invoke forwarded action
        /** @var Response $response */
        $response = call_user_func_array([$controller, $action], $methodArgs);

        return $response;
    }

    /**
     * Creates a controller
     *
     * @param string $controller
     * @return AbstractController
     */
    protected function createController(string $controller): AbstractController
    {
        return ControllersFactory::getInstance()->create($controller);
    }

    /**
     * Renders a template
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function render(string $template, array $data = []): string
    {
        return $this->getTemplateRenderer()->render($template, $data);
    }

    /**
     * @return TemplateRendererInterface
     */
    protected abstract function getTemplateRenderer(): TemplateRendererInterface;
}

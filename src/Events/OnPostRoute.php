<?php
/**
 * Definition of OnPostRoute
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Controllers\Events;

use FF\Controllers\AbstractController;
use FF\Events\AbstractEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OnPostRoute
 *
 * @package FF\Controllers\Events
 */
class OnPostRoute extends AbstractEvent
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var AbstractController
     */
    protected $controller;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @param Request $request
     * @param AbstractController $controller
     * @param string $action
     * @param array $args
     */
    public function __construct(Request $request, AbstractController $controller, $action, array $args = [])
    {
        $this->request = $request;
        $this->controller = $controller;
        $this->action = $action;
        $this->args = $args;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return AbstractController
     */
    public function getController(): AbstractController
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Retrieves the action arguments
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}

<?php
/**
 * Definition of ControllersFactory
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Controllers;


use FF\Factories\AbstractSingletonFactory;
use FF\Factories\ClassLocators\ClassLocatorInterface;
use FF\Factories\ClassLocators\NamespacePrefixedClassLocator;

/**
 * Class ControllersFactory
 *
 * @package FF\Controllers
 */
class ControllersFactory extends AbstractSingletonFactory
{
    /**
     * @var ControllersFactory
     */
    protected static $instance;

    /**
     * Declared protected to prevent external usage.
     * Uses a NamespacePrefixedClassLocator pre-configured with 'Controllers' prefix and the FF namespace.
     * @see \FF\Factories\ClassLocators\NamespacePrefixedClassLocator
     */
    protected function __construct()
    {
        parent::__construct(new NamespacePrefixedClassLocator('Controllers', 'FF'));
    }

    /**
     * Declared protected to prevent external usage
     */
    protected function __clone()
    {

    }

    /**
     * {@inheritDoc}
     * @return NamespacePrefixedClassLocator
     */
    public function getClassLocator(): ClassLocatorInterface
    {
        return parent::getClassLocator();
    }

    /**
     * Retrieves the singleton instance of this class
     *
     * @return ControllersFactory
     */
    public static function getInstance(): ControllersFactory
    {
        if (is_null(self::$instance)) {
            self::$instance = new ControllersFactory();
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     * @return AbstractController
     */
    public function create(string $classIdentifier, ...$args)
    {
        /** @var AbstractController $controller */
        $controller = parent::create($classIdentifier, ...$args);
        return $controller;
    }
}
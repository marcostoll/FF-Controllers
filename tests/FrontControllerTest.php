<?php
/**
 * Definition of FrontControllerTest
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Tests\Controllers;

use FF\Controllers\AbstractController;
use FF\Controllers\ControllersFactory;
use FF\Controllers\Events\OnPostDispatch;
use FF\Controllers\Events\OnPostRoute;
use FF\Controllers\Events\OnPreDispatch;
use FF\Controllers\Exceptions\IncompleteRouteException;
use FF\Controllers\Exceptions\ResourceNotFoundException;
use FF\Controllers\FrontController;
use FF\Events\AbstractEvent;
use FF\Events\EventBroker;
use FF\Templating\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Test FrontControllerTest
 *
 * @package FF\Tests
 */
class FrontControllerTest extends TestCase
{
    /**
     * @var RouteCollection
     */
    protected static $routes;

    /**
     * @var AbstractEvent[]
     */
    protected static $lastEvents;

    /**
     * @var FrontController
     */
    protected $uut;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        ControllersFactory::getInstance()->getClassLocator()->prependNamespaces('FF\Tests');

        $locator = new FileLocator(__DIR__ . '/routing');
        $loader = new YamlFileLoader($locator);
        self::$routes = $loader->load('test-routing.yml');

        // register test listener
        EventBroker::getInstance()
            ->subscribe([__CLASS__, 'listener'], 'Controllers\OnPreDispatch')
            ->subscribe([__CLASS__, 'listener'], 'Controllers\OnPostRoute')
            ->subscribe([__CLASS__, 'listener'], 'Controllers\OnPostDispatch');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->uut = new FrontController(self::$routes);
    }

    /**
     * Dummy event listener
     *
     * @param AbstractEvent $event
     */
    public static function listener(AbstractEvent $event)
    {
        self::$lastEvents[get_class($event)] = $event;
    }

    /**
     * Retrieves a request instance
     *
     * @param string $requestUri
     * @return Request
     */
    protected function buildRequest($requestUri)
    {
        return new Request([], [], [], [], [], ['REQUEST_URI' => $requestUri]);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testSetGetRoutes()
    {
        $value = new RouteCollection();
        $same = $this->uut->setRoutes($value);
        $this->assertSame($this->uut, $same);
        $this->assertSame($value, $this->uut->getRoutes());
    }

    /**
     * Tests the namesake method/feature
     */
    public function testGetRoutePath()
    {
        $this->assertEquals('/default', $this->uut->getRoutePath('default'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testGetRoutePathMissing()
    {
        $this->assertEquals('', $this->uut->getRoutePath('unknown'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrl()
    {
        $path = $this->uut->buildPath('default');
        $this->assertEquals('/default', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrlExtraArgs()
    {
        $path = $this->uut->buildPath('default', ['foo' => 'bar']);
        $this->assertEquals('/default', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrlWithArgs()
    {
        $path = $this->uut->buildPath('with-args', ['foo' => 'foo', 'bar' => 'bar']);
        $this->assertEquals('/with-args/foo/bar', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrlWithoutArgs()
    {
        $path = $this->uut->buildPath('with-args');
        $this->assertEquals('/with-args', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrlDefaultArgs()
    {
        $path = $this->uut->buildPath('omitted-args', ['foo' => 'foo']);
        $this->assertEquals('/omitted-args/foo/bar', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testBuildUrlMissingArgs()
    {
        $path = $this->uut->buildPath('omitted-args');
        $this->assertEquals('/omitted-args/{foo}/bar', $path);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchDefault()
    {
        $response = $this->uut->dispatch($this->buildRequest('/default'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('default', $response->getContent());
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchWithArgs()
    {
        $response = $this->uut->dispatch($this->buildRequest('/with-args/foo/bar'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('foo-bar', $response->getContent());
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchOmittedArgs()
    {
        $response = $this->uut->dispatch($this->buildRequest('/omitted-args/foo'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('foo-bar', $response->getContent());
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchEvents()
    {
        $this->uut->dispatch($this->buildRequest('/default'));

        $this->assertArrayHasKey(OnPreDispatch::class, self::$lastEvents);
        $this->assertArrayHasKey(OnPostRoute::class, self::$lastEvents);
        $this->assertArrayHasKey(OnPostDispatch::class, self::$lastEvents);
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorRouteController()
    {
        $this->expectException(IncompleteRouteException::class);

        $this->uut->dispatch($this->buildRequest('/missing-controller'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorRouteAction()
    {
        $this->expectException(IncompleteRouteException::class);

        $this->uut->dispatch($this->buildRequest('/missing-action'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorNoRoute()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->uut->dispatch($this->buildRequest('/unknown-path'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorNoController()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->uut->dispatch($this->buildRequest('/unknown-controller'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorNoAction()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->uut->dispatch($this->buildRequest('/unknown-action'));
    }

    /**
     * Tests the namesake method/feature
     */
    public function testDispatchErrorMissingArg()
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->uut->dispatch($this->buildRequest('/missing-arg'));
    }
}

class HelloWorldController extends AbstractController
{
    public function default(): Response
    {
        return new Response('default');
    }

    public function helloWorld(string $foo, string $bar = 'baz'): Response
    {
        return new Response($foo . '-' .  $bar);
    }

    /**
     * @return TemplateRendererInterface
     */
    protected function getTemplateRenderer(): TemplateRendererInterface
    {
        return new TwigRenderer(__DIR__ . '/templates');
    }
}
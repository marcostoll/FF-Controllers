<?php
/**
 * Definition of AbstractControllerTest
 *
 * @author Marco Stoll <marco@fast-forward-encoding.de>
 * @copyright 2019-forever Marco Stoll
 * @filesource
 */
declare(strict_types=1);

namespace FF\Tests\Controllers;

use FF\Controllers\AbstractController;
use FF\Controllers\ControllersFactory;
use FF\Factories\Exceptions\ClassNotFoundException;
use FF\Templating\TemplateRendererInterface;
use FF\Templating\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test AbstractControllerTest
 *
 * @package FF\Tests
 */
class AbstractControllerTest extends TestCase
{
    /**
     * @var MyController
     */
    protected $uut;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        ControllersFactory::getInstance()->getClassLocator()->prependNamespaces('FF\Tests');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->uut = new MyController();
    }

    /**
     * Tests the namesake method/feature
     */
    public function testForwardByObject()
    {
        $response = $this->uut->forward(new MyController(), 'foo', 'bar');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($response->getContent(), 'bar');
    }

    /**
     * Tests the namesake method/feature
     */
    public function testForwardByClassName()
    {
        $response = $this->uut->forward('MyController', 'foo', 'bar', 'baz');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($response->getContent(), 'barbaz');
    }

    /**
     * Tests the namesake method/feature
     */
    public function testForwardErrorController()
    {
        $this->expectException(ClassNotFoundException::class);

        $this->uut->forward('UnknownController', 'foo', 'bar');
    }

    /**
     * Tests the namesake method/feature
     */
    public function testForwardErrorAction()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->uut->forward('MyController', 'baz');
    }

    /**
     * Tests the namesake method/feature
     */
    public function testForwardErrorRequiredArg()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->uut->forward('MyController', 'foo');
    }
}

class MyController extends AbstractController
{
    public function foo(string $bar, string $baz = ''): Response
    {
        return new Response($bar . $baz);
    }

    /**
     * @return TemplateRendererInterface
     */
    protected function getTemplateRenderer(): TemplateRendererInterface
    {
        return new TwigRenderer(__DIR__ . '/templates');
    }
}
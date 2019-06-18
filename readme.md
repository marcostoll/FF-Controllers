FF\Controllers | Fast Forward Components Collection
===============================================================================

by Marco Stoll

- <marco.stoll@rocketmail.com>
- <http://marcostoll.de>
- <https://github.com/marcostoll>
- <https://github.com/marcostoll/FF-Controllers>
------------------------------------------------------------------------------------------------------------------------

# What is the Fast Forward Components Collection?
The Fast Forward Components Collection, in short **Fast Forward** or **FF**, is a loosely coupled collection of code 
repositories each addressing common problems while building web application. Multiple **FF** components may be used 
together if desired. And some more complex **FF** components depend on other rather basic **FF** components.

**FF** is not a framework in and of itself and therefore should not be called so. 
But you may orchestrate multiple **FF** components to build an web application skeleton that provides the most common 
tasks.

# Introduction

This component provides a `FrontController` implementation that lets you dispatch request to your application based on
a defined routing configuration. This component makes heavy use of suitable **Symfony** components.

# Registering your Controller Classes

Controller classes must be concrete children of `AbstractController`. They should define one or more action methods that
meet the following requirements:

- must be public
- must not be static
- must return an instance of Symfony\Component\HttpFoundation\Response

The `ControllersFactory` uses a pre-configured instance of an `FF\Factories\ClassLocators\NamespacePrefixedClassLocator` 
to find the controller class definition identified by the class identifier. 

In short, if your project's namespace would be `MyProject\` then your controller classes should be stored in 
`MyProject\Controllers\`.
 
Example:

    namespace MyProject\Controllers;
    
    use FF\Controllers\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    
    /**
     * This controllers's class identifier would just be 'IndexController'
     */
    class IndexController extends AbstractController
    {
        public function helloWorld()
        {
            return new Respone($this->render('hello-world.twig.html', ['message' => 'Hello, world!']);
        }
    }
    
In this example you would register your base namespace `MyProject` to the `ControllersFactory`.    

Example:

    use FF\Controller\ControllersFactory;
    
    ControllersFactory::getInstance()
        ->getCLassLocator()
        ->prependNamespaces('MyProject');
        
## Dividing Controllers into Sub Namespaces            
        
If your project is composed of multiple packages sharing a common base namespace and you want to distribute your 
controller definitions their sub package context, then only the controllers's class identifiers would become a little 
longer.

Let's assume your project's structure looks something like this:

    |- MyProject\
    |- MyProject\PackageOne\
    |- MyProject\PackageOne\Controllers\
    |- MyProject\PackageOne\Controllers\IndexController      -> class identifer: 'PackageOne\IndexController'
    |- MyProject\PackageTwo\
    |- MyProject\PackageThree\
    |- MyProject\PackageThree\Controllers\
    |- MyProject\PackageThree\Controllers\IndexController    -> class identifer: 'PackageThree\IndexController'
    
Each package which should receive its own controller definitions must be provided with the `Controllers\' sub namespace 
to store the controller definitions of this package.      

Now you would register your project's namespace at the `ControllersFactory` just like shown above only using the base
namespace `MyProject`. The class identifiers of your controllers now are composed of the package name (e.g. 
`PackageThree`) and the controller's class name (e.g. `IndexController`) divided by an backslash (\).

So use 'PackageThree\IndexController' as class identifier to get to this controller.

# Routing

The `FrontController` expects a `Symfony\Component\Routing\RouteCollection` to provide its routing configuration.

An eays way to provide this is by defining a routing file in the yml format and parsing it via **Symfony**'s 
`YamlFileLoader`

An example:

    use FF\Controllers\FronteCOntroller
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\Routing\Loader\YamlFileLoader;

    // parse routing configuration
    $locator = new FileLocator('/path/to/routing');
    $loader = new YamlFileLoader($locator);
    $routes = $loader->load('routing.yml');
    
    // create front controller
    $myFrontController = new FrontController($routes);
    
As valid yml structure route entries in the configuration should look like this:

    << route name >>:
        path: << url path >>
        defaults: { controller: << controller class identifier >>, action: << action method >> }
        
The `path` my contain argument placeholder in the form of `{arg}`.
The `defaults` section may additionally contain named arguments to provide default values for `path`arguments.
You may provide a `requirements` section to specify route limitations (like patterns for acceptable argument values).
See <https://symfony.com/doc/current/routing.html> for more information.

Some examples:

    # route to the 'index' action of your project's IndexController
    home:
        path: /
        defaults: { controller: IndexController, action: index }
        
    # route to the 'list' action of your project's ArticlesController located in the sub package 'Articles'
    # defines an optional path argument 'category' that will be fill with an empty string if omitted    
    list-articles:
        path: /articles/{category}
        defaults: { controller: Articles\ArticlesController, action: list, category: '' }
        requirements:
            category: \w+            
            

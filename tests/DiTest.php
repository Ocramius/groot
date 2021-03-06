<?php
namespace GianArb\GrootTest;

use GianArb\Groot\App;
use DI\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyDiCBuilder;
use Acclimate\Container\CompositeContainer;
use Acclimate\Container\ContainerAcclimator;
use TestApp\Controller\Index;
use Zend\EventManager\EventManager;

class DiTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $router = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/', ['TestApp\Controller\Index', 'index']);
            $r->addRoute('GET', '/fail', ['TestApp\Controller\Index', 'failed']);
        });
        $this->app = new App($router);
        $acclimate = new ContainerAcclimator();

        $mnapoliDiCBuilder = new ContainerBuilder();
        $mnapoliDiC = $acclimate->acclimate($mnapoliDiCBuilder->build());
        $symfonyDiC = new SymfonyDiCBuilder();
        $syAcclimate = $acclimate->acclimate($symfonyDiC);

        $container = $this->getMockBuilder("Acclimate\Container\CompositeContainer")
            ->setConstructorArgs([[$syAcclimate, $mnapoliDiC]])->getMock();

        $container->expects($this->any())
            ->method("get")
            ->with($this->logicalOr(
                 $this->equalTo('http.flow'),
                 $this->equalTo('troyan'),
                 $this->equalTo('TestApp\Controller\Index')
             ))
            ->will($this->returnCallback(function($arg) use ($mnapoliDiC) {
                if($arg == "tryan"){
                    $stub = $this->getMock("stdClass");
                    return $stub;
                }
                if($arg == "http.flow"){
                    return new EventManager();
                }
                return $mnapoliDiC->get("TestApp\\Controller\\Index");
            }));

        $symfonyDiC->register('dispatcher', "GianArb\\Groot\\Dispatcher")
            ->addMethodCall('setRouter', [$router]);

        $mnapoliDiC->set('di', $container);



        $this->app->setContainer($container);
    }

    public function testInjectionHttpFlow()
    {
        $index = $this->app->getContainer()->get("TestApp\Controller\Index");
        $request = $this->getMock("Zend\\Diactoros\\Request");
        $response = $this->getMock("Zend\\Diactoros\\Response");
        $index->diTest($request, $response);
    }
}

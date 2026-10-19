<?php

declare(strict_types=1);

namespace Contenir\Mvc\Sitemap\Tests\Trait;

use Contenir\Mvc\Sitemap\Module;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\View\NavigationHelperFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper\Navigation as NavigationHelper;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;

/**
 * A minimal laminas-mvc application: the module's routes plus "home", a
 * "cms" navigation container with pages hidden from the menu, and a view
 * helper manager with the navigation helper, serving https://www.example.com.
 */
trait SitemapApplicationTrait
{
    private function createRouter(): TreeRouteStack
    {
        $router = TreeRouteStack::factory(['routes' => (new Module())->getConfig()['router']['routes']]);
        $router->addRoute('home', ['type' => Literal::class, 'options' => ['route' => '/']]);

        return $router;
    }

    /**
     * @param array<string, mixed>|null $config The "config" service; null leaves it out.
     */
    private function createServices(?array $config = null): ServiceManager
    {
        $services = new ServiceManager();
        $services->setService('cms', new Navigation([
            ['label' => 'Home', 'uri' => '/'],
            [
                'label' => 'About',
                'uri'   => '/about',
                'pages' => [['label' => 'Team', 'uri' => '/about/team', 'visible' => false]],
            ],
            [
                'label'   => 'Privacy',
                'uri'     => '/privacy',
                'visible' => false,
                'pages'   => [['label' => 'Cookies', 'uri' => '/privacy/cookies']],
            ],
        ]));
        if (null !== $config) {
            $services->setService('config', $config);
        }

        $helpers = new HelperPluginManager($services, [
            'factories' => [NavigationHelper::class => NavigationHelperFactory::class],
        ]);
        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager($helpers);
        $renderer->plugin(ServerUrl::class)->setScheme('https')->setHost('www.example.com');

        $services->setService('ViewHelperManager', $helpers);

        return $services;
    }
}

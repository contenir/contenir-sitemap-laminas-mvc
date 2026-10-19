<?php

declare(strict_types=1);

namespace Contenir\Mvc\Sitemap\Factory;

use Contenir\Mvc\Sitemap\SitemapController;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\View\Exception\RuntimeException as ViewRuntimeException;
use Laminas\View\Helper\Navigation as NavigationProxyHelper;
use Laminas\View\Helper\Navigation\Sitemap;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function filter_var;
use function get_debug_type;
use function is_string;
use function sprintf;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Builds the SitemapController around the Sitemap helper of the
 * application's navigation view helper, with the CMS's "site.sitemap.enabled"
 * and "site.url" settings. The CMS writes its settings under "settings" in the
 * site's config/autoload/settings.global.php and settings.local.php, so they
 * arrive in the application's "config".
 *
 * @api
 */
final class SitemapControllerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceNotCreatedException when the view helper manager or the Sitemap helper has the wrong type.
     * @throws ViewRuntimeException when the navigation helper has no Sitemap helper.
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public function __invoke(ContainerInterface $container): SitemapController
    {
        $viewHelperManager = $container->get('ViewHelperManager');
        if (! $viewHelperManager instanceof HelperPluginManager) {
            throw new ServiceNotCreatedException(sprintf(
                'Service "ViewHelperManager" must be a %s, %s given',
                HelperPluginManager::class,
                get_debug_type($viewHelperManager),
            ));
        }

        $navigation = $viewHelperManager->get(NavigationProxyHelper::class);

        $sitemap = $navigation->findHelper(Sitemap::class);
        if (! $sitemap instanceof Sitemap) {
            throw new ServiceNotCreatedException(sprintf(
                'Navigation helper "%s" must be a %s, %s given',
                Sitemap::class,
                Sitemap::class,
                get_debug_type($sitemap),
            ));
        }

        $config  = $container->has('config') ? $container->get('config') : [];
        $enabled = $config['settings']['site']['sitemap']['enabled'] ?? true;
        $siteUrl = $config['settings']['site']['url'] ?? '';

        return new SitemapController(
            $sitemap,
            enabled: filter_var($enabled, FILTER_VALIDATE_BOOLEAN),
            siteUrl: is_string($siteUrl) ? $siteUrl : '',
        );
    }
}

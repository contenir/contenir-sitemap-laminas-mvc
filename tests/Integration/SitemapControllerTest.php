<?php

declare(strict_types=1);

namespace Contenir\Mvc\Sitemap\Tests\Integration;

use Contenir\Mvc\Sitemap\Factory\SitemapControllerFactory;
use Contenir\Mvc\Sitemap\SitemapController;
use Contenir\Mvc\Sitemap\Tests\Trait\SitemapApplicationTrait;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function get_debug_type;
use function str_ends_with;

#[CoversClass(SitemapController::class)]
#[CoversClass(SitemapControllerFactory::class)]
#[Group('integration')]
#[Group('controller')]
final class SitemapControllerTest extends TestCase
{
    use SitemapApplicationTrait;

    private const string SITEMAP =
        '<?xml version="1.0" encoding="UTF-8"?>'
            . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . '<url><loc>https://www.example.com/</loc></url>'
            . '<url><loc>https://www.example.com/about</loc></url>'
            . '<url><loc>https://www.example.com/about/team</loc></url>'
            . '<url><loc>https://www.example.com/privacy</loc></url>'
            . '<url><loc>https://www.example.com/privacy/cookies</loc></url>'
            . '</urlset>';

    /**
     * @return array<string, array{mixed}>
     */
    public static function disabledProvider(): array
    {
        return [
            'the CMS checkbox cleared' => ['0'],
            'false'                    => [false],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function enabledProvider(): array
    {
        return [
            'the CMS checkbox ticked' => ['1'],
            'true'                    => [true],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>|null}>
     */
    public static function noSiteUrlProvider(): array
    {
        return [
            'no config service'           => [null],
            'no CMS settings'             => [[]],
            'an empty site URL'           => [['settings' => ['site' => ['url' => '']]]],
            'a bare host name'            => [['settings' => ['site' => ['url' => 'www.example.com']]]],
            'a site URL without scheme'   => [['settings' => ['site' => ['url' => '//www.example.com']]]],
            'a site URL without host'     => [['settings' => ['site' => ['url' => 'https:/en']]]],
            'a malformed site URL'        => [['settings' => ['site' => ['url' => 'https://']]]],
            'a site URL that is not text' => [['settings' => ['site' => ['url' => ['https://www.example.com']]]]],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function siteUrlProvider(): array
    {
        return [
            'an origin'            => ['https://www.example.com', 'https://www.example.com'],
            'a trailing slash'     => ['https://www.example.com/', 'https://www.example.com'],
            'a path'               => ['https://www.example.com/en/', 'https://www.example.com'],
            'a port'               => ['http://localhost:8080', 'http://localhost:8080'],
            'a query and fragment' => ['https://www.example.com?a=b#c', 'https://www.example.com'],
        ];
    }

    #[Test]
    #[DataProvider('disabledProvider')]
    public function answersNotFoundForTheSitemapWhenItIsDisabled(mixed $enabled): void
    {
        $event    = $this->dispatch('/sitemap.xml', ['settings' => ['site' => ['sitemap' => ['enabled' => $enabled]]]]);
        $response = $event->getResponse();
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(
            [404, '', false, ViewModel::class],
            [
                $response->getStatusCode(),
                $response->getContent(),
                $response->getHeaders()->has('Content-Type'),
                get_debug_type($event->getResult()),
            ],
        );
    }

    #[Test]
    #[DataProvider('disabledProvider')]
    public function leavesTheSitemapOutOfRobotsRulesWhenItIsDisabled(mixed $enabled): void
    {
        $config = ['settings' => ['site' => ['sitemap' => ['enabled' => $enabled]]]];

        $response = $this->dispatch('/robots.txt', $config)->getResponse();
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(
            "# robots.txt for https://shop.example.org/\n\nUser-agent: *\nAllow: /",
            $response->getContent(),
        );
    }

    #[Test]
    #[DataProvider('siteUrlProvider')]
    public function linksTheSitemapFromTheConfiguredSiteUrl(string $siteUrl, string $origin): void
    {
        $response = $this->dispatch('/robots.txt', ['settings' => ['site' => ['url' => $siteUrl]]])->getResponse();
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(
            "# robots.txt for {$origin}/\n\nUser-agent: *\nAllow: /\n\nSitemap: {$origin}/sitemap.xml",
            $response->getContent(),
        );
    }

    /**
     * @param array<string, mixed>|null $config
     */
    #[Test]
    #[DataProvider('noSiteUrlProvider')]
    public function servesRobotsRulesForTheRequestHostWithoutASiteUrl(?array $config): void
    {
        $response = $this->dispatch('/robots.txt', $config)->getResponse();
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(
            [
                'text/plain; charset=utf-8',
                "# robots.txt for https://shop.example.org/\n\nUser-agent: *\nAllow: /\n\n"
                    . 'Sitemap: https://shop.example.org/sitemap.xml',
            ],
            [$response->getHeaders()->get('Content-Type')?->getFieldValue(), $response->getContent()],
        );
    }

    #[Test]
    public function servesTheCmsNavigationAsASitemapIncludingPagesHiddenFromTheMenu(): void
    {
        $response = $this->dispatch('/sitemap.xml')->getResponse();
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(
            ['application/xml; charset=utf-8', self::SITEMAP],
            [$response->getHeaders()->get('Content-Type')?->getFieldValue(), $response->getContent()],
        );
    }

    #[Test]
    #[DataProvider('enabledProvider')]
    public function servesTheSitemapWhenItIsEnabled(mixed $enabled): void
    {
        $config = ['settings' => ['site' => ['sitemap' => ['enabled' => $enabled]]]];

        $sitemap = $this->dispatch('/sitemap.xml', $config)->getResponse();
        $robots  = $this->dispatch('/robots.txt', $config)->getResponse();
        static::assertInstanceOf(Response::class, $sitemap);
        static::assertInstanceOf(Response::class, $robots);

        static::assertSame(
            [200, self::SITEMAP, true],
            [
                $sitemap->getStatusCode(),
                $sitemap->getContent(),
                str_ends_with($robots->getContent(), "\n\nSitemap: https://shop.example.org/sitemap.xml"),
            ],
        );
    }

    /**
     * @param array<string, mixed>|null $config
     */
    private function dispatch(string $path, ?array $config = null): MvcEvent
    {
        $request = new Request();
        $request->setUri("https://shop.example.org{$path}");

        $router     = $this->createRouter();
        $routeMatch = $router->match($request);
        static::assertInstanceOf(RouteMatch::class, $routeMatch);

        $controller = (new SitemapControllerFactory())($this->createServices($config));
        $event      = new MvcEvent();
        $event->setRouter($router)->setRouteMatch($routeMatch);
        $controller->setEvent($event);
        $controller->dispatch($request, new Response());

        return $event;
    }
}

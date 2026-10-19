<?php

declare(strict_types=1);

namespace Contenir\Mvc\Sitemap;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\RuntimeException as MvcRuntimeException;
use Laminas\View\Exception\ExceptionInterface as ViewException;
use Laminas\View\Helper\Navigation\Sitemap;
use Laminas\View\Model\ViewModel;
use Override;

use function parse_url;
use function sprintf;

/**
 * Serves /sitemap.xml from the "cms" navigation container, including pages
 * hidden from the menu, and a /robots.txt that allows everything and points
 * at the sitemap. With the sitemap disabled, /sitemap.xml is not found and
 * robots.txt does not mention it.
 *
 * @api
 */
final class SitemapController extends AbstractActionController
{
    /**
     * The navigation container service the sitemap is rendered from.
     */
    public const string CONTAINER = 'cms';

    /**
     * @param string $siteUrl The site's URL, such as https://www.example.com, whose scheme, host and port
     *                        robots.txt links the sitemap from; without both a scheme and a host, the
     *                        request's are used.
     */
    public function __construct(
        private readonly Sitemap $sitemapHelper,
        private readonly bool $enabled = true,
        private readonly string $siteUrl = '',
    ) {}

    /**
     * @return Response|ViewModel The rendered sitemap XML, or laminas-mvc's not-found model when the sitemap is
     *                            disabled.
     *
     * @throws DomainException when not dispatched over HTTP.
     * @throws ViewException when the navigation container cannot be found or rendered.
     *
     * @mago-expect analysis:incompatible-return-type laminas-mvc returns any action result as is; the parent's
     *                                                ViewModel return type is docblock-only.
     */
    #[Override]
    public function indexAction(): Response|ViewModel
    {
        if (! $this->enabled) {
            return $this->notFoundAction();
        }

        $response = $this->httpResponse('application/xml; charset=utf-8');
        $response->setContent(
            $this->sitemapHelper->setContainer(self::CONTAINER)->setRenderInvisible(true)->render(),
        );

        return $response;
    }

    /**
     * @throws DomainException when not dispatched over HTTP.
     * @throws MvcRuntimeException when the "home" or "sitemap" route is missing.
     */
    public function robotsAction(): Response
    {
        $response = $this->httpResponse('text/plain; charset=utf-8');
        $request  = $this->getRequest();
        if (! $request instanceof Request) {
            throw new DomainException('The robots action needs an HTTP request');
        }

        $origin = $this->origin($request);
        $home   = $this->url()->fromRoute('home');
        $robots = <<<ROBOTS
            # robots.txt for {$origin}{$home}

            User-agent: *
            Allow: /
            ROBOTS;

        if ($this->enabled) {
            $robots .= "\n\nSitemap: {$origin}" . $this->url()->fromRoute('sitemap');
        }

        $response->setContent($robots);

        return $response;
    }

    /**
     * @throws DomainException when not dispatched over HTTP.
     */
    private function httpResponse(string $contentType): Response
    {
        $response = $this->getResponse();
        if (! $response instanceof Response) {
            throw new DomainException('The sitemap controller needs an HTTP response');
        }

        $response->getHeaders()->addHeaderLine('Content-Type', $contentType);

        return $response;
    }

    /**
     * The configured site URL's scheme, host and port, or else the request's scheme and host.
     */
    private function origin(Request $request): string
    {
        $site   = parse_url($this->siteUrl);
        $scheme = $site['scheme'] ?? null;
        $host   = $site['host'] ?? null;
        if (null !== $scheme && null !== $host) {
            $port = $site['port'] ?? null;

            return sprintf('%s://%s%s', $scheme, $host, null === $port ? '' : ":{$port}");
        }

        return sprintf('%s://%s', (string) $request->getUri()->getScheme(), (string) $request->getUri()->getHost());
    }
}

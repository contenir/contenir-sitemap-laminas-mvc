# contenir/contenir-sitemap-laminas-mvc

Formerly `contenir/contenir-mvc-sitemap`; the old package is abandoned in favour of this one.

[![Continuous Integration](https://github.com/contenir/contenir-sitemap-laminas-mvc/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-sitemap-laminas-mvc/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-sitemap-laminas-mvc/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-sitemap-laminas-mvc)

A laminas-mvc module for [Contenir CMS](https://contenir.com.au) sites. It
serves `/sitemap.xml` from the site's `cms` navigation container, and a
`/robots.txt` that points crawlers at it.

## Requirements

- PHP 8.3, 8.4 or 8.5
- laminas-mvc 3.8+, laminas-router 3.13+, laminas-view 2.35+ and
  laminas-navigation 2.19+

The 1.x releases, which support PHP 8.0 to 8.2, remain available from the
`1.x` branch and `v1.*` tags; see [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Installation

```bash
composer require contenir/contenir-sitemap-laminas-mvc
```

With the Laminas component installer, the `Contenir\Mvc\Sitemap` module
registers itself. Otherwise add it to `config/modules.config.php`.

## Usage

The module adds two routes, both served by `SitemapController`:

| Route | Path | Response |
| --- | --- | --- |
| `sitemap` | `/sitemap.xml` | `application/xml`: the `cms` navigation container, rendered by laminas-view's `navigation()->sitemap()` helper |
| `robots` | `/robots.txt` | `text/plain`, as below |

The sitemap lists every page in the `cms` container, including pages hidden
from the menu (`visible` false). Which pages are in the container at all is
up to whatever builds it: inactive, unpublished or expired pages must be left
out there.

```text
# robots.txt for https://www.example.com/

User-agent: *
Allow: /

Sitemap: https://www.example.com/sitemap.xml
```

The scheme, host and port come from the site URL setting (below), and the
paths from the `home` and `sitemap` routes. Only when no usable site URL is
set does robots.txt fall back to the request's scheme and `Host` header,
which a client can spoof: set the site URL on every live site.

With the sitemap disabled, `/sitemap.xml` answers 404 through laminas-mvc's
`notFoundAction()`, so the site's usual not-found page is shown, and
robots.txt has no `Sitemap:` line.

## Configuration

The application must provide:

- **A `cms` navigation container service** (`SitemapController::CONTAINER`),
  such as one built by laminas-navigation's abstract factory from
  `'navigation' => ['cms' => [...]]` configuration. Page URIs are made
  absolute with the `serverUrl()` helper.
- **A `home` route**, used in the robots.txt comment.
- **The navigation view helper**, which laminas-navigation's module
  registers in the `ViewHelperManager`.

Override either route in your own router configuration to move it.

### CMS settings

The module reads two Contenir CMS settings from the application's `config`
service. The CMS saves its settings under a `settings` key in the site's
`config/autoload/settings.global.php`, and its changes in
`settings.local.php`, which laminas-mvc merges into `config` along with the
rest of `config/autoload`:

| Setting | CMS field | Effect |
| --- | --- | --- |
| `settings.site.sitemap.enabled` | Enable Sitemap | Off (`'0'`, as the CMS saves a cleared box, or `false`) disables the sitemap. Unset, the sitemap is enabled, as before this setting was honoured. |
| `settings.site.url` | Site URL | The site's URL, such as `https://www.example.com`, that robots.txt links from. Only its scheme, host and port are used. |

A site without the CMS, or whose CMS settings file is not in
`config/autoload`, can set them in its own configuration:

```php
// config/autoload/sitemap.global.php
return [
    'settings' => [
        'site' => [
            'url'     => 'https://www.example.com',
            'sitemap' => ['enabled' => true],
        ],
    ],
];
```

With laminas-mvc's configuration cache on (`config_cache_enabled`), clear it
after changing either setting.

## Development

The QA toolchain is [contenir/contenir-qa-tools](https://github.com/contenir/contenir-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: module config and controller with a doubled helper, no I/O
composer test-integration  # integration suite: real router, view helpers and navigation container
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection mutation testing over both suites
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).

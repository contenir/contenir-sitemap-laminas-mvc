# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.2.0] - 2026-10-19

### Added

- The CMS's "Enable Sitemap" setting, `settings.site.sitemap.enabled`, is
  honoured. Turned off, `/sitemap.xml` answers 404 and robots.txt has no
  `Sitemap:` line. Unset, the sitemap stays enabled.

### Changed

- robots.txt builds its links from the CMS's site URL setting,
  `settings.site.url`, instead of the request's `Host` header, which a client
  can spoof. Without a usable site URL it falls back to the request, as
  before.

### Fixed

- Pages hidden from the menu (`visible` false) are listed in the sitemap.
  laminas-view's Sitemap helper skipped them.
- robots.txt no longer disallows `/.well-known/`, which kept crawlers from
  paths such as `/.well-known/security.txt`.

## [2.1.0] - 2026-10-05

### Changed

- Renamed from `contenir/contenir-mvc-sitemap` to
  `contenir/contenir-sitemap-laminas-mvc`. The package declares `replace` for
  the old name; require `contenir/contenir-sitemap-laminas-mvc` instead. See
  [UPGRADE-2.0.md](UPGRADE-2.0.md).

### Added

- Infection mutation testing in CI, MSI 100%.

## [2.0.0] - 2026-10-05

The routes, the controller and their output are unchanged. The major
version marks the move to PHP 8.3+ and the php-db QA toolchain shared by
all Contenir 2.x packages. See [UPGRADE-2.0.md](UPGRADE-2.0.md).

### Changed

- Requires PHP 8.3, 8.4 or 8.5. PHP 8.0 to 8.2 are no longer supported.
- `SitemapControllerFactory` takes a PSR-11 container instead of the
  deprecated container-interop interface, which laminas-servicemanager 4
  drops, and no longer implements laminas-servicemanager's
  `FactoryInterface`. `Module`, the factory and `SitemapController` are
  `final`.
- The controller throws a `DomainException` when it is dispatched without an
  HTTP request or response.
- The laminas packages the code uses (laminas-http, laminas-router,
  laminas-view, laminas-navigation, laminas-servicemanager) and
  `psr/container` are now required. Before, only laminas-mvc was declared;
  it now needs 3.8, the first release free of PHP 8.4 deprecations.

### Added

- `LICENSE.md` with the BSD-3-Clause text `composer.json` already declared.

- `SitemapController::CONTAINER`, the navigation container's service name
  (`cms`).
- Continuous integration on PHP 8.3, 8.4 and 8.5 against lowest, locked and
  latest dependencies, with coverage reported to Codecov.
- Unit and integration test suites, with 100% line and branch coverage.

### Fixed

- A `ViewHelperManager` or Sitemap helper of the wrong type raises a
  `ServiceNotCreatedException` that names it, instead of a `TypeError`.

### Removed

- `laminas/laminas-coding-standard` and `phpcs.xml`, replaced by Mago via
  `php-db/phpdb-qa-tools`.

## [1.0.6] - 2023-12-15

- Drop PHP 7.x support.

## [1.0.0] to [1.0.5] - 2021-12-05

- Initial release: sitemap and robots.txt routes, controller and factory,
  then autoload and component installer fixes.

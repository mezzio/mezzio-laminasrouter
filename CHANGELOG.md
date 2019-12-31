# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.0.0alpha1 - 2018-02-06

### Added

- [zendframework/zend-expressive-zendrouter#30](https://github.com/zendframework/zend-expressive-zendrouter/pull/30) and
  [zendframework/zend-expressive-zendrouter#35](https://github.com/zendframework/zend-expressive-zendrouter/pull/35) add
  support for the mezzio-router 3.0 series.

- [zendframework/zend-expressive-zendrouter#34](https://github.com/zendframework/zend-expressive-zendrouter/pull/34)
  adds `Mezzio\Router\LaminasRouter\ConfigProvider` and exposes it as a
  config provider within the package definition.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-zendrouter#30](https://github.com/zendframework/zend-expressive-zendrouter/pull/30)
  removes support for the mezzio-router 2.0 series.

- [zendframework/zend-expressive-zendrouter#30](https://github.com/zendframework/zend-expressive-zendrouter/pull/30)
  removes support for PHP 5.6 and PHP 7.0.

### Fixed

- Nothing.

## 2.2.0 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.1 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.0 - 2017-12-06

### Added

- [zendframework/zend-expressive-zendrouter#26](https://github.com/zendframework/zend-expressive-zendrouter/pull/26)
  adds support for PHP 7.2.

- [zendframework/zend-expressive-zendrouter#27](https://github.com/zendframework/zend-expressive-zendrouter/pull/27)
  adds support for the laminas-psr7bridge 1.0 series of releases.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-zendrouter#26](https://github.com/zendframework/zend-expressive-zendrouter/pull/26)
  removes support for HHVM.

### Fixed

- Nothing.

## 2.0.1 - 2017-03-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendrouter#20](https://github.com/zendframework/zend-expressive-zendrouter/pull/20)
  fixes an import statement in `LaminasRouter` to ensure the correct exception
  namespace is used.

## 2.0.0 - 2017-01-11

### Added

- [zendframework/zend-expressive-zendrouter#16](https://github.com/zendframework/zend-expressive-zendrouter/pull/16)
  adds support for mezzio-router 2.0. This includes a breaking change
  to those _extending_ `Mezzio\Router\LaminasRouter`, as the
  `generateUri()` method now expects a third, optional argument,
  `array $options = []`.

  For consumers, this represents new functionality; you may now pass router
  options, such as a translator and/or translation text domain, via the new
  argument when generating a URI.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.0 - 2016-12-14

### Added

- Nothing.

### Changed

- [zendframework/zend-expressive-zendrouter#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the mezzio-router dependency to 1.3.2+

- [zendframework/zend-expressive-zendrouter#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the router to compose the `Mezzio\Router\Route` instance
  associated with a successful route match in the returned `RouteResult`. This
  allows you to access other route metadata like the path, allowed HTTP methods,
  and route options.

- [zendframework/zend-expressive-zendrouter#12](https://github.com/zendframework/zend-expressive-zendrouter/pull/12)
  updates the router to always support `HEAD` and `OPTIONS` requests made to any
  valid route. Dispatchers will need to check if such requests are supported
  explicitly or implicitly by the matched route (using `Route::implicitHead()`
  and `Route::implicitOptions()`).

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2016-08-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- This release removes support for PHP 5.5.

### Fixed

- [zendframework/zend-expressive-zendrouter#7](https://github.com/zendframework/zend-expressive-zendrouter/pull/7)
  updates the laminas-router dependency to `^3.0`; this also required changing
  which routes and routers are imported internally to use the new namespace
  introduced in that version. The changes should have no effect on existing
  code, except that they will result in dependency updates.

## 1.1.0 - 2016-03-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendrouter#6](https://github.com/zendframework/zend-expressive-zendrouter/pull/6)
  updates the component to depend on laminas-router instead of laminas-mvc.

## 1.0.1 - 2016-01-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive-zendrouter#3](https://github.com/zendframework/zend-expressive-zendrouter/pull/3) fixes
  an issue whereby appending a trailing slash to a route that did not define one
  was resulting in a 405 instead of a 404 error.

## 1.0.0 - 2015-12-07

First stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.3.0 - 2015-12-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to use [mezzio/mezzio-router](https://github.com/mezzio/mezzio-router)
  instead of mezzio/mezzio.

## 0.2.0 - 2015-10-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to mezzio RC1.
- Added branch alias for dev-master, pointing to 1.0-dev.

## 0.1.0 - 2015-10-10

Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

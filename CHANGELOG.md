# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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

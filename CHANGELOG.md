# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to **Semantic Versioning (SemVer)**.

---

## [Unreleased]
 
### Added
### Changed
### Fixed
### Removed
 
---
 
## [1.1.1] – 2026-06-20
 
### Fixed
 
* `Folder::parent()` and `Folder::children()` now actually use the model
  returned by `static::model()` instead of hard-coding `self::class`. Any
  application extending `Folder` (via `docstore.models.folder`) previously
  got base-package `Folder` instances back from these two relations instead
  of its own subclass, silently losing any extra casts, accessors or
  relations defined there.
---


## [1.1.0] – 2026-06-19

### Added

* Official support for Laravel 12.
* MIT license file.
* Comprehensive project README.
* PHPStan static analysis configuration and fixes.
* Improved model PHPDoc annotations for better IDE and static analysis support.
* Strongly typed Eloquent relationships using Larastan generics.
* Improved command method signatures and return types.
* Package quality assurance workflow documentation.

### Changed

* Updated Composer constraints to support Laravel 10, 11 and 12.
* Updated development dependencies for Laravel 12 compatibility.
* Improved type safety across models, commands and services.
* Simplified document download path handling.
* Refined package structure and documentation.
* Improved test environment configuration.

### Fixed

* Fixed PHPStan errors related to Eloquent relations and model properties.
* Fixed test suite bootstrapping with Pest and Orchestra Testbench.
* Fixed migration loading during package tests.
* Fixed download controller test failures.
* Fixed visibility resolver contract implementation.
* Fixed Laravel 12 compatibility issues.

### Quality

* PHPStan analysis passing without errors.
* Pest test suite fully passing.
* Improved maintainability and long-term compatibility.

---

## [1.0.0] – 2025-11-20

### Added

* Initial stable release.
* Complete Document and Folder models.
* Download controller with customizable visibility resolver.
* Publishable migrations.
* Configurable models and storage.
* Pest test suite.
* Laravel 10 and Laravel 11 support.
* Orchestra Testbench integration.
* Extensible visibility resolver system.
* Package service provider and configuration publishing.

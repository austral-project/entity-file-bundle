# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

### Added

- Unit tests for `EntityFileTrait` and `EntityFileCropperTrait`.
- `composer-require-checker.json`: symbols of the optional bundles used by `src/Bridge/` are whitelisted.

### Changed

- Sources moved to `src/`, tests to `tests/` (PSR-4 `Austral\EntityFileBundle\` → `src/`).
- **BREAKING** Doctrine annotations replaced by PHP attributes: `Austral\EntityFileBundle\Annotation\*` → `Austral\EntityFileBundle\Attribute\*` (`Cropper`, `Croppers`, `ImageSize`, `Path`, `UploadParameters`), and `EntityFileAnnotationInterface` → `Attribute\EntityFileAttributeInterface`.
- **BREAKING** `UploadParameters::$virtualnameField` renamed to `$virtualNameField`.
- **BREAKING** The integrations with optional bundles moved to `src/Bridge/<Bundle>/`:
  - `Listener\UploadFormListener` → `Bridge\Form\Listener\UploadFormListener`;
  - `Listener\ConfigurationCheckerListener` → `Bridge\Admin\Listener\ConfigurationCheckerListener`.

  The service ids do not change (`austral.entity_file.form.listener`, `austral.entity_file.configuration_checker.listener`).
- The bridge services (`Resources/config/bridge/form.yaml` and `bridge/admin.yaml`) are loaded when `AustralFormBundle` / `AustralAdminBundle` is registered in the kernel (`kernel.bundles`), instead of reading `config/bundles.php`.
- `Cropper::$picto` is optional (`?string`).
- The dependencies used by the code are declared in `composer.json` (contracts, Doctrine, Symfony components, Twig), and `ext-fileinfo` is required. `ext-exif` and the optional Austral bundles are listed in `suggest`.

### Fixed

### Removed

- **BREAKING** `Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterFileCropperTrait` no longer exists in entity-translate-bundle: it is replaced by `Austral\EntityFileBundle\Bridge\EntityTranslate\Entity\Traits\EntityFileCropperTranslateMasterTrait`.

## [3.1.0] - 2022-09-17

- Changed the Entities Interfaces used
- Fix to upload files if multi objects
- Picture loses rotate information when compress webp format after uploads

## [3.0.0] - 2021-02-10

- Created new repository and new branch in to Github
- Changed the project License to the MIT
- Writing and publish documentation
- Add to Packagist Service for installation with composer

## 2.x and 1.x

These versions were not open source and have no public documentation.

[Unreleased]: https://github.com/austral-project/entity-file-bundle/compare/v3.1.2...4.x
[3.1.0]: https://github.com/austral-project/entity-file-bundle/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/austral-project/entity-file-bundle/releases/tag/v3.0.0

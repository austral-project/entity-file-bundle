# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

### Added

- Unit tests for `EntityFileTrait`, `EntityFileCropperTrait` and the cropper check of `EntityMappingListener`.
- The entity mapping throws a `\LogicException` when an entity uses `EntityFileCropperTrait` or `EntityFileCropperTranslateMasterTrait` without implementing `CropperInterface`.
- `composer-require-checker.json`: symbols of the optional bundles used by `src/Bridge/` are whitelisted.
- `tests/DependencyInjection/ContainerTest.php`: compiles the container with and without the bridges, creates every service and checks the public aliases, the command, the controller and the Twig names.
- Unit tests for the path parameters of `EntityMappingListener`.
- `Bridge\Form\Extension\UploadFileTypeExtension`: describes the uploaded file (real name, paths, MIME type, size, image dimensions) in `form.vars.austral.widget.file` of an `UploadField`, for each form view.
- Tests of the bridge with form-bundle (`tests/Bridge/Form`) and of `FileUploader::validateRequiredFiles()`. `src/Bridge/Form` is analysed by PHPStan again.

### Changed

- Sources moved to `src/`, tests to `tests/` (PSR-4 `Austral\EntityFileBundle\` → `src/`).
- **BREAKING** Doctrine annotations replaced by PHP attributes: `Austral\EntityFileBundle\Annotation\*` → `Austral\EntityFileBundle\Attribute\*` (`Cropper`, `Croppers`, `ImageSize`, `Path`, `UploadParameters`), and `EntityFileAnnotationInterface` → `Attribute\EntityFileAttributeInterface`.
- **BREAKING** `UploadParameters::$virtualnameField` renamed to `$virtualNameField`.
- **BREAKING** The integrations with optional bundles moved to `src/Bridge/<Bundle>/`:
  - `Listener\UploadFormListener` → `Bridge\Form\Listener\UploadFormListener`;
  - `Listener\ConfigurationCheckerListener` → `Bridge\Admin\Listener\ConfigurationCheckerListener`.

  Their service ids are now `Austral\EntityFileBundle\Bridge\Form\Listener\UploadFormListener` and `Austral\EntityFileBundle\Bridge\Admin\Listener\ConfigurationCheckerListener` (see the services entry below).
- **BREAKING** `Bridge\Form\Listener\UploadFormListener` is rewritten for form-bundle 4 and is final. It listens to `austral.form.configure_after` (`configure()`, was `formAddAutoFields()` on `austral.event.form.add_auto_fields_after`), `austral.form.field.build` (`buildField()`, was `fieldConfiguration()` on `austral.event.form.field.configuration`), `austral.form.submit` (`validate()`) and `austral.form.save_before` (`uploads()`). Its constructor only receives `FileUploader` and `Mapping`. Each entry of a collection is handled with its own form and object: the listener no longer walks the collections.
- The upload fields are configured from the file mapping when the mapper is configured (`FormMapperFactory`), with the hidden fields (`<field>DeleteFile`, `cropperData`, `generateCropperByKey`) and the editor modal. The `File` and `Image` constraints are computed by `UploadField` from its options; the bridge adds `Length` and `SvgSecurity` and removes the `NotNull` / `NotBlank` of the field (the required file is checked on submit).
- The bridge services (`Resources/config/bridge/form.yaml` and `bridge/admin.yaml`) are loaded when `AustralFormBundle` / `AustralAdminBundle` is registered in the kernel (`kernel.bundles`), instead of reading `config/bundles.php`.
- `Cropper::$picto` is optional (`?string`).
- **BREAKING** Services are defined explicitly (no autowiring, no autoconfiguration). Service ids named after a class (`Austral\EntityFileBundle\…`) are private: use the public `austral.entity_file.*` aliases to get a service from the container. Autowiring a class of the bundle in an application still works.
- **BREAKING** The aliases of the listeners are private (`austral.entity_file.bridge.form.upload.listener`, `austral.entity_file.bridge.admin.configuration_checker.listener`), and the 3.1 aliases `austral.entity_file.doctrine.listener`, `austral.entity_file.entity_mapping.listener`, `austral.entity_file.entity_manager.listener`, `austral.entity_file.form.listener` and `austral.entity_file.configuration_checker.listener` are removed: listeners are called by the dispatchers, not fetched from the container.
- **BREAKING** `FileController` receives `ImageRender` and `FileDownloader` through its constructor: `setContainer()` and the `$container` property are removed. It is declared as a service with the `controller.service_arguments` tag, instead of being loaded from the `Controller/` folder.
- **BREAKING** `EntityMappingListener` receives the parameter bag (`ParameterBagInterface $parameterBag`) instead of the service container, to replace the `%parameter%` placeholders of the upload paths.
- **BREAKING** `DoctrineListener` is a Doctrine event listener (tag `doctrine.event_listener` on `preRemove`) and no longer implements `Doctrine\Common\EventSubscriber`. `preRemove()` receives a `Doctrine\ORM\Event\PreRemoveEventArgs` (it received `Doctrine\ORM\Event\LifecycleEventArgs`, removed in Doctrine ORM 3). The listener is registered on every connection (in 3.1, only on the `default` connection).
- **BREAKING** `CompressCommand` receives `Compression` and `Mapping` through its constructor, and the event dispatcher through `setEventDispatcher()`.
- **BREAKING** `MediaTwig` extends `AbstractExtension` and its service id is its class name (`twig.extension.media` is a private alias). The Twig names do not change.
- The setters of `EntityFileCropperTranslateMasterTrait` throw a `\LogicException` when the translation class does not implement `CropperInterface`.
- **BREAKING** An entity with croppers is detected by `Austral\Contracts\File\CropperInterface` instead of the use of `EntityFileCropperTrait` (or `EntityTranslateMasterFileCropperTrait`): an entity that uses the trait must also implement the interface.
- The dependencies used by the code are declared in `composer.json` (contracts, Doctrine, Symfony components, Twig), and `ext-fileinfo` is required. `ext-exif` and the optional Austral bundles are listed in `suggest`.

### Fixed

- The getters of `EntityFileCropperTranslateMasterTrait` no longer fail when the entity has no current translation: they return an empty array, and reading never creates a translation.
- `Compression` no longer builds the compression command by concatenation: the paths and the options are escaped by Symfony Process.
- `FileUploader::validateRequiredFiles()` no longer throws when a required file field is not in the form (a field that is not visible for the user).
- In a collection, each entry describes its own uploaded file (in 3.x, the information came from the object of the mapper).
- An image size limit given for one dimension only no longer adds a limit of `0` to the other ones.
- The configuration is merged with `ConfigurationMerger` instead of `array_replace_recursive()` and `array_merge_recursive()`: a list (`mimeTypes`, `croppers`) given by the application replaces the default list (in 3.x, it only replaced its first items: `[image/webp]` gave `[image/webp, image/jpeg, image/png…]`), and two bundles that declare the same upload no longer turn its values into lists.
- The configuration checker of the admin showed the rules of `default_image` for `default_file`.

### Removed

- The skeleton `Resources/config/squelette/austral_entity_file.yaml`: it no longer matched the configuration (`uploads.default.image` and `cropper` are refused by Symfony). The [Austral recipe](https://github.com/austral-project/recipes) creates `config/packages/austral_entity_file.yaml` and `config/routes/austral_entity_file.yaml`.

- **BREAKING** `Austral\EntityFileBundle\Entity\Interfaces\FileInterface` and `CropperInterface`: use `Austral\Contracts\File\FileInterface` and `Austral\Contracts\File\CropperInterface` (austral/contracts), without alias.

- **BREAKING** `DoctrineListener::getSubscribedEvents()`, `getEventAdapter()` and `getNamespace()`: the listener is registered by its tag.

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

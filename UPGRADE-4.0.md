# Upgrade from 3.1 to 4.0

## Requirements

- PHP 8.4 minimum
- Symfony 7.4
- Doctrine ORM 3

## Update the dependencies

```diff
- "austral/entity-file-bundle": "^3.1"
+ "austral/entity-file-bundle": "^4.0"
```

All `austral/*` packages must be upgraded to `^4.0` at the same time.

## Breaking changes

### File interfaces in `austral/contracts`

The entity interfaces are in `austral/contracts`, without alias:

| 3.1 | 4.0 |
|-----|-----|
| `Austral\EntityBundle\Entity\Interfaces\FileInterface` | `Austral\Contracts\File\FileInterface` |
| — (the use of `EntityFileCropperTrait` was enough) | `Austral\Contracts\File\CropperInterface` |

**An entity with croppers must now implement `CropperInterface`.** In 3.1, the croppers of an entity were detected by the use of `EntityFileCropperTrait` (or `EntityTranslateMasterFileCropperTrait`). In 4.0 they are detected by the interface. To avoid losing the cropping data without notice, the entity mapping throws a `\LogicException` when an entity uses a cropper trait without implementing the interface.

```diff
- use Austral\EntityBundle\Entity\Interfaces\FileInterface;
+ use Austral\Contracts\File\CropperInterface;
+ use Austral\Contracts\File\FileInterface;
  use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;

- class Page extends Entity implements FileInterface
+ class Page extends Entity implements FileInterface, CropperInterface
  {
    use EntityFileCropperTrait;
```

### Annotations replaced by attributes

The Doctrine annotations are replaced by PHP attributes, in the `Austral\EntityFileBundle\Attribute` namespace.

| 3.1 | 4.0 |
|-----|-----|
| `Austral\EntityFileBundle\Annotation\Cropper` | `Austral\EntityFileBundle\Attribute\Cropper` |
| `Austral\EntityFileBundle\Annotation\Croppers` | `Austral\EntityFileBundle\Attribute\Croppers` |
| `Austral\EntityFileBundle\Annotation\ImageSize` | `Austral\EntityFileBundle\Attribute\ImageSize` |
| `Austral\EntityFileBundle\Annotation\Path` | `Austral\EntityFileBundle\Attribute\Path` |
| `Austral\EntityFileBundle\Annotation\UploadParameters` | `Austral\EntityFileBundle\Attribute\UploadParameters` |
| `Austral\EntityFileBundle\Annotation\EntityFileAnnotationInterface` | `Austral\EntityFileBundle\Attribute\EntityFileAttributeInterface` |

The `virtualnameField` option of `UploadParameters` is renamed `virtualNameField`.

Before:

```php
use Austral\EntityFileBundle\Annotation as AustralFile;

/**
 * @AustralFile\UploadParameters(configName="page_image", virtualnameField="imageReelname")
 * @AustralFile\ImageSize()
 * @AustralFile\Croppers({"desktop", "tablet", "mobile"})
 */
protected ?string $image = null;
```

After:

```php
use Austral\EntityFileBundle\Attribute as AustralFile;

#[AustralFile\UploadParameters(configName: 'page_image', virtualNameField: 'imageReelname')]
#[AustralFile\ImageSize()]
#[AustralFile\Croppers(croppers: ['desktop', 'tablet', 'mobile'])]
protected ?string $image = null;
```

`Croppers` accepts the names of the croppers defined in the configuration, as in 3.1, or `Cropper` objects: `new AustralFile\Cropper(name: 'favicon', picto: 'austral-picto-globe', ratio: '1/1')`.

### Listeners moved to `src/Bridge/`

The integrations with optional bundles are grouped by target bundle in `src/Bridge/<Bundle>/`, and are only loaded when that bundle is registered in the kernel.

| 3.1 | 4.0 |
|-----|-----|
| `Austral\EntityFileBundle\Listener\UploadFormListener` | `Austral\EntityFileBundle\Bridge\Form\Listener\UploadFormListener` |
| `Austral\EntityFileBundle\Listener\ConfigurationCheckerListener` | `Austral\EntityFileBundle\Bridge\Admin\Listener\ConfigurationCheckerListener` |

Their service ids change too (see "Services: explicit definitions, private class ids"). Only a class that extends one of these listeners, references it by its class name, or decorates its 3.1 service id, must be updated.

### Services: explicit definitions, private class ids

The services of the bundle are no longer autowired nor autoconfigured: each one is defined with its arguments and tags. As a result, the service ids named after a class are **private**. The `austral.entity_file.*` aliases below are public, and they are the supported way to get a service from the container.

| Class | Public alias |
|-------|--------------|
| `Configuration\UploadsConfiguration` | `austral.entity_file.uploads.config` |
| `Configuration\CropperConfiguration` | `austral.entity_file.cropper.config` |
| `Configuration\ImageSizeConfiguration` | `austral.entity_file.image_size.config` |
| `Configuration\CompressionConfiguration` | `austral.entity_file.compression.config` |
| `File\Upload\FileUploader` | `austral.entity_file.uploader` |
| `File\Download\FileDownloader` | `austral.entity_file.downloader` |
| `File\Image\Image` | `austral.entity_file.image` |
| `File\Image\ImageRender` | `austral.entity_file.image.render` |
| `File\Link\Generator` | `austral.entity_file.link.generator` |
| `File\Cropper\Cropper` | `austral.entity_file.cropper` |
| `File\Compression\Compression` | `austral.entity_file.compression` |

Before:

```php
$uploader = $container->get(\Austral\EntityFileBundle\File\Upload\FileUploader::class);
```

After:

```php
$uploader = $container->get('austral.entity_file.uploader');
```

Injecting a service is not affected: autowiring `Austral\EntityFileBundle\File\Upload\FileUploader` in a constructor of your application keeps working, and so does a reference to an alias in YAML (`'@austral.entity_file.uploader'`).

**Listeners.** They are called by the dispatchers and are not meant to be fetched, so their aliases are private, and the 3.1 aliases are removed:

| 3.1 alias (removed) | 4.0 service id |
|---------------------|----------------|
| `austral.entity_file.doctrine.listener` | `Austral\EntityFileBundle\Listener\DoctrineListener` |
| `austral.entity_file.entity_mapping.listener` | `Austral\EntityFileBundle\Listener\EntityMappingListener` |
| `austral.entity_file.entity_manager.listener` | `Austral\EntityFileBundle\Listener\EntityManagerListener` |
| `austral.entity_file.form.listener` | `Austral\EntityFileBundle\Bridge\Form\Listener\UploadFormListener` |
| `austral.entity_file.configuration_checker.listener` | `Austral\EntityFileBundle\Bridge\Admin\Listener\ConfigurationCheckerListener` |

> **Warning:** a service of your application that decorates one of these listeners by its 3.1 alias (`decorates: austral.entity_file.form.listener`) makes the container compilation fail. Decorate the 4.0 service id instead.

The Twig extension `MediaTwig` is registered under its class name; `twig.extension.media` is a private alias. The names used in templates do not change.

### No more service container in the controller, the command and the mapping listener

`FileController`, `CompressCommand` and `EntityMappingListener` no longer receive the service container:

| Class | 3.1 | 4.0 |
|-------|-----|-----|
| `Controller\FileController` | `setContainer()`, then `$this->container->get('austral.entity_file.image.render')` / `->get('austral.entity_file.downloader')` | constructor: `ImageRender $imageRender`, `FileDownloader $fileDownloader` |
| `Command\CompressCommand` | `$this->container->get(…)` | constructor: `Compression $compression`, `Mapping $mapping`; event dispatcher through `setEventDispatcher()` |
| `Listener\EntityMappingListener` | constructor: `ContainerInterface $container` | constructor: `ParameterBagInterface $parameterBag` |

`FileController` is declared as a service with the `controller.service_arguments` tag; the routes of the bundle (`austral_entity_file_thumbnail`, `austral_entity_file_download`) do not change.

A class of yours that extends one of these classes must call the new constructor, and replace `$this->container` by the injected services.

### Doctrine listener

`DoctrineListener` no longer implements `Doctrine\Common\EventSubscriber`: it is registered with a `doctrine.event_listener` tag on `preRemove`, on every connection (in 3.1, only on the `default` connection). `preRemove()` receives a `Doctrine\ORM\Event\PreRemoveEventArgs` instead of `Doctrine\ORM\Event\LifecycleEventArgs`, and `getSubscribedEvents()`, `getEventAdapter()` and `getNamespace()` are removed. A class of yours that extends the listener must update the signature of `preRemove()`.

### Cropper trait for translated entities

`EntityTranslateMasterFileCropperTrait` of entity-translate-bundle is replaced by a trait of entity-file-bundle:

```diff
- use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterFileCropperTrait;
+ use Austral\EntityFileBundle\Bridge\EntityTranslate\Entity\Traits\EntityFileCropperTranslateMasterTrait;
```

Rename the `use` inside the class as well.

Reading a cropper value (`getCropperData()`, `getCropperDataByFilename()`, `getGenerateCropperByKey()`) never creates a translation: without a current translation, it returns an empty array. Writing a value (`setCropperData()`, `setGenerateCropperByKey()`) creates the current translation when needed, and throws a `\LogicException` when the translation class does not implement `CropperInterface`.

### Updating a project

```bash
grep -rl 'EntityFileBundle\\Annotation' src/ | xargs perl -pi -e 's/EntityFileBundle\\Annotation\\EntityFileAnnotationInterface/EntityFileBundle\\Attribute\\EntityFileAttributeInterface/g; s/EntityFileBundle\\Annotation/EntityFileBundle\\Attribute/g'
grep -rl 'EntityTranslateMasterFileCropperTrait' src/ | xargs perl -pi -e 's/Austral\\EntityTranslateBundle\\Entity\\Traits\\EntityTranslateMasterFileCropperTrait/Austral\\EntityFileBundle\\Bridge\\EntityTranslate\\Entity\\Traits\\EntityFileCropperTranslateMasterTrait/g; s/\bEntityTranslateMasterFileCropperTrait\b/EntityFileCropperTranslateMasterTrait/g'
grep -rl 'Interfaces\\FileInterface\|Interfaces\\CropperInterface' src/ | xargs perl -pi -e 's/Austral\\(EntityBundle|EntityFileBundle)\\Entity\\Interfaces\\(File|Cropper)Interface/Austral\\Contracts\\File\\$2Interface/g'
```

Then add `CropperInterface` to the entities that use `EntityFileCropperTrait` or `EntityFileCropperTranslateMasterTrait`: this step cannot be done by a command.

The annotations themselves (`/** @AustralFile\... */`) must be rewritten as attributes by hand, or with Rector.


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

The service ids do not change. Only a class that extends one of these listeners, or references it by its class name, must be updated.

### Cropper trait for translated entities

`EntityTranslateMasterFileCropperTrait` of entity-translate-bundle is replaced by a trait of entity-file-bundle:

```diff
- use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterFileCropperTrait;
+ use Austral\EntityFileBundle\Bridge\EntityTranslate\Entity\Traits\EntityFileCropperTranslateMasterTrait;
```

Rename the `use` inside the class as well.

### Updating a project

```bash
grep -rl 'EntityFileBundle\\Annotation' src/ | xargs perl -pi -e 's/EntityFileBundle\\Annotation\\EntityFileAnnotationInterface/EntityFileBundle\\Attribute\\EntityFileAttributeInterface/g; s/EntityFileBundle\\Annotation/EntityFileBundle\\Attribute/g'
grep -rl 'EntityTranslateMasterFileCropperTrait' src/ | xargs perl -pi -e 's/Austral\\EntityTranslateBundle\\Entity\\Traits\\EntityTranslateMasterFileCropperTrait/Austral\\EntityFileBundle\\Bridge\\EntityTranslate\\Entity\\Traits\\EntityFileCropperTranslateMasterTrait/g; s/\bEntityTranslateMasterFileCropperTrait\b/EntityFileCropperTranslateMasterTrait/g'
```

The annotations themselves (`/** @AustralFile\... */`) must be rewritten as attributes by hand, or with Rector.


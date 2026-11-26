# Austral Entity File

[![Version](https://img.shields.io/packagist/v/austral/entity-file-bundle?label=version)](https://packagist.org/packages/austral/entity-file-bundle)
[![CI](https://github.com/austral-project/entity-file-bundle/actions/workflows/ci.yml/badge.svg?branch=4.x)](https://github.com/austral-project/entity-file-bundle/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/austral/entity-file-bundle/php?version=4.x-dev)](https://packagist.org/packages/austral/entity-file-bundle)
[![Symfony](https://img.shields.io/badge/symfony-7.4-black)](https://symfony.com/releases/7.4)
[![License](https://img.shields.io/github/license/austral-project/entity-file-bundle)](LICENSE)

File uploads, thumbnails and download for entity to Symfony. Compress file with squoosh-cli

> This bundle is part of [Austral](https://github.com/austral-project), a modular back office and CMS for Symfony.

## Requirements

- PHP 8.4 or higher
- Symfony 7.4

## Installation

```bash
composer require austral/entity-file-bundle
```

If you do not use Symfony Flex, register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Austral\EntityFileBundle\AustralEntityFileBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/austral_entity_file.yaml
austral_entity_file:
    compression:
        command: ''                 # command that compresses the uploaded images; none when not set
        options:
            webp:       { quality: 70 }
    cropper:                        # merged with the default croppers: desktop, tablet, mobile
        desktop:    { name: desktop, picto: austral-picto-monitor, ratio: '16/9' }
    image_size:                     # merged with the default sizes (default: 100 to 10000 pixels)
        default:
            width:  { min: 100, max: 10000 }
            height: { min: 100, max: 10000 }
    uploads:                        # merged with the default rules: default_image, default_file (2M)
        default_image:
            size:       { max: '5M' }
            mimeTypes:  [ 'image/jpeg', 'image/png', 'image/webp' ]
```

The configuration of the application is merged with the default values, then with the values declared by the
bundles (the avatar of security-bundle, for example): a list (`mimeTypes`, `croppers`) replaces the list it
overrides, and a value that is not set keeps the default one.

```yaml
# config/routes/austral_entity_file.yaml: thumbnails and downloads
austral_entity_file:
    resource: '@AustralEntityFileBundle/Resources/config/routes.yaml'
```

With the [Austral recipes](https://github.com/austral-project/recipes), Flex creates both files.

## Usage

<!-- Minimal, concrete example. -->

## Administration

When [austral/admin-bundle](https://github.com/austral-project/admin-bundle) 4 is installed, the configuration page of the admin
(`/austral-admin/status-configuration`) shows the category "Uploads": the rules `default_image` and `default_file`, then each
file field of the entities, with its maximum size, accepted MIME types, size of the images and upload folder. A rule without
maximum size or that accepts every MIME type is a warning: set `size.max` and `mimeTypes` in `austral_entity_file.uploads`.

## Documentation

The full documentation is available at https://austral.dev.

## Versions

| Bundle | Symfony | PHP | Branch |
|--------|---------|-----|---------|
| 4.x    | 7.4     | ≥ 8.4 | `4.x` |
| 3.1    | 5.4 / 6.4 | ≥ 8.0 | `3.1` (maintenance) |

See the [CHANGELOG](CHANGELOG.md) and, to upgrade from 3.1, [UPGRADE-4.0](UPGRADE-4.0.md).

## Contributing

Contributions are welcome: see the [contributing guide](https://github.com/austral-project/.github/blob/master/CONTRIBUTING.md). To report a security issue, follow the [security policy](https://github.com/austral-project/.github/blob/master/SECURITY.md).

## License

MIT, see [LICENSE](LICENSE).

## Credits

Created by [Matthieu Beurel](https://www.mbeurel.com). Sponsored by [Yipikai Studio](https://yipikai.studio).

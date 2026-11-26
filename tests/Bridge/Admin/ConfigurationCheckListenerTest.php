<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\EntityFileBundle\Tests\Bridge\Admin;

use Austral\AdminBundle\Configuration\ConfigurationCheck;
use Austral\AdminBundle\Event\ConfigurationCheckEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Attribute\ImageSize;
use Austral\EntityFileBundle\Attribute\Path;
use Austral\EntityFileBundle\Attribute\UploadParameters;
use Austral\EntityFileBundle\Bridge\Admin\Listener\ConfigurationCheckListener;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\DependencyInjection\Configuration;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\Tests\Fixtures\FileEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Category "uploads" of the configuration page of admin-bundle.
 */
final class ConfigurationCheckListenerTest extends TestCase
{
  public function testTheDefaultRulesAndTheFieldsOfTheEntities(): void
  {
    $checks = $this->checks([
      'image' => new FieldFileMapping(
        new UploadParameters('default_image', false, ['image/png', 'image/jpeg'], '5M'),
        new Path('/var/www/public/uploads/file_entity'),
        new ImageSize(null, 800, 2000, 600),
      ),
      'document' => new FieldFileMapping(new UploadParameters('default_file', false, [], '2M'), new Path('/var/www/public/uploads/file_entity')),
    ]);

    self::assertSame(['uploads.default_image', 'uploads.default_file', 'uploads.file_entity.image', 'uploads.file_entity.document'], array_keys($checks));

    $image = $checks['uploads.file_entity.image'];
    self::assertSame(ConfigurationCheck::STATUS_SUCCESS, $image->status);
    self::assertSame('FileEntity · image', $image->label);
    self::assertSame('configuration.check.uploads.rule', $image->description);
    self::assertSame('5M · image/png, image/jpeg · ≥ 800 x 600 px · ≤ 2000 x ... px · /var/www/public/uploads/file_entity', $image->value);
  }

  public function testARuleThatAcceptsEveryTypeOrSizeIsAWarning(): void
  {
    $checks = $this->checks([
      'document' => new FieldFileMapping(new UploadParameters('default_file', false, [], '2M'), new Path('/uploads')),
      'archive' => new FieldFileMapping(new UploadParameters('default_file', false, ['application/zip']), new Path('/uploads')),
    ]);

    self::assertSame(ConfigurationCheck::STATUS_SUCCESS, $checks['uploads.default_image']->status, 'default_image: 2M and a list of types.');
    self::assertSame(ConfigurationCheck::STATUS_WARNING, $checks['uploads.default_file']->status, 'default_file accepts every type by default.');
    self::assertSame('2M · */*', $checks['uploads.default_file']->value);

    self::assertSame(ConfigurationCheck::STATUS_WARNING, $checks['uploads.file_entity.document']->status);
    self::assertSame('configuration.check.uploads.unrestricted', $checks['uploads.file_entity.document']->description);
    self::assertSame(ConfigurationCheck::STATUS_WARNING, $checks['uploads.file_entity.archive']->status);
    self::assertSame('∞ · application/zip · /uploads', $checks['uploads.file_entity.archive']->value);
  }

  /**
   * @param array<string, FieldFileMapping<object>> $fields File fields of the entity FileEntity
   *
   * @return array<string, ConfigurationCheck> By category and key
   */
  private function checks(array $fields): array
  {
    $entityMapping = self::entityMapping(FileEntity::class, 'file_entity');
    foreach ($fields as $fieldname => $fieldMapping)
    {
      $entityMapping->addFieldMapping($fieldname, $fieldMapping);
    }
    $mapping = $this->createStub(Mapping::class);
    $mapping->method('getEntitiesMapping')->willReturn([FileEntity::class => $entityMapping]);

    $configuration = new Configuration();
    $uploads = new UploadsConfiguration($configuration->getUploadsDefault(), new CropperConfiguration([]));

    $event = new ConfigurationCheckEvent(new Request());
    new ConfigurationCheckListener($mapping, $uploads)->configurationCheck($event);

    $checks = [];
    foreach ($event->getChecks() as $check)
    {
      self::assertSame(ConfigurationCheckListener::CATEGORY, $check->category);
      $checks["{$check->category}.{$check->key}"] = $check;
    }

    return $checks;
  }

  /**
   * @param class-string $entityClass
   *
   * @return EntityMapping<object>
   */
  private static function entityMapping(string $entityClass, string $slugger): EntityMapping
  {
    return new EntityMapping($entityClass, $slugger);
  }
}

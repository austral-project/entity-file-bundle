<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Bridge\Admin\Listener;

use Austral\AdminBundle\Configuration\ConfigurationCheck;
use Austral\AdminBundle\Event\ConfigurationCheckEvent;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Attribute\ImageSize;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;

/**
 * Austral Configuration Check Listener.
 *
 * Category "uploads" of the configuration page of admin-bundle: the rules of default_image and default_file, then
 * those of each file field of the entities (maximum size, accepted MIME types, size of the images, upload
 * folder). A rule without maximum size or that accepts every MIME type is a warning: any file, of any size, can be
 * sent to the public folder.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class ConfigurationCheckListener
{
  public const string CATEGORY = 'uploads';

  /**
   * Upload rules of the configuration shown before the fields (austral_entity_file.uploads).
   */
  private const array DEFAULT_RULES = ['default_image', 'default_file'];

  /**
   * @param Mapping<T>           $mapping
   * @param UploadsConfiguration $uploadsConfiguration
   */
  public function __construct(
    private readonly Mapping $mapping,
    private readonly UploadsConfiguration $uploadsConfiguration,
  ) {
  }

  /**
   * Listener of austral.admin.configuration_check.
   *
   * @param ConfigurationCheckEvent $event
   *
   * @return void
   */
  public function configurationCheck(ConfigurationCheckEvent $event): void
  {
    foreach (self::DEFAULT_RULES as $rule)
    {
      $sizeMax = $this->uploadsConfiguration->get("{$rule}.size.max");
      $mimeTypes = $this->uploadsConfiguration->get("{$rule}.mimeTypes", []);
      $event->add(self::check(
        $rule,
        "configuration.check.uploads.{$rule}.title",
        \is_scalar($sizeMax) ? (string) $sizeMax : null,
        \is_array($mimeTypes) ? $mimeTypes : [],
      ));
    }

    foreach ($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      /** @var FieldFileMapping<T> $fieldFileMapping */
      foreach ($entityMapping->getFieldsMappingByClass(FieldFileMapping::class) as $fieldFileMapping)
      {
        $fieldname = $fieldFileMapping->getFieldname();
        $event->add(self::check(
          "{$entityMapping->slugger}.{$fieldname}",
          self::shortClassname($entityMapping->entityClass)." · {$fieldname}",
          $fieldFileMapping->uploadParameters->sizeMax,
          $fieldFileMapping->uploadParameters->mimeTypes,
          $fieldFileMapping->imageSize,
          $fieldFileMapping->path->upload,
        ));
      }
    }
  }

  /**
   * @param string         $key
   * @param string         $label     Translation key, or the entity and the field (shown as it is)
   * @param string|null    $sizeMax
   * @param array<mixed>   $mimeTypes Empty: every type
   * @param ImageSize|null $imageSize
   * @param string|null    $path
   *
   * @return ConfigurationCheck
   */
  private static function check(string $key, string $label, ?string $sizeMax, array $mimeTypes, ?ImageSize $imageSize = null, ?string $path = null): ConfigurationCheck
  {
    $mimeTypes = array_values(array_filter($mimeTypes, \is_string(...)));
    $unrestricted = null === $sizeMax || '' === $sizeMax || [] === $mimeTypes;

    $value = [$sizeMax ?: '∞', $mimeTypes ? implode(', ', $mimeTypes) : '*/*'];
    if (null !== $imageSize && $imageSize->hasLimit())
    {
      $value[] = self::dimensions('≥', $imageSize->widthMin, $imageSize->heightMin);
      $value[] = self::dimensions('≤', $imageSize->widthMax, $imageSize->heightMax);
    }
    $value[] = $path;

    return new ConfigurationCheck(
      self::CATEGORY,
      $key,
      $unrestricted ? ConfigurationCheck::STATUS_WARNING : ConfigurationCheck::STATUS_SUCCESS,
      $label,
      $unrestricted ? 'configuration.check.uploads.unrestricted' : 'configuration.check.uploads.rule',
      implode(' · ', array_filter($value, static fn (?string $part): bool => null !== $part && '' !== $part)),
    );
  }

  /**
   * @param string     $sign
   * @param float|null $width
   * @param float|null $height
   *
   * @return string|null "≥ 100 x 100 px", null without limit
   */
  private static function dimensions(string $sign, ?float $width, ?float $height): ?string
  {
    if (!$width && !$height)
    {
      return null;
    }

    return "{$sign} ".($width ?: '...').' x '.($height ?: '...').' px';
  }

  /**
   * @param string $class
   *
   * @return string
   */
  private static function shortClassname(string $class): string
  {
    $position = strrpos($class, '\\');

    return false === $position ? $class : substr($class, $position + 1);
  }
}

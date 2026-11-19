<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Mapping;

use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\FieldMapping;
use Austral\EntityBundle\Mapping\FieldMappingInterface;
use Austral\EntityFileBundle\Attribute as AustralFile;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral FieldFileMapping.
 *
 * @template T of object
 *
 * @extends FieldMapping<T>
 *
 * @implements FieldMappingInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
final class FieldFileMapping extends FieldMapping implements FieldMappingInterface
{
  /**
   * @var AustralFile\UploadParameters
   */
  public AustralFile\UploadParameters $uploadParameters;

  /**
   * @var AustralFile\Path
   */
  public AustralFile\Path $path;

  /**
   * @var AustralFile\ImageSize|null
   */
  public ?AustralFile\ImageSize $imageSize = null;

  /**
   * @var AustralFile\Croppers<T>|null
   */
  public ?AustralFile\Croppers $croppers = null;

  /**
   * FieldFileMapping constructor
   *
   * @param AustralFile\UploadParameters $uploadParameters
   * @param AustralFile\Path             $path
   * @param AustralFile\ImageSize|null   $imageSize
   * @param AustralFile\Croppers<T>|null $croppers
   */
  public function __construct(
    AustralFile\UploadParameters $uploadParameters,
    AustralFile\Path $path,
    ?AustralFile\ImageSize $imageSize = null,
    ?AustralFile\Croppers $croppers = null
  ) {
    $this->uploadParameters = $uploadParameters;
    $this->path = $path;
    $this->imageSize = $imageSize;
    $this->croppers = $croppers;
  }

  /**
   * @return string
   */
  public function getSlugger(): string
  {
    return $this->entityMapping->slugger ?? '';
  }

  /**
   * @param FileInterface<T> $object
   * @param bool             $reel
   * @param string|null      $default
   *
   * @return string|null
   *
   * @throws \Exception
   */
  protected function getEntityFileValue(FileInterface $object, bool $reel = true, ?string $default = null): ?string
  {
    $valueKey = $reel ? $this->uploadParameters->keyname : ($this->uploadParameters->virtualNameField ?? $this->uploadParameters->keyname);
    $value = $valueKey ? $object->getValueByFieldname($valueKey) : null;

    return $value ?: $default;
  }

  /**
   * @param FileInterface<T> $object
   *
   * @return string|null
   *
   * @throws \Exception
   */
  public function getObjectFilePath(FileInterface $object): ?string
  {
    $filePath = null;
    if (($value = $this->getEntityFileValue($object)) && ($pathDir = $this->getFilePathDir()))
    {
      $filePath = AustralTools::join(
        $pathDir,
        $value
      );
    }

    return ($filePath && file_exists($filePath) && is_file($filePath)) ? $filePath : null;
  }

  /**
   * @return string
   */
  public function getFilePathDir(): string
  {
    return AustralTools::join(
      $this->path->upload,
      $this->uploadParameters->keyname
    );
  }

  /**
   * @param FileInterface<T> $object
   * @param bool             $reel
   *
   * @return ?string
   *
   * @throws \Exception
   */
  public function getFilename(FileInterface $object, bool $reel = false): ?string
  {
    if (!$reel)
    {
      $value = $this->getEntityFileValue($object, $reel, $this->getEntityFileValue($object));
    }
    else
    {
      $value = $this->getEntityFileValue($object);
    }
    if ($value)
    {
      preg_match("/(.*)__UNIQID__.*(\..*)/", $value, $matches);
      if (3 == count($matches))
      {
        return $matches[1].$matches[2];
      }

      return $value;
    }

    return null;
  }

  /**
   * @param FileInterface<T> $object
   *
   * @return string|null
   *
   * @throws \Exception
   */
  public function getFilenameWithoutExtension(FileInterface $object): ?string
  {
    return $this->getFilename($object) ? pathinfo($this->getFilename($object), PATHINFO_FILENAME) : null;
  }

  /**
   * @param FileInterface<T> $object
   *
   * @return string|null
   *
   * @throws \Exception
   */
  public function getFilenameExtension(FileInterface $object): ?string
  {
    return $this->getEntityFileValue($object) ? pathinfo($this->getEntityFileValue($object), PATHINFO_EXTENSION) : null;
  }

  /**
   * @param string $cropperKey
   *
   * @return AustralFile\Cropper<T>|null
   */
  public function getCropperByName(string $cropperKey): ?AustralFile\Cropper
  {
    return $this->croppers ? (array_key_exists($cropperKey, $this->croppers->croppers) ? $this->croppers->croppers[$cropperKey] : null) : null;
  }

  /**
   * @param FileInterface<T> $object
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperData(FileInterface $object): array
  {
    $value = $object->getValueByFieldname('cropperData');

    return ($value && is_array($value)) ? $value : [];
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $cropperKey
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperDataByFieldname(FileInterface $object, string $cropperKey): array
  {
    if ($this->getCropperByName($cropperKey) && $this->getFieldname())
    {
      return array_key_exists($this->getFieldname(), $this->getCropperData($object)) ? $this->getCropperData($object)[$this->getFieldname()] : [];
    }

    return [];
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $cropperKey
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperDataValue(FileInterface $object, string $cropperKey): array
  {
    if ($cropper = $this->getCropperByName($cropperKey))
    {
      return array_key_exists($cropper->key, $this->getCropperDataByFieldname($object, $cropperKey)) ? $this->getCropperDataByFieldname($object, $cropperKey)[$cropper->key] : [];
    }

    return [];
  }
}

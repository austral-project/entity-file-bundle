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

use Austral\EntityBundle\Mapping\FieldMapping;
use Austral\EntityFileBundle\Annotation as AustralFile;
use Austral\EntityFileBundle\Entity\Interfaces\EntityFileInterface;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral FieldFileMapping.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
final Class FieldFileMapping extends FieldMapping
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
   * @var AustralFile\Croppers|null
   */
  public ?AustralFile\Croppers $croppers = null;


  /**
   * Constructor.
   */
  public function __construct(
    AustralFile\UploadParameters $uploadParameters,
    AustralFile\Path $path,
    ?AustralFile\ImageSize $imageSize = null,
    ?AustralFile\Croppers $croppers = null
  )
  {
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
    return $this->entityMapping->slugger;
  }

  /**
   * @param EntityFileInterface $object
   * @param bool $reel
   * @param null $default
   *
   * @return string|null
   * @throws \Exception
   */
  protected function getEntityFileValue(EntityFileInterface $object, bool $reel = true, $default = null): ?string
  {
    $value = $object->getValueByFieldname($reel ? $this->uploadParameters->keyname : ($this->uploadParameters->virtualnameField ?? $this->uploadParameters->keyname));
    return $value ?: $default;
  }

  /**
   * @param EntityFileInterface $object
   *
   * @return string|null
   * @throws \Exception
   */
  public function getObjectFilePath(EntityFileInterface $object): ?string
  {
    $filePath = null;
    if(($value = $this->getEntityFileValue($object, $this->getFieldname())) && ($pathDir = $this->getFilePathDir()))
    {
      $filePath = AustralTools::join(
        $pathDir,
        $value
      );
    }
    return (file_exists($filePath) && is_file($filePath)) ? $filePath : false;
  }

  /**
   * @return string|null
   */
  public function getFilePathDir(): ?string
  {
    return AustralTools::join(
      $this->path->upload,
      $this->uploadParameters->keyname
    );
  }

  /**
   * @param EntityFileInterface $object
   * @param bool $reel
   *
   * @return ?string
   * @throws \Exception
   */
  public function getFilename(EntityFileInterface $object, bool $reel = false): ?string
  {
    if(!$reel)
    {
      $value = $this->getEntityFileValue($object, $reel, $this->getEntityFileValue($object));
    }
    else
    {
      $value = $this->getEntityFileValue($object);
    }
    if($value)
    {
      preg_match("/(.*)__UNIQID__.*(\..*)/", $value, $matches);
      if(count($matches) == 3)
      {
        return $matches[1].$matches[2];
      }
      else
      {
        return $value;
      }
    }
    return null;
  }

  /**
   * @param EntityFileInterface $object
   *
   * @return string|null
   * @throws \Exception
   */
  public function getFilenameWithoutExtension(EntityFileInterface $object): ?string
  {
    return pathinfo($this->getFilename($object), PATHINFO_FILENAME);
  }

  /**
   * @param EntityFileInterface $object
   *
   * @return string|null
   * @throws \Exception
   */
  public function getFilenameExtension(EntityFileInterface $object): ?string
  {
    return pathinfo($this->getEntityFileValue($object), PATHINFO_EXTENSION);
  }

  /**
   * @param string $cropperName
   *
   * @return AustralFile\Cropper|null
   */
  public function getCropperByName(string $cropperName): ?AustralFile\Cropper
  {
    return $this->croppers ? (array_key_exists($cropperName, $this->croppers->croppers) ? $this->croppers->croppers[$cropperName] : null) : null;
  }

  /**
   * @param EntityFileInterface $object
   *
   * @return array
   * @throws \Exception
   */
  public function getCropperData(EntityFileInterface $object): array
  {
    $value = $object->getValueByFieldname("cropperData");
    return $value ?: array();
  }

  /**
   * @param $object
   * @param string $cropperName
   *
   * @return array
   * @throws \Exception
   */
  public function getCropperDataByFieldname($object, string $cropperName): array
  {
    if($this->getCropperByName($cropperName))
    {
      return array_key_exists($this->getFieldname(), $this->getCropperData($object)) ? $this->getCropperData($object)[$this->getFieldname()] : array();
    }
    return array();
  }

  /**
   * @param $object
   * @param string $cropperName
   *
   * @return array
   * @throws \Exception
   */
  public function getCropperDataValue($object, string $cropperName): array
  {
    if($cropper = $this->getCropperByName($cropperName))
    {
      return array_key_exists($cropper->name, $this->getCropperDataByFieldname($object, $cropperName)) ?  $this->getCropperDataByFieldname($object, $cropperName)[$cropper->name] : array();
    }
    return array();
  }

}

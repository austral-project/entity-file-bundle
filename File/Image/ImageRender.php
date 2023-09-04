<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Image;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\File\Cropper\Cropper;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Image Render.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ImageRender
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var Image
   */
  protected Image $imageLib;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * @var Cropper
   */
  protected Cropper $cropper;

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * ImageRender constructor.
   *
   * @param ContainerInterface $container
   * @param Mapping $mapping
   * @param Image $imageLib
   * @param UploadsConfiguration $uploadsConfiguration
   * @param Cropper $cropper
   */
  public function __construct(ContainerInterface $container, Mapping $mapping, Image $imageLib, UploadsConfiguration $uploadsConfiguration, Cropper $cropper)
  {
    $this->container = $container;
    $this->mapping = $mapping;
    $this->imageLib = $imageLib;
    $this->filesystem = new Filesystem();
    $this->uploadsConfiguration = $uploadsConfiguration;
    $this->cropper = $cropper;
  }

  /**
   * @param string $entityKey
   * @param string|integer $id
   * @param string $fieldname
   * @param string $type
   * @param string $mode
   * @param float|null $width
   * @param float|null $height
   * @param string|null $value
   * @param string|null $extension
   * @param bool $force
   *
   * @return string
   * @throws \Exception
   */
  public function initRender(string $entityKey, $id, string $fieldname, string $type = "original", string $mode = "resize", float $width = null, float $height= null, string $value = null, string $extension = null, bool $force = false): string
  {
    $modeResize = null;
    if(strpos($mode, "resize") !== false)
    {
      $modeResize = "ratio";
      if(strpos($mode, "-") !== false)
      {
        list($mode, $modeResize) = explode("-", $mode);
      }
    }

    $cropperKey = null;
    if($type != "original")
    {
      $cropperKey = $type;
      $type = "crop";
      if($mode !== "resize")
      {
        $mode = "resize";
      }
    }

    /** @var FieldFileMapping $fieldFileMapping */
    $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object ?? $entityKey, FieldFileMapping::class, $fieldname);

    $object = null;
    if(!$value || !$extension)
    {
      /** @var FileInterface $object */
      if($object = $this->mapping->getObject($entityKey, $id))
      {
        $value = $value ? : $fieldFileMapping->getFilenameWithoutExtension($object);
        $extension = $extension ? : $fieldFileMapping->getFilenameExtension($object);
      }
    }

    $imageThumbnailPathDir = AustralTools::join(
      $fieldFileMapping->path->thumbnail,
      $id,
      $fieldname,
      $cropperKey ? : $type,
      $mode.($modeResize ? "-{$modeResize}" : ""),
      "{$width}x{$height}"
    );
    $imageThumbnailPath = AustralTools::join($imageThumbnailPathDir, "{$value}.{$extension}");

    if(file_exists($imageThumbnailPath) && !$force)
    {
      return $imageThumbnailPath;
    }

    if(!$object)
    {
      $object = $this->mapping->getObject($entityKey, $id);
      if(!$object)
      {
        throw new \InvalidArgumentException("EntityFileBundle -> ImageRender : Not object {$entityKey} -> id : {$id}");
      }
    }

    if(!$fileName = $object->getValueByFieldname($fieldname))
    {
      throw new \InvalidArgumentException("EntityFileBundle -> ImageRender : Not fieldname {$fieldname} in object {$entityKey} -> id : {$id}");
    }

    $reelFilePath = AustralTools::join(
      $fieldFileMapping->path->upload,
      $fieldname,
      $fileName
    );

    $originalExtension = $fieldFileMapping->getFilenameExtension($object);
    if($originalExtension !== $extension)
    {
      $imageThumbnailPathSave = AustralTools::join(
        $imageThumbnailPathDir,
        $value.".". $originalExtension
      );
    }
    else
    {
      $imageThumbnailPathSave = $imageThumbnailPath;
    }

    if($type == "crop" && $fieldFileMapping->getCropperDataByFieldname($object, $cropperKey)) {
      $fileNameWithoutExtension = pathinfo($reelFilePath,  PATHINFO_FILENAME);
      $cropExtension = pathinfo($reelFilePath,  PATHINFO_EXTENSION);
      $reelFilePath = AustralTools::join(
        $fieldFileMapping->path->upload,
        $fieldname,
        "{$fileNameWithoutExtension}__CROP__{$cropperKey}.{$cropExtension}"
      );
      if(!file_exists($reelFilePath))
      {
        $this->cropper->crop($object, $fieldname, $cropperKey);
      }
      if(!file_exists($reelFilePath))
      {
        $reelFilePath = AustralTools::join(
          $fieldFileMapping->path->upload,
          $fieldname,
          $fileName
        );
      }
    }

    if(!file_exists($reelFilePath) || !is_file($reelFilePath))
    {
      throw new \InvalidArgumentException(sprintf('EntityFileBundle -> ImageRender : File not found %s', $reelFilePath));
    }

    if((AustralTools::extension($reelFilePath) != "svg" && AustralTools::extension($reelFilePath) != "webp") && ($width || $height))
    {
      $image = $this->imageLib->open($reelFilePath);
      $image->autoRotate();
      if($mode === "resize")
      {
        $image->resize($width, $height, $modeResize);
      }
      else
      {
        $image->thumbnail($width, $height, $mode);
      }
      $image->save($imageThumbnailPathSave, array("webp"));
      if(!file_exists($imageThumbnailPath))
      {
        $imageThumbnailPath = $imageThumbnailPathSave;
      }
    }
    else
    {
      $this->filesystem->copy($reelFilePath, $imageThumbnailPathSave);
      $this->container->get('austral.entity_file.compression')->compress($imageThumbnailPathSave, array("webp"));
      $imageThumbnailPath = $reelFilePath;
    }
    return $imageThumbnailPath;
  }

}
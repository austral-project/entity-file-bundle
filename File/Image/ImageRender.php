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
use Austral\Contracts\File\FileInterface;
use Austral\EntityFileBundle\File\Compression\Compression;
use Austral\EntityFileBundle\File\Cropper\Cropper;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Image Render.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ImageRender
{

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * ImageRender constructor.
   *
   * @param Mapping<T> $mapping
   * @param Image<T> $imageLib
   * @param UploadsConfiguration $uploadsConfiguration
   * @param Cropper<T> $cropper
   * @param Compression $compression
   */
  public function __construct(
    #[Autowire(service: "austral.entity.mapping")] protected Mapping $mapping,
    #[Autowire(service: "austral.entity_file.image")] protected Image $imageLib,
    #[Autowire(service: "austral.entity_file.uploads.config")] protected UploadsConfiguration $uploadsConfiguration,
    #[Autowire(service: "austral.entity_file.cropper")] protected Cropper $cropper,
    #[Autowire(service: "austral.entity_file.compression")] protected Compression $compression,
  )
  {
    $this->filesystem = new Filesystem();
  }

  /**
   * @param string $entityKey
   * @param string $id
   * @param string $fieldname
   * @param string $type
   * @param string $mode
   * @param int|null $width
   * @param int|null $height
   * @param string|null $value
   * @param string|null $extension
   * @param bool $force
   *
   * @return string
   * @throws \Exception
   */
  public function initRender(string $entityKey, string $id, string $fieldname, string $type = "original", string $mode = "resize", ?int $width = null, ?int $height= null, ?string $value = null, ?string $extension = null, bool $force = false): string
  {
    $modeResize = null;
    if(str_contains($mode, "resize"))
    {
      $modeResize = "ratio";
      if(str_contains($mode, "-"))
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

    /** @var FieldFileMapping<T> $fieldFileMapping */
    $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($entityKey, FieldFileMapping::class, $fieldname);

    $object = null;
    if(!$value || !$extension)
    {
      /** @var FileInterface<T>|null $object */
      $object = $this->mapping->getObject($entityKey, $id);
      if($object)
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
      /** @var FileInterface<T>|null $object */
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
      $this->compression->compress($imageThumbnailPathSave, array("webp"));
      $imageThumbnailPath = $reelFilePath;
    }
    return $imageThumbnailPath;
  }

}
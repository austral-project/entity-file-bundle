<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Cropper;

use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Image\Image;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Cropper.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class Cropper
{
  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * Cropper constructor
   *
   * @param Image<T>   $image
   * @param Mapping<T> $mapping
   */
  public function __construct(protected Image $image, protected Mapping $mapping)
  {
    $this->filesystem = new Filesystem();
  }

  /**
   * @param FileInterface<T>     $object
   * @param string               $fieldname
   * @param string               $cropperKey
   * @param array<string, mixed> $cropperData
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function crop(FileInterface $object, string $fieldname, string $cropperKey, array $cropperData = []): self
  {
    /** @var FieldFileMapping<T>|null $fieldFileMapping */
    $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
    if ($fieldFileMapping)
    {
      if (!$cropperData)
      {
        $cropperData = $fieldFileMapping->getCropperDataValue($object, $cropperKey);
      }
      $filePath = $fieldFileMapping->getObjectFilePath($object);
      if ($cropperData && $filePath && AustralTools::isImage($filePath))
      {
        $this->image->open($filePath);

        $cropBoxData = AustralTools::valueByKey($cropperData, 'cropBoxData', []);
        $canvasData = AustralTools::valueByKey($cropperData, 'cropCanvas', []);
        $imageData = AustralTools::valueByKey($cropperData, 'imageData', []);

        $imageNaturalWidth = (int) AustralTools::valueByKey($imageData, 'naturalWidth', 0);
        $imageNaturalHeight = (int) AustralTools::valueByKey($imageData, 'naturalHeight', 0);

        $imageWidth = (int) AustralTools::valueByKey($imageData, 'width', 0);
        $coeffZoom = $imageWidth > 0 ? $imageNaturalWidth / $imageWidth : 0;

        $cropBoxWidth = (int) AustralTools::valueByKey($cropBoxData, 'width', 0);
        $cropBoxHeight = (int) AustralTools::valueByKey($cropBoxData, 'height', 0);

        $cropBoxLeft = (int) AustralTools::valueByKey($cropBoxData, 'left', 0);
        $cropBoxTop = (int) AustralTools::valueByKey($cropBoxData, 'top', 0);

        $canvasLeft = (int) AustralTools::valueByKey($canvasData, 'left', 0);
        $canvasTop = (int) AustralTools::valueByKey($canvasData, 'top', 0);

        $positionX = $cropBoxLeft - $canvasLeft;
        $positionY = $cropBoxTop - $canvasTop;

        $positionFinalX = $positionX * $coeffZoom;
        $positionFinalY = $positionY * $coeffZoom;

        $cropBoxFinalWidth = $cropBoxWidth * $coeffZoom;
        $cropBoxFinalHeight = $cropBoxHeight * $coeffZoom;

        if ($cropBoxFinalWidth > $imageNaturalWidth)
        {
          $cropBoxFinalWidth = $imageNaturalWidth;
        }

        if ($cropBoxFinalHeight > $imageNaturalHeight)
        {
          $cropBoxFinalHeight = $imageNaturalHeight;
        }

        $rotate = 0;
        $flip = null;

        $this->image->crop(
          $imageNaturalWidth,
          $imageNaturalHeight,
          $positionFinalX,
          $positionFinalY,
          $cropBoxFinalWidth,
          $cropBoxFinalHeight,
          $rotate,
          $flip
        );

        $originalFilename = pathinfo($filePath, PATHINFO_FILENAME);
        $filePathSave = AustralTools::join(
          $fieldFileMapping->getFilePathDir(),
          $originalFilename."__CROP__{$cropperKey}.".$this->image->getExtension()
        );
        $generateOtherFormat = ['webp'];
        foreach ($generateOtherFormat as $format)
        {
          $savePathWidthFormat = AustralTools::join(
            $fieldFileMapping->getFilePathDir(),
            $originalFilename."__CROP__{$cropperKey}.".$format
          );
          if (file_exists($savePathWidthFormat) && is_file($savePathWidthFormat))
          {
            $this->filesystem->remove($savePathWidthFormat);
          }
        }
        $this->image->save($filePathSave, $generateOtherFormat);
      }
    }

    return $this;
  }
}

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

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\File\Image\Image;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral Cropper.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Cropper
{
  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var Image
   */
  protected Image $image;

  /**
   * Cropper constructor.
   *
   */
  public function __construct(Image $image, Mapping $mapping)
  {
    $this->mapping = $mapping;
    $this->image = $image;
  }

  /**
   * @param FileInterface|EntityFileCropperTrait $object
   * @param string $fieldname
   * @param string $cropperKey
   * @param array $cropperData
   *
   * @return Cropper
   * @throws \Exception
   */
  public function crop(FileInterface $object, string $fieldname, string $cropperKey, array $cropperData = array()): Cropper
  {
    if($fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      if(!$cropperData)
      {
        $cropperData = $fieldFileMapping->getCropperDataValue($object, $cropperKey);
      }

      $filePath = $fieldFileMapping->getObjectFilePath($object);
      if($cropperData && AustralTools::isImage($filePath))
      {
        $this->image->open($filePath);

        $cropBoxData = AustralTools::getValueByKey($cropperData, "cropBoxData", array());
        $canvasData = AustralTools::getValueByKey($cropperData, "cropCanvas", array());
        $imageData = AustralTools::getValueByKey($cropperData, "imageData", array());

        $imageNaturalWidth = (float) AustralTools::getValueByKey($imageData, "naturalWidth", 0);
        $imageNaturalHeight = (float) AustralTools::getValueByKey($imageData, "naturalHeight", 0);

        $imageWidth = (float) AustralTools::getValueByKey($imageData, "width", 0);

        $coeffZoom = $imageWidth > 0 ? $imageNaturalWidth/$imageWidth : 0;

        $cropBoxWidth = (float) AustralTools::getValueByKey($cropBoxData, "width", 0);
        $cropBoxHeight = (float) AustralTools::getValueByKey($cropBoxData, "height", 0);

        $cropBoxLeft = (float) AustralTools::getValueByKey($cropBoxData, "left", 0);
        $cropBoxTop = (float) AustralTools::getValueByKey($cropBoxData, "top", 0);

        $canvasLeft = (float) AustralTools::getValueByKey($canvasData, "left", 0);
        $canvasTop = (float) AustralTools::getValueByKey($canvasData, "top", 0);

        $positionX = $cropBoxLeft - $canvasLeft;
        $positionY = $cropBoxTop - $canvasTop;

        $positionFinalX = $positionX*$coeffZoom;
        $positionFinalY = $positionY*$coeffZoom;

        $cropBoxFinalWidth = $cropBoxWidth*$coeffZoom;
        $cropBoxFinalHeight = $cropBoxHeight*$coeffZoom;

        $rotate = 0;
        $flip = null;

        $this->image->crop($imageNaturalWidth,
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
        $this->image->save($filePathSave, array("webp"));
      }
    }
    return $this;
  }


}
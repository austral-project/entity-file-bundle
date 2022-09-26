<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Upload;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;

use Austral\EntityFileBundle\File\Cropper\Cropper;
use Austral\EntityFileBundle\File\Image\Image;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Austral File Uploads services.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
Class FileUploader
{

  /**
   * @var RequestStack
   */
  protected $request;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * @var AsciiSlugger
   */
  protected AsciiSlugger $slugger;

  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @var Cropper
   */
  protected Cropper $cropper;

  /**
   * @var Image
   */
  protected Image $image;

  /**
   * UploadFiles constructor.
   *
   * @param RequestStack $request
   * @param Mapping $mapping
   * @param Cropper $cropper
   * @param CropperConfiguration $cropperConfiguration
   * @param Image $image
   */
  public function __construct(RequestStack $request,
    Mapping $mapping,
    Cropper $cropper,
    CropperConfiguration $cropperConfiguration,
    Image $image
  )
  {
    $this->request = $request->getCurrentRequest();
    $this->filesystem = new Filesystem();
    $this->slugger = new AsciiSlugger();
    $this->cropper = $cropper;
    $this->cropperConfiguration = $cropperConfiguration;
    $this->image = $image;
    $this->mapping = $mapping;
    return $this;
  }

  /**
   * @param FormInterface $form
   * @param FileInterface $object
   *
   * @return bool
   * @throws Exception
   */
  public function validateRequiredFiles(FormInterface $form, FileInterface $object): bool
  {
    $uploadedFiles = $object->getUploadFiles();
    $requiredFileSuccess = true;

    /**
     * @var string $fieldname
     * @var FieldFileMapping $fieldFileMapping
     */
    foreach($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
    {
      if($fieldFileMapping->uploadParameters->isRequired && !$fieldFileMapping->getObjectFilePath($object))
      {
        if(!array_key_exists($fieldFileMapping->getFieldname(), $uploadedFiles) || !($uploadedFiles[$fieldFileMapping->getFieldname()] instanceof UploadedFile))
        {
          $requiredFileSuccess = false;
          $form->get($fieldFileMapping->getFieldname())->addError(new FormError("required"));
        }
      }
    }
    return $requiredFileSuccess;
  }

  /**
   * @param FormInterface $form
   * @param FileInterface $object
   *
   * @return $this
   * @throws Exception
   */
  public function uploadFiles(FormInterface $form, FileInterface $object): FileUploader
  {
    try {

      /**
       * @var string $fieldname
       * @var FieldFileMapping $fieldFileMapping
       */
      foreach($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {
        if(array_key_exists($fieldFileMapping->getFieldname(), $object->getUploadFiles()))
        {
          if($object->getUploadFiles()[$fieldFileMapping->getFieldname()] instanceof UploadedFile)
          {
            $this->uploadFile($fieldFileMapping, $object, $object->getUploadFiles()[$fieldFileMapping->getFieldname()]);
          }
        }

        if(method_exists($object, "getGenerateCropperByKey") && $fieldFileMapping->croppers)
        {
          if(array_key_exists($fieldFileMapping->getFieldname(), $object->getGenerateCropperByKey()))
          {
            foreach($object->getGenerateCropperByKey()[$fieldFileMapping->getFieldname()] as $cropperKey => $value)
            {
              $this->cropper->crop($object, $fieldFileMapping->getFieldname(), $cropperKey, $fieldFileMapping->getCropperDataValue($object, $cropperKey));
              $this->deleteThumbnails($fieldFileMapping, $object, $cropperKey);
            }
          }
        }

        if(array_key_exists($fieldFileMapping->getFieldname(), $object->getDeleteFiles()))
        {
          if($object->getDeleteFiles()[$fieldFileMapping->getFieldname()] && !$fieldFileMapping->uploadParameters->isRequired)
          {
            $this->deleteFileByFieldname($fieldFileMapping, $object)
              ->deleteThumbnails($fieldFileMapping, $object);
          }
        }

      }

    } catch(Exception $e) {
      throw $e;
    }
    return $this;
  }


  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface|EntityInterface $objectSource
   * @param FileInterface|EntityInterface $objectDestination
   *
   * @return $this
   * @throws Exception
   */
  public function copyFilename(FieldFileMapping $fieldFileMapping, $objectSource, $objectDestination): FileUploader
  {
    if($filenameSource = $fieldFileMapping->getObjectValue($objectSource))
    {
      $pathInfo = pathinfo($filenameSource);
      $originalFilenameValues = explode("__UNIQID__", $filenameSource);

      $filenameDestination = AustralTools::getValueByKey($originalFilenameValues, 0, $filenameSource).uniqid("__UNIQID__").'.'.$pathInfo['extension'];

      $fieldFileMapping->setObjectValue($objectDestination, null, $filenameDestination);
    }
    return $this;
  }

  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface|EntityInterface $objectSource
   * @param FileInterface|EntityInterface $objectDestination
   *
   * @return FileUploader
   * @throws Exception
   */
  public function copyFile(FieldFileMapping $fieldFileMapping, $objectSource, $objectDestination): FileUploader
  {
    if($filenameSource = $fieldFileMapping->getObjectValue($objectSource))
    {
      $filesystem = new Filesystem();
      $uploadsPath = AustralTools::join(
        $fieldFileMapping->path->upload,
        $fieldFileMapping->getFieldname()
      );
      $pathSource = AustralTools::join($uploadsPath, $filenameSource);

      $filenameDestination = $fieldFileMapping->getObjectValue($objectDestination);
      $pathDestination = AustralTools::join($uploadsPath, $filenameDestination);

      if(file_exists($pathSource) && is_file($pathSource))
      {
        $filesystem->copy($pathSource, $pathDestination);
        if(AustralTools::isImage($pathSource))
        {
          $this->image->open($pathDestination)->autoRotate()->save($pathDestination, array("webp"));
        }
      }
    }
    return $this;
  }

  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface|EntityInterface $object
   * @param UploadedFile $uploadedFile
   *
   * @return $this
   * @throws Exception
   */
  public function uploadFile(FieldFileMapping $fieldFileMapping, FileInterface $object, UploadedFile $uploadedFile): FileUploader
  {
    $uploadsPath = AustralTools::join(
      $fieldFileMapping->path->upload,
      $fieldFileMapping->getFieldname()
    );
    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = strtolower($this->slugger->slug($originalFilename));
    $filename = $safeFilename.uniqid("__UNIQID__").'.'.$uploadedFile->guessExtension();
    $uploadedFile->move(
      $uploadsPath,
      $filename
    );
    $filePath = AustralTools::join($uploadsPath, $filename);
    if(AustralTools::isImage($filename) && AustralTools::extension($filePath) != "svg")
    {
      $this->image->open($filePath)->autoRotate()->save($filePath, array("webp"));
    }
    $this->deleteFileByFieldname($fieldFileMapping, $object, $uploadsPath)->deleteThumbnails($fieldFileMapping, $object);
    $fieldFileMapping->setObjectValue($object, null, $filename);
    return $this;
  }

  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface|EntityInterface $object
   * @param string|null $uploadsPath
   *
   * @return $this
   * @throws Exception
   */
  public function deleteFileByFieldname(FieldFileMapping $fieldFileMapping, FileInterface $object, string $uploadsPath = null): FileUploader
  {
    if(!$uploadsPath)
    {
      $uploadsPath = AustralTools::join(
        $fieldFileMapping->path->upload,
        $fieldFileMapping->getFieldname()
      );
    }

    if($value = $fieldFileMapping->getObjectValue($object))
    {
      $oldFilePath = AustralTools::join($uploadsPath, $value);
      if(file_exists($oldFilePath) && is_file($oldFilePath))
      {
        $this->filesystem->remove($oldFilePath);
      }
      $oldFilename = pathinfo($oldFilePath,  PATHINFO_FILENAME);
      $oldFilePathWebp = AustralTools::join($uploadsPath, "{$oldFilename}.webp");
      if(file_exists($oldFilePathWebp) && is_file($oldFilePathWebp))
      {
        $this->filesystem->remove($oldFilePathWebp);
      }

      if(method_exists($object, "getCropperDataByFilename"))
      {
        foreach ($object->getCropperDataByFilename($fieldFileMapping->getFieldname()) as $cropperKey => $values)
        {
          $oldFilename = pathinfo($oldFilePath,  PATHINFO_FILENAME);
          $extension = pathinfo($oldFilePath,  PATHINFO_EXTENSION);
          $cropperFilePath = AustralTools::join($uploadsPath, "{$oldFilename}__CROP__{$cropperKey}.{$extension}");
          if(file_exists($cropperFilePath) && is_file($cropperFilePath))
          {
            $this->filesystem->remove($cropperFilePath);
          }
          $cropperFilePathWebp = AustralTools::join($uploadsPath, "{$oldFilename}__CROP__{$cropperKey}.webp");
          if(file_exists($cropperFilePathWebp) && is_file($cropperFilePathWebp))
          {
            $this->filesystem->remove($cropperFilePathWebp);
          }
        }
      }
    }
    return $this;
  }

  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface $object
   * @param string|null $subDir
   *
   * @return $this
   */
  public function deleteThumbnails(FieldFileMapping $fieldFileMapping, FileInterface $object, string $subDir = null): FileUploader
  {
    $thumbnailPath = AustralTools::join(
      $fieldFileMapping->path->thumbnail,
      $object->getId(),
      $fieldFileMapping->getFieldname()
    );
    if($subDir)
    {
      $thumbnailPath = AustralTools::join($thumbnailPath, $subDir);
    }
    if(file_exists($thumbnailPath))
    {
      $this->filesystem->remove($thumbnailPath);
    }
    $thumbnailPathByFieldname = pathinfo($thumbnailPath,  PATHINFO_FILENAME);
    $thumbnailPathByFieldnameWebp = AustralTools::join($thumbnailPath, "{$thumbnailPathByFieldname}.webp");
    if(file_exists($thumbnailPathByFieldnameWebp) && is_file($thumbnailPathByFieldnameWebp))
    {
      $this->filesystem->remove($thumbnailPathByFieldnameWebp);
    }
    return $this;
  }

}
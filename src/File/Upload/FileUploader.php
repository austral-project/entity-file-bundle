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

use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\File\Cropper\Cropper;
use Austral\EntityFileBundle\File\Image\Image;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Austral File Uploads services.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class FileUploader
{
  /**
   * @var Request|null
   */
  protected ?Request $request = null;

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * @var AsciiSlugger
   */
  protected AsciiSlugger $slugger;

  /**
   * UploadFiles constructor.
   *
   * @param Mapping<T>           $mapping
   * @param Cropper<T>           $cropper
   * @param CropperConfiguration $cropperConfiguration
   * @param Image<T>             $image
   */
  public function __construct(
    protected Mapping $mapping,
    protected Cropper $cropper,
    protected CropperConfiguration $cropperConfiguration,
    protected Image $image,
  ) {
    $this->filesystem = new Filesystem();
    $this->slugger = new AsciiSlugger();
  }

  /**
   * setRequest
   *
   * @param RequestStack $request
   *
   * @return $this
   */
  public function setRequest(RequestStack $request): static
  {
    $this->request = $request->getCurrentRequest();

    return $this;
  }

  /**
   * @param FormInterface<mixed> $form
   * @param FileInterface<T>     $object
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function validateRequiredFiles(FormInterface $form, FileInterface $object): bool
  {
    $uploadedFiles = $object->getUploadFiles();
    $requiredFileSuccess = true;

    /**
     * @var FieldFileMapping<T> $fieldFileMapping
     */
    foreach ($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
    {
      if ($fieldFileMapping->uploadParameters->isRequired && !$fieldFileMapping->getObjectFilePath($object))
      {
        if ($fieldFileMapping->getFieldname() && (!array_key_exists($fieldFileMapping->getFieldname(), $uploadedFiles) || !($uploadedFiles[$fieldFileMapping->getFieldname()] instanceof UploadedFile)))
        {
          $requiredFileSuccess = false;
          $form->get($fieldFileMapping->getFieldname())->addError(new FormError('required'));
        }
      }
    }

    return $requiredFileSuccess;
  }

  /**
   * @param FormInterface<mixed> $form
   * @param FileInterface<T>     $object
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function uploadFiles(FormInterface $form, FileInterface $object): self
  {
    /**
     * @var FieldFileMapping<T> $fieldFileMapping
     */
    foreach ($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
    {
      if ($fieldFileMapping->getFieldname() && array_key_exists($fieldFileMapping->getFieldname(), $object->getUploadFiles()))
      {
        if ($object->getUploadFiles()[$fieldFileMapping->getFieldname()] instanceof UploadedFile)
        {
          $this->uploadFile($fieldFileMapping, $object, $object->getUploadFiles()[$fieldFileMapping->getFieldname()]);
        }
      }

      $this->generateCrop($fieldFileMapping, $object);

      if ($fieldFileMapping->getFieldname() && array_key_exists($fieldFileMapping->getFieldname(), $object->getDeleteFiles()))
      {
        if ($object->getDeleteFiles()[$fieldFileMapping->getFieldname()] && !$fieldFileMapping->uploadParameters->isRequired)
        {
          $this->deleteFileByFieldname($fieldFileMapping, $object)
            ->deleteThumbnails($fieldFileMapping, $object);
        }
      }
    }

    return $this;
  }

  /**
   * generateCrop
   *
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $object
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function generateCrop(FieldFileMapping $fieldFileMapping, FileInterface $object): static
  {
    if (method_exists($object, 'getGenerateCropperByKey') && $fieldFileMapping->croppers)
    {
      if ($fieldFileMapping->getFieldname() && array_key_exists($fieldFileMapping->getFieldname(), $object->getGenerateCropperByKey()))
      {
        foreach ($object->getGenerateCropperByKey()[$fieldFileMapping->getFieldname()] as $cropperKey => $value)
        {
          $this->cropper->crop($object, $fieldFileMapping->getFieldname(), $cropperKey, $fieldFileMapping->getCropperDataValue($object, $cropperKey));
          $this->deleteThumbnails($fieldFileMapping, $object, $cropperKey);
        }
      }
    }

    return $this;
  }

  /**
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $objectSource
   * @param FileInterface<T>    $objectDestination
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function copyFilename(FieldFileMapping $fieldFileMapping, FileInterface $objectSource, FileInterface $objectDestination): self
  {
    if ($filenameSource = $fieldFileMapping->getObjectValue($objectSource))
    {
      $pathInfo = pathinfo($filenameSource);
      $originalFilenameValues = explode('__UNIQID__', $filenameSource);
      $filenameDestination = AustralTools::valueByKey($originalFilenameValues, 0, $filenameSource).
        uniqid('__UNIQID__').'.'.
        AustralTools::valueByKey($pathInfo, 'extension');
      $fieldFileMapping->setObjectValue($objectDestination, null, $filenameDestination);
    }

    return $this;
  }

  /**
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $objectSource
   * @param FileInterface<T>    $objectDestination
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function copyFile(FieldFileMapping $fieldFileMapping, FileInterface $objectSource, FileInterface $objectDestination): self
  {
    if ($filenameSource = $fieldFileMapping->getObjectValue($objectSource))
    {
      $filesystem = new Filesystem();
      $uploadsPath = AustralTools::join(
        $fieldFileMapping->path->upload,
        $fieldFileMapping->getFieldname()
      );
      $pathSource = AustralTools::join($uploadsPath, $filenameSource);

      $filenameDestination = $fieldFileMapping->getObjectValue($objectDestination);
      $pathDestination = AustralTools::join($uploadsPath, $filenameDestination);

      if (file_exists($pathSource) && is_file($pathSource))
      {
        $filesystem->copy($pathSource, $pathDestination);
        if (AustralTools::isImage($pathSource) && 'svg' != AustralTools::extension($pathSource))
        {
          $this->image->open($pathDestination)->autoRotate()->save($pathDestination, ['webp']);
        }
      }
    }

    return $this;
  }

  /**
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $object
   * @param UploadedFile        $uploadedFile
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function uploadFile(FieldFileMapping $fieldFileMapping, FileInterface $object, UploadedFile $uploadedFile): self
  {
    $uploadsPath = AustralTools::join(
      $fieldFileMapping->path->upload,
      $fieldFileMapping->getFieldname()
    );
    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = strtolower($this->slugger->slug($originalFilename));
    $filename = $safeFilename.uniqid('__UNIQID__').'.'.($uploadedFile->guessExtension() ?? $uploadedFile->guessClientExtension());
    $uploadedFile->move(
      $uploadsPath,
      $filename
    );
    $object->setUploadFileByFieldname($fieldFileMapping->getFieldname(), null);
    $filePath = AustralTools::join($uploadsPath, $filename);
    if (AustralTools::isImage($filename) && 'svg' != AustralTools::extension($filePath))
    {
      $this->image->open($filePath)->autoRotate()->save($filePath, ['webp']);
    }
    $this->deleteFileByFieldname($fieldFileMapping, $object, $uploadsPath)->deleteThumbnails($fieldFileMapping, $object);
    $fieldFileMapping->setObjectValue($object, null, $filename);

    return $this;
  }

  /**
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $object
   * @param string|null         $uploadsPath
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function deleteFileByFieldname(FieldFileMapping $fieldFileMapping, FileInterface $object, ?string $uploadsPath = null): self
  {
    if (!$uploadsPath)
    {
      $uploadsPath = AustralTools::join(
        $fieldFileMapping->path->upload,
        $fieldFileMapping->getFieldname()
      );
    }

    if ($value = $fieldFileMapping->getObjectValue($object))
    {
      $oldFilePath = AustralTools::join($uploadsPath, $value);
      if (file_exists($oldFilePath) && is_file($oldFilePath))
      {
        $this->filesystem->remove($oldFilePath);
      }
      $oldFilename = pathinfo($oldFilePath, PATHINFO_FILENAME);
      $oldFilePathWebp = AustralTools::join($uploadsPath, "{$oldFilename}.webp");
      if (file_exists($oldFilePathWebp) && is_file($oldFilePathWebp))
      {
        $this->filesystem->remove($oldFilePathWebp);
      }
      $fieldFileMapping->setObjectValue($object, null);

      if (method_exists($object, 'getCropperDataByFilename'))
      {
        foreach ($object->getCropperDataByFilename($fieldFileMapping->getFieldname()) as $cropperKey => $values)
        {
          $oldFilename = pathinfo($oldFilePath, PATHINFO_FILENAME);
          $extension = pathinfo($oldFilePath, PATHINFO_EXTENSION);
          $cropperFilePath = AustralTools::join($uploadsPath, "{$oldFilename}__CROP__{$cropperKey}.{$extension}");
          if (file_exists($cropperFilePath) && is_file($cropperFilePath))
          {
            $this->filesystem->remove($cropperFilePath);
          }
          $cropperFilePathWebp = AustralTools::join($uploadsPath, "{$oldFilename}__CROP__{$cropperKey}.webp");
          if (file_exists($cropperFilePathWebp) && is_file($cropperFilePathWebp))
          {
            $this->filesystem->remove($cropperFilePathWebp);
          }
        }
      }
    }

    return $this;
  }

  /**
   * @param FieldFileMapping<T> $fieldFileMapping
   * @param FileInterface<T>    $object
   * @param string|null         $subDir
   *
   * @return $this
   */
  public function deleteThumbnails(FieldFileMapping $fieldFileMapping, FileInterface $object, ?string $subDir = null): self
  {
    $thumbnailPath = AustralTools::join(
      $fieldFileMapping->path->thumbnail,
      $object->getId(),
      $fieldFileMapping->getFieldname()
    );
    if ($subDir)
    {
      $thumbnailPath = AustralTools::join($thumbnailPath, $subDir);
    }
    if (file_exists($thumbnailPath))
    {
      $this->filesystem->remove($thumbnailPath);
    }
    $thumbnailPathByFieldname = pathinfo($thumbnailPath, PATHINFO_FILENAME);
    $thumbnailPathByFieldnameWebp = AustralTools::join($thumbnailPath, "{$thumbnailPathByFieldname}.webp");
    if (file_exists($thumbnailPathByFieldnameWebp) && is_file($thumbnailPathByFieldnameWebp))
    {
      $this->filesystem->remove($thumbnailPathByFieldnameWebp);
    }

    return $this;
  }
}

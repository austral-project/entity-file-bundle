<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Bridge\Form\Listener;

use Austral\Contracts\Entity\EntityInterface;
use Austral\Contracts\File\CropperInterface;
use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Attribute\Cropper;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\Exception\FormUploadException;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\EntityFileBundle\Validator\SvgSecurity;
use Austral\FormBundle\Event\FormEvent;
use Austral\FormBundle\Event\FormFieldEvent;
use Austral\FormBundle\Field;
use Austral\FormBundle\Field\UploadField;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\File;

/**
 * Austral UploadFormListener Listener.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class UploadFormListener
{
  /**
   * FormListener constructor.
   *
   * @param FileUploader<T>      $fileUploader
   * @param UploadsConfiguration $uploadsConfiguration
   * @param CropperConfiguration $cropperConfiguration
   * @param Generator<T>         $fileLinkGenerator
   * @param Mapping<T>           $mapping
   */
  public function __construct(
    #[Autowire(service: 'austral.entity_file.uploader')] protected FileUploader $fileUploader,
    #[Autowire(service: 'austral.entity_file.uploads.config')] protected UploadsConfiguration $uploadsConfiguration,
    #[Autowire(service: 'austral.entity_file.cropper.config')] protected CropperConfiguration $cropperConfiguration,
    #[Autowire(service: 'austral.entity_file.link.generator')] protected Generator $fileLinkGenerator,
    #[Autowire(service: 'austral.entity.mapping')] protected Mapping $mapping
  ) {
  }

  /**
   * @param FormEvent<T> $formEvent
   *
   * @return void
   *
   * @throws \Exception
   */
  public function validate(FormEvent $formEvent): void
  {
    if ($formEvent->getFormMapper()->getObject() instanceof FileInterface)
    {
      if ($formEvent->getForm())
      {
        $objects = [];
        $data = $formEvent->getForm()->getData();
        if ($data instanceof Collection)
        {
          $objects = $data->toArray();
        }
        else
        {
          $objects[] = $data;
        }
        $this->validateObjects($formEvent->getForm(), $objects);
      }
    }
  }

  /**
   * @param FormInterface                                                       $form
   * @param array<string, EntityInterface<T>|array<string, EntityInterface<T>>> $objects
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function validateObjects(FormInterface $form, array $objects): void
  {
    /** @var EntityInterface<T>|array<string, EntityInterface<T>> $object */
    foreach ($objects as $object)
    {
      if (is_array($object))
      {
        $this->validateObjects($form, $object);
      }
      elseif ($object instanceof FileInterface)
      {
        $this->fileUploader->validateRequiredFiles($form, $object);
      }
    }
  }

  /**
   * @param FormEvent<T> $formEvent
   *
   * @return void
   */
  public function uploads(FormEvent $formEvent): void
  {
    try
    {
      if ($formEvent->getFormMapper()->getObject() instanceof FileInterface)
      {
        $objects = [];
        if ($formEvent->getForm())
        {
          $data = $formEvent->getForm()->getData();
          if ($data instanceof Collection)
          {
            $objects = $data->toArray();
          }
          else
          {
            $objects[] = $data;
          }
          $this->uploadsObjects($formEvent->getForm(), $objects);
        }
      }
    }
    catch (\Exception $e)
    {
      $formEvent->getFormMapper()->setFormStatus('exception');
      throw new FormUploadException($e->getMessage());
    }
  }

  /**
   * @param FormInterface                                                       $form
   * @param array<string, EntityInterface<T>|array<string, EntityInterface<T>>> $objects
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function uploadsObjects(FormInterface $form, array $objects): void
  {
    /** @var EntityInterface<T>|array<string, EntityInterface<T>> $object */
    foreach ($objects as $object)
    {
      if (is_array($object))
      {
        $this->uploadsObjects($form, $object);
      }
      elseif ($object instanceof FileInterface)
      {
        $this->fileUploader->uploadFiles($form, $object);
      }
    }
  }

  /**
   * @param FormEvent<T> $formEvent
   *
   * @return void
   *
   * @throws \Exception
   */
  public function formAddAutoFields(FormEvent $formEvent): void
  {
    $object = $formEvent->getFormMapper()->getObject();
    if ($object instanceof CropperInterface)
    {
      $cropperIsEnabled = false;
      foreach ($formEvent->getFormMapper()->allFields() as $field)
      {
        if ($field instanceof UploadField)
        {
          if ($field->getCropper())
          {
            $cropperIsEnabled = true;
          }
        }
      }

      $fieldsetHidden = $formEvent->getFormMapper()
        ->addFieldset('hidden')
        ->setPositionName(Fieldset::POSITION_NONE);
      if ($cropperIsEnabled)
      {
        $fieldsetHidden->add(Field\SymfonyField::create('cropperData', HiddenType::class, [
          'setter' => function ($object, $value) {
            $object->setCropperData($value ? json_decode($value, true) : []);
          },
          'getter' => function ($object) {
            return json_encode($object->getCropperData());
          },
          'attr' => [
            'data-cropper-data' => '',
            'autocomplete' => 'off',
          ],
        ]));
        $fieldsetHidden->add(Field\SymfonyField::create('generateCropperByKey', HiddenType::class, [
          'setter' => function ($object, $value) {
            $object->setGenerateCropperByKey(json_decode($value ?: '[]', true));
          },
          'getter' => function ($object) {
            return json_encode($object->getGenerateCropperByKey());
          },
          'attr' => [
            'data-cropper-key-generate' => '',
            'autocomplete' => 'off',
          ],
        ]));
      }
    }
  }

  /**
   * @param FormFieldEvent<T> $formFieldEvent
   *
   * @return void
   *
   * @throws \Exception
   */
  public function fieldConfiguration(FormFieldEvent $formFieldEvent): void
  {
    $object = $formFieldEvent->getFormMapper()->getObject();
    if ($formFieldEvent->getField() instanceof UploadField && $object instanceof FileInterface)
    {
      /** @var FieldFileMapping<T>|null $fieldMapping */
      $fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $formFieldEvent->getField()->getFieldname());
      if ($fieldMapping)
      {
        /** @var UploadField<T> $field */
        $field = $formFieldEvent->getField();

        if (!$field->getMimeTypes())
        {
          $field->setMimeTypes($fieldMapping->uploadParameters->mimeTypes);
        }

        $field->setTypeFile($fieldMapping->uploadParameters->getTypeFile());

        if (!$field->getMaxSize(false, false) && $fieldMapping->uploadParameters->sizeMax)
        {
          $field->setMaxSize($fieldMapping->uploadParameters->sizeMax);
        }

        if (!$field->getMaxSizeMessage(false))
        {
          $field->setMaxSizeMessage($fieldMapping->uploadParameters->errorMaxSize);
        }

        if (!$field->getMimeTypesMessage(false))
        {
          $field->setMimeTypesMessage($fieldMapping->uploadParameters->errorMimeTypes);
        }

        if (!$field->getCropper() && $fieldMapping->croppers)
        {
          $cropperFinal = [];
          /** @var Cropper<T> $cropper */
          foreach ($fieldMapping->croppers->croppers as $cropper)
          {
            $cropperFinal[$cropper->key] = (array) $cropper;
          }
          $field->setCropper($cropperFinal);
        }

        if (!$field->getImageSizes() && $fieldMapping->imageSize && $fieldMapping->imageSize->hasLimit())
        {
          $field->setImageSizes([
            'minWidth' => (int) $fieldMapping->imageSize->widthMin,
            'maxWidth' => (int) $fieldMapping->imageSize->widthMax,
            'minHeight' => (int) $fieldMapping->imageSize->heightMin,
            'maxHeight' => (int) $fieldMapping->imageSize->heightMax,
          ]);
        }

        if ($field->getAutoConstraints())
        {
          $field->addConstraint(new Constraints\Length(max: 255, maxMessage: 'errors.length.max'));

          $extensions = [];
          foreach ($field->getMimeTypes() as $mimeType)
          {
            $extensions = array_merge($extensions, MimeTypes::getDefault()->getExtensions($mimeType));
          }
          $field->addConstraint(new File(
            maxSize: $field->getMaxSize(),
            extensions: array_values(array_unique($extensions)),
            mimeTypes: $field->getMimeTypes(),
            maxSizeMessage: $field->getMaxSizeMessage(),
            mimeTypesMessage: $field->getMimeTypesMessage()
          ));
          $field->addConstraint(new SvgSecurity());

          if ($imageSizes = $field->getImageSizes())
          {
            $field->addConstraint(
              new Constraints\Image(
                [
                  'minWidth' => $imageSizes['minWidth'],
                  'maxWidth' => $imageSizes['maxWidth'],
                  'minHeight' => $imageSizes['minHeight'],
                  'maxHeight' => $imageSizes['maxHeight'],
                ]
              )
            );
          }
        }

        if ($field->hasContraint(Constraints\NotNull::class))
        {
          $field->removeContraintByClass(Constraints\NotNull::class);
        }

        if ($field->hasContraint(Constraints\NotBlank::class))
        {
          $field->removeContraintByClass(Constraints\NotBlank::class);
        }
        $field->setRequired(false);

        $this->initUploadFileInfos($field, $fieldMapping, $object);

        $field->addOption('setter', function (FileInterface $object, ?UploadedFile $uploadFile = null) use ($field) {
          $object->setUploadFileByFieldname($field->getFieldname(), $uploadFile);
        });
        $field->addOption('getter', function (FileInterface $object) use ($field) {
          return $object->getUploadFileByFieldname($field->getFieldname());
        });

        $fieldDeleteAttr = [
          'autocomplete' => 'off',
          'data-delete-file' => '',
        ];
        if (array_key_exists('data-popin-update-input', $field->getOptions()['attr']))
        {
          $fieldDeleteAttr['data-popin-update-input'] = $field->getOptions()['attr']['data-popin-update-input'].'-delete';
        }

        $formFieldEvent->getFormMapper()->addFieldset('hidden')->setPositionName(Fieldset::POSITION_NONE)->add(Field\SymfonyField::create("{$field->getFieldname()}DeleteFile", HiddenType::class, [
          'setter' => function (FileInterface $object, $value) use ($field) {
            $object->setDeleteFileByFieldname($field->getFieldname(), $value);
          },
          'getter' => function (FileInterface $object) use ($field) {
            return $object->getDeleteFileByFieldname($field->getFieldname());
          },
          'attr' => $fieldDeleteAttr,
        ]));

        if ($field->getCropper() || !$fieldMapping->uploadParameters->isRequired)
        {
          $formFieldEvent->getFormMapper()->addModal(
            "popup-editor-{$field->getFieldname()}",
            $field->getFieldname(),
            [
              'button' => [
                'entitled' => 'actions.picture.edit',
                'picto' => '',
                'class' => 'button-action',
              ],
              'popin' => [
                'id' => 'upload',
                'template' => 'uploadEditor',
              ],
            ]
          );
        }
      }
    }
  }

  /**
   * Fills the field with the information of the already uploaded file
   * (real name, paths, MIME type, size, dimensions), to display it in the form.
   *
   * @param UploadField<T>      $field
   * @param FieldFileMapping<T> $fieldMapping
   * @param FileInterface       $object
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function initUploadFileInfos(UploadField $field, FieldFileMapping $fieldMapping, FileInterface $object): void
  {
    $filePath = $fieldMapping->getObjectFilePath($object);
    if (!$filePath || !is_file($filePath))
    {
      return;
    }
    $fieldname = $field->getFieldname();
    $parameters = $field->getUploadFileParameters();

    $isImage = AustralTools::isImage($filePath);
    $parameters['file']['reelFilename'] = $fieldMapping->getFilename($object, true);
    $parameters['file']['path'] = [
      'view' => $isImage ? $this->fileLinkGenerator->image($object, $fieldname, 'original', 'i', 1000, 1000) : null,
      'original' => $isImage ? $this->fileLinkGenerator->image($object, $fieldname) : null,
      'download' => $this->fileLinkGenerator->download($object, $fieldname),
      'absolute' => $filePath,
    ];
    $parameters['file']['infos']['mimeType'] = AustralTools::mimeType($filePath);
    $parameters['file']['infos']['extension'] = AustralTools::extension($filePath);
    $parameters['file']['infos']['size'] = filesize($filePath) ?: null;
    $parameters['file']['infos']['sizeHuman'] = AustralTools::humanizeSize($filePath);
    if ($isImage)
    {
      $imageDimension = AustralTools::imageDimension($filePath, true);
      $aspectRatio = null;
      if (($imageDimension['width'] ?? 0) > 0 && ($imageDimension['height'] ?? 0) > 0)
      {
        $aspectRatio = $imageDimension['width'] / $imageDimension['height'];
      }
      $parameters['file']['infos']['imageSize'] = $imageDimension ? implode(' x ', $imageDimension) : null;
      $parameters['file']['infos']['imageDimension'] = $imageDimension;
      $parameters['file']['infos']['aspectRatio'] = $aspectRatio;
    }
    $field->setUploadFileParameters($parameters);
  }
}

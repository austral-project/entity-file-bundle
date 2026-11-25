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

use Austral\Contracts\File\CropperInterface;
use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Exception\FormUploadException;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\EntityFileBundle\Validator\SvgSecurity;
use Austral\FormBundle\Event\FieldBuildEvent;
use Austral\FormBundle\Event\FormMapperEvent;
use Austral\FormBundle\Event\FormProcessEvent;
use Austral\FormBundle\Field\SymfonyField;
use Austral\FormBundle\Field\UploadField;
use Austral\FormBundle\Layout\Fieldset;
use Austral\FormBundle\Mapper\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Austral Upload Form Listener.
 *
 * Integrates the files of entity-file in the Austral forms (form-bundle 4):
 *  - configure_after: each UploadField of a FileInterface form is configured from its Doctrine file mapping
 *    (MIME types, size, messages, croppers, image sizes), and the hidden fields it needs are added (deletion
 *    of the file, cropper data) with the modal of the editor;
 *  - field.build: an upload field is never required by the form (the required file is checked on submit),
 *    and receives Length and SvgSecurity; File and Image are added by UploadField itself, from its options;
 *  - submit: the required files are checked;
 *  - save_before: the files are uploaded, cropped or deleted.
 * form-bundle dispatches the events for each entry of the collections: every object is handled with its form.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class UploadFormListener
{
  /**
   * Fieldset of the hidden fields added by entity-file (not shown).
   */
  public const string HIDDEN_FIELDSET = 'hidden';

  /**
   * @param FileUploader<object> $fileUploader
   * @param Mapping<object>      $mapping
   */
  public function __construct(private readonly FileUploader $fileUploader, private readonly Mapping $mapping)
  {
  }

  /**
   * @param FormMapperEvent $event
   *
   * @return void
   */
  public function configure(FormMapperEvent $event): void
  {
    $mapper = $event->getMapper();
    $class = $this->fileClass($mapper);
    if (null === $class)
    {
      return;
    }
    $cropperEnabled = false;
    foreach ($mapper->getFields() as $field)
    {
      if (!$field instanceof UploadField || !($fieldMapping = $this->fieldMapping($class, $field)))
      {
        continue;
      }
      $this->configureField($mapper, $field, $fieldMapping);
      $cropperEnabled = $cropperEnabled || [] !== $field->getCropper();
    }
    if ($cropperEnabled && is_a($class, CropperInterface::class, true))
    {
      $this->addCropperFields($mapper);
    }
  }

  /**
   * After the guesser: a NotNull from a column that is not nullable would refuse the form when the file is
   * already uploaded. The required file is checked on submit instead.
   *
   * @param FieldBuildEvent $event
   *
   * @return void
   */
  public function buildField(FieldBuildEvent $event): void
  {
    $field = $event->getField();
    $class = $this->fileClass($event->getMapper());
    if (!$field instanceof UploadField || null === $class || !$this->fieldMapping($class, $field))
    {
      return;
    }
    $field->removeConstraint(NotNull::class);
    $field->removeConstraint(NotBlank::class);
    $field->setRequired(false);
    if ($field->hasAutoConstraints())
    {
      $field->addConstraint(new Length(max: 255, maxMessage: 'errors.length.max'));
      $field->addConstraint(new SvgSecurity());
    }
  }

  /**
   * @param FormProcessEvent $event
   *
   * @return void
   *
   * @throws \Exception
   */
  public function validate(FormProcessEvent $event): void
  {
    $object = $event->getData();
    if ($object instanceof FileInterface)
    {
      $this->fileUploader->validateRequiredFiles($event->getForm(), $object);
    }
  }

  /**
   * @param FormProcessEvent $event
   *
   * @return void
   *
   * @throws FormUploadException
   */
  public function uploads(FormProcessEvent $event): void
  {
    $object = $event->getData();
    if (!$object instanceof FileInterface)
    {
      return;
    }
    try
    {
      $this->fileUploader->uploadFiles($event->getForm(), $object);
    }
    catch (\Exception $exception)
    {
      throw new FormUploadException($exception->getMessage(), $exception);
    }
  }

  /**
   * Parameters of the upload from the file mapping, unless the Admin class gave them.
   *
   * @param FormMapper               $mapper
   * @param UploadField              $field
   * @param FieldFileMapping<object> $fieldMapping
   *
   * @return void
   */
  private function configureField(FormMapper $mapper, UploadField $field, FieldFileMapping $fieldMapping): void
  {
    $parameters = $fieldMapping->uploadParameters;
    if (!$field->getMimeTypes())
    {
      $field->setMimeTypes(array_values($parameters->mimeTypes));
    }
    $field->setTypeFile($parameters->getTypeFile());
    if (!$field->hasMaxSize() && $parameters->sizeMax)
    {
      $field->setMaxSize($parameters->sizeMax);
    }
    if (!$field->hasMaxSizeMessage())
    {
      $field->setMaxSizeMessage($parameters->errorMaxSize);
    }
    if (!$field->hasMimeTypesMessage())
    {
      $field->setMimeTypesMessage($parameters->errorMimeTypes);
    }
    if (!$field->getCropper() && $fieldMapping->croppers)
    {
      $croppers = [];
      foreach ($fieldMapping->croppers->croppers as $cropper)
      {
        $croppers[$cropper->key] = ['key' => $cropper->key, 'name' => $cropper->name, 'ratio' => $cropper->ratio, 'picto' => $cropper->picto];
      }
      $field->setCropper($croppers);
    }
    if (!$field->hasImageSizes() && $fieldMapping->imageSize && $fieldMapping->imageSize->hasLimit())
    {
      $imageSize = $fieldMapping->imageSize;
      $field->setImageSizes([
        'min_width' => null !== $imageSize->widthMin ? (int) $imageSize->widthMin : null,
        'max_width' => null !== $imageSize->widthMax ? (int) $imageSize->widthMax : null,
        'min_height' => null !== $imageSize->heightMin ? (int) $imageSize->heightMin : null,
        'max_height' => null !== $imageSize->heightMax ? (int) $imageSize->heightMax : null,
      ]);
    }

    $name = $field->getName();
    $field->setOptions([
      'required' => false,
      'setter' => static function (FileInterface $object, ?UploadedFile $uploadedFile = null) use ($name): void {
        $object->setUploadFileByFieldname($name, $uploadedFile);
      },
      'getter' => static fn (FileInterface $object): mixed => $object->getUploadFileByFieldname($name),
    ]);

    $deleteAttr = ['autocomplete' => 'off', 'data-delete-file' => ''];
    $attr = $field->getOption('attr');
    if (\is_array($attr) && isset($attr['data-popin-update-input']))
    {
      $deleteAttr['data-popin-update-input'] = $attr['data-popin-update-input'].'-delete';
    }
    $this->hiddenFieldset($mapper)->add(SymfonyField::create("{$name}DeleteFile", HiddenType::class, [
      'setter' => static function (FileInterface $object, mixed $value) use ($name): void {
        $object->setDeleteFileByFieldname($name, $value);
      },
      'getter' => static fn (FileInterface $object): mixed => $object->getDeleteFileByFieldname($name),
      'attr' => $deleteAttr,
    ]));

    if ($field->getCropper() || !$parameters->isRequired)
    {
      $mapper->addModal("popup-editor-{$name}", $name, [
        'button' => ['label' => 'actions.picture.edit', 'picto' => '', 'class' => 'button-action'],
        'modal' => ['template' => 'uploadEditor'],
      ]);
    }
  }

  /**
   * @param FormMapper $mapper
   *
   * @return void
   */
  private function addCropperFields(FormMapper $mapper): void
  {
    $this->hiddenFieldset($mapper)
      ->add(SymfonyField::create('cropperData', HiddenType::class, [
        'setter' => static function (CropperInterface $object, mixed $value): void {
          $object->setCropperData(\is_string($value) && '' !== $value ? (array) json_decode($value, true) : []);
        },
        'getter' => static fn (CropperInterface $object): string => (string) json_encode($object->getCropperData()),
        'attr' => ['data-cropper-data' => '', 'autocomplete' => 'off'],
      ]))
      ->add(SymfonyField::create('generateCropperByKey', HiddenType::class, [
        'setter' => static function (CropperInterface $object, mixed $value): void {
          $object->setGenerateCropperByKey((array) json_decode(\is_string($value) && '' !== $value ? $value : '[]', true));
        },
        'getter' => static fn (CropperInterface $object): string => (string) json_encode($object->getGenerateCropperByKey()),
        'attr' => ['data-cropper-key-generate' => '', 'autocomplete' => 'off'],
      ]));
  }

  /**
   * @param FormMapper $mapper
   *
   * @return Fieldset
   */
  private function hiddenFieldset(FormMapper $mapper): Fieldset
  {
    return $mapper->addFieldset(self::HIDDEN_FIELDSET, false, Fieldset::POSITION_NONE);
  }

  /**
   * Class of the data of the form when it handles files (FileInterface), null otherwise.
   *
   * @param FormMapper $mapper
   *
   * @return string|null
   */
  private function fileClass(FormMapper $mapper): ?string
  {
    $object = $mapper->getObject();
    $class = $object instanceof FileInterface ? $object->getClassnameForMapping() : $mapper->getDataClass();

    return null !== $class && is_a($class, FileInterface::class, true) ? $class : null;
  }

  /**
   * @param string      $class
   * @param UploadField $field
   *
   * @return FieldFileMapping<object>|null
   *
   * @throws \Throwable
   */
  private function fieldMapping(string $class, UploadField $field): ?FieldFileMapping
  {
    $fieldMapping = $this->mapping->getFieldsMappingByFieldname($class, FieldFileMapping::class, $field->getName());

    return $fieldMapping instanceof FieldFileMapping ? $fieldMapping : null;
  }
}

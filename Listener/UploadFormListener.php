<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Listener;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Annotation\Cropper;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\Entity\Interfaces\EntityFileInterface;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\Exception\FormUploadException;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\FormBundle\Field as Field;
use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterFileCropperTrait;
use Austral\FormBundle\Event\FormEvent;

use Austral\FormBundle\Event\FormFieldEvent;
use Austral\FormBundle\Field\UploadField;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints as Constraints;

use \Exception;

/**
 * Austral UploadFormListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UploadFormListener
{

  /**
   * @var FileUploader
   */
  protected FileUploader $fileUploader;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * @var Generator
   */
  protected Generator $fileLinkGenerator;

  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * FormListener constructor.
   *
   * @param FileUploader $fileUploader
   * @param UploadsConfiguration $uploadsConfiguration
   * @param CropperConfiguration $cropperConfiguration
   * @param Generator $fileLinkGenerator
   * @param Mapping $mapping
   */
  public function __construct(FileUploader $fileUploader,
    UploadsConfiguration $uploadsConfiguration,
    CropperConfiguration $cropperConfiguration,
    Generator $fileLinkGenerator,
    Mapping $mapping
  )
  {
    $this->fileUploader = $fileUploader;
    $this->uploadsConfiguration = $uploadsConfiguration;
    $this->cropperConfiguration = $cropperConfiguration;
    $this->fileLinkGenerator = $fileLinkGenerator;
    $this->mapping = $mapping;
  }

  /**
   * @param FormEvent $formEvent
   *
   * @return void
   * @throws Exception
   */
  public function validate(FormEvent $formEvent)
  {
    if($formEvent->getFormMapper()->getObject() instanceof EntityFileInterface)
    {
      if($formEvent->getForm())
      {
        $objects = array();
        $data = $formEvent->getForm()->getData();
        if($data instanceof Collection)
        {
          $objects = $data->toArray();
        }
        else
        {
          $objects[] = $data;
        }

        /** @var EntityFileInterface|EntityFileCropperTrait $object */
        foreach($objects as $object)
        {
          $this->fileUploader->validateRequiredFiles($formEvent->getForm(), $object);
        }
      }
    }
  }

  /**
   * @param FormEvent $formEvent
   */
  public function uploads(FormEvent $formEvent)
  {
    try {
      if($formEvent->getFormMapper()->getObject() instanceof EntityFileInterface)
      {
        $objects = array();
        if($formEvent->getForm())
        {
          $data = $formEvent->getForm()->getData();
          if($data instanceof Collection)
          {
            $objects = $data->toArray();
          }
          else
          {
            $objects[] = $data;
          }

          /** @var EntityFileInterface|EntityFileCropperTrait $object */
          foreach($objects as $object)
          {
            $this->fileUploader->uploadFiles($formEvent->getForm(), $object);
          }
        }
      }
    } catch(Exception $e) {
      $formEvent->getFormMapper()->setFormStatus("exception");
      throw new FormUploadException($e->getMessage());
    }
  }

  /**
   * @param FormEvent $formEvent
   * @param null $type
   *
   * @throws \ReflectionException
   * @throws Exception
   */
  public function formAddAutoFields(FormEvent $formEvent, $type = null)
  {
    $object = $formEvent->getFormMapper()->getObject();
    if(AustralTools::usedClass($object, EntityTranslateMasterFileCropperTrait::class) || AustralTools::usedClass($object, EntityFileCropperTrait::class) )
    {
      $formEvent->getFormMapper()->add(Field\SymfonyField::create("cropperData", HiddenType::class,  array(
        "setter"  =>  function($object, $value){
          $object->setCropperData(json_decode($value, true));
        },
        "getter"  =>  function($object){
          return json_encode($object->getCropperData());
        },
        "attr"  =>  array(
          'data-cropper-data' => "",
          "autocomplete" => "off"
        )
      )));
      $formEvent->getFormMapper()->add(Field\SymfonyField::create("generateCropperByKey", HiddenType::class, array(
        "setter"  =>  function($object, $value){
          $object->setGenerateCropperByKey(json_decode($value ? : "[]", true));
        },
        "getter"  =>  function($object){
          return json_encode($object->getGenerateCropperByKey());
        },
        "attr"  =>  array(
          'data-cropper-key-generate' => "",
          "autocomplete" => "off"
        )
      )));
    }
  }

  /**
   * @param FormFieldEvent $formFieldEvent
   *
   * @throws Exception
   */
  public function fieldConfiguration(FormFieldEvent $formFieldEvent)
  {
    $object = $formFieldEvent->getFormMapper()->getObject();
    if($formFieldEvent->getField() instanceof UploadField && $object instanceof EntityFileInterface)
    {
      if($fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $formFieldEvent->getField()->getFieldname()))
      {
        /** @var UploadField $field */
        $field = $formFieldEvent->getField();

        if(!$field->getMimeTypes())
        {
          $field->setMimeTypes($fieldMapping->uploadParameters->mimeTypes);
        }

        $field->setTypeFile($fieldMapping->uploadParameters->getTypeFile());

        if(!$field->getMaxSize(false, false) && $fieldMapping->uploadParameters->sizeMax)
        {
          $field->setMaxSize($fieldMapping->uploadParameters->sizeMax);
        }

        if(!$field->getMaxSizeMessage(false))
        {
          $field->setMaxSizeMessage($fieldMapping->uploadParameters->errorMaxSize);
        }

        if(!$field->getMimeTypesMessage(false))
        {
          $field->setMimeTypesMessage($fieldMapping->uploadParameters->errorMimeTypes);
        }

        if(!$field->getCropper() && $fieldMapping->croppers)
        {
          $cropperFinal = array();
          /** @var Cropper $cropper */
          foreach($fieldMapping->croppers->croppers as $cropper)
          {
            $cropperFinal[$cropper->name] = (array) $cropper;
          }
          $field->setCropper($cropperFinal);
        }

        if(!$field->getImageSizes() && $fieldMapping->imageSize && $fieldMapping->imageSize->hasLimit())
        {
          $field->setImageSizes(array(
            "minWidth"      =>  (int) $fieldMapping->imageSize->widthMin,
            "maxWidth"      =>  (int) $fieldMapping->imageSize->widthMax,
            "minHeight"     =>  (int) $fieldMapping->imageSize->heightMin,
            "maxHeight"     =>  (int) $fieldMapping->imageSize->heightMax,
          ));
        }

        if($field->getAutoConstraints()) {
          $field->addConstraint(new Constraints\Length(array(
                "max" => "255",
                "maxMessage" => "errors.length.max"
              )
            )
          );
          $field->addConstraint(new File(array(
                'maxSize' => $field->getMaxSize(),
                'mimeTypes' => $field->getMimeTypes(),
                'maxSizeMessage' => $field->getMaxSizeMessage(),
                'mimeTypesMessage' => $field->getMimeTypesMessage()
              )
            )
          );
          if($imageSizes = $field->getImageSizes())
          {
            $field->addConstraint(new Constraints\Image(array(
                  'minWidth'  => $imageSizes["minWidth"],
                  'maxWidth'  => $imageSizes["maxWidth"],
                  'minHeight' => $imageSizes["minHeight"],
                  'maxHeight' => $imageSizes["maxHeight"]
                )
              )
            );
          }
        }

        if($field->hasContraint(Constraints\NotNull::class))
        {
          $field->removeContraintByClass(Constraints\NotNull::class);
        }

        if($field->hasContraint(Constraints\NotBlank::class))
        {
          $field->removeContraintByClass(Constraints\NotBlank::class);
        }
        $field->setRequired(false);

        $field->addOption("setter", function(EntityFileInterface $object, ?UploadedFile $uploadFile = null) use ($field){
          $object->setUploadFileByFieldname($field->getFieldname(), $uploadFile);
        });
        $field->addOption("getter", function(EntityFileInterface $object) use ($field){
          return $object->getUploadFileByFieldname($field->getFieldname());
        });


        $fieldDeleteAttr = array(
          "autocomplete" => "off",
          "data-delete-file" => ""
        );
        if(array_key_exists("data-popin-update-input", $field->getOptions()['attr']))
        {
          $fieldDeleteAttr["data-popin-update-input"] = $field->getOptions()['attr']["data-popin-update-input"]."-delete";
        }

        $formFieldEvent->getFormMapper()->add(Field\SymfonyField::create("{$field->getFieldname()}DeleteFile", HiddenType::class, array(
          "setter"  =>  function(EntityFileInterface $object, $value) use($field) {
            $object->setDeleteFileByFieldname($field->getFieldname(), $value);
          },
          "getter"  =>  function(EntityFileInterface $object) use($field){
            return $object->getDeleteFileByFieldname($field->getFieldname());
          },
          "attr"  =>  $fieldDeleteAttr
        )));

        if($field->getCropper() || !$fieldMapping->uploadParameters->isRequired) {
          $formFieldEvent->getFormMapper()->addPopin("popup-editor-{$field->getFieldname()}", $field->getFieldname(), array(
              "button"  =>  array(
                "entitled"            =>  "actions.picture.edit",
                "picto"               =>  "",
                "class"               =>  "button-action"
              ),
              "popin"  =>  array(
                "id"            =>  "upload",
                "template"      =>  "uploadEditor",
              )
            )
          );
        }
      }
    }
  }

}
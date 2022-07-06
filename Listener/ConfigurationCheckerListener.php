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


use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Austral\AdminBundle\Configuration\ConfigurationCheckerValue;
use Austral\AdminBundle\Event\ConfigurationCheckerEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use function Symfony\Component\String\u;

/**
 * Austral ConfigurationChecker Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationCheckerListener
{

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @param Mapping $mapping
   * @param UploadsConfiguration $uploadsConfiguration
   * @param CropperConfiguration $cropperConfiguration
   */
  public function __construct(Mapping $mapping, UploadsConfiguration $uploadsConfiguration, CropperConfiguration $cropperConfiguration)
  {
    $this->mapping = $mapping;
    $this->uploadsConfiguration = $uploadsConfiguration;
    $this->cropperConfiguration = $cropperConfiguration;
  }

  /**
   * @param ConfigurationCheckerEvent $configurationCheckerEvent
   */
  public function configurationChecker(ConfigurationCheckerEvent $configurationCheckerEvent)
  {

    $configurationChecker = $configurationCheckerEvent->getConfigurationChecker();

    $configurationCheckerUploads = $configurationCheckerEvent->addConfiguration("uploads", $configurationChecker)
      ->setName("configuration.check.uploads.title")
      ->setIsTranslatable(true)
      ->setDescription("configuration.check.uploads.description");


    $this->createConfigurationByKeyConfigDefault($configurationCheckerUploads, "default_image", $this->uploadsConfiguration->getConfig("default_image"));
    $this->createConfigurationByKeyConfigDefault($configurationCheckerUploads, "default_file", $this->uploadsConfiguration->getConfig("default_image"));

    /**
     * @var EntityMapping $entityMapping
     */
    foreach($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      /** @var FieldFileMapping $fieldFileMapping */
      foreach ($entityMapping->getFieldsMappingByClass(FieldFileMapping::class) as $fieldFileMapping)
      {
        $this->createConfigurationByKeyConfig($configurationCheckerUploads, $entityMapping, $fieldFileMapping);
      }
    }
  }

  /**
   * @param ConfigurationChecker $configurationCheckerUploads
   * @param string $keyConfig
   * @param array $config
   *
   * @return $this
   */
  protected function createConfigurationByKeyConfigDefault(ConfigurationChecker $configurationCheckerUploads, string $keyConfig, array $config): ConfigurationCheckerListener
  {
    $configurationCheckerDefault = new ConfigurationChecker($keyConfig);
    $configurationCheckerDefault->setName("configuration.check.uploads.{$keyConfig}.title")
      ->setIsTranslatable(true)
      ->setWidth(ConfigurationChecker::$WIDTH_FULL)
      ->setParent($configurationCheckerUploads);
    $this->createConfigurationCheckerValues($configurationCheckerDefault, $keyConfig, "all", "default");
    return $this;
  }


  /**
   * @param ConfigurationChecker $configurationCheckerUploads
   * @param EntityMapping $entityMapping
   * @param FieldFileMapping $fieldFileMapping
   *
   * @return $this
   */
  protected function createConfigurationByKeyConfig(ConfigurationChecker $configurationCheckerUploads, EntityMapping $entityMapping, FieldFileMapping $fieldFileMapping): ConfigurationCheckerListener
  {
    $configurationCheckerDefault = new ConfigurationChecker("{$entityMapping->slugger}_{$fieldFileMapping->getFieldname()}");
    $configurationCheckerDefault->setName($entityMapping->entityClass)
      ->setIsTranslatable(false)
      ->setWidth(ConfigurationChecker::$WIDTH_FULL)
      ->setParent($configurationCheckerUploads);
    $configurationCheckerValue = new ConfigurationCheckerValue("fieldname", $configurationCheckerDefault);
    $configurationCheckerValue->setName("configuration.check.uploads.fieldname")
      ->setIsTranslatable(true)
      ->setType(ConfigurationCheckerValue::$TYPE_STRING)
      ->setValue($fieldFileMapping->getFieldname());

    $configurationCheckerValue = new ConfigurationCheckerValue("path_uploads", $configurationCheckerDefault);
    $configurationCheckerValue->setName("configuration.check.uploads.path.uploads.entitled")
      ->setIsTranslatable(true)
      ->setType(ConfigurationCheckerValue::$TYPE_STRING)
      ->setValue($fieldFileMapping->path->upload);

    if($fieldFileMapping->uploadParameters->sizeMax)
    {
      $configurationCheckerValue = new ConfigurationCheckerValue("max_size", $configurationCheckerDefault);
      $configurationCheckerValue->setName("configuration.check.uploads.form.size_max.entitled")
        ->setIsTranslatable(true)
        ->setStatus(ConfigurationCheckerValue::$STATUS_VALUE)
        ->setType(ConfigurationCheckerValue::$TYPE_CHECKED)
        ->setValue($fieldFileMapping->uploadParameters->sizeMax);
    }

    if($fieldFileMapping->uploadParameters->mimeTypes)
    {
      $configurationCheckerValue = new ConfigurationCheckerValue("mime_types", $configurationCheckerDefault);
      $configurationCheckerValue->setName("configuration.check.uploads.form.mime_types.entitled")
        ->setIsTranslatable(true)
        ->setStatus(ConfigurationCheckerValue::$STATUS_NONE)
        ->setType(ConfigurationCheckerValue::$TYPE_ARRAY_WITHOUT_INDEX)
        ->setValues($fieldFileMapping->uploadParameters->mimeTypes);
    }
    else
    {
      $configurationCheckerValue = new ConfigurationCheckerValue("mime_types", $configurationCheckerDefault);
      $configurationCheckerValue->setName("configuration.check.uploads.form.mime_types.entitled")
        ->setIsTranslatable(true)
        ->setStatus(ConfigurationCheckerValue::$STATUS_NONE)
        ->setType(ConfigurationCheckerValue::$TYPE_STRING)
        ->setIsTranslatableValue(true)
        ->setValue("configuration.check.uploads.form.mime_types.all");


    }

    if($fieldFileMapping->imageSize)
    {
      if($fieldFileMapping->imageSize->widthMin || $fieldFileMapping->imageSize->heightMin)
      {
        $configurationCheckerValue = new ConfigurationCheckerValue("image_size_min", $configurationCheckerDefault);
        $configurationCheckerValue->setName("configuration.check.uploads.form.image.size_min.entitled")
          ->setIsTranslatable(true)
          ->setType(ConfigurationCheckerValue::$TYPE_STRING)
          ->setValue(($fieldFileMapping->imageSize->widthMin ? : "...")." x ".($fieldFileMapping->imageSize->heightMin ? : "...")." px");
      }

      if($fieldFileMapping->imageSize->widthMax || $fieldFileMapping->imageSize->heightMax)
      {
        $configurationCheckerValue = new ConfigurationCheckerValue("image_size_max", $configurationCheckerDefault);
        $configurationCheckerValue->setName("configuration.check.uploads.form.image.size_max.entitled")
          ->setIsTranslatable(true)
          ->setType(ConfigurationCheckerValue::$TYPE_STRING)
          ->setValue(($fieldFileMapping->imageSize->widthMax ? : "...")." x ".($fieldFileMapping->imageSize->heightMax ? : "...")." px");
      }
    }
    return $this;
  }

  /**
   * @param ConfigurationChecker $configurationCheckerDefault
   * @param string $keyConfig
   * @param string $keyname
   * @param string $fieldname
   *
   * @return $this
   */
  protected function createConfigurationCheckerValues(ConfigurationChecker $configurationCheckerDefault, string $keyConfig, string $keyname, string $fieldname): ConfigurationCheckerListener
  {
    foreach(array('size.max', "mimeTypes") as $keyValue)
    {
      $keyValueUnderscorize = u($keyValue)->snake()->toString();

      $value = $this->uploadsConfiguration->get("{$keyConfig}.{$keyValue}");
      $type = is_array($value) ? ConfigurationCheckerValue::$TYPE_ARRAY_WITHOUT_INDEX : ConfigurationCheckerValue::$TYPE_CHECKED;

      $configurationCheckerValue = new ConfigurationCheckerValue("{$keyname}_{$keyValue}", $configurationCheckerDefault);
      $configurationCheckerValue->setName("configuration.check.uploads.form.{$keyValueUnderscorize}.entitled")
        ->setIsTranslatable(true)
        ->setStatus($keyValue == "size.max" ? ConfigurationCheckerValue::$STATUS_VALUE : ConfigurationCheckerValue::$STATUS_NONE)
        ->setType($type);
      if($type == "array_without_index" || $keyValue == "mimeTypes")
      {
        if(is_array($value) && count($value) > 0)
        {
          $configurationCheckerValue->setValues($value);
        }
        else
        {
          $configurationCheckerValue->setType("string")
            ->setIsTranslatableValue(true)
            ->setValue("configuration.check.uploads.form.{$keyValueUnderscorize}.all");
        }
      }
      else
      {
        $configurationCheckerValue->setValue($value ? : "all");
      }
    }
    return $this;
  }

}
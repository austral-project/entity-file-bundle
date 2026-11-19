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

use Austral\EntityBundle\EntityAttributes\EntityAttributes;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityFileBundle\Attribute as AustralFile;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\ImageSizeConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityAttribute Listener.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityMappingListener
{
  /**
   * @param ContainerInterface     $container
   * @param UploadsConfiguration   $uploadsConfiguration
   * @param CropperConfiguration   $cropperConfiguration
   * @param ImageSizeConfiguration $imageSizeConfiguration
   */
  public function __construct(
    #[Autowire(service: 'service_container')] protected ContainerInterface $container,
    #[Autowire(service: 'austral.entity_file.uploads.config')] protected UploadsConfiguration $uploadsConfiguration,
    #[Autowire(service: 'austral.entity_file.cropper.config')] protected CropperConfiguration $cropperConfiguration,
    #[Autowire(service: 'austral.entity_file.image_size.config')] protected ImageSizeConfiguration $imageSizeConfiguration,
  ) {
  }

  /**
   * @param EntityMappingEvent<T> $entityAttributeEvent
   *
   * @return void
   *
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityAttributeEvent): void
  {
    $initialiseEntitesAttributes = $entityAttributeEvent->getEntitiesAttributes();

    /**
     * @var EntityAttributes<T> $entityAttribute
     */
    foreach ($initialiseEntitesAttributes->all() as $entityAttribute)
    {
      foreach ($entityAttribute->getFieldsAttributes() as $fieldname => $attributes)
      {
        $attributesEnabled = [];
        foreach ($attributes as $attribute)
        {
          if (AustralTools::usedImplements($attribute, AustralFile\EntityFileAttributeInterface::class))
          {
            $attributesEnabled[$attribute->getClassname()] = $attribute;
          }
        }

        if (count($attributesEnabled) > 0)
        {
          if (!$entityMapping = $entityAttributeEvent->getMapping()->getEntityMapping($entityAttribute->getClassname()))
          {
            $entityMapping = new EntityMapping($entityAttribute->getClassname(), $entityAttribute->getSlugger());
          }
          if (!array_key_exists(AustralFile\UploadParameters::class, $attributesEnabled))
          {
            $attributesEnabled[AustralFile\UploadParameters::class] = new AustralFile\UploadParameters('default_image');
            $attributesEnabled[AustralFile\UploadParameters::class]->keyname = $fieldname;
          }
          if ($attributesEnabled[AustralFile\UploadParameters::class]->configName)
          {
            if (!$attributesEnabled[AustralFile\UploadParameters::class]->mimeTypes)
            {
              $attributesEnabled[AustralFile\UploadParameters::class]->mimeTypes = $this->uploadsConfiguration->get("{$attributesEnabled[AustralFile\UploadParameters::class]->configName}.mimeTypes", []);
            }
            if (!$attributesEnabled[AustralFile\UploadParameters::class]->sizeMax)
            {
              $attributesEnabled[AustralFile\UploadParameters::class]->sizeMax = $this->uploadsConfiguration->get("{$attributesEnabled[AustralFile\UploadParameters::class]->configName}.size.max", null);
            }
            if (!$attributesEnabled[AustralFile\UploadParameters::class]->isRequired)
            {
              $attributesEnabled[AustralFile\UploadParameters::class]->isRequired = $this->uploadsConfiguration->get("{$attributesEnabled[AustralFile\UploadParameters::class]->configName}.required", false);
            }
            if (!$attributesEnabled[AustralFile\UploadParameters::class]->errorMaxSize)
            {
              $attributesEnabled[AustralFile\UploadParameters::class]->errorMaxSize = $this->uploadsConfiguration->get("{$attributesEnabled[AustralFile\UploadParameters::class]->configName}.errors.maxSize", 'file.errors.maxSize');
            }
            if (!$attributesEnabled[AustralFile\UploadParameters::class]->errorMimeTypes)
            {
              $attributesEnabled[AustralFile\UploadParameters::class]->errorMimeTypes = $this->uploadsConfiguration->get("{$attributesEnabled[AustralFile\UploadParameters::class]->configName}.errors.mimeTypes", 'file.errors.mimeTypes');
            }
          }

          if (!array_key_exists(AustralFile\Path::class, $attributesEnabled))
          {
            $attributesEnabled[AustralFile\Path::class] = new AustralFile\Path();
            $attributesEnabled[AustralFile\Path::class]->keyname = $fieldname;
          }
          $attributesEnabled[AustralFile\Path::class]->upload = $this->replacePath($attributesEnabled[AustralFile\Path::class]->upload, $entityAttribute->getSlugger());
          $attributesEnabled[AustralFile\Path::class]->thumbnail = $this->replacePath($attributesEnabled[AustralFile\Path::class]->thumbnail, $entityAttribute->getSlugger());

          if (array_key_exists(AustralFile\ImageSize::class, $attributesEnabled))
          {
            if (!$attributesEnabled[AustralFile\ImageSize::class]->configName)
            {
              $attributesEnabled[AustralFile\ImageSize::class]->configName = 'default';
            }
            if (!$attributesEnabled[AustralFile\ImageSize::class]->widthMin)
            {
              $attributesEnabled[AustralFile\ImageSize::class]->widthMin = $this->imageSizeConfiguration->get("{$attributesEnabled[AustralFile\ImageSize::class]->configName}.width.min", null);
            }
            if (!$attributesEnabled[AustralFile\ImageSize::class]->widthMax)
            {
              $attributesEnabled[AustralFile\ImageSize::class]->widthMax = $this->imageSizeConfiguration->get("{$attributesEnabled[AustralFile\ImageSize::class]->configName}.width.max", null);
            }
            if (!$attributesEnabled[AustralFile\ImageSize::class]->heightMin)
            {
              $attributesEnabled[AustralFile\ImageSize::class]->heightMin = $this->imageSizeConfiguration->get("{$attributesEnabled[AustralFile\ImageSize::class]->configName}.height.min", null);
            }
            if (!$attributesEnabled[AustralFile\ImageSize::class]->heightMax)
            {
              $attributesEnabled[AustralFile\ImageSize::class]->heightMax = $this->imageSizeConfiguration->get("{$attributesEnabled[AustralFile\ImageSize::class]->configName}.height.max", null);
            }
          }
          if (array_key_exists(AustralFile\Croppers::class, $attributesEnabled))
          {
            if (!$attributesEnabled[AustralFile\Croppers::class]->croppers)
            {
              unset($attributesEnabled[AustralFile\Croppers::class]);
            }
            else
            {
              foreach ($attributesEnabled[AustralFile\Croppers::class]->croppers as $key => $cropper)
              {
                if (!$cropper instanceof AustralFile\Cropper)
                {
                  if (!$this->cropperConfiguration->getConfig($cropper))
                  {
                    throw new \Exception("{$cropper} cropper name is not defined !!!");
                  }
                  $cropperObject = new AustralFile\Cropper(
                    $this->cropperConfiguration->get("{$cropper}.name", null),
                    $this->cropperConfiguration->get("{$cropper}.picto", null),
                    $this->cropperConfiguration->get("{$cropper}.ratio", null)
                  );
                  $cropperObject->key = $cropper;
                }
                else
                {
                  $cropperObject = $cropper;
                }
                unset($attributesEnabled[AustralFile\Croppers::class]->croppers[$key]);
                $attributesEnabled[AustralFile\Croppers::class]->croppers[$cropperObject->key] = $cropperObject;
              }
            }
          }

          /** @var AustralFile\UploadParameters $attributesUploadParameters */
          $attributesUploadParameters = $attributesEnabled[AustralFile\UploadParameters::class];

          /** @var AustralFile\Path $attributesPath */
          $attributesPath = $attributesEnabled[AustralFile\Path::class];

          /** @var FieldFileMapping<T> $fieldFileMapping */
          $fieldFileMapping = new FieldFileMapping(
            $attributesUploadParameters,
            $attributesPath,
            array_key_exists(AustralFile\ImageSize::class, $attributesEnabled) ? $attributesEnabled[AustralFile\ImageSize::class] : null,
            array_key_exists(AustralFile\Croppers::class, $attributesEnabled) ? $attributesEnabled[AustralFile\Croppers::class] : null
          );

          $entityMapping->addFieldMapping($fieldname, $fieldFileMapping);
          $entityAttributeEvent->getMapping()->addEntityMapping($entityAttribute->getClassname(), $entityMapping);
        }
      }
    }
  }

  /**
   * @param string $path
   * @param string $entitySlugger
   *
   * @return string
   */
  protected function replacePath(string $path, string $entitySlugger): string
  {
    preg_match_all("|%(\S+)%|iuU", $path, $matches, PREG_SET_ORDER);
    foreach ($matches as $match)
    {
      $parameter = $this->container->getParameter($match[1]);
      $path = $parameter && is_string($parameter) ? str_replace($match[0], $parameter, $path) : $path;
    }

    return str_replace('@entity_slugger_case@', $entitySlugger, $path);
  }
}

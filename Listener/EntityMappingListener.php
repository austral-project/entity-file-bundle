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


use Austral\EntityBundle\Annotation\AustralEntityAnnotationInterface;
use Austral\EntityBundle\EntityAnnotation\EntityAnnotations;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityFileBundle\Annotation\Cropper;
use Austral\EntityFileBundle\Annotation\Croppers;
use Austral\EntityFileBundle\Annotation\ImageSize;
use Austral\EntityFileBundle\Annotation\Path;
use Austral\EntityFileBundle\Annotation\EntityFileAnnotationInterface;
use Austral\EntityFileBundle\Annotation\UploadParameters;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\ImageSizeConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityAnnotation Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityMappingListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @var ImageSizeConfiguration
   */
  protected ImageSizeConfiguration $imageSizeConfiguration;

  /**
   * @param ContainerInterface $container
   * @param UploadsConfiguration $uploadsConfiguration
   * @param CropperConfiguration $cropperConfiguration
   * @param ImageSizeConfiguration $imageSizeConfiguration
   */
  public function __construct(ContainerInterface $container,
    UploadsConfiguration $uploadsConfiguration,
    CropperConfiguration $cropperConfiguration,
    ImageSizeConfiguration $imageSizeConfiguration
  )
  {
    $this->container = $container;
    $this->uploadsConfiguration = $uploadsConfiguration;
    $this->cropperConfiguration = $cropperConfiguration;
    $this->imageSizeConfiguration = $imageSizeConfiguration;
  }


  /**
   * @param EntityMappingEvent $entityAnnotationEvent
   *
   * @return void
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityAnnotationEvent)
  {
    $initialiseEntitesAnnotations = $entityAnnotationEvent->getEntitiesAnnotations();

    /**
     * @var EntityAnnotations $entityAnnotation
     */
    foreach($initialiseEntitesAnnotations->all() as $entityAnnotation)
    {
      foreach($entityAnnotation->getFieldsAnnotations() as $fieldname => $annotations)
      {
        $annotationsToFile = [];
        /** @var AustralEntityAnnotationInterface $annotation */
        foreach($annotations as $annotation)
        {
          if(AustralTools::usedImplements($annotation, EntityFileAnnotationInterface::class))
          {
            $annotationsToFile[$annotation->getClassname()] = $annotation;
          }
        }

        if(count($annotationsToFile) > 0)
        {
          if(!$entityMapping = $entityAnnotationEvent->getMapping()->getEntityMapping($entityAnnotation->getClassname()))
          {
            $entityMapping = new EntityMapping($entityAnnotation->getClassname(), $entityAnnotation->getSlugger());
          }
          if(!array_key_exists(UploadParameters::class, $annotationsToFile))
          {
            $annotationsToFile[UploadParameters::class] = new UploadParameters("default_image");
            $annotationsToFile[UploadParameters::class]->keyname = $fieldname;
          }
          if($annotationsToFile[UploadParameters::class]->configName)
          {
            if(!$annotationsToFile[UploadParameters::class]->mimeTypes)
            {
              $annotationsToFile[UploadParameters::class]->mimeTypes = $this->uploadsConfiguration->get("{$annotationsToFile[UploadParameters::class]->configName}.mimeTypes", array());
            }
            if(!$annotationsToFile[UploadParameters::class]->sizeMax)
            {
              $annotationsToFile[UploadParameters::class]->sizeMax = $this->uploadsConfiguration->get("{$annotationsToFile[UploadParameters::class]->configName}.size.max", null);
            }
            if(!$annotationsToFile[UploadParameters::class]->isRequired)
            {
              $annotationsToFile[UploadParameters::class]->isRequired = $this->uploadsConfiguration->get("{$annotationsToFile[UploadParameters::class]->configName}.required", false);
            }
            if(!$annotationsToFile[UploadParameters::class]->errorMaxSize)
            {
              $annotationsToFile[UploadParameters::class]->errorMaxSize = $this->uploadsConfiguration->get("{$annotationsToFile[UploadParameters::class]->configName}.errors.maxSize", "file.errors.maxSize");
            }
            if(!$annotationsToFile[UploadParameters::class]->errorMimeTypes)
            {
              $annotationsToFile[UploadParameters::class]->errorMimeTypes = $this->uploadsConfiguration->get("{$annotationsToFile[UploadParameters::class]->configName}.errors.mimeTypes", "file.errors.mimeTypes");
            }
          }

          if(!array_key_exists(Path::class, $annotationsToFile))
          {
            $annotationsToFile[Path::class] = new Path();
            $annotationsToFile[Path::class]->keyname = $fieldname;
          }
          $annotationsToFile[Path::class]->upload = $this->replacePath($annotationsToFile[Path::class]->upload, $entityAnnotation->getSlugger());
          $annotationsToFile[Path::class]->thumbnail = $this->replacePath($annotationsToFile[Path::class]->thumbnail, $entityAnnotation->getSlugger());

          if(array_key_exists(ImageSize::class, $annotationsToFile))
          {
            if(!$annotationsToFile[ImageSize::class]->configName)
            {
              $annotationsToFile[ImageSize::class]->configName = "default";
            }
            if(!$annotationsToFile[ImageSize::class]->widthMin === null)
            {
              $annotationsToFile[ImageSize::class]->widthMin = $this->imageSizeConfiguration->get("{$annotationsToFile[ImageSize::class]->configName}.width.min", null);
            }
            if(!$annotationsToFile[ImageSize::class]->widthMax === null)
            {
              $annotationsToFile[ImageSize::class]->widthMax = $this->imageSizeConfiguration->get("{$annotationsToFile[ImageSize::class]->configName}.width.max", null);
            }
            if(!$annotationsToFile[ImageSize::class]->heightMin === null)
            {
              $annotationsToFile[ImageSize::class]->heightMin = $this->imageSizeConfiguration->get("{$annotationsToFile[ImageSize::class]->configName}.height.min", null);
            }
            if(!$annotationsToFile[ImageSize::class]->heightMax === null)
            {
              $annotationsToFile[ImageSize::class]->heightMax = $this->imageSizeConfiguration->get("{$annotationsToFile[ImageSize::class]->configName}.height.max", null);
            }
          }
          if(array_key_exists(Croppers::class, $annotationsToFile))
          {
            if(!$annotationsToFile[Croppers::class]->croppers)
            {
              unset($annotationsToFile[Croppers::class]);
            }
            else
            {
              foreach($annotationsToFile[Croppers::class]->croppers as $key => $cropper)
              {
                if(!$cropper instanceof Cropper)
                {
                  if(!$this->cropperConfiguration->getConfig($cropper))
                  {
                    throw new \Exception("{$cropper} cropper name is not defined !!!");
                  }
                  $cropperObject = new Cropper(
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
                unset($annotationsToFile[Croppers::class]->croppers[$key]);
                $annotationsToFile[Croppers::class]->croppers[$cropperObject->key] = $cropperObject;
              }
            }
          }

          $entityMapping->addFieldMapping($fieldname, new FieldFileMapping($annotationsToFile[UploadParameters::class],
              $annotationsToFile[Path::class],
              array_key_exists(ImageSize::class, $annotationsToFile) ? $annotationsToFile[ImageSize::class] : null,
              array_key_exists(Croppers::class, $annotationsToFile) ? $annotationsToFile[Croppers::class] : null
            )
          );
          $entityAnnotationEvent->getMapping()->addEntityMapping($entityAnnotation->getClassname(), $entityMapping);
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
    foreach($matches as $match)
    {
      $path = str_replace($match[0], $this->container->getParameter($match[1]), $path);
    }
    return str_replace("@entity_slugger_case@", $entitySlugger, $path);
  }

}

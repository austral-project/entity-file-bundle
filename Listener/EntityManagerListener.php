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


use Austral\EntityBundle\Event\EntityManagerEvent;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;
use Austral\ToolsBundle\AustralTools;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityManager Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityManagerListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var FileUploader
   */
  protected FileUploader $fileUploader;


  /**
   * @param ContainerInterface $container
   * @param Mapping $mapping
   * @param FileUploader $fileUploader
   */
  public function __construct(ContainerInterface $container,
    Mapping $mapping,
    FileUploader $fileUploader
  )
  {
    $this->container = $container;
    $this->mapping = $mapping;
    $this->fileUploader = $fileUploader;
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @throws Exception
   */
  public function duplicate(EntityManagerEvent $entityManagerEvent)
  {
    $this->copyFilenameOrFile($entityManagerEvent, "filename");
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   *
   * @throws Exception
   */
  public function copyFile(EntityManagerEvent $entityManagerEvent)
  {
    $this->copyFilenameOrFile($entityManagerEvent, "file");
  }

  /**
   * @param EntityManagerEvent $entityManagerEvent
   * @param string $type
   *
   * @return void
   * @throws Exception
   */
  protected function copyFilenameOrFile(EntityManagerEvent $entityManagerEvent, string $type)
  {

    if(AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), TranslateChildInterface::class))
    {
      $objectMaster = $entityManagerEvent->getObject()->getMaster();
    }
    else
    {
      $objectMaster = $entityManagerEvent->getObject();
    }

    if(AustralTools::usedImplements(get_class($objectMaster), FileInterface::class))
    {
      /** @var FieldFileMapping $fieldFileMapping */
      foreach($this->mapping->getFieldsMappingByClass($objectMaster->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {

        /** @var FileInterface $sourceObject */
        $sourceObject = $entityManagerEvent->getSourceObject();

        /** @var FileInterface $destinationObject */
        $destinationObject = $entityManagerEvent->getObject();

        if($sourceObject && $destinationObject)
        {
          if($type === "file")
          {
            $this->fileUploader->copyFile($fieldFileMapping, $sourceObject, $destinationObject);
          }
          elseif($type === "filename")
          {
            $this->fileUploader->copyFilename($fieldFileMapping, $sourceObject, $destinationObject);
          }
        }
      }
    }
  }


}

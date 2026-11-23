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

use Austral\Contracts\File\FileInterface;
use Austral\Contracts\Translate\TranslateChildInterface;
use Austral\EntityBundle\Event\EntityManagerEvent;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral EntityManager Listener.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityManagerListener
{
  /**
   * @param Mapping<T>      $mapping
   * @param FileUploader<T> $fileUploader
   */
  public function __construct(protected Mapping $mapping, protected FileUploader $fileUploader)
  {
  }

  /**
   * @param EntityManagerEvent<T> $entityManagerEvent
   *
   * @throws \Exception
   */
  public function duplicate(EntityManagerEvent $entityManagerEvent): void
  {
    $this->copyFilenameOrFile($entityManagerEvent, 'filename');
  }

  /**
   * @param EntityManagerEvent<T> $entityManagerEvent
   *
   * @throws \Exception
   */
  public function copyFile(EntityManagerEvent $entityManagerEvent): void
  {
    $this->copyFilenameOrFile($entityManagerEvent, 'file');
  }

  /**
   * @param EntityManagerEvent<T> $entityManagerEvent
   * @param string                $type
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function copyFilenameOrFile(EntityManagerEvent $entityManagerEvent, string $type): void
  {
    if (AustralTools::usedImplements(get_class($entityManagerEvent->getObject()), TranslateChildInterface::class))
    {
      /** @var TranslateChildInterface<T> $object */
      $object = $entityManagerEvent->getObject();
      $objectMaster = $object->getMaster();
    }
    else
    {
      $objectMaster = $entityManagerEvent->getObject();
    }

    if (AustralTools::usedImplements(get_class($objectMaster), FileInterface::class))
    {
      /** @var FieldFileMapping<T> $fieldFileMapping */
      foreach ($this->mapping->getFieldsMappingByClass($objectMaster->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {
        /** @var FileInterface<T>|null $sourceObject */
        $sourceObject = $entityManagerEvent->getSourceObject();

        /** @var FileInterface<T> $destinationObject */
        $destinationObject = $entityManagerEvent->getObject();
        if ($sourceObject)
        {
          if ('file' === $type)
          {
            $this->fileUploader->copyFile($fieldFileMapping, $sourceObject, $destinationObject);
          }
          elseif ('filename' === $type)
          {
            $this->fileUploader->copyFilename($fieldFileMapping, $sourceObject, $destinationObject);
          }
        }
      }
    }
  }
}

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

use Austral\Contracts\File\FileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;

use Austral\Contracts\Translate\TranslateChildInterface;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Austral Doctrine Listener.
 * @template TObjectManager of ObjectManager
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener
{

  /**
   * DoctrineListener constructor
   *
   * @param Mapping<T> $mapping
   * @param FileUploader<T> $fileUploader
   */
  public function __construct(
    #[Autowire(service: "austral.entity.mapping")] protected Mapping $mapping,
    #[Autowire(service: "austral.entity_file.uploader")] protected FileUploader $fileUploader)
  {
  }

  /**
   * @return string[]
   */
  public function getSubscribedEvents(): array
  {
      return array(
        Events::preRemove
      );
  }


  /**
   * @param LifecycleEventArgs<TObjectManager> $args
   *
   * @throws \Exception
   */
  public function preRemove(LifecycleEventArgs $args): void
  {
    $object = $args->getObject();
    if(!$object instanceof FileInterface)
    {
      if(AustralTools::usedImplements(get_class($object), "Austral\Contracts\Translate\TranslateChildInterface"))
      {
        /** @var TranslateChildInterface<T> $childObject */
        $childObject = $args->getObject();
        $object = $childObject->getMaster();
      }
    }
    if($object instanceof FileInterface)
    {
      /** @var FieldFileMapping<T> $fieldFileMapping */
      foreach($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {
        $this->fileUploader->deleteFileByFieldname($fieldFileMapping, $object)
          ->deleteThumbnails($fieldFileMapping, $object);
      }
    }
  }

}
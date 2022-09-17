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
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;

use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;

use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Austral Doctrine Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener implements EventSubscriber
{

  /**
   * @var mixed
   */
  protected $name;

  /**
   * @var FileUploader
   */
  protected FileUploader $fileUploader;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var UploadsConfiguration
   */
  protected UploadsConfiguration $uploadsConfiguration;

  /**
   * DoctrineListener constructor.
   */
  public function __construct(Mapping $mapping, FileUploader $fileUploader)
  {
    $this->mapping = $mapping;
    $this->fileUploader = $fileUploader;
    $parts = explode('\\', $this->getNamespace());
    $this->name = end($parts);
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
   * @param LifecycleEventArgs $args
   *
   * @throws \Exception
   */
  public function preRemove(LifecycleEventArgs $args): void
  {
    $ea = $this->getEventAdapter($args);
    $object = $ea->getObject();
    if(!$object instanceof FileInterface)
    {
      if(AustralTools::usedImplements(get_class($object), "Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface"))
      {
        $object = $object->getMaster();
      }
    }
    if($object instanceof FileInterface)
    {
      /** @var FieldFileMapping $fieldFileMapping */
      foreach($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {
        $this->fileUploader->deleteFileByFieldname($fieldFileMapping, $object)
          ->deleteThumbnails($fieldFileMapping, $object);
      }
    }
  }

  /**
   * @param EventArgs $args
   *
   * @return EventArgs
   */
  protected function getEventAdapter(EventArgs $args)
  {
    return $args;
  }

  /**
   * @return string
   */
  protected function getNamespace()
  {
    return __NAMESPACE__;
  }
}
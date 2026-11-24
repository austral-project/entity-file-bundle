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
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Doctrine\ORM\Event\PreRemoveEventArgs;

/**
 * Austral Doctrine Listener.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class DoctrineListener
{
  /**
   * DoctrineListener constructor
   *
   * @param Mapping<T>      $mapping
   * @param FileUploader<T> $fileUploader
   */
  public function __construct(protected Mapping $mapping, protected FileUploader $fileUploader)
  {
  }

  /**
   * @param PreRemoveEventArgs $args
   *
   * @throws \Exception
   */
  public function preRemove(PreRemoveEventArgs $args): void
  {
    $object = $args->getObject();
    if (!$object instanceof FileInterface && $object instanceof TranslateChildInterface)
    {
      $object = $object->getMaster();
    }
    if ($object instanceof FileInterface)
    {
      /** @var FieldFileMapping<T> $fieldFileMapping */
      foreach ($this->mapping->getFieldsMappingByClass($object->getClassnameForMapping(), FieldFileMapping::class) as $fieldFileMapping)
      {
        $this->fileUploader->deleteFileByFieldname($fieldFileMapping, $object)
          ->deleteThumbnails($fieldFileMapping, $object);
      }
    }
  }
}

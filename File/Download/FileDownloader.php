<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Download;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\Contracts\File\FileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Austral File Download.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FileDownloader
{

  /**
   * @var string|null
   */
  protected ?string $objectFilePath = null;

  /**
   * @var string|null
   */
  protected ?string $name = null;

  /**
   * FileDownloader constructor.
   *
   * @param Mapping<T> $mapping
   */
  public function __construct(
    #[Autowire(service: "austral.entity.mapping")] protected Mapping $mapping)
  {
  }

  /**
   * @param class-string $entityKey
   * @param string $fieldname
   * @param string $id
   *
   * @return $this
   * @throws \Exception
   */
  public function init(string $entityKey, string $fieldname, string $id): FileDownloader
  {
    /** @var FileInterface<T>|null $object */
    $object = $this->mapping->getObject($entityKey, $id);
    if($object)
    {
      /** @var FieldFileMapping<T>|null $fieldFieldMapping */
      $fieldFieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
      if($fieldFieldMapping )
      {
        $this->objectFilePath = $fieldFieldMapping->getObjectFilePath($object);
        if($this->objectFilePath && !file_exists($this->objectFilePath) && !is_file($this->objectFilePath))
        {
          throw new \InvalidArgumentException("Not file exist {$this->objectFilePath}");
        }
        $this->name = $fieldFieldMapping->getFilename($object);
      }
      else
      {
        throw new \InvalidArgumentException("Object ".get_class($object)." is not init FieldMapping");
      }
    }


    return $this;
  }

  /**
   * @return string|null
   */
  public function getObjectFilePath(): ?string
  {
    return $this->objectFilePath;
  }

  /**
   * @return false|int
   */
  public function getSize(): false|int
  {
    return $this->objectFilePath ? filesize($this->objectFilePath) : false;
  }

  /**
   * @param string|null $default
   *
   * @return string|null
   */
  public function getName(?string $default = null): ?string
  {
    return $this->name ? : $default;
  }
  
}
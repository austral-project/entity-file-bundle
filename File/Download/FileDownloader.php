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
use Austral\EntityFileBundle\Entity\Interfaces\EntityFileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral File Download.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FileDownloader
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
   * @var string
   */
  protected string $objectFilePath;

  /**
   * @var string|null
   */
  protected ?string $name;

  /**
   * FileDownloader constructor.
   *
   * @param ContainerInterface $container
   * @param Mapping $mapping
   */
  public function __construct(ContainerInterface $container, Mapping $mapping)
  {
    $this->container = $container;
    $this->mapping = $mapping;
  }

  /**
   * @param string $entityKey
   * @param string $fieldname
   * @param string $id
   *
   * @return $this
   * @throws \Exception
   */
  public function init(string $entityKey, string $fieldname, string $id): FileDownloader
  {
    /** @var EntityFileInterface $object */
    $object = $this->mapping->getObject($entityKey, $id);
    /** @var FieldFileMapping $fieldFieldMapping */
    if($fieldFieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      $this->objectFilePath = $fieldFieldMapping->getObjectFilePath($object);
      if(!file_exists($this->objectFilePath) && !is_file($this->objectFilePath))
      {
        throw new \InvalidArgumentException("Not file exist {$this->objectFilePath}");
      }
      $this->name = $fieldFieldMapping->getFilename($object);
    }
    else
    {
      throw new \InvalidArgumentException("Object ".get_class($object)." is not init FieldMapping");
    }

    return $this;
  }

  /**
   * @return string
   */
  public function getObjectFilePath(): string
  {
    return $this->objectFilePath;
  }

  /**
   * @return false|int
   */
  public function getSize()
  {
    return filesize($this->objectFilePath);
  }

  /**
   * @param null $default
   *
   * @return string
   */
  public function getName($default = null): ?string
  {
    return $this->name ? : $default;
  }
  
}
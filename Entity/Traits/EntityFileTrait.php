<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\EntityFileBundle\Entity\Traits;

use Austral\EntityFileBundle\Entity\Interfaces\EntityFileInterface;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Austral Entity File Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityFileTrait
{

  /**
   * @var array
   */
  protected array $uploadFiles = array();

  /**
   * @var array
   */
  protected array $deleteFiles = array();

  /**
   * @return int|string
   */
  public function getObjectIdToFile()
  {
    return $this->getId();
  }

  /**
   * @return array
   */
  public function getUploadFiles(): array
  {
    return $this->uploadFiles;
  }

  /**
   * @param string $fieldname
   * @param UploadedFile|null $uploadedFile
   *
   * @return EntityFileInterface
   */
  public function setUploadFileByFieldname(string $fieldname, ?UploadedFile $uploadedFile = null): EntityFileInterface
  {
    $this->uploadFiles[$fieldname] = $uploadedFile;
    return $this;
  }

  /**
   * @param string $fieldname
   *
   * @return UploadedFile|null
   */
  public function getUploadFileByFieldname(string $fieldname): ?UploadedFile
  {
    return AustralTools::getValueByKey($this->uploadFiles, $fieldname);
  }

  /**
   * @param string $fieldname
   *
   * @return bool
   */
  public function getDeleteFileByFieldname(string $fieldname): bool
  {
    return array_key_exists($fieldname, $this->getDeleteFiles()) && $this->getDeleteFiles()[$fieldname];
  }

  /**
   * @param string $fieldname
   * @param $value
   *
   * @return EntityFileInterface
   */
  public function setDeleteFileByFieldname(string $fieldname, $value): EntityFileInterface
  {
    $this->deleteFiles[$fieldname] = $value;
    return $this;
  }

  /**
   * @return array
   */
  public function getDeleteFiles(): array
  {
    return $this->deleteFiles;
  }

  /**
   * @param $deleteFiles
   *
   * @return EntityFileInterface
   */
  public function setDeleteFiles($deleteFiles): EntityFileInterface
  {
    $this->deleteFiles = $deleteFiles;
    return $this;
  }

}
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

use Austral\EntityBundle\Entity\Entity;
use Austral\Contracts\File\FileInterface;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Austral Entity File Trait.
 * @template T of object
 *
 * @phpstan-require-implements FileInterface<T>
 * @phpstan-require-extends Entity<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityFileTrait
{

  /**
   * @var array<string|int, UploadedFile|null>
   */
  protected array $uploadFiles = [];

  /**
   * @var array<string, bool>
   */
  protected array $deleteFiles = [];

  /**
   * @inheritDoc
   */
  abstract public function getId(): string;

  /**
   * @inheritDoc
   */
  public function getObjectIdToFile(): string
  {
    return $this->getId();
  }

  /**
   * @inheritDoc
   */
  public function removeUploadFiles(): array
  {
    return $this->uploadFiles = [];
  }

  /**
   * @return array
   */
  public function getUploadFiles(): array
  {
    return $this->uploadFiles;
  }

  /**
   * @inheritDoc
   */
  public function setUploadFiles(array $uploadFiles = []): self
  {
    $this->uploadFiles = $uploadFiles;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setUploadFileByFieldname(string $fieldname, ?UploadedFile $uploadedFile = null): self
  {
    $this->uploadFiles[$fieldname] = $uploadedFile;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getUploadFileByFieldname(string $fieldname): ?UploadedFile
  {
    return AustralTools::valueByKey($this->uploadFiles, $fieldname);
  }

  /**
   * @inheritDoc
   */
  public function getDeleteFileByFieldname(string $fieldname): bool
  {
    return array_key_exists($fieldname, $this->getDeleteFiles()) && $this->getDeleteFiles()[$fieldname];
  }

  /**
   * @inheritDoc
   */
  public function setDeleteFileByFieldname(string $fieldname, mixed $value): self
  {
    $this->deleteFiles[$fieldname] = $value;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDeleteFiles(): array
  {
    return $this->deleteFiles;
  }

  /**
   * @inheritDoc
   */
  public function setDeleteFiles($deleteFiles): self
  {
    $this->deleteFiles = $deleteFiles;
    return $this;
  }

}
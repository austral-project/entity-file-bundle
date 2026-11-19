<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Bridge\EntityTranslate\Entity\Traits;

use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityFileBundle\Entity\Interfaces\CropperInterface;
use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterTrait;

/**
 * Austral Translate Entity File Cropper To Master Translate Trait.
 *
 * @template T of object
 *
 * @phpstan-require-implements TranslateMasterInterface
 *
 * @phpstan-require-extends Entity
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityFileCropperTranslateMasterTrait
{
  use EntityTranslateMasterTrait;

  /**
   * @return CropperInterface|null
   *
   * @throws \Exception
   */
  private function getTranslateCurrentCropper(): ?CropperInterface
  {
    $object = $this->getTranslateCurrent();
    if ($object instanceof CropperInterface)
    {
      return $object;
    }

    return null;
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperData(): array
  {
    return $this->getTranslateCurrentCropper() ? $this->getTranslateCurrentCropper()->getCropperData() : [];
  }

  /**
   * @param array<string, mixed> $cropperData
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function setCropperData(array $cropperData): self
  {
    if ($this->getTranslateCurrentCropper())
    {
      $this->getTranslateCurrentCropper()->setCropperData($cropperData);
    }

    return $this;
  }

  /**
   * @param string $filename
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperDataByFilename(string $filename): array
  {
    return $this->getTranslateCurrentCropper() ? $this->getTranslateCurrentCropper()->getCropperDataByFilename($filename) : [];
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getGenerateCropperByKey(): array
  {
    return $this->getTranslateCurrentCropper() ? $this->getTranslateCurrentCropper()->getGenerateCropperByKey() : [];
  }

  /**
   * @param array $generateCropperByKey
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function setGenerateCropperByKey(array $generateCropperByKey): self
  {
    if ($this->getTranslateCurrentCropper())
    {
      $this->getTranslateCurrentCropper()->setGenerateCropperByKey($generateCropperByKey);
    }

    return $this;
  }
}

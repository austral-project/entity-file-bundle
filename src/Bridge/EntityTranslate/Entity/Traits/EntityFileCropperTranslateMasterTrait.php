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

use Austral\Contracts\File\CropperInterface;
use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\EntityBundle\Entity\Entity;
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
   * @param bool $create creates the current translation when there is none (writes only)
   *
   * @return CropperInterface|null
   *
   * @throws \Exception
   */
  private function getTranslateCurrentCropper(bool $create = false): ?CropperInterface
  {
    $translate = $this->getTranslateCurrent();
    if (!$translate && $create && is_a($this->getTranslateClassname(), CropperInterface::class, true))
    {
      $translate = $this->createNewTranslateByLanguage();
    }

    return $translate instanceof CropperInterface ? $translate : null;
  }

  /**
   * Current translation for a write, created when needed.
   *
   * @throws \LogicException when the translation class does not implement CropperInterface
   */
  private function getCropperOrFail(): CropperInterface
  {
    return $this->getTranslateCurrentCropper(true)
      ?? throw new \LogicException(sprintf('The translation class "%s" of "%s" must implement %s.', $this->getTranslateClassname(), static::class, CropperInterface::class));
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getCropperData(): array
  {
    return $this->getTranslateCurrentCropper()?->getCropperData() ?? [];
  }

  /**
   * @param array<string, mixed> $cropperData
   *
   * @return $this
   *
   * @throws \LogicException when the translation class does not implement CropperInterface
   */
  public function setCropperData(array $cropperData): self
  {
    $this->getCropperOrFail()->setCropperData($cropperData);

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
    return $this->getTranslateCurrentCropper()?->getCropperDataByFilename($filename) ?? [];
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function getGenerateCropperByKey(): array
  {
    return $this->getTranslateCurrentCropper()?->getGenerateCropperByKey() ?? [];
  }

  /**
   * @param array<string, mixed> $generateCropperByKey
   *
   * @return $this
   *
   * @throws \LogicException when the translation class does not implement CropperInterface
   */
  public function setGenerateCropperByKey(array $generateCropperByKey): self
  {
    $this->getCropperOrFail()->setGenerateCropperByKey($generateCropperByKey);

    return $this;
  }
}

<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Entity\Interfaces;

use Austral\Contracts\Entity\EntityInterface;

/**
 * Austral Entity Cropper Interface.
 *
 * @template T of object
 *
 * @extends EntityInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface CropperInterface extends EntityInterface
{
  /**
   * @return array<string, mixed>
   */
  public function getCropperData(): array;

  /**
   * @param array<string, mixed> $cropperData
   *
   * @return $this
   */
  public function setCropperData(array $cropperData): static;

  /**
   * @param string $filename
   *
   * @return array<string, mixed>
   */
  public function getCropperDataByFilename(string $filename): array;

  /**
   * @return array<string, mixed>
   */
  public function getGenerateCropperByKey(): array;

  /**
   * @param array<string, mixed> $generateCropperByKey
   *
   * @return $this
   */
  public function setGenerateCropperByKey(array $generateCropperByKey): static;
}

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
use Austral\EntityFileBundle\Entity\Interfaces\CropperInterface;
use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\Mapping as ORM;

/**
 * Austral Entity File Cropper Trait.
 *
 * @template T of object
 *
 * @phpstan-require-implements CropperInterface<T>
 *
 * @phpstan-require-extends Entity<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityFileCropperTrait
{
  /**
   * @var array<string, mixed>|null
   */
  #[ORM\Column(name: 'cropper_data', type: 'json', nullable: true)]
  protected ?array $cropperData = [];

  /**
   * @var array<string, mixed>
   */
  protected array $generateCropperByKey = [];

  /**
   * @return array<string, mixed>
   */
  public function getCropperData(): array
  {
    return $this->cropperData ?: [];
  }

  /**
   * @param array<string, mixed> $cropperData
   *
   * @return $this
   */
  public function setCropperData(array $cropperData): static
  {
    $this->cropperData = $cropperData;

    return $this;
  }

  /**
   * @param string $filename
   *
   * @return array<string, mixed>
   */
  public function getCropperDataByFilename(string $filename): array
  {
    return AustralTools::valueByKey($this->getCropperData(), $filename, []);
  }

  /**
   * @return array<string, mixed>
   */
  public function getGenerateCropperByKey(): array
  {
    return $this->generateCropperByKey;
  }

  /**
   * @param array<string, mixed> $generateCropperByKey
   *
   * @return $this
   */
  public function setGenerateCropperByKey(array $generateCropperByKey): static
  {
    $this->generateCropperByKey = $generateCropperByKey;

    return $this;
  }
}

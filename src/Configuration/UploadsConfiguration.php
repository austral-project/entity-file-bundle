<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Configuration;

use Austral\ToolsBundle\Configuration\BaseConfiguration;

/**
 * Austral File Entity Uploads Parameters.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class UploadsConfiguration extends BaseConfiguration
{
  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @var int|null
   */
  protected ?int $niveauMax = null;

  /**
   * @var string|null
   */
  protected ?string $prefix = 'entity_file_upload';

  /**
   * Initialize the service
   *
   * @param array<mixed> $config
   */
  public function __construct(array $config, CropperConfiguration $cropperConfiguration)
  {
    parent::__construct($config);
    $this->cropperConfiguration = $cropperConfiguration;
  }

  /**
   * @param string $key
   *
   * @return string
   */
  public function getUploadsPath(string $key): string
  {
    return $this->get("{$key}.path.uploads");
  }

  /**
   * @param string $key
   *
   * @return string
   */
  public function getThumbnailPath(string $key): string
  {
    return $this->get("{$key}.path.thumbnail");
  }

  /**
   * getCropper
   *
   * @param string $key
   *
   * @return array<string, mixed>
   */
  public function getCropper(string $key): array
  {
    $listCropper = $this->get("{$key}.cropper", []);
    $cropperFinal = [];
    foreach ($listCropper as $cropperKey)
    {
      if ($this->cropperConfiguration->get($cropperKey))
      {
        $cropperFinal[$cropperKey] = $this->cropperConfiguration->get($cropperKey);
      }
    }

    return $cropperFinal;
  }
}

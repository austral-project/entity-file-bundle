<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Attribute;

use Austral\EntityBundle\Attribute\AustralEntityAttribute;

/**
 * Attribute UploadParameters
 *
 * @Attribute
 *
 * @Target({"TARGET_PROPERTY", "TARGET_METHOD"})
 *
 * @author  Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class UploadParameters extends AustralEntityAttribute implements EntityFileAttributeInterface
{
  /**
   * @var string
   */
  public string $configName;

  /**
   * @var bool
   */
  public bool $isRequired = false;

  /**
   * @var string[]
   */
  public array $mimeTypes = [];

  /**
   * @var string|null
   */
  public ?string $sizeMax = null;

  /**
   * @var string|null
   */
  public ?string $virtualNameField = null;

  /**
   * @var string
   */
  public string $errorMaxSize = 'file.errors.maxSize';

  /**
   * @var string
   */
  public string $errorMimeTypes = 'file.errors.mimeTypes';

  /**
   * @param string             $configName
   * @param bool               $isRequired
   * @param array<int, string> $mimeTypes
   * @param string|null        $sizeMax
   * @param string|null        $virtualNameField
   */
  public function __construct(
    string $configName,
    bool $isRequired = false,
    array $mimeTypes = [],
    ?string $sizeMax = null,
    ?string $virtualNameField = null
  ) {
    $this->configName = $configName;
    $this->isRequired = $isRequired;
    $this->mimeTypes = $mimeTypes;
    $this->sizeMax = $sizeMax;
    $this->virtualNameField = $virtualNameField;
  }

  /**
   * @return string
   */
  public function getTypeFile(): string
  {
    $typeFile = $this->mimeTypes ? 'picture' : 'file';
    foreach ($this->mimeTypes as $mimeType)
    {
      if (!str_contains($mimeType, 'image/'))
      {
        $typeFile = 'file';
      }
    }

    return $typeFile;
  }
}

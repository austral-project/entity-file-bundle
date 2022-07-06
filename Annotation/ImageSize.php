<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\EntityFileBundle\Annotation;

use Austral\EntityBundle\Annotation\AustralEntityAnnotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class ImageSize extends AustralEntityAnnotation implements EntityFileAnnotationInterface
{

  /**
   * @var string|null
   */
  public ?string $configName = "";

  /**
   * @var float|null
   */
  public ?float $widthMin = null;

  /**
   * @var float|null
   */
  public ?float $widthMax = null;

  /**
   * @var float|null
   */
  public ?float $heightMin = null;

  /**
   * @var float|null
   */
  public ?float $heightMax = null;

  /**
   * @param string|null $configName
   * @param float|null $widthMin
   * @param float|null $widthMax
   * @param float|null $heightMin
   * @param float|null $heightMax
   */
  public function __construct(?string $configName = null,
    ?float $widthMin = null,
    ?float $widthMax = null,
    ?float $heightMin = null,
    ?float $heightMax = null
  ) {
    $this->configName = $configName;
    $this->widthMin = $widthMin;
    $this->widthMax = $widthMax;
    $this->heightMin = $heightMin;
    $this->heightMax = $heightMax;
  }

  /**
   * @return bool
   */
  public function hasLimit(): bool
  {
    return ($this->widthMax || $this->widthMin || $this->heightMax || $this->heightMin);
  }

}
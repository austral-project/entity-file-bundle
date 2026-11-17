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

use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Attribute Cropper
 * @template T of object
 *
 * @Attribute
 * @package Austral\EntityBundle\Attribute
 * @Target({"TARGET_PROPERTY", "TARGET_METHOD"})
 *
 * @author  Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Cropper
{

  /**
   * @var string
   */
  public string $key;

  /**
   * @var string
   */
  public string $name;

  /**
   * @var string|null
   */
  public ?string $picto = null;

  /**
   * @var string|null
   */
  public ?string $ratio = null;

  /**
   * @param string $name
   * @param string|null $picto
   * @param string|null $ratio
   */
  public function __construct(string $name, ?string $picto = null, ?string $ratio = null)
  {
    $this->name = $name;
    $this->key = $name;
    $this->picto = $picto;
    $this->ratio = $ratio;
  }

}

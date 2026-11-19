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
 * Attribute Croppers
 *
 * @template T of object
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
final class Croppers extends AustralEntityAttribute implements EntityFileAttributeInterface
{
  /**
   * @var array<int, Cropper<T>>
   */
  public array $croppers = [];

  /**
   * @param array<int, Cropper<T>> $croppers
   */
  public function __construct(array $croppers = [])
  {
    $this->croppers = $croppers;
  }
}

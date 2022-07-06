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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class Cropper
{

  /**
   * @var string
   */
  public string $name;

  /**
   * @var string
   */
  public string $picto;

  /**
   * @var string|null
   */
  public ?string $ratio = null;



  /**
   * @param string $name
   * @param string $picto
   * @param string|null $ratio
   */
  public function __construct(string $name, string $picto, ?string $ratio = null)
  {
    $this->name = $name;
    $this->picto = $picto;
    $this->ratio = $ratio;
  }

}

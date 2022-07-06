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
final class Croppers extends AustralEntityAnnotation implements EntityFileAnnotationInterface
{

  /**
   * @var array
   */
  public array $croppers = array();

  /**
   * @param array $croppers
   */
  public function __construct(array $croppers = array())
  {
    $this->croppers = $croppers;
  }

}

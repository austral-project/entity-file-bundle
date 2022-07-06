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
final class Path extends AustralEntityAnnotation implements EntityFileAnnotationInterface
{
  /**
   * @var string
   */
  public string $upload = "%kernel.project_dir%/public/uploads/@entity_slugger_case@";

  /**
   * @var string
   */
  public string $thumbnail = "%kernel.project_dir%/public/thumbnail/@entity_slugger_case@";


}

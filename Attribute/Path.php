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
 * Attribute Path
 *
 * @Attribute
 * @package Austral\EntityBundle\Attribute
 * @Target({"TARGET_PROPERTY", "TARGET_METHOD"})
 *
 * @author  Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Path extends AustralEntityAttribute implements EntityFileAttributeInterface
{
  /**
   * @var string
   */
  public string $upload = "%kernel.project_dir%/%austral.file.public.path%/uploads/@entity_slugger_case@";

  /**
   * @var string
   */
  public string $thumbnail = "%kernel.project_dir%/%austral.file.public.path%/thumbnail/@entity_slugger_case@";

  /**
   * Path constructor
   *
   * @param string|null $upload
   * @param string|null $thumbnail
   */
  public function __construct(?string $upload = null, ?string $thumbnail = null)
  {
    $this->upload = $upload ?? $this->upload;
    $this->thumbnail = $thumbnail ?? $this->thumbnail;
  }


}

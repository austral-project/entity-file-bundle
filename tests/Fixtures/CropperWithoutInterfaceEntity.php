<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\Fixtures;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;

/**
 * Test entity that uses the cropper trait without implementing CropperInterface (3.1 style).
 *
 * @extends Entity<object>
 */
// @phpstan-ignore class.missingImplements
final class CropperWithoutInterfaceEntity extends Entity
{
  /** @use EntityFileCropperTrait<object> */
  use EntityFileCropperTrait;
}

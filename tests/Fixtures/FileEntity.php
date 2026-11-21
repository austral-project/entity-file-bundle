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

use Austral\Contracts\File\CropperInterface;
use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Entity\Entity;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;

/**
 * Test entity with uploaded files and croppers.
 *
 * @extends Entity<object>
 *
 * @implements FileInterface<object>
 * @implements CropperInterface<object>
 */
final class FileEntity extends Entity implements FileInterface, CropperInterface
{
  /** @use EntityFileTrait<object> */
  use EntityFileTrait;

  /** @use EntityFileCropperTrait<object> */
  use EntityFileCropperTrait;
}

<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\Entity\Traits;

use Austral\EntityFileBundle\Tests\Fixtures\FileEntity;
use PHPUnit\Framework\TestCase;

final class EntityFileCropperTraitTest extends TestCase
{
  public function testCropperDataIsEmptyByDefault(): void
  {
    $entity = new FileEntity();

    self::assertSame([], $entity->getCropperData());
    self::assertSame([], $entity->getCropperDataByFilename('image'));
  }

  public function testCropperDataByFilename(): void
  {
    $data = ['image' => ['square' => ['x' => 10, 'y' => 20, 'width' => 300, 'height' => 300]]];
    $entity = new FileEntity()->setCropperData($data);

    self::assertSame($data, $entity->getCropperData());
    self::assertSame($data['image'], $entity->getCropperDataByFilename('image'));
    self::assertSame([], $entity->getCropperDataByFilename('unknown'));
  }

  public function testGenerateCropperByKey(): void
  {
    $entity = new FileEntity()->setGenerateCropperByKey(['image' => ['square' => true]]);

    self::assertSame(['image' => ['square' => true]], $entity->getGenerateCropperByKey());
  }
}

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
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class EntityFileTraitTest extends TestCase
{
  public function testObjectIdToFileIsTheEntityId(): void
  {
    $entity = new FileEntity()->setId('abc-123');

    self::assertSame('abc-123', $entity->getObjectIdToFile());
  }

  public function testUploadFilesByFieldname(): void
  {
    $entity = new FileEntity();
    $file = new UploadedFile(__FILE__, 'image.jpg', null, null, true);

    self::assertSame([], $entity->getUploadFiles());
    self::assertNull($entity->getUploadFileByFieldname('image'));

    $entity->setUploadFileByFieldname('image', $file);
    self::assertSame($file, $entity->getUploadFileByFieldname('image'));
    self::assertSame(['image' => $file], $entity->getUploadFiles());

    self::assertSame([], $entity->removeUploadFiles());
    self::assertSame([], $entity->getUploadFiles());
  }

  public function testDeleteFilesByFieldname(): void
  {
    $entity = new FileEntity();

    self::assertFalse($entity->getDeleteFileByFieldname('image'));

    $entity->setDeleteFileByFieldname('image', true);
    self::assertTrue($entity->getDeleteFileByFieldname('image'));

    $entity->setDeleteFiles(['image' => false]);
    self::assertFalse($entity->getDeleteFileByFieldname('image'));
    self::assertSame(['image' => false], $entity->getDeleteFiles());
  }
}

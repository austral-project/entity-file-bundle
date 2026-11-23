<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\Listener;

use Austral\Contracts\File\CropperInterface;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\Configuration\ImageSizeConfiguration;
use Austral\EntityFileBundle\Configuration\UploadsConfiguration;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\Tests\Fixtures\CropperWithoutInterfaceEntity;
use Austral\EntityFileBundle\Tests\Fixtures\FileEntity;
use Austral\EntityFileBundle\Tests\Fixtures\TestEntityMappingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class EntityMappingListenerTest extends TestCase
{
  public function testEntityWithCropperTraitAndInterfaceIsAccepted(): void
  {
    $this->expectNotToPerformAssertions();

    $this->createListener()->callCheckCropperInterface(FileEntity::class);
  }

  public function testEntityWithoutCropperTraitIsAccepted(): void
  {
    $this->expectNotToPerformAssertions();

    $this->createListener()->callCheckCropperInterface(\stdClass::class);
  }

  public function testEntityWithCropperTraitWithoutInterfaceIsRejected(): void
  {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage(sprintf('%s uses %s but does not implement %s', CropperWithoutInterfaceEntity::class, EntityFileCropperTrait::class, CropperInterface::class));

    $this->createListener()->callCheckCropperInterface(CropperWithoutInterfaceEntity::class);
  }

  public function testPathParametersAndEntitySluggerAreReplaced(): void
  {
    $listener = $this->createListener(['kernel.project_dir' => '/var/www', 'austral.uploads' => 'uploads']);

    self::assertSame(
      '/var/www/public/uploads/my_entity',
      $listener->callReplacePath('%kernel.project_dir%/public/%austral.uploads%/@entity_slugger_case@', 'my_entity')
    );
  }

  public function testUnknownOrNonStringParametersAreLeftAsTheyAre(): void
  {
    $listener = $this->createListener(['austral.sizes' => [100, 200], 'austral.empty' => '']);

    self::assertSame(
      '%unknown.parameter%/%austral.sizes%/%austral.empty%/file',
      $listener->callReplacePath('%unknown.parameter%/%austral.sizes%/%austral.empty%/file', 'my_entity')
    );
  }

  /**
   * @param array<string, mixed> $parameters
   */
  private function createListener(array $parameters = []): TestEntityMappingListener
  {
    return new TestEntityMappingListener(
      new ParameterBag($parameters),
      $this->createMock(UploadsConfiguration::class),
      $this->createMock(CropperConfiguration::class),
      $this->createMock(ImageSizeConfiguration::class)
    );
  }
}

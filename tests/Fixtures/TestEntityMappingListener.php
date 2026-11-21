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

use Austral\EntityFileBundle\Listener\EntityMappingListener;

/**
 * Exposes the protected checks of EntityMappingListener to the tests.
 *
 * @extends EntityMappingListener<object>
 */
final class TestEntityMappingListener extends EntityMappingListener
{
  public function callCheckCropperInterface(string $classname): void
  {
    $this->checkCropperInterface($classname);
  }
}

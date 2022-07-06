<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle;
use Austral\EntityFileBundle\DependencyInjection\Compiler\EntityFileCompiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Austral EntityFile Bundle.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class AustralEntityFileBundle extends Bundle
{

  public function build(ContainerBuilder $container)
  {
    parent::build($container);
    $container->addCompilerPass(new EntityFileCompiler(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
  }
  
  
}

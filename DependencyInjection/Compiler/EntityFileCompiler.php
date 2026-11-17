<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\DependencyInjection\Compiler;

use Austral\ToolsBundle\AustralTools;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Austral Upload File Compiler.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class EntityFileCompiler implements CompilerPassInterface
{
  /**
   * Init Configuration Austral Admin with all parameters defined
   */
  public function process(ContainerBuilder $container): void
  {
    foreach(array('uploads', "cropper", "image_size") as $keyParameter)
    {
      /** @var array<string, mixed> $initialConfig */
      $initialConfig = $container->getParameter("austral_entity_file.{$keyParameter}");

      $allsParameters = $container->getParameterBag()->all();
      $keys = array_keys($allsParameters);
      $matches = preg_grep("/austral_entity_file\.{$keyParameter}\.\w/i", $keys);
      if ($matches === false) {
        $matches = [];
      }

      /** @var array<int|string, bool> $flip */
      $flip = array_fill_keys($matches, true);
      $australManagerBundlesModules = array_intersect_key($allsParameters, $flip);
      $bundlesModules = [];

      foreach($australManagerBundlesModules as $keyParameters => $australAdminModules)
      {
        if(is_array($australAdminModules))
        {
          foreach($australAdminModules as $moduleKey => $australAdminModule)
          {
            $bundlesModules[$moduleKey] = array_merge_recursive(AustralTools::getValueByKey($bundlesModules, $moduleKey, []), $australAdminModule);
          }
          $container->getParameterBag()->remove($keyParameters);
        }
      }
      $container->setParameter("austral_entity_file.{$keyParameter}", array_replace_recursive($bundlesModules, $initialConfig));
    }

  }
}
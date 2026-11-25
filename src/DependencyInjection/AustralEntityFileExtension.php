<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Austral File Entity Bundle Extension.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class AustralEntityFileExtension extends Extension
{
  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function load(array $configs, ContainerBuilder $container): void
  {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $config['uploads'] = ConfigurationMerger::merge($configuration->getUploadsDefault(), $config['uploads']);
    $config['image_size'] = ConfigurationMerger::merge($configuration->getImageSizeDefault(), $config['image_size']);
    if ($config['cropper'])
    {
      $config['cropper'] = ConfigurationMerger::merge($configuration->getCropperDefault(), $config['cropper']);
    }

    $container->setParameter('austral_entity_file.uploads', $config['uploads']);
    $container->setParameter('austral_entity_file.compression', $config['compression']);
    $container->setParameter('austral_entity_file.cropper', $config['cropper']);
    $container->setParameter('austral_entity_file.image_size', $config['image_size']);

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('parameters.yaml');
    $loader->load('services.yaml');
    $loader->load('command.yaml');
    $this->loadBridges($container, $loader);
  }

  /**
   * Loads the services of the bridges, only when the bundle they integrate with is registered.
   *
   * @throws \Exception
   */
  protected function loadBridges(ContainerBuilder $container, YamlFileLoader $loader): void
  {
    $bundles = $container->hasParameter('kernel.bundles') ? $container->getParameter('kernel.bundles') : [];
    $bundles = is_array($bundles) ? $bundles : [];

    if (in_array('Austral\\FormBundle\\AustralFormBundle', $bundles, true))
    {
      $loader->load('bridge/form.yaml');
    }
    if (in_array('Austral\\AdminBundle\\AustralAdminBundle', $bundles, true))
    {
      $loader->load('bridge/admin.yaml');
    }
  }

  /**
   * @return string
   */
  public function getNamespace(): string
  {
    return 'https://austral.dev/schema/dic/austral_entity_file';
  }
}

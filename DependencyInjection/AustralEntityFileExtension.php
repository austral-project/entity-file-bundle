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
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralEntityFileExtension extends Extension
{
  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $config["uploads"] = array_replace_recursive($configuration->getUploadsDefault(), $config["uploads"]);
    $config["image_size"] = array_replace_recursive($configuration->getImageSizeDefault(), $config["image_size"]);
    if($config["cropper"])
    {
      $config["cropper"] = array_replace_recursive($configuration->getCropperDefault(), $config["cropper"]);
    }

    $container->setParameter('austral_entity_file.uploads', $config["uploads"]);
    $container->setParameter('austral_entity_file.compression', $config['compression']);
    $container->setParameter('austral_entity_file.cropper', $config['cropper']);
    $container->setParameter('austral_entity_file.image_size', $config['image_size']);

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
    $loader->load('services.yaml');
    $this->loadConfigToAustralFormBundle($container, $loader);
  }

  /**
   * @param ContainerBuilder $container
   * @param YamlFileLoader $loader
   *
   * @throws \Exception
   */
  protected function loadConfigToAustralFormBundle(ContainerBuilder $container, YamlFileLoader $loader)
  {
    $bundlesConfigPath = $container->getParameter("kernel.project_dir")."/config/bundles.php";
    if(file_exists($bundlesConfigPath))
    {
      $contents = require $bundlesConfigPath;
      if(array_key_exists("Austral\FormBundle\AustralFormBundle", $contents))
      {
        $loader->load('austral_form.yaml');
      }
      if(array_key_exists("Austral\AdminBundle\AustralAdminBundle", $contents))
      {
        $loader->load('austral_admin.yaml');
      }
    }
  }

  /**
   * @return string
   */
  public function getNamespace(): string
  {
    return 'https://austral.app/schema/dic/austral_entity_file';
  }

}

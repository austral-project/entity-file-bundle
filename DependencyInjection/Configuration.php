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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral File Entity Bundle Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{

  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder()
  {

    $treeBuilder = new TreeBuilder('austral_entity_file');

    $rootNode = $treeBuilder->getRootNode();
    $node = $rootNode->children();
      $node = $node->arrayNode('compression')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('command')->end();
      $node = $this->buildCompressionOptionsNode(
        $node->arrayNode('options')
          ->arrayPrototype()
      )->end()->end()->end()->end();

      $node = $this->buildCropperNode($node
        ->arrayNode('cropper')
          ->arrayPrototype()
      )->end()->defaultValue($this->getCropperDefault())->end();

      $node = $this->buildImageSizeNode($node
        ->arrayNode('image_size')
          ->arrayPrototype()
      )->end()->defaultValue($this->getImageSizeDefault())->end();

      $node = $this->buildUploadsNode($node
        ->arrayNode('uploads')
          ->arrayPrototype()
      );
      $node->end()->defaultValue($this->getUploadsDefault())->end()->end();
    return $treeBuilder;
  }

  /**
   * @param $node
   *
   * @return mixed
   */
  protected function buildCompressionOptionsNode($node)
  {
    return $node
      ->children()
        ->scalarNode('quality')->end()
        ->scalarNode('level')->end()
      ->end();
  }

  /**
   * @param $node
   *
   * @return mixed
   */
  protected function buildCropperNode($node)
  {
    return $node
      ->children()
        ->scalarNode('name')->cannotBeEmpty()->end()
        ->scalarNode('picto')->cannotBeEmpty()->end()
        ->scalarNode('ratio')->cannotBeEmpty()->end()
      ->end();
  }

  /**
   * @param $node
   *
   * @return mixed
   */
  protected function buildImageSizeNode($node)
  {
    return $node
      ->children()
        ->arrayNode('width')
          ->children()
            ->scalarNode('min')->end()
            ->scalarNode('max')->end()
          ->end()
        ->end()
        ->arrayNode('height')
          ->children()
            ->scalarNode('min')->end()
            ->scalarNode('max')->end()
          ->end()
        ->end()
      ->end();
  }

  /**
   * @param $node
   *
   * @return mixed
   */
  protected function buildUploadsNode($node)
  {
    return $node
      ->children()
        ->arrayNode('size')
          ->children()
            ->scalarNode('max')->defaultValue("1024k")->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('min')->end()
          ->end()
        ->end()
        ->arrayNode('mimeTypes')->scalarPrototype()->end()->end()
        ->arrayNode('croppers')->scalarPrototype()->end()->end()
        ->arrayNode('errors')
          ->children()
            ->scalarNode('maxSize')->end()
            ->scalarNode('mimeTypes')->end()
          ->end()
        ->end() // errors
      ->end();
  }

  /**
   * @return array
   */
  public function getUploadsDefault(): array
  {
    return array(
      "default_image"   =>  array(
        "size"        =>  array(
          "max"       => "2M"
        ),
        "mimeTypes" =>  array('image/jpg', 'image/jpeg', 'image/png', 'image/gif', 'image/svg', 'image/svg+xml'),
        "errors"    =>  array(
          "maxSize"   =>  "file.errors.maxSize",
          "mimeTypes" =>  "file.errors.mimeTypes"
        )
      ),
      "default_file"   =>  array(
        "size"        =>  array(
          "max"       => "2M"
        ),
        "errors"    =>  array(
          "maxSize"   =>  "file.errors.maxSize",
          "mimeTypes" =>  "file.errors.mimeTypes"
        )
      ),
    );
  }

  /**
   * @return array
   */
  public function getImageSizeDefault(): array
  {
    return array(
      "default"     =>  array(
        "width"     =>  array(
          "max"       =>  10000,
          "min"       =>  100,
        ),
        "height"    =>  array(
          "max"       =>  10000,
          "min"       =>  100,
        )
      )
    );
  }

  /**
   * @return array
   */
  public function getCropperDefault(): array
  {
    return array(
      "desktop"   =>  array(
        "name"        =>  "desktop",
        "picto"       =>  "austral-picto-monitor",
        "ratio"       =>  ""
      ),
      "tablet"   =>  array(
        "name"        =>  "tablet",
        "picto"       =>  "austral-picto-pad",
        "ratio"       =>  ""
      ),
      "mobile"   =>  array(
        "name"        =>  "mobile",
        "picto"       =>  "austral-picto-smartphone",
        "ratio"       =>  ""
      ),
    );
  }

}

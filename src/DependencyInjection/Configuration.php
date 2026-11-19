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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral File Entity Bundle Configuration.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_entity_file');
    $rootNode = $treeBuilder->getRootNode();
    $children = $rootNode->children();

    // compression
    $compression = $children->arrayNode('compression')->addDefaultsIfNotSet();
    $compression->children()->scalarNode('command')->end();
    $optionsProto = $compression->children()->arrayNode('options')->arrayPrototype();
    $this->buildCompressionOptionsNode($optionsProto);

    // cropper
    $cropperProto = $children->arrayNode('cropper')->defaultValue($this->getCropperDefault())->arrayPrototype();
    $this->buildCropperNode($cropperProto);

    // image_size
    $imageSizeProto = $children->arrayNode('image_size')->defaultValue($this->getImageSizeDefault())->arrayPrototype();
    $this->buildImageSizeNode($imageSizeProto);

    // uploads
    $uploadsProto = $children->arrayNode('uploads')->defaultValue($this->getUploadsDefault())->arrayPrototype();
    $this->buildUploadsNode($uploadsProto);

    return $treeBuilder;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return ArrayNodeDefinition
   */
  protected function buildCompressionOptionsNode(ArrayNodeDefinition $node): ArrayNodeDefinition
  {
    $children = $node->children();
    $children->scalarNode('quality')->end();
    $children->scalarNode('level')->end();

    return $node;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return ArrayNodeDefinition
   */
  protected function buildCropperNode(ArrayNodeDefinition $node): ArrayNodeDefinition
  {
    $children = $node->children();
    $children->scalarNode('name')->cannotBeEmpty()->end();
    $children->scalarNode('picto')->cannotBeEmpty()->end();
    $children->scalarNode('ratio')->cannotBeEmpty()->end();

    return $node;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return ArrayNodeDefinition
   */
  protected function buildImageSizeNode(ArrayNodeDefinition $node): ArrayNodeDefinition
  {
    $children = $node->children();

    $width = $children->arrayNode('width');
    $widthChildren = $width->children();
    $widthChildren->scalarNode('min')->end();
    $widthChildren->scalarNode('max')->end();

    $height = $children->arrayNode('height');
    $heightChildren = $height->children();
    $heightChildren->scalarNode('min')->end();
    $heightChildren->scalarNode('max')->end();

    return $node;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return ArrayNodeDefinition
   */
  protected function buildUploadsNode(ArrayNodeDefinition $node): ArrayNodeDefinition
  {
    $children = $node->children();

    // size
    $size = $children->arrayNode('size');
    $sizeChildren = $size->children();
    $sizeChildren->scalarNode('max')
      ->defaultValue('1024k')
      ->isRequired()
      ->cannotBeEmpty()
      ->end();
    $sizeChildren->scalarNode('min')->end();

    // mimeTypes
    $mimeTypes = $children->arrayNode('mimeTypes');
    $mimeTypes->scalarPrototype()->end();

    // croppers
    $croppers = $children->arrayNode('croppers');
    $croppers->scalarPrototype()->end();

    // errors
    $errors = $children->arrayNode('errors');
    $errorsChildren = $errors->children();
    $errorsChildren->scalarNode('maxSize')->end();
    $errorsChildren->scalarNode('mimeTypes')->end();

    return $node;
  }

  /**
   * @return array<string, array<string, array<int|string, string>>>
   */
  public function getUploadsDefault(): array
  {
    return [
      'default_image' => [
        'size' => [
          'max' => '2M',
        ],
        'mimeTypes' => [
          'image/jpg',
          'image/jpeg',
          'image/png',
          'image/gif',
          'image/svg',
          'image/svg+xml',
        ],
        'errors' => [
          'maxSize' => 'file.errors.maxSize',
          'mimeTypes' => 'file.errors.mimeTypes',
        ],
      ],
      'default_file' => [
        'size' => [
          'max' => '2M',
        ],
        'errors' => [
          'maxSize' => 'file.errors.maxSize',
          'mimeTypes' => 'file.errors.mimeTypes',
        ],
      ],
    ];
  }

  /**
   * @return array<string, array<string, array<string, int>>>
   */
  public function getImageSizeDefault(): array
  {
    return [
      'default' => [
        'width' => [
          'max' => 10000,
          'min' => 100,
        ],
        'height' => [
          'max' => 10000,
          'min' => 100,
        ],
      ],
    ];
  }

  /**
   * @return array<string, array<string, string>>
   */
  public function getCropperDefault(): array
  {
    return [
      'desktop' => [
        'name' => 'desktop',
        'picto' => 'austral-picto-monitor',
        'ratio' => '',
      ],
      'tablet' => [
        'name' => 'tablet',
        'picto' => 'austral-picto-pad',
        'ratio' => '',
      ],
      'mobile' => [
        'name' => 'mobile',
        'picto' => 'austral-picto-smartphone',
        'ratio' => '',
      ],
    ];
  }
}

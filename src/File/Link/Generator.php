<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Link;

use Austral\Contracts\File\FileInterface;
use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Austral Generator File and Image url.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class Generator
{
  /**
   * @var string
   */
  protected string $defaultLocale = 'en';

  /**
   * Generator constructor.
   *
   * @param RouterInterface $router
   * @param Mapping<T>      $mapping
   * @param string          $defaultLocale
   */
  public function __construct(
    protected RouterInterface $router,
    protected Mapping $mapping,
    string $defaultLocale = 'en'
  ) {
    $this->defaultLocale = $defaultLocale;
  }

  /**
   * Download Url initializations
   *
   * @param FileInterface<T>                       $object
   * @param string                                 $fieldname
   * @param array{urlPath?: string, urlType?: int} $params
   *
   * @return string|null
   *
   * @throws \Exception
   */
  public function download(FileInterface $object, string $fieldname, array $params = []): ?string
  {
    /** @var FieldFileMapping<T>|null $fieldFileMapping */
    $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
    if ($fieldFileMapping)
    {
      $urlParameters = [
        'entityKey' => $fieldFileMapping->getSlugger(),
        'id' => $object->getObjectIdToFile(),
        'fieldname' => $fieldname,
        'value' => $fieldFileMapping->getFilenameWithoutExtension($object),
        'extension' => $fieldFileMapping->getFilenameExtension($object),
      ];

      if ($this->router->getRouteCollection()->get(AustralTools::valueByKey($params, 'urlPath', 'austral_entity_file_download'))?->getRequirement('_locale'))
      {
        if ($object instanceof TranslateMasterInterface)
        {
          $urlParameters['_locale'] = $object->getLanguageCurrent();
        }
        else
        {
          $urlParameters['_locale'] = $this->defaultLocale;
        }
      }

      return $this->getObjectFilePath($fieldFileMapping, $object) ? $this->generateUrl(
        AustralTools::valueByKey($params, 'urlPath', 'austral_entity_file_download'),
        $urlParameters,
        AustralTools::valueByKey($params, 'urlType', UrlGeneratorInterface::ABSOLUTE_PATH)
      ) : null;
    }

    return null;
  }

  /**
   * @param FileInterface<T>                                     $object
   * @param string                                               $fieldname
   * @param string|null                                          $mode
   * @param int|null                                             $width
   * @param int|null                                             $height
   * @param string|null                                          $type
   * @param array{urlPath?: string, urlType?: int, webp?: mixed} $params
   *
   * @return string|null
   *
   * @throws \Exception
   */
  public function image(
    FileInterface $object,
    string $fieldname,
    ?string $type = 'original',
    ?string $mode = 'resize',
    ?int $width = null,
    ?int $height = null,
    array $params = []
  ): ?string {
    $type = $type ?: 'original';
    $mode = $mode ?: 'resize';

    if (!str_contains($mode, '-'))
    {
      $mode .= '-ratio';
    }

    /** @var FieldFileMapping<T>|null $fieldFileMapping */
    $fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
    if ($fieldFileMapping)
    {
      $urlParameters = [
        'entityKey' => $fieldFileMapping->getSlugger(),
        'id' => $object->getObjectIdToFile(),
        'fieldname' => $fieldname,
        'type' => $type,
        'mode' => $mode,
        'width' => $width,
        'height' => $height,
        'value' => $fieldFileMapping->getFilenameWithoutExtension($object),
        'extension' => array_key_exists('webp', $params) ? 'webp' : $fieldFileMapping->getFilenameExtension($object),
      ];

      if ($this->router->getRouteCollection()->get(AustralTools::valueByKey($params, 'urlPath', 'austral_entity_file_thumbnail'))?->getRequirement('_locale'))
      {
        if ($object instanceof TranslateMasterInterface)
        {
          $urlParameters['_locale'] = $object->getLanguageCurrent();
        }
        else
        {
          $urlParameters['_locale'] = $this->defaultLocale;
        }
      }

      return $this->getObjectFilePath($fieldFileMapping, $object) ? $this->generateUrl(
        AustralTools::valueByKey($params, 'urlPath', 'austral_entity_file_thumbnail'),
        $urlParameters,
        AustralTools::valueByKey($params, 'urlType', UrlGeneratorInterface::ABSOLUTE_PATH)
      ) : null;
    }

    return null;
  }

  /**
   * @param string               $route
   * @param array<string, mixed> $parameters
   * @param int                  $referenceType
   *
   * @return string
   */
  protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    return $this->router->generate($route, $parameters, $referenceType);
  }

  /**
   * @param FieldFileMapping<T>   $fieldFileMapping
   * @param FileInterface<T>|null $object
   *
   * @return string|null
   *
   * @throws \Exception
   */
  protected function getObjectFilePath(FieldFileMapping $fieldFileMapping, ?FileInterface $object): ?string
  {
    return $object ? $fieldFileMapping->getObjectFilePath($object) : null;
  }
}

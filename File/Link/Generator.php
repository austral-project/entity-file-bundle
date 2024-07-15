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

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Austral Generator File and Image url.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Generator
{
  /**
   * @var UrlGeneratorInterface $urlGenerator
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * @var Mapping $mapping
   */
  protected Mapping $mapping;

  /**
   * @var string
   */
  protected string $defaultLocale = "en";

  /**
   * Generator constructor.
   *
   * @param UrlGeneratorInterface $urlGenerator
   * @param Mapping $mapping
   * @param string $defaultLocale
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, Mapping $mapping, string $defaultLocale = "en")
  {
    $this->urlGenerator = $urlGenerator;
    $this->mapping = $mapping;
    $this->defaultLocale = $defaultLocale;
  }

  /**
   * Download Url initializations
   *
   * @param FileInterface $object
   * @param string $fieldname
   * @param array $params
   *
   * @return string|null
   * @throws \Exception
   */
  public function download(FileInterface $object, string $fieldname, array $params = array()): ?string
  {
    if($fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      $urlParameters = array(
        "entityKey"   =>  $fieldFileMapping->getSlugger(),
        "id"          =>  $object->getObjectIdToFile(),
        "fieldname"   =>  $fieldname,
        "value"       =>  $fieldFileMapping->getFilenameWithoutExtension($object),
        "extension"   =>  $fieldFileMapping->getFilenameExtension($object)
      );

      if($this->urlGenerator->getRouteCollection()->get(AustralTools::getValueByKey($params, "urlPath", "austral_entity_file_download"))->getRequirement("_locale"))
      {
        if($object instanceof TranslateMasterInterface)
        {
          $urlParameters["_locale"] = $object->getLanguageCurrent();
        }
        else
        {
          $urlParameters["_locale"] = $this->defaultLocale;
        }
      }

      return $this->getObjectFilePath($fieldFileMapping, $object) ? $this->generateUrl(
        AustralTools::getValueByKey($params, "urlPath", "austral_entity_file_download"),
        $urlParameters,
        AustralTools::getValueByKey($params, "urlType", UrlGeneratorInterface::ABSOLUTE_PATH)
      ) : null;
    }
    return null;
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   * @param string|null $mode
   * @param int|null $width
   * @param int|null $height
   * @param string|null $type
   * @param array $params
   *
   * @return string|null
   * @throws \Exception
   */
  public function image(FileInterface $object,
    string $fieldname,
    ?string $type = "original",
    ?string $mode = "resize",
    int $width = null,
    int $height = null,
    array $params = array()
  ): ?string {

    $type = $type ? : "original";
    $mode = $mode ? : "resize";

    if(strpos($mode, "-") === false)
    {
      $mode .= "-ratio";
    }

    /** @var FieldFileMapping $fieldFileMapping */
    if($fieldFileMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      $urlParameters = array(
        "entityKey"   =>  $fieldFileMapping->getSlugger(),
        "id"          =>  $object->getObjectIdToFile(),
        "fieldname"   =>  $fieldname,
        "type"        =>  $type,
        "mode"        =>  $mode,
        "width"       =>  $width,
        "height"      =>  $height,
        "value"       =>  $fieldFileMapping->getFilenameWithoutExtension($object),
        "extension"   =>  array_key_exists("webp", $params)? "webp" : $fieldFileMapping->getFilenameExtension($object)
      );


      if($this->urlGenerator->getRouteCollection()->get(AustralTools::getValueByKey($params, "urlPath", "austral_entity_file_thumbnail"))->getRequirement("_locale"))
      {
        if($object instanceof TranslateMasterInterface)
        {
          $urlParameters["_locale"] = $object->getLanguageCurrent();
        }
        else
        {
          $urlParameters["_locale"] = $this->defaultLocale;
        }
      }

      return $this->getObjectFilePath($fieldFileMapping, $object) ? $this->generateUrl(
        AustralTools::getValueByKey($params, "urlPath", "austral_entity_file_thumbnail"),
        $urlParameters,
        AustralTools::getValueByKey($params, "urlType", UrlGeneratorInterface::ABSOLUTE_PATH)
      ) : null;
    }
    return null;
  }

  /**
   * @param string $route
   * @param array $parameters
   * @param int $referenceType
   *
   * @return string
   */
  protected function generateUrl(string $route, array $parameters = array(), int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
  {
    return $this->urlGenerator->generate($route, $parameters, $referenceType);
  }

  /**
   * @param FieldFileMapping $fieldFileMapping
   * @param FileInterface|null $object
   *
   * @return string|null
   * @throws \Exception
   */
  protected function getObjectFilePath(FieldFileMapping $fieldFileMapping, ?FileInterface $object): ?string
  {
    return $object ? $fieldFileMapping->getObjectFilePath($object) : null;
  }

}

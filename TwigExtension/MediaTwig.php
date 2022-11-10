<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\EntityFileBundle\TwigExtension;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\Entity\Interfaces\FileInterface;
use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Austral Media Twig Extension.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class MediaTwig extends AbstractExtension
{

  /**
   * @var ContainerInterface $container
   */
  protected ContainerInterface $container;

  /**
   * @var Mapping $mapping
   */
  protected Mapping $mapping;

  /**
   * Initialize tinymce helper
   *
   * @param ContainerInterface $container
   * @param Mapping $mapping
   */
  public function __construct(ContainerInterface $container, Mapping $mapping)
  {
    $this->container = $container;
    $this->mapping = $mapping;
  }

  /**
   * @return TwigFilter[]
   */
  public function getFilters()
  {
    return [
      new TwigFilter('austral_entity_file_parameters', [$this, 'parameters']),
      new TwigFilter('austral_entity_file_image_url', [$this, 'image']),
      new TwigFilter('austral_entity_file_download_url', [$this, 'download']),

      new TwigFilter('austral_entity_file_path', [$this, 'filePath']),
      new TwigFilter('austral_entity_file_exist', [$this, 'fileExist']),
      new TwigFilter('austral_entity_file_size', [$this, 'fileSize']),
      new TwigFilter('austral_entity_file_mime_type', [$this, 'fileMimeType']),
      new TwigFilter('austral_entity_file_is_image', [$this, 'isImage']),
      new TwigFilter('austral_entity_file_image_size', [$this, 'imageSize']),
      new TwigFilter('austral_entity_file_image_ratio', [$this, 'imageRatio']),
    ];
  }

  /**
   * @return TwigFunction[]
   */
  public function getFunctions()
  {
    return array(
      "austral_entity_file.image_url"    => new TwigFunction("image", array($this, "image")),
      "austral_entity_file.download_url" => new TwigFunction("download", array($this, "download")),

      "austral_entity_file.path"         => new TwigFunction("austral_entity_file.path", array($this, "filePath")),
      "austral_entity_file.parameters"   => new TwigFunction("austral_entity_file.parameters", array($this, "parameters")),
      "austral_entity_file.exist"        => new TwigFunction("austral_entity_file.exist", array($this, "fileExist")),
      "austral_entity_file.size"         => new TwigFunction("austral_entity_file.size", array($this, "fileSize")),
      "austral_entity_file.mime_type"    => new TwigFunction("austral_entity_file.mime_type", array($this, "fileMimeType")),
      "austral_entity_file.is_image"     => new TwigFunction("austral_entity_file.is_image", array($this, "isImage")),
      "austral_entity_file.image_size"   => new TwigFunction("austral_entity_file.image_size", array($this, "imageSize")),
      "austral_entity_file.image_ratio"  => new TwigFunction("austral_entity_file.image_ratio", array($this, "imageRatio")),
    );
  }

  /**
   * Download Url initializations
   *
   * @param FileInterface $object
   * @param string $fieldname
   * @param array $params
   *
   * @return string|null
   */
  public function download(FileInterface $object, string $fieldname, array $params = array()): ?string
  {
    return $this->container->get('austral.entity_file.link.generator')->download($object, $fieldname, $params);
  }

  /**
   * Download Url initializations
   *
   * @param FileInterface $object
   * @param string $fieldname
   * @param string|null $mode
   * @param int|null $width
   * @param int|null $height
   * @param string|null $type
   * @param array $params
   *
   * @return string|null
   */
  public function image(FileInterface $object, string $fieldname, ?string $type = "original", ?string $mode = "resize", int $width = null, int $height = null, array $params = array()): ?string
  {
    return $this->container->get('austral.entity_file.link.generator')->image($object, $fieldname, $type, $mode, $width, $height, $params);
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   *
   * @return bool
   * @throws \Exception
   */
  public function fileExist(FileInterface $object, string $fieldname): bool
  {
    return (bool)$this->filePath($object, $fieldname);
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   * @param bool $humanize
   *
   * @return false|int|string
   * @throws \Exception
   */
  public function fileSize(FileInterface $object, string $fieldname, bool $humanize = false)
  {
    if($filePath = $this->filePath($object, $fieldname))
    {
      return $humanize ? AustralTools::humanizeSize($filePath) : filesize($filePath);
    }
    return 0;
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   *
   * @return string|null
   * @throws \Exception
   */
  public function fileMimeType(FileInterface $object, string $fieldname): ?string
  {
    if($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::mimeType($filePath);
    }
    return null;
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   *
   * @return bool
   * @throws \Exception
   */
  public function isImage(FileInterface $object, string $fieldname): bool
  {
    if($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::isImage($filePath);
    }
    return false;
  }

  /**
   * @param FileInterface $object
   * @param string $fieldname
   * @param bool $returnArray
   *
   * @return array|string|null
   * @throws \Exception
   */
  public function imageSize(FileInterface $object, string $fieldname, bool $returnArray = true)
  {
    if($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::imageDimension($filePath, $returnArray);
    }
    return $returnArray ? array() : null;
  }

  /**
   * @param FileInterface|null $object
   * @param string $fieldname
   *
   * @return string|null
   * @throws \Exception
   */
  public function filePath(?FileInterface $object, string $fieldname): ?string
  {
    /** @var FieldFileMapping $fieldMapping */
    if($fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      return $fieldMapping->getObjectFilePath($object);
    }
    return null;
  }


  /**
   * @param FileInterface|EntityFileCropperTrait|null $object
   * @param string $fieldname
   * @param string|null $cropperKey
   *
   * @return float|null
   * @throws \Exception
   */
  public function imageRatio(?FileInterface $object, string $fieldname, string $cropperKey = null): ?float
  {
    $ratio = null;
    if($imageSizes = $this->imageSize($object, $fieldname, true))
    {
      $ratio = $imageSizes["width"]/$imageSizes['height'];
      /** @var FieldFileMapping $fieldMapping */
      if($fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
      {
        if($cropperKey && ($cropperData = $fieldMapping->getCropperDataByFieldname($object, $cropperKey)))
        {
          $cropBoxData = AustralTools::getValueByKey($cropperData, "cropBoxData", array());
          $cropBoxWidth = (float) AustralTools::getValueByKey($cropBoxData, "width", 0);
          $cropBoxHeight = (float) AustralTools::getValueByKey($cropBoxData, "height", 0);
          $ratio = $cropBoxWidth/$cropBoxHeight;
        }
      }
    }
    return $ratio;
  }

  /**
   * @param FileInterface|null $object
   * @param string $fieldname
   *
   * @return array
   * @throws \Exception
   */
  public function parameters(?FileInterface $object, string $fieldname): array
  {
    $parameters = array(
      "file"          =>  array(
        "reelFilename"    =>  "",
        "path"            =>  array(
          "view"          =>  null,
          "download"      =>  null,
          "absolute"      =>  null,
        ),
      ),
      "infos"         =>  array(
        "mimeType"      =>  null,
        "extension"     =>  null,
        "size"          =>  null,
        "sizeHuman"     =>  null,
        "imageSize"     =>  null,
      ),
    );
    /** @var FieldFileMapping $fieldMapping */
    if($fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname))
    {
      if($filePath = $this->filePath($object, $fieldname))
      {
        $isImage  = AustralTools::isImage($filePath);
        $parameters["file"]["reelFilename"] = $fieldMapping->getFilename($object, true);
        $parameters["file"]['path'] = array(
          "view"          =>  $isImage ? $this->image($object, $fieldname, "original", "i", 200, 200) : null,
          "download"      =>  $this->download($object, $fieldname),
          "absolute"      =>  $filePath,
        );
        $parameters['infos'] = array(
          "mimeType"      =>  AustralTools::mimeType($filePath),
          "extension"     =>  AustralTools::extension($filePath),
          "size"          =>  filesize($filePath),
          "sizeHuman"     =>  AustralTools::humanizeSize($filePath),
          "imageSize"     =>  $isImage ? AustralTools::imageDimension($filePath) : null
        );
      }
    }
    return $parameters;
  }

}
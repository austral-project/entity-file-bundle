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

use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Austral Media Twig Extension.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class MediaTwig
{
  /**
   * Cropper constructor
   *
   * @param Generator<T> $generator
   * @param Mapping<T>   $mapping
   */
  public function __construct(
    #[Autowire(service: 'austral.entity_file.link.generator')] protected Generator $generator,
    #[Autowire(service: 'austral.entity.mapping')] protected Mapping $mapping
  ) {
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
  #[AsTwigFilter('austral_entity_file.download_url')]
  #[AsTwigFunction('austral_entity_file_download_url')]
  public function download(FileInterface $object, string $fieldname, array $params = []): ?string
  {
    return $this->generator->download($object, $fieldname, $params);
  }

  /**
   * Image Url initializations
   *
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
  #[AsTwigFilter('austral_entity_file.image_url')]
  #[AsTwigFunction('austral_entity_file_image_url')]
  public function image(FileInterface $object, string $fieldname, ?string $type = 'original', ?string $mode = 'resize', ?int $width = null, ?int $height = null, array $params = []): ?string
  {
    return $this->generator->image($object, $fieldname, $type, $mode, $width, $height, $params);
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   *
   * @return bool
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.exist')]
  #[AsTwigFunction('austral_entity_file_exist')]
  public function fileExist(FileInterface $object, string $fieldname): bool
  {
    return (bool) $this->filePath($object, $fieldname);
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   * @param bool             $humanize
   *
   * @return false|int|string
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.size')]
  #[AsTwigFunction('austral_entity_file_size')]
  public function fileSize(FileInterface $object, string $fieldname, bool $humanize = false): false|int|string
  {
    if ($filePath = $this->filePath($object, $fieldname))
    {
      return $humanize ? AustralTools::humanizeSize($filePath) : filesize($filePath);
    }

    return 0;
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   *
   * @return string|null
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.mime_type')]
  #[AsTwigFunction('austral_entity_file_mime_type')]
  public function fileMimeType(FileInterface $object, string $fieldname): ?string
  {
    if ($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::mimeType($filePath);
    }

    return null;
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   *
   * @return bool
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.is_image')]
  #[AsTwigFunction('austral_entity_file_is_image')]
  public function isImage(FileInterface $object, string $fieldname): bool
  {
    if ($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::isImage($filePath);
    }

    return false;
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   * @param bool             $returnArray
   *
   * @return array<string, int>|string
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.image_size')]
  #[AsTwigFunction('austral_entity_file_image_size')]
  public function imageSize(FileInterface $object, string $fieldname, bool $returnArray = true): array|string
  {
    if ($filePath = $this->filePath($object, $fieldname))
    {
      return AustralTools::imageDimension($filePath, $returnArray);
    }

    return $returnArray ? [] : '';
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   *
   * @return string|null
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.path')]
  #[AsTwigFunction('austral_entity_file_path')]
  public function filePath(FileInterface $object, string $fieldname): ?string
  {
    /** @var FieldFileMapping<T>|null $fieldMapping */
    $fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);

    return $fieldMapping?->getObjectFilePath($object);
  }

  /**
   * @param FileInterface<T> $object
   * @param string           $fieldname
   * @param string|null      $cropperKey
   *
   * @return float|null
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.image_ratio')]
  #[AsTwigFunction('austral_entity_file_image_ratio')]
  public function imageRatio(FileInterface $object, string $fieldname, ?string $cropperKey = null): ?float
  {
    $ratio = null;
    $imageSizes = $this->imageSize($object, $fieldname);
    if (is_array($imageSizes) && array_key_exists('width', $imageSizes) && array_key_exists('height', $imageSizes) && $imageSizes['width'] > 0 && $imageSizes['height'] > 0)
    {
      $ratio = $imageSizes['width'] / $imageSizes['height'];
      /** @var FieldFileMapping<T>|null $fieldMapping */
      $fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
      if ($fieldMapping && $cropperKey && ($cropperData = $fieldMapping->getCropperDataByFieldname($object, $cropperKey)))
      {
        $cropBoxData = AustralTools::valueByKey($cropperData, 'cropBoxData', []);
        $cropBoxWidth = (float) AustralTools::valueByKey($cropBoxData, 'width', 0);
        $cropBoxHeight = (float) AustralTools::valueByKey($cropBoxData, 'height', 0);
        $ratio = $cropBoxHeight > 0 ? $cropBoxWidth / $cropBoxHeight : $ratio;
      }
    }

    return $ratio;
  }

  /**
   * @param FileInterface<T>|null $object
   * @param string                $fieldname
   *
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  #[AsTwigFilter('austral_entity_file.parameters')]
  #[AsTwigFunction('austral_entity_file_parameters')]
  public function parameters(?FileInterface $object, string $fieldname): array
  {
    $parameters = [
      'file' => [
        'reelFilename' => '',
        'path' => [
          'view' => null,
          'original' => null,
          'download' => null,
          'absolute' => null,
        ],
      ],
      'infos' => [
        'mimeType' => null,
        'extension' => null,
        'size' => null,
        'sizeHuman' => null,
        'imageSize' => null,
        'imageDimension' => [
          'width' => null,
          'height' => null,
        ],
        'aspectRatio' => null,
      ],
    ];
    if ($object)
    {
      /** @var FieldFileMapping<T>|null $fieldMapping */
      $fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
      if ($fieldMapping)
      {
        if ($filePath = $this->filePath($object, $fieldname))
        {
          $isImage = AustralTools::isImage($filePath);
          $parameters['file']['reelFilename'] = $fieldMapping->getFilename($object, true);
          $parameters['file']['path'] = [
            'view' => $isImage ? $this->image($object, $fieldname, 'original', 'i', 1000, 1000) : null,
            'original' => $isImage ? $this->image($object, $fieldname) : null,
            'download' => $this->download($object, $fieldname),
            'absolute' => $filePath,
          ];
          $parameters['infos']['mimeType'] = AustralTools::mimeType($filePath);
          $parameters['infos']['extension'] = AustralTools::extension($filePath);
          $parameters['infos']['size'] = filesize($filePath);
          $parameters['infos']['sizeHuman'] = AustralTools::humanizeSize($filePath);
          if ($isImage)
          {
            /** @var array<string, int> $imageDimension */
            $imageDimension = AustralTools::imageDimension($filePath, true);
            $aspectRatio = null;
            if (array_key_exists('width', $imageDimension) && array_key_exists('height', $imageDimension))
            {
              if ($imageDimension['width'] > 0 && $imageDimension['height'] > 0)
              {
                $aspectRatio = $imageDimension['width'] / $imageDimension['height'];
              }
            }
            $parameters['infos']['imageSize'] = $imageDimension ? implode(' x ', $imageDimension) : null;
            $parameters['infos']['imageDimension'] = $imageDimension;
            $parameters['infos']['aspectRatio'] = $aspectRatio;
          }
        }
      }
    }

    return $parameters;
  }
}

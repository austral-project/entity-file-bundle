<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Bridge\Form\Extension;

use Austral\Contracts\File\FileInterface;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Austral Upload File Type Extension.
 *
 * Describes the file already uploaded for the object of the form (real name, paths, MIME type, size, image
 * dimensions) in form.vars.austral.widget.file of an UploadField. Computed for each view, so each entry of a
 * collection describes its own file (the prototype describes none).
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class UploadFileTypeExtension extends AbstractTypeExtension
{
  /**
   * @param Mapping<object>   $mapping
   * @param Generator<object> $fileLinkGenerator
   */
  public function __construct(
    private readonly Mapping $mapping,
    private readonly Generator $fileLinkGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getExtendedTypes(): iterable
  {
    return [FileType::class];
  }

  /**
   * {@inheritdoc}
   */
  public function buildView(FormView $view, FormInterface $form, array $options): void
  {
    $austral = $view->vars['austral'] ?? null;
    $object = $form->getParent()?->getData();
    if (!\is_array($austral) || 'uploadField' !== ($austral['field_class'] ?? null) || !$object instanceof FileInterface)
    {
      return;
    }
    $fieldname = $form->getName();
    $fieldMapping = $this->mapping->getFieldsMappingByFieldname($object->getClassnameForMapping(), FieldFileMapping::class, $fieldname);
    if (!$fieldMapping instanceof FieldFileMapping)
    {
      return;
    }
    $file = $this->describe($object, $fieldname, $fieldMapping);
    if (null !== $file)
    {
      $austral['widget']['file'] = $file;
      $view->vars['austral'] = $austral;
    }
  }

  /**
   * Same structure as the option "file" of UploadField, null when the object has no file.
   *
   * @param FileInterface<object> $object
   * @param string $fieldname
   * @param FieldFileMapping<object> $fieldMapping
   *
   * @return array<string, mixed>|null
   * @throws \Exception
   */
  private function describe(FileInterface $object, string $fieldname, FieldFileMapping $fieldMapping): ?array
  {
    $filePath = $fieldMapping->getObjectFilePath($object);
    if (!$filePath || !is_file($filePath))
    {
      return null;
    }
    $isImage = AustralTools::isImage($filePath);
    $infos = [
      'mime_type' => AustralTools::mimeType($filePath),
      'extension' => AustralTools::extension($filePath),
      'size' => filesize($filePath) ?: null,
      'size_human' => AustralTools::humanizeSize($filePath),
      'image_size' => null,
      'aspect_ratio' => null,
      'image_dimension' => ['width' => null, 'height' => null],
    ];
    if ($isImage)
    {
      $dimension = AustralTools::imageDimension($filePath, true);
      $width = $dimension['width'] ?? null;
      $height = $dimension['height'] ?? null;
      $infos['image_size'] = $dimension ? implode(' x ', $dimension) : null;
      $infos['image_dimension'] = ['width' => $width, 'height' => $height];
      $infos['aspect_ratio'] = $width > 0 && $height > 0 ? $width / $height : null;
    }

    return [
      'reel_filename' => $fieldMapping->getFilename($object, true),
      'path' => [
        'view' => $isImage ? $this->fileLinkGenerator->image($object, $fieldname, 'original', 'i', 1000, 1000) : null,
        'original' => $isImage ? $this->fileLinkGenerator->image($object, $fieldname) : null,
        'download' => $this->fileLinkGenerator->download($object, $fieldname),
        'absolute' => $filePath,
      ],
      'infos' => $infos,
    ];
  }
}

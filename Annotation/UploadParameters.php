<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\EntityFileBundle\Annotation;

use Austral\EntityBundle\Annotation\AustralEntityAnnotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class UploadParameters extends AustralEntityAnnotation implements EntityFileAnnotationInterface
{

  /**
   * @var string|null
   */
  public ?string $configName = "";

  /**
   * @var bool
   */
  public bool $isRequired = false;

  /**
   * @var array
   */
  public array $mimeTypes = array();

  /**
   * @var string|null
   */
  public ?string $sizeMax = null;

  /**
   * @var string|null
   */
  public ?string $virtualnameField = null;

  /**
   * @var string
   */
  public string $errorMaxSize = "file.errors.maxSize";

  /**
   * @var string
   */
  public string $errorMimeTypes = "file.errors.mimeTypes";

  /**
   * @param string|null $configName
   * @param bool $isRequired
   * @param array $mimeTypes
   * @param string|null $sizeMax
   * @param string|null $virtualnameField
   */
  public function __construct(string $configName = null,
    bool $isRequired = false,
    array $mimeTypes = array(),
    ?string $sizeMax = null,
    ?string $virtualnameField = null
  ) {
    $this->configName = $configName;
    $this->isRequired = $isRequired;
    $this->mimeTypes = $mimeTypes;
    $this->sizeMax = $sizeMax;
    $this->virtualnameField = $virtualnameField;
  }

  /**
   * @return string
   */
  public function getTypeFile(): string
  {
    $typeFile = $this->mimeTypes ? "picture" : "file";
    foreach($this->mimeTypes as $mimeType)
    {
      if(strpos($mimeType, "image/") === false)
      {
        $typeFile = "file";
      }
    }
    return $typeFile;
  }

}
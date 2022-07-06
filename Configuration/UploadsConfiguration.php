<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\EntityFileBundle\Configuration;

use Austral\ToolsBundle\Configuration\BaseConfiguration;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral File Entity Uploads Parameters.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
Class UploadsConfiguration extends BaseConfiguration
{
  /**
   * @var CropperConfiguration
   */
  protected CropperConfiguration $cropperConfiguration;

  /**
   * @var int|null
   */
  protected ?int $niveauMax = null;

  /**
   * @var string|null
   */
  protected ?string $prefix = "entity_file_upload";


  /**
   * Initialize the service
   *
   * @param array Config
   */
  public function __construct(array $config, CropperConfiguration $cropperConfiguration)
  {
    parent::__construct($config);
    $this->cropperConfiguration = $cropperConfiguration;
  }

  /**
   * @param string $key
   *
   * @return string
   */
  public function getUploadsPath(string $key): string
  {
    return $this->get("{$key}.path.uploads");
  }

  /**
   * @param string $key
   *
   * @return string
   */
  public function getThumbnailPath(string $key): string
  {
    return $this->get("{$key}.path.thumbnail");
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return array|string
   */
  public function get_(string $key, $default = null)
  {
    $matches = explode(".", $key);
    $entitySluggerCase = $matches[0];
    if(strpos($entitySluggerCase, "@") !== false)
    {
      $keys = array();
      foreach (explode("@", $entitySluggerCase) as $key)
      {
        $keys[] = AustralTools::slugger($key);
      }
      $entitySluggerCase = implode("@", $keys);
    }
    if($key === "root.path.uploads" || $key === "root.path.thumbnail") {
      $entitySluggerCase = null;
    }
    $matches[0] = "default";
    $defaultKeyWithField = implode(".", $matches);
    if($matches[1] === "form")
    {
      $matches[2] = "default";
    }
    $defaultKey = implode(".", $matches);
    $return = parent::get($key, parent::get($defaultKeyWithField, parent::get($defaultKey, $default)));

    return is_array($return) ? $return : str_replace(array('@entity_slugger_case@'), array($entitySluggerCase), $return);
  }

  public function getCropper($key): array
  {
    $listCropper = $this->get("{$key}.cropper", array());
    $cropperFinal = array();
    foreach($listCropper as $cropperKey)
    {
      if($this->cropperConfiguration->get($cropperKey))
      {
        $cropperFinal[$cropperKey] = $this->cropperConfiguration->get($cropperKey);
      }
    }
    return $cropperFinal;
  }

}
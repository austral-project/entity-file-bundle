<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\EntityFileBundle\Entity\Traits;

use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\Mapping as ORM;


/**
 * Austral Entity File Cropper Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait EntityFileCropperTrait
{

  /**
   * @var array|null
   * @ORM\Column(name="cropper_data", type="json", length=255, nullable=true)
   */
  protected ?array $cropperData = array();

  /**
   * @var array
   */
  protected array $generateCropperByKey = array();

  /**
   * @return array
   */
  public function getCropperData(): array
  {
    return $this->cropperData ? : array();
  }

  /**
   * @param array $cropperData
   *
   * @return $this
   */
  public function setCropperData(array $cropperData)
  {
    $this->cropperData = $cropperData;
    return $this;
  }

  /**
   * @param string $filename
   *
   * @return array
   */
  public function getCropperDataByFilename(string $filename): array
  {
    return AustralTools::getValueByKey($this->getCropperData(), $filename, array());
  }

  /**
   * @return array
   */
  public function getGenerateCropperByKey(): array
  {
    return $this->generateCropperByKey;
  }

  /**
   * @param array $generateCropperByKey
   *
   * @return $this
   */
  public function setGenerateCropperByKey(array $generateCropperByKey)
  {
    $this->generateCropperByKey = $generateCropperByKey;
    return $this;
  }

}
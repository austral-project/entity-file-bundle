<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Image;

use Austral\EntityFileBundle\File\Compression\Compression;
use Austral\ToolsBundle\AustralTools;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Image.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Image
{

  /**
   * @var string
   */
  protected string $reelImagePath;

  /**
   * @var mixed
   */
  protected $imagine;

  /**
   * @var \Imagine\Gd\Image
   */
  protected \Imagine\Gd\Image $image;

  /**
   * @var Filesystem
   */
  protected Filesystem $fileSystem;

  /**
   * @var Compression
   */
  protected Compression $compression;

  /**
   * Image constructor.
   *
   * @param $imagineLib
   * @param Compression $compression
   */
  public function __construct($imagineLib, Compression $compression)
  {
    $this->imagine = new $imagineLib();
    $this->compression = $compression;
    $this->fileSystem = new Filesystem();
  }

  /**
   * @param string $reelImagePath
   *
   * @return $this
   * @throws \Exception
   */
  public function open(string $reelImagePath): Image
  {
    $this->reelImagePath = $reelImagePath;
    if(!$this->isImage(($this->reelImagePath)))
    {
      throw new \Exception("The file is not image {$this->reelImagePath}");
    }
    $this->image = $this->imagine->open($reelImagePath);
    return $this;
  }

  /**
   * @param string $reelImagePath
   *
   * @return array
   */
  public function infosForReelImage(string $reelImagePath): array
  {
    $params = array();
    if(file_exists($reelImagePath))
    {
      if($this->isImage($reelImagePath))
      {
        $this->reelImagePath = $reelImagePath;
        $this->image = $this->imagine->open($reelImagePath);
        $params['width'] = $this->getWidth();
        $params['height'] = $this->getHeight();
      }
    }
    return $params;
  }

  /**
   * @return array
   */
  public function metadata(): array
  {
    if($this->getMimeTypeReel() == "image/jpg" || $this->getMimeTypeReel() == "image/jpeg")
    {
      try {
        return exif_read_data($this->reelImagePath);
      }
      catch (\Exception $e)
      {
        return array();
      }
    }
    return array();
  }

  /**
   * @return mixed|string|string[]
   */
  public function orientation()
  {
    return AustralTools::getValueByKey($this->metadata(), "Orientation", null);
  }

  /**
   * @return $this
   */
  public function autoRotate(): Image
  {
    $rotateVal = 0;
    switch($this->orientation()) {
      case 8:
        $rotateVal = -90;
      break;
      case 3:
        $rotateVal = 180;
      break;
      case 6:
        $rotateVal = 90;
      break;
    }
    if($rotateVal)
    {
      $this->image->rotate($rotateVal);
    }
    return $this;
  }

  /**
   * @param $filename
   *
   * @return bool
   */
  public function isImage($filename): bool
  {
    return AustralTools::isImage($filename);
  }

  /**
   * @param null $width
   * @param null $height
   * @param null $mode
   *
   * @return $this
   */
  public function thumbnail($width = null, $height = null, $mode = null): Image
  {
    $mode = ($mode == "o" ? ImageInterface::THUMBNAIL_OUTBOUND : ImageInterface::THUMBNAIL_INSET);
    if(!$width)
    {
      $width = $this->calculateAutoWidth($height);
    }
    elseif(!$height)
    {
      $height = $this->calculateAutoHeight($width);
    }
    $sizeBox = new Box($width, $height);
    $this->image->thumbnail($sizeBox, $mode);
    return $this;
  }


  /**
   * @param null $width
   * @param null $height
   * @param null $modeResize
   *
   * @return $this
   */
  public function resize($width = null, $height = null, $modeResize = null): Image
  {
    if($width && $height)
    {
      if($modeResize == "min")
      {
        if($this->getWidth() > $this->getHeight())
        {
          $width = $this->calculateAutoWidth($height);
        }
        elseif($this->getWidth() < $this->getHeight())
        {
          $height = $this->calculateAutoHeight($width);
        }
        else
        {
          if($width < $height)
          {
            $width = $height;
          }
          else
          {
            $height = $width;
          }
        }
      }
      elseif($modeResize == "ratio")
      {
        $ratioImage = (float) $this->getWidth()/$this->getHeight();
        $ratioBySize = (float) $width/$height;
        if($ratioImage < $ratioBySize)
        {
          $height = $this->calculateAutoHeight($width);
        }
        elseif($ratioImage > $ratioBySize)
        {
          $width = $this->calculateAutoWidth($height);
        }
      }
      elseif($modeResize == "max")
      {
        if($this->getWidth() < $this->getHeight())
        {
          $width = $this->calculateAutoWidth($height);
        }
        elseif($this->getWidth() > $this->getHeight())
        {
          $height = $this->calculateAutoHeight($width);
        }
        else
        {
          if($width > $height)
          {
            $width = $height;
          }
          else
          {
            $height = $width;
          }
        }
      }
    }
    else
    {
      if(!$width)
      {
        $width = $this->calculateAutoWidth($height);
      }
      elseif(!$height)
      {
        $height = $this->calculateAutoHeight($width);
      }
    }
    $sizeBox = new Box($width, $height);
    $this->image->resize($sizeBox);
    return $this;
  }

  /**
   * @param $natutalWidth
   * @param $naturalHeight
   * @param $positionX
   * @param $positionY
   * @param $cropWidth
   * @param $cropHeight
   * @param int $rotate
   * @param null $flip
   *
   * @return $this
   */

  public function crop($natutalWidth, $naturalHeight, $positionX, $positionY, $cropWidth, $cropHeight, int $rotate = 0, $flip = null): Image
  {
    switch($this->orientation()) {
      case 8:
        $rotate = -90;
      break;
      case 3:
        $rotate = 180;
      break;
      case 6:
        $rotate = 90;
      break;
    }

    if($rotate)
    {
      $this->image->rotate($rotate);
    }

    if($flip == "horizontal")
    {
      $this->image->flipHorizontally();
    }
    elseif($flip == "vertical")
    {
      $this->image->flipVertically();
    }

    $sizeImg = new Box($natutalWidth, $naturalHeight);
    $sizeBox = new Box($cropWidth, $cropHeight);
    $point = new Point($positionX, $positionY);

    $this->image->resize($sizeImg)->crop($point, $sizeBox);
    return $this;
  }

  /**
   * @param $savePath
   * @param array $generateOtherFormat
   *
   * @return $this
   */
  public function save($savePath, array $generateOtherFormat = array()): Image
  {
    $this->createDirSave($savePath);
    $this->image->save($savePath);
    $this->compression->compress($savePath, $generateOtherFormat);
    return $this;
  }

  /**
   * @param $savePath
   */
  protected function createDirSave($savePath)
  {
    $infoPath = pathinfo($savePath);
    $dirname = array_key_exists("dirname", $infoPath) ? $infoPath['dirname'] : null;
    if(!file_exists($dirname) && $dirname)
    {
      $this->fileSystem->mkdir($dirname);
    }
  }

  /**
   * @return string|null
   */
  public function getMimeTypeReel(): ?string
  {
    return AustralTools::mimeType($this->reelImagePath);
  }

  /**
   * @return string
   */
  public function getExtension(): string
  {
    return AustralTools::extension($this->reelImagePath);
  }

  /**
   * @return false|int
   */
  public function getFileSizeReel()
  {
    return filesize($this->reelImagePath);
  }

  /**
   * @return Box|BoxInterface
   */
  public function getSize()
  {
    return $this->image->getSize();
  }

  /**
   * @return int
   */
  public function getWidth(): int
  {
    return $this->getSize()->getWidth();
  }

  /**
   * @return int
   */
  public function getHeight(): int
  {
    return $this->getSize()->getHeight();
  }

  /**
   * @param $newHeight
   *
   * @return float
   */
  protected function calculateAutoWidth($newHeight): float
  {
    $width = $this->getWidth();
    $height = $this->getHeight();
    return round($width*$newHeight/$height);
  }

  /**
   * @param $newWidth
   *
   * @return float
   */
  protected function calculateAutoHeight($newWidth): float
  {
    $width = $this->getWidth();
    $height = $this->getHeight();
    return round($height*$newWidth/$width);
  }
  
}
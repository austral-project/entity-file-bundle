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
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Image.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Image
{

  /**
   * @var string|null
   */
  protected ?string $reelImagePath = null;

  /**
   * @var mixed
   */
  protected mixed $imagine;

  /**
   * @var \Imagine\Gd\Image|null
   */
  protected ?\Imagine\Gd\Image $image = null;

  /**
   * @var Filesystem
   */
  protected Filesystem $fileSystem;

  /**
   * Image constructor.
   *
   * @param string $imagineLib
   * @param Compression $compression
   */
  public function __construct(string $imagineLib,
    #[Autowire(service: "austral.entity_file.compression")] protected Compression $compression)
  {
    $this->imagine = new $imagineLib();
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
   * @return array<string, mixed>
   */
  public function infosForReelImage(string $reelImagePath): array
  {
    $params = [];
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
   * @return array<string, mixed>
   */
  public function metadata(): array
  {
    if ($this->getMimeTypeReel() !== 'image/jpg' && $this->getMimeTypeReel() !== 'image/jpeg') {
      return [];
    }
    try {
      $data = $this->reelImagePath && file_exists($this->reelImagePath) ? @exif_read_data($this->reelImagePath) : [];
      return is_array($data) ? $data : [];
    } catch (\Throwable) {
      return [];
    }
  }

  /**
   * @return mixed|string|string[]
   */
  public function orientation(): mixed
  {
    return AustralTools::valueByKey($this->metadata(), "Orientation", null);
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
      $this->image?->rotate($rotateVal);
    }
    return $this;
  }

  /**
   * @param string $filename
   *
   * @return bool
   */
  public function isImage(string $filename): bool
  {
    return AustralTools::isImage($filename);
  }

  /**
   * @param int|float|null $width
   * @param int|float|null $height
   * @param string|null $mode
   *
   * @return $this
   */
  public function thumbnail(int|float|null $width = null, int|float|null $height = null, ?string $mode = null): Image
  {
    $mode = ($mode === "o" ? ManipulatorInterface::THUMBNAIL_OUTBOUND : ManipulatorInterface::THUMBNAIL_INSET);
    if(!$width && $height)
    {
      $width = $this->calculateAutoWidth($height);
    }
    elseif(!$height && $width)
    {
      $height = $this->calculateAutoHeight($width);
    }
    $width = (int) $width;
    $height = (int) $height;
    $sizeBox = new Box($width, $height);
    if ($this->image) {
      $this->image = $this->image->thumbnail($sizeBox, $mode);
    }
    return $this;
  }


  /**
   * @param float|int|null $width
   * @param float|int|null $height
   * @param string|null $modeResize
   *
   * @return $this
   */
  public function resize(float|int|null $width = null, float|int|null $height = null, ?string $modeResize = null): Image
  {
    if($width && $height)
    {
      if($modeResize === "min")
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
      elseif($modeResize === "ratio")
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
      if(!$width && $height)
      {
        $width = $this->calculateAutoWidth($height);
      }
      elseif(!$height && $width)
      {
        $height = $this->calculateAutoHeight($width);
      }
    }
    $width = (int) $width;
    $height = (int) $height;
    $sizeBox = new Box($width, $height);
    $this->image?->resize($sizeBox);
    return $this;
  }

  /**
   * @param int $natutalWidth
   * @param int $naturalHeight
   * @param int $positionX
   * @param int $positionY
   * @param int $cropWidth
   * @param int $cropHeight
   * @param int $rotate
   * @param string|null $flip
   *
   * @return $this
   */

  public function crop(int $natutalWidth, int $naturalHeight, int $positionX, int $positionY, int $cropWidth, int $cropHeight, int $rotate = 0, ?string $flip = null): Image
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
      $this->image?->rotate($rotate);
    }

    if($flip === "horizontal")
    {
      $this->image?->flipHorizontally();
    }
    elseif($flip === "vertical")
    {
      $this->image?->flipVertically();
    }

    $sizeImg = new Box($natutalWidth, $naturalHeight);
    $sizeBox = new Box($cropWidth, $cropHeight);
    $point = new Point($positionX, $positionY);

    $this->image?->resize($sizeImg)->crop($point, $sizeBox);
    return $this;
  }

  /**
   * @param string $savePath
   * @param array<int, mixed> $generateOtherFormat
   *
   * @return $this
   */
  public function save(string $savePath, array $generateOtherFormat = []): Image
  {
    $this->createDirSave($savePath);
    $this->image?->save($savePath);
    $this->compression->compress($savePath, $generateOtherFormat);
    return $this;
  }

  /**
   * @param string $savePath
   */
  protected function createDirSave(string $savePath): void
  {
    $infoPath = pathinfo($savePath);
    $dirname = array_key_exists("dirname", $infoPath) ? $infoPath['dirname'] : null;
    if($dirname && !file_exists($dirname))
    {
      $this->fileSystem->mkdir($dirname);
    }
  }

  /**
   * @return string|null
   */
  public function getMimeTypeReel(): ?string
  {
    return $this->reelImagePath ? AustralTools::mimeType($this->reelImagePath) : null;
  }

  /**
   * @return string|null
   */
  public function getExtension(): ?string
  {
    return $this->reelImagePath ? AustralTools::extension($this->reelImagePath) : null;
  }

  /**
   * @return false|int
   */
  public function getFileSizeReel(): false|int
  {
    return $this->reelImagePath ? filesize($this->reelImagePath) : 0;
  }

  /**
   * @return Box|BoxInterface|null
   */
  public function getSize(): Box|BoxInterface|null
  {
    return $this->image?->getSize();
  }

  /**
   * @return int|null
   */
  public function getWidth(): ?int
  {
    return $this->getSize()?->getWidth();
  }

  /**
   * @return int|null
   */
  public function getHeight(): ?int
  {
    return $this->getSize()?->getHeight();
  }

  /**
   * @param float|int $newHeight
   *
   * @return float
   */
  protected function calculateAutoWidth(float|int $newHeight): float
  {
    $width = $this->getWidth();
    $height = $this->getHeight();
    return round($width*$newHeight/$height);
  }

  /**
   * @param float|int $newWidth
   *
   * @return float
   */
  protected function calculateAutoHeight(float|int $newWidth): float
  {
    $width = $this->getWidth();
    $height = $this->getHeight();
    return round($height*$newWidth/$width);
  }
  
}
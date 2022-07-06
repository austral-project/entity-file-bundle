<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\File\Compression;

use Austral\EntityFileBundle\Configuration\CompressionConfiguration;
use Symfony\Component\Process\Process;

/**
 * Austral Compression.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Compression
{

  /**
   * @var CompressionConfiguration
   */
  protected CompressionConfiguration $compressionConfiguration;

  /**
   * @var array
   */
  protected array $squooshFormatByFormat = array(
    "jpg"       =>  "mozjpeg",
    "jpeg"      =>  "mozjpeg",
    "png"       =>  "oxipng",
    "webp"      =>  "webp"
  );

  /**
   * @param CompressionConfiguration $compressionConfiguration
   */
  public function __construct(CompressionConfiguration $compressionConfiguration)
  {
    $this->compressionConfiguration = $compressionConfiguration;
  }

  /**
   * @param string $imageThumbnailPath
   * @param array $generateOtherFormat
   */
  public function compress(string $imageThumbnailPath, array $generateOtherFormat = array())
  {
    if($command = $this->compressionConfiguration->get('command', null))
    {
      $imageThumbnailPathDir = pathinfo($imageThumbnailPath, PATHINFO_DIRNAME);
      $extension = pathinfo($imageThumbnailPath, PATHINFO_EXTENSION);

      if($squooshFormat = $this->getSquooshOptionByFormat($extension))
      {
        $commandParameters = array(
          $command,
          "--{$squooshFormat}"
        );
        $optionsBySquooshFormat = $this->compressionConfiguration->get("options.{$squooshFormat}", null);
        $optionsBySquooshFormatJson = $optionsBySquooshFormat ? json_encode($optionsBySquooshFormat) : "{}";
        $commandParameters[] = $optionsBySquooshFormatJson;
        if($generateOtherFormat)
        {
          foreach($generateOtherFormat as $format)
          {
            if($otherSquooshFormat = $this->getSquooshOptionByFormat($format))
            {
              $otherOptionsBySquooshFormat = $this->compressionConfiguration->get("options.{$otherSquooshFormat}", null);
              $otherOptionsBySquooshFormatJson = $otherOptionsBySquooshFormat ? json_encode($otherOptionsBySquooshFormat) : "{}";
              $commandParameters[] = "--{$otherSquooshFormat}";
              $commandParameters[] = $otherOptionsBySquooshFormatJson;
            }
          }
        }
        $commandParameters[] = "-d";
        $commandParameters[] = $imageThumbnailPathDir;
        $commandParameters[] = $imageThumbnailPath;

        $commandParameters[]  = ">";
        $commandParameters[]  = "/dev/null";
        $commandParameters[]  = "2>&1";
        $commandParameters[]  = "&";

        $process = Process::fromShellCommandline(implode(" ", $commandParameters));
        $process->setTimeout(120);
        $process->run();
      }
    }
  }

  /**
   * @param string $extension
   *
   * @return false|string
   */
  protected function getSquooshOptionByFormat(string $extension)
  {
    if(array_key_exists($extension, $this->squooshFormatByFormat))
    {
      return $this->squooshFormatByFormat[$extension];
    }
    return false;
  }

}
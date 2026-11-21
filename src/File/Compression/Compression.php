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
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Austral Compression.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class Compression
{
  /**
   * @var CompressionConfiguration
   */
  protected CompressionConfiguration $compressionConfiguration;

  /**
   * @var array<string, string>
   */
  protected array $squooshFormatByFormat = [
    'jpg' => 'mozjpeg',
    'jpeg' => 'mozjpeg',
    'png' => 'oxipng',
    'webp' => 'webp',
  ];

  /**
   * @param CompressionConfiguration $compressionConfiguration
   */
  public function __construct(CompressionConfiguration $compressionConfiguration)
  {
    $this->compressionConfiguration = $compressionConfiguration;
  }

  /**
   * @param string   $imageThumbnailPath
   * @param string[] $generateOtherFormat
   */
  public function compress(string $imageThumbnailPath, array $generateOtherFormat = []): void
  {
    if ($command = $this->compressionConfiguration->get('command', null))
    {
      if (true === str_contains($command, 'api://'))
      {
        $this->executeApi($command, $imageThumbnailPath, $generateOtherFormat);
      }
      else
      {
        $this->executeCli($command, $imageThumbnailPath, $generateOtherFormat);
      }
    }
  }

  /**
   * executeCli
   *
   * @param string   $command
   * @param string   $imageThumbnailPath
   * @param string[] $generateOtherFormat
   *
   * @return void
   */
  protected function executeCli(string $command, string $imageThumbnailPath, array $generateOtherFormat = []): void
  {
    $imageThumbnailPathDir = pathinfo($imageThumbnailPath, PATHINFO_DIRNAME);
    $extension = pathinfo($imageThumbnailPath, PATHINFO_EXTENSION);

    if ($squooshFormat = $this->getSquooshOptionByFormat($extension))
    {
      $optionsBySquooshFormat = $this->compressionConfiguration->get("options.{$squooshFormat}", null);
      $commandLine = $command.' "${:format_0}" "${:options_0}"';
      $parameters = [
        'format_0' => "--{$squooshFormat}",
        'options_0' => $optionsBySquooshFormat ? json_encode($optionsBySquooshFormat) : '{}',
      ];

      $index = 1;
      foreach ($generateOtherFormat as $format)
      {
        if ($otherSquooshFormat = $this->getSquooshOptionByFormat($format))
        {
          $otherOptionsBySquooshFormat = $this->compressionConfiguration->get("options.{$otherSquooshFormat}", null);
          $commandLine .= ' "${:format_'.$index.'}" "${:options_'.$index.'}"';
          $parameters["format_{$index}"] = "--{$otherSquooshFormat}";
          $parameters["options_{$index}"] = $otherOptionsBySquooshFormat ? json_encode($otherOptionsBySquooshFormat) : '{}';
          ++$index;
        }
      }

      $commandLine .= ' -d "${:directory}" "${:path}" > /dev/null 2>&1 &';
      $parameters['directory'] = $imageThumbnailPathDir;
      $parameters['path'] = $imageThumbnailPath;

      $process = Process::fromShellCommandline($commandLine);
      $process->setTimeout(120);
      $process->run(null, $parameters);
    }
  }

  /**
   * executeApi
   *
   * @param string   $command
   * @param string   $imageThumbnailPath
   * @param string[] $generateOtherFormat
   *
   * @return void
   */
  protected function executeApi(string $command, string $imageThumbnailPath, array $generateOtherFormat = []): void
  {
    $url = str_replace('api://', 'http://', $command).'/optimize';
    $imageThumbnailPathDir = pathinfo($imageThumbnailPath, PATHINFO_DIRNAME);
    $extension = pathinfo($imageThumbnailPath, PATHINFO_EXTENSION);
    $formats = [];
    $options = [];
    if ($squooshFormat = $this->getSquooshOptionByFormat($extension))
    {
      $formats[] = $squooshFormat;
      $options[$squooshFormat] = $this->compressionConfiguration->get("options.{$squooshFormat}", []);
    }
    if ($generateOtherFormat)
    {
      foreach ($generateOtherFormat as $format)
      {
        if ($otherSquooshFormat = $this->getSquooshOptionByFormat($format))
        {
          $formats[] = $otherSquooshFormat;
          $options[$otherSquooshFormat] = $this->compressionConfiguration->get("options.{$otherSquooshFormat}", []);
        }
      }
    }
    if (count($formats) > 0)
    {
      $httpClient = new NativeHttpClient();
      $payload = [
        'filename' => $imageThumbnailPath,
        'outputDir' => $imageThumbnailPathDir,
        'formats' => $formats,
        'options' => $options,
        'wait' => true,
      ];

      try
      {
        $response = $httpClient->request('POST', $url, [
          'headers' => [
            'Content-Type' => 'application/json',
          ],
          'body' => json_encode($payload),
          'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
      }
      catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface $e)
      {
        throw new \RuntimeException('HTTP error: '.$e->getMessage());
      }
      $data = json_decode($content, true);
      $requestSuccess = false;
      if (is_array($data) && isset($data['accepted']))
      {
        $requestSuccess = (bool) $data['accepted'];
      }
      elseif (is_array($data) && isset($data['success']))
      {
        $requestSuccess = (bool) $data['success'];
      }
      if (200 !== $statusCode || !$data || !$requestSuccess)
      {
        throw new \RuntimeException("Invalid response from the Squoosh service: $content");
      }
    }
  }

  /**
   * @param string $extension
   *
   * @return string|null
   */
  protected function getSquooshOptionByFormat(string $extension): ?string
  {
    if (array_key_exists($extension, $this->squooshFormatByFormat))
    {
      return $this->squooshFormatByFormat[$extension];
    }

    return null;
  }
}

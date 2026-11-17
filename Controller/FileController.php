<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Controller;

use Austral\EntityFileBundle\File\Download\FileDownloader;
use Austral\EntityFileBundle\File\Image\ImageRender;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral File Controller.
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FileController
{

  /**
   * @var ContainerInterface|null
   */
  protected ?ContainerInterface $container = null;

  /**
   * setContainer
   *
   * @param ContainerInterface|null $container
   * @return void
   */
  public function setContainer(?ContainerInterface $container = null): void
  {
    $this->container = $container;
  }

  /**
   * @param Request $request
   * @param string $entityKey
   * @param string $id
   * @param string $fieldname
   * @param string $type
   * @param string $mode
   * @param int|null $width
   * @param int|null $height
   * @param string|null $value
   * @param string|null $extension
   *
   * @return Response
   * @throws \Exception
   */
  public function thumbnail(Request $request,
    string $entityKey,
    string $id,
    string $fieldname,
    string $type = "original",
    string $mode = "resize",
    ?int $width = null,
    ?int $height = null,
    ?string $value = null,
    ?string $extension = null
  ): Response
  {
    header("Access-Control-Allow-Origin: *");
    header("Austral: Generate");
    header('Access-Control-Expose-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description, Content-Length, Cache-Control');

    /** @var ImageRender<T> $imageRender */
    $imageRender = $this->container?->get("austral.entity_file.image.render");
    $imagePath = $imageRender->initRender(
      $entityKey,
      $id,
      $fieldname,
      $type,
      $mode,
      $width ? intval($width) : null,
      $height ? intval($height) : null,
      $value,
      $extension,
      $request->query->has("force")
    );

    $lastModifiedTime = filemtime($imagePath);
    $etag = 'W/"' . md5((string) $lastModifiedTime) . '"';
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) $lastModifiedTime) . " GMT");
    header('Cache-Control: public, max-age=31536000, must-revalidate'); // Cache lifetime can be changed here
    header("Etag: $etag");

    if (
      (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModifiedTime) ||
      (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $etag === trim($_SERVER['HTTP_IF_NONE_MATCH']))
    ) {
      // 304 if file is not modified
      header('HTTP/2 304 Not Modified');
      exit();
    }

    $imageContent = file_get_contents($imagePath);
    if(!$imageContent)
    {
      throw new \Exception("Image file is empty");
    }
    $response = new Response($imageContent);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if($finfo && ($mineType = finfo_file($finfo, $imagePath)))
    {
      if(!str_contains($mineType, "svg+xml"))
      {
        $mineType = str_replace("svg", "svg+xml", $mineType);
      }
      $response->headers->set('Content-Type', $mineType);
    }
    $fileSize = filesize($imagePath);
    $response->headers->set('Content-Length', "{$fileSize}");
    return $response;
  }

  /**
   * @param Request $request
   * @param class-string $entityKey
   * @param string $fieldname
   * @param string $id
   * @param string $value
   *
   * @return Response
   * @throws \Exception
   */
  public function download(Request $request, string $entityKey, string $fieldname, string $id, string $value): Response
  {
    /** @var FileDownloader<T> $fileDownloader */
    $fileDownloader = $this->container?->get("austral.entity_file.downloader");
    $fileDownloader->init(
      $entityKey,
      $fieldname,
      $id
    );

    $downloadContent = $fileDownloader->getObjectFilePath() ? file_get_contents($fileDownloader->getObjectFilePath()) : null;
    if(!$downloadContent)
    {
      throw new \Exception("File is empty");
    }

    $response = new Response();
    $response->headers->set('Content-Description', 'File Transfer'); 
    $response->headers->set('Content-Type', 'application/force-download'); 
    $response->headers->set('Content-disposition', 'attachment; filename='.$fileDownloader->getName());
    $response->headers->set('Content-Length', "{$fileDownloader->getSize()}");
    $response->headers->set('Expires', '0');
    $response->headers->set('Cache-Control', 'must-revalidate');
    $response->setContent($downloadContent);
    return $response;
  }

  
  
}

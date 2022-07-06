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

use Austral\EntityFileBundle\File\Image\ImageRender;
use Austral\HttpBundle\Controller\HttpController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Austral File Controller.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FileController extends HttpController
{

  /**
   * @param Request $request
   * @param string $entityKey
   * @param string|integer $id
   * @param string $fieldname
   * @param string $type
   * @param string $mode
   * @param mixed|null $width
   * @param mixed|null $height
   * @param string|null $value
   * @param string|null $extension
   *
   * @return Response
   */
  public function thumbnail(Request $request,
    string $entityKey,
    $id,
    string $fieldname,
    string $type = "original",
    string $mode = "resize",
    $width = null,
    $height = null,
    string $value = null,
    string $extension = null
  ): Response
  {
    header("Access-Control-Allow-Origin: *");
    header("Austral: Generate");
    header('Access-Control-Expose-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description, Content-Length, Cache-Control');
    try
    {
      /** @var ImageRender $imageRender */
      $imageRender = $this->container->get("austral.entity_file.image.render");
      $imagePath = $imageRender->initRender(
        $entityKey,
        $id,
        $fieldname,
        $type,
        $mode,
        $width ? floatval($width) : null,
        $height ? floatval($height) : null,
        $value,
        $extension,
        $request->query->has("force")
      );

      $lastModifiedTime = filemtime($imagePath);
      $etag = 'W/"' . md5($lastModifiedTime) . '"';
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModifiedTime) . " GMT");
      header('Cache-Control: public, max-age=31536000, must-revalidate'); // On peut ici changer la durée de validité du cache
      header("Etag: $etag");

      if (
        (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModifiedTime) ||
        (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $etag === trim($_SERVER['HTTP_IF_NONE_MATCH']))
      ) {
        // 304 if file is not modified
        header('HTTP/2 304 Not Modified');
        exit();
      }
      $response = new Response(file_get_contents($imagePath));
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mineType = finfo_file($finfo, $imagePath);
      if(strpos($mineType, "svg+xml") === false)
      {
        $mineType = str_replace("svg", "svg+xml", $mineType);
      }
      $response->headers->set('Content-Type', $mineType);
      $response->headers->set('Content-Length', filesize($imagePath));
      return $response;
    }
    catch(\Exception $e)
    {
      throw $this->createNotFoundException("Image not found : {$e->getMessage()}");
    }
  }

  /**
   * @param Request $request
   * @param string $entityKey
   * @param string $fieldname
   * @param string $id
   * @param string $value
   *
   * @return Response
   */
  public function download(Request $request, string $entityKey, string $fieldname, string $id, string $value): Response
  {
    $fileDownloader = $this->container->get("austral.entity_file.downloader")->init(
      $entityKey,
      $fieldname,
      $id
    );
    $response = new Response();
    $response->headers->set('Content-Description', 'File Transfer'); 
    $response->headers->set('Content-Type', 'application/force-download'); 
    $response->headers->set('Content-disposition', 'attachment; filename='.$fileDownloader->getName());
    $response->headers->set('Content-Length', $fileDownloader->getSize());
    $response->headers->set('Expires', '0');
    $response->headers->set('Cache-Control', 'must-revalidate');
    $response->setContent(file_get_contents($fileDownloader->getObjectFilePath()));
    return $response;
  }
  
  
  
}

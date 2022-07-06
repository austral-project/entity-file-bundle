<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Entity\Interfaces;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Austral Entity File Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface EntityFileInterface
{

  /**
   * @return int|string
   */
  public function getObjectIdToFile();

  /**
   * @return array
   */
  public function getUploadFiles(): array;

  /**
   * @param string $fieldname
   * @param UploadedFile|null $uploadedFile
   *
   * @return EntityFileInterface
   */
  public function setUploadFileByFieldname(string $fieldname, ?UploadedFile $uploadedFile = null): EntityFileInterface;

  /**
   * @param string $fieldname
   *
   * @return UploadedFile|null
   */
  public function getUploadFileByFieldname(string $fieldname): ?UploadedFile;

  /**
   * @param string $fieldname
   *
   * @return bool
   */
  public function getDeleteFileByFieldname(string $fieldname): bool;

  /**
   * @param string $fieldname
   * @param $value
   *
   * @return $this
   */
  public function setDeleteFileByFieldname(string $fieldname, $value): EntityFileInterface;

  /**
   * @return array
   */
  public function getDeleteFiles(): array;

  /**
   * @param $deleteFiles
   *
   * @return EntityFileInterface
   */
  public function setDeleteFiles($deleteFiles): EntityFileInterface;

  /**
   * @param string $fieldname
   *
   * @return mixed
   * @throws \Exception
   */
  public function getValueByFieldname(string $fieldname);

  /**
   * @param string $fieldname
   * @param $value
   *
   * @return EntityFileInterface
   * @throws \Exception
   */
  public function setValueByFieldname(string $fieldname, $value = null);

}

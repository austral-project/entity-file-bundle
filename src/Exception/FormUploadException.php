<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Exception;

/**
 * Class FormUploadException
 *
 * @author  Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class FormUploadException extends \RuntimeException
{
  /**
   * @param string          $message  The internal exception message
   * @param \Throwable|null $previous The previous exception
   * @param int             $code     The internal exception code
   */
  public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0)
  {
    parent::__construct($message, $code, $previous);
  }
}

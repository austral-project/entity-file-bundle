<?php

namespace Austral\EntityFileBundle\Exception;

class FormUploadException extends \RuntimeException
{
  /**
   * @param string|null     $message  The internal exception message
   * @param \Throwable|null $previous The previous exception
   * @param int             $code     The internal exception code
   */
  public function __construct(?string $message = '', \Throwable $previous = null, int $code = 0)
  {
    parent::__construct($message, $code, $previous);
  }
}
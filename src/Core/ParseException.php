<?php
namespace App\Core;
use Zend\Code\Exception\RuntimeException;
use Throwable;

class ParseException extends RuntimeException
{
  public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}
<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-26
 * Time: 5:00 PM
 */

class ParseException extends RuntimeException
{
  public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}
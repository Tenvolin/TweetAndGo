<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-31
 * Time: 1:08 AM
 */

class Logger
{
  private $path = './';
  private $file;
  public function __construct()
  {
    $this->file = fopen('log.txt', 'a');
    fwrite($this->file, "\n");
  }
  public function log($msg) {
    $file = &$this->file;
    try {
      $timeStamp = new DateTime();
      $timeStr = $timeStamp->format("Y-m-d H:i:s");
      $timeStr .= ":  ";
    } catch (Exception $e) {
      $timeStr = "";
    }


    fwrite($file, $timeStr . $msg . "\n");
  }

  public function close() {
    $file = &$this->file;
    fclose($file);
  }
}
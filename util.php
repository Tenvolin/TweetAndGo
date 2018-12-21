<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-20
 * Time: 7:18 PM
 */
// https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php/14366023#14366023
// return false or char.
function nextChar($string, &$pointer){
  if(!isset($string[$pointer])) return false;
  $char = ord($string[$pointer]);
  if($char < 128){
    return $string[$pointer++];
  }else{
    if($char < 224){
      $bytes = 2;
    }elseif($char < 240){
      $bytes = 3;
    }elseif($char < 248){
      $bytes = 4;
    }elseif($char == 252){
      $bytes = 5;
    }else{
      $bytes = 6;
    }
    $str =  substr($string, $pointer, $bytes);
    $pointer += $bytes;
    return $str;
  }
}
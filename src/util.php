<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-20
 * Time: 7:18 PM
 */



class Util {
  const TIME_FORMAT_WITHIN_SECONDS = 1;
  const TIME_FORMAT_WITHIN_MINUTES = 2;
  const TIME_FORMAT_WITHIN_HOURS = 3;
  const TIME_FORMAT_WITHIN_MONTH = 4;
  const TIME_FORMAT_BEYOND_YEAR = 5;

  // https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php/14366023#14366023
  // return false or char.
  public static function nextChar($string, &$pointer){
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

  /**
   * Timestamps of tweets are not consistently formatted. They take five possible variations and thus need to be converted
   * to a DateTime object before persistence.
   * @param $abbreviatedDate
   * @return DateTime
   */
  public static function convertUnformattedTwitterDateToDateTime($abbreviatedDate) {
    // abbreviated date will be of the following form:
    // Not going to store exact times, as that's going to require many more http requests, which may result in banning.
    // | "dec 21" - This tweet took place on this day.
    // | "2h" - 2 hours old.
    // | "2m" - 2 mins old.
    // | "2s" - 2 secs old.
    // | "9 Jun 2016" - a date in a previous year.
    try {
      $currentDateTime = null;
      $courseOfAction = self::findTimeFormat($abbreviatedDate);
      $durationToSubtract = "P";
      $tweetDateTime = null;

      if ($courseOfAction === self::TIME_FORMAT_WITHIN_HOURS) {
        $currentDateTime = new DateTime();
//        $number = self::findNumber($abbreviatedDate);

        $durationToSubtract.= mb_convert_case($abbreviatedDate, MB_CASE_UPPER, "UTF-8");
        $dateInterval = new DateInterval($durationToSubtract);
        $tweetDateTime = $currentDateTime->sub($dateInterval);
      } else if ( $courseOfAction === self::TIME_FORMAT_WITHIN_MONTH ||
                  $courseOfAction === self::TIME_FORMAT_WITHIN_HOURS) {
        $tweetDateTime = new DateTime($abbreviatedDate);
      }

      if (is_null($tweetDateTime)) {
        throw new Exception("Date format not understood ($abbreviatedDate)");
      }

      return $tweetDateTime;

    } catch(Exception $e) {
      Throw new \http\Exception\RuntimeException("Problem when determining date and time: $e");
    }

  }

  /**
   * Invariant: input string is trimmed
   * Returns a value
   * @param $abbreviatedDate string
   * @return int
   */
  private static function findTimeFormat($abbreviatedDate) {
    $case = 0;
    $charsArray = preg_split('//u', $abbreviatedDate, null, PREG_SPLIT_NO_EMPTY);
    $lastIndex = count($charsArray) - 1;

    if (mb_strlen($abbreviatedDate) === 2 && $charsArray[$lastIndex] == 'h') {
      $case = self::TIME_FORMAT_WITHIN_HOURS;
    } else if (mb_strlen($abbreviatedDate) === 2 && $charsArray[$lastIndex] == 'm') {
      $case = self::TIME_FORMAT_WITHIN_MINUTES;
    } else if (mb_strlen($abbreviatedDate) === 2 && $charsArray[$lastIndex] == 's') {
      $case = self::TIME_FORMAT_WITHIN_SECONDS;
    } else if (mb_strlen($abbreviatedDate) <= 6) {
      $case = self::TIME_FORMAT_WITHIN_MONTH;
    } else if (mb_strlen($abbreviatedDate) <= 10) {
      $case = self::TIME_FORMAT_BEYOND_YEAR;
    }

    return $case;
  }

//  /**
//   * @param $abbreviatedDate
//   * @return string
//   */
//  private static function findNumber($abbreviatedDate) {
//    $charsArray = preg_split('//u', $abbreviatedDate, null, PREG_SPLIT_NO_EMPTY);
//    $lastIndex = count($charsArray) - 1;
//
//    $numStr = "";
//    for ($i = 0; $i < $lastIndex; $i++) {
//      $numStr .= $charsArray[$i];
//    }
//
//    return $numStr;
//  }
//
//  private static function findNumber($abbreviatedDate) {
//    $charsArray = preg_split('//u', $abbreviatedDate, null, PREG_SPLIT_NO_EMPTY);
//    $lastIndex = count($charsArray) - 1;
//
//    $numStr = "";
//    for ($i = 0; $i < $lastIndex; $i++) {
//      $numStr .= $charsArray[$i];
//    }
//
//    return $numStr;
//  }
}

